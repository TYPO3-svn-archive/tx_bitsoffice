<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003 Robert Lemke (rl@robertlemke.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   59: class tx_rlmpofficeimport_msoffice2003 extends tx_rlmpofficeimport_xml
 *   73:     function mainWord ($content, $conf)
 *   95:     function mainExcel ($content, $conf)
 *  121:     function wordTraverseSection($explodedSubsection)
 *  147:     function wordRenderParagraph($pArray, $tempDontAddPTags=0)
 *  198:     function wordRenderPPR ($paragraph)
 *  260:     function wordRenderR ($paragraph)
 *  318:     function wordRenderRPR ($pArray, $textELWraps)
 *  377:     function wordRenderTable($tArray)
 *  412:     function excelTraverseSection($explodedSubsection)
 *  463:     function excelRenderWorkSheet ($wArray)
 *  514:     function excelGetStyles ($sArray)
 *  559:     function renderImage($binDataConf)
 *  584:     function traverseToTag ($sourceArray, $tag, $level=0)
 *  607:     function callUserFunction($funcName,&$params,&$ref,$checkPrefix='user_',$silent=0)
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_xml.php');

/**
 * Class for parsing MSOffice 2003 documents for the 'rlmp_officeimport' extension.
 *
 * @author	Robert Lemke <rl@robertlemke.com>
 * @author	Kasper Sk�rh�j <kasper@typo3.com>
 */
class tx_bitsoffice_msoffice2003 extends tx_bitsoffice_xml {

	var $currentListLevel = 0;		// used for determining the level of ordered and unordered lists
	var $wordWraps = array ();		//	used for wrapping paragraphs and such by several functions
	var $dontAddPTags = 0;			// used by wordRenderParagraph
	var $colsCount = 0;				// used by Excel rendering function
	var $rowsCount = 0;				//					 "
	var $cssBaseClass = 'tx-bitsoffice-pi1';

	/**
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function mainWord ($content, $conf) {
			// Could merge the arrays, but I don't do that yet:
		if (is_array ($conf)) {
			$this->officeConf = $conf;
		}
			// Exploding subsection in a useful way for traversing:
		$p = xml_parser_create();
		$vals=array();
		$index=array();
		xml_parse_into_struct($p,$content,$vals,$index);
		xml_parser_free($p);
		$explodedSubsection = $this->indentSubTagsRec(array_slice($vals,$index['WX:SECT'][0]+1,$index['WX:SECT'][1]-$index['WX:SECT'][0]-1),999);

		$content = $this->wordTraverseSection ($explodedSubsection);
		return $content;
	}

	/**
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function mainExcel ($content, $conf) {
			// Could merge the arrays, but I don't do that yet:
		if (is_array ($conf)) {
			$this->officeConf = $conf;
		}

			// Exploding subsection in a useful way for traversing:
		$p = xml_parser_create();
		$vals=array();
		$index=array();
		xml_parse_into_struct($p,$content,$vals,$index);
		xml_parser_free($p);
		$explodedSubsection = $this->indentSubTagsRec(array_slice($vals,$index['WX:SECT'][0]+1,$index['WX:SECT'][1]-$index['WX:SECT'][0]-1),999);
		$content = $this->ExcelTraverseSection($explodedSubsection);
		return $content;
	}

	// --- WORD FUNCTIONS BELOW ------------------------------------------------------------------------------------

	/**
	 * Traverse the sections. Sub sections lets us go one level deeper and traverse again, paragraphs etc. are
	 * handle by sub functions
	 *
	 * @param	[array]		$explodedSubsection: Array containing subTags
	 * @return	[string]		HTML content
	 */
	function wordTraverseSection($explodedSubsection)	{
//debug ($explodedSubsection);
		foreach($explodedSubsection as $value)	{
			$this->wraps = array ();	// clear previous wraps;
			switch($value['tag'])	{
				case 'WX:SUB-SECTION':
					$content.=$this->wordTraverseSection($value['subTags']);
				break;
				case 'W:P':
					$this->dontAddPTags = 0;
					$content.= $this->wordRenderParagraph($value['subTags']);
				break;
				case 'AML:ANNOTATION':
					if ($value['attributes']['W:TYPE'] == 'Word.Bookmark.Start') {
						$content.= '<a name="officeimport'.ereg_replace ("[^a-z:._-]","",strtolower($value['attributes']['W:NAME'])).'"></a>';
					}
				break;
				case 'W:TBL':
					$content.=$this->wordRenderTable($value['subTags']);
				break;
			}
		}
		return $content;
	}

	/**
	 * Renders a paragraph
	 *
	 * @param	[array]		$pArray: subparts
	 * @param	[type]		$tempDontAddPTags: ...
	 * @return	[string]		rendered HTML output
	 */
	function wordRenderParagraph($pArray, $tempDontAddPTags=0)	{
		if (is_array ($pArray)) {
			foreach ($pArray as $paragraph) {
				switch ($paragraph['tag']) {
					case 'W:PPR':
							// PPR mostly contains things elements like lists and such, no actual content.
							//	That's why only wraps are returned
						$this->wraps = $this->wordRenderPPR ($paragraph);
					break;
					case 'W:R' :
						$content .= $this->wordRenderR ($paragraph);
					break;
					case 'ST1:CITY' :
							// Microsoft Smarttag for identifying cities etc.
					   $x = $this->traverseToTag ($paragraph['subTags'], 'W:T');
						if ($this->officeConf['parseOptions.']['renderMicrosoftSmartTags']) {
							$x = '<span style="smarttag-city">'.$x.'</span>';
						}
						$content .= $x;
					break;
					case 'ST1:STREET' :
							// Microsoft Smarttag for identifying cities etc.
					   $x = $this->traverseToTag ($paragraph['subTags'], 'W:T');
						if ($this->officeConf['parseOptions.']['renderMicrosoftSmartTags']) {
							$x = '<span style="smarttag-street">'.$x.'</span>';
						}
						$content .= $x;
					break;
					case 'W:HLINK' :
							// A hyperlink or bookmark within the document
						if ($paragraph['attributes']['W:DEST']) {
								$href = $paragraph['attributes']['W:DEST'];
								$value = $this->wordRenderParagraph ($paragraph['subTags'],1);
								$target = $this->officeConf['parseOptions.']['extLinksTarget'] ? ' target="'.$this->officeConf['parseOptions.']['extLinksTarget'].'"' : '';
								$content .= '<a href="'.$href.'"'.$target.'>'.$value.'</a>';
						} elseif ($paragraph['attributes']['W:BOOKMARK']) {
								$href = '#officeimport'.ereg_replace ("[^a-z:._-]","",strtolower($paragraph['attributes']['W:BOOKMARK']));
								$value = $this->wordRenderParagraph ($paragraph['subTags'],1);
								$target = $this->officeConf['parseOptions.']['intLinksTarget'] ? ' target="'.$this->officeConf['parseOptions.']['extLinksTarget'].'"' :'';
								$content .= '<a href="'.$href.'"'.$target.'>'.$value.'</a>';
						}
					break;
					default:
				}
			}
		}

		$content = $this->wraps['pPrepend'].$content.$this->wraps['pAppend'];
		if ((!$this->dontAddPTags) && (!$tempDontAddPTags)) { $content = $this->wrap ($content, '<p'.($this->wraps['pAddParams'] ? ' '.$this->wraps['pAddParams'] : '') .'>|</p>'."\n"); }
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$paragraph: ...
	 * @return	[type]		...
	 */
	function wordRenderPPR ($paragraph) {

		foreach ($paragraph['subTags'] as $subTag) {
			switch ($subTag['tag']) {
				case 'W:PSTYLE' :	// Now there comes some style information (like header, custom styles etc.)
					$wraps['pPrepend'] .= $this->getWrapPart($this->officeConf['tagWraps.'][strtolower ($subTag['attributes']['W:VAL'])],0);
					$wraps['pAppend']  = $wraps['pAppend'].$this->getWrapPart($this->officeConf['tagWraps.'][strtolower ($subTag['attributes']['W:VAL'])],1);
				break;
				case 'W:JC' : // Horizontal alignment
					$wraps['pAddParams'] .= 'style="text-align:'.$subTag['attributes']['W:VAL'].'" ';
				break;
				case 'W:LISTPR' : // A List
					foreach ($subTag['subTags'] as $subTagL2) {
						switch ($subTagL2['tag']) {
							case 'W:ILVL':
									// The level didn't change, we only need to output the LI tag
								if ($subTagL2['attributes']['W:VAL'] == $this->currentListLevel) {
									$wraps['pPrepend'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],0);
									$wraps['pAppend'] = $wraps['pAppend'].$this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],1);
								}
									// One level down: Open a new UL tag
								if ($subTagL2['attributes']['W:VAL'] > $this->currentListLevel) {
									$wraps['pPrepend'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['unorderedlist'],0);
									$wraps['pPrepend'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],0);
									$wraps['pAppend'] = $wraps['pAppend'].$this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],1);
									$this->dontAddPTags = 1;
								}
									// One level up: Close an UL tag
								if ($subTagL2['attributes']['W:VAL'] < $this->currentListLevel) {
									$wraps['pPrepend'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],0);
									$wraps['pAppend'] = $wraps['pAppend'].$this->getWrapPart ($this->officeConf['tagWraps.']['listitem'],1);
									$wraps['pAppend'] = $wraps['pAppend'].$this->getWrapPart ($this->officeConf['tagWraps.']['unorderedlist'],1);
									$this->dontAddPTags = 1;
								}
								$this->currentListLevel = $subTagL2['attributes']['W:VAL'];
							break;
							case 'WX:T':	// defines the character used as a bullet sign
								// not parsed yet
							break;
							default:
						}
					}
				break;
				case 'W:IND' : // This creates an indent
					$wraps['pPrepend'] .= $this->getWrapPart($this->officeConf['tagWraps.']['indented'],0);
					$wraps['pAppend']  = $wraps['pAppend'].$this->getWrapPart($this->officeConf['tagWraps.']['indented'],1);
				break;
				case 'W:TABS' : // Defines tabulators
					// not parsed yet
				break;
				default:
			}
		}
		return $wraps;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$paragraph: ...
	 * @return	[type]		...
	 */
	function wordRenderR ($paragraph) {
		foreach ($paragraph['subTags'] as $subTag) {
			$textElement = '';
			$textELWraps ['prepend'] = $textELWraps ['prependNext'];
			$textELWraps ['append'] =  $textELWraps ['appendNext'];
			$textELWraps ['prependNext'] = '';
			$textELWraps ['appendNext'] = '';
			switch ($subTag['tag']) {
				case 'W:T' :	// This is the actual bodytext
					$textElement = $this->pValue ($subTag['value']);
				break;
				case 'W:BR' :	// A line break
					$textElement = '<br />';
				break;
				case 'W:RPR' :
					$textELWraps = $this->wordRenderRPR ($subTag['subTags'], $textELWraps);
				break;
				case 'W:SYM' :	// Some symbol like Wingdings etc.
					// don't want to parse them yet
				break;
				case 'W:FOOTNOTEREF' :
				case 'W:FOOTNOTE' :
					// not parsed yet
				break;
				case 'W:PICT' :
					foreach($subTag['subTags'] as $subTagL2)	{
						switch ($subTagL2['tag']) {
							case 'V:GROUP':	// textbox or sth. similar
								//not parsed yet
							break;
							case 'W:BINDATA':	// picture
								$textElement = $this->renderImage($subTagL2);
							break;
						}
					}
				break;
				default:

			}
			if ($textELWraps ['styles']) {
				$textELWraps['prepend'] .= '<span style="'.$textELWraps['styles'].'">';
				$textELWraps['append'] = $textELWraps['append'] .'</span>';
			}
			$content .= $textELWraps ['prepend'].$textElement.$textELWraps ['append'];
		}
		return $content;
	}

	/**
	 * **************************************************
	 * Renders the RPR subsections.
	 * NOTE that some parameters are passed by reference!
	 *
	 * @param	[array]		$pArray: subparts array
	 * @param	[array]		$textELWraps: array of appending and prepending string. BY REFERENCE
	 * @param	[strin]		$wraps: string of parameters being added to the <p> tag. BY REFERENCE
	 * @return	[type]		nothing.
	 */
	function wordRenderRPR ($pArray, $textELWraps) {
		foreach ($pArray as $subTag) {
			switch ($subTag['tag']) {
				case 'W:I': // ITALIC
					$textELWraps ['prependNext'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['italic'],0);
					$textELWraps ['appendNext']  = $textELWraps ['appendNext'].$this->getWrapPart ($this->officeConf['tagWraps.']['italic'],1);
				break;
				case 'W:B': // BOLD
					$textELWraps ['prependNext'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['bold'],0);
					$textELWraps ['appendNext']  = $textELWraps ['appendNext'].$this->getWrapPart ($this->officeConf['tagWraps.']['bold'],1);
				break;
				case 'W:U': // UNDERLINED
					$textELWraps ['prependNext'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['underlined'],0);
					$textELWraps ['appendNext']  = $textELWraps ['appendNext'].$this->getWrapPart ($this->officeConf['tagWraps.']['underlined'],1);
				break;
				case 'W:VERTALIGN':
					if ($subTag['attributes']['W:VAL'] == 'superscript') {
						$textELWraps ['prependNext'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['superscript'],0);
						$textELWraps ['appendNext'] = $textELWraps ['appendNext'].$this->getWrapPart ($this->officeConf['tagWraps.']['superscript'],1);
					}
					if ($subTag['attributes']['W:VAL'] == 'subscript') {
						$textELWraps ['prependNext'] .= $this->getWrapPart ($this->officeConf['tagWraps.']['subscript'],0);
						$textELWraps ['appendNext']  = $textELWraps ['appendNext'].$this->getWrapPart ($this->officeConf['tagWraps.']['subscript'],1);
					}
				break;
				case 'W:COLOR':
					if ($this->officeConf['parseOptions.']['renderColors']) {
						$textELWraps ['styles'] .= 'color:#'.$subTag['attributes']['W:VAL'].' ';
					}
				break;
				case 'W:LANG': // Defines the language:
					$this->wraps['pAddParams'] .= 'lang="'.$subTag['attributes']['W:VAL'].'" ';
				break;
				case 'W:RSTYLE': // Defines a special style:
					$textELWraps['prepend'] .= $this->getWrapPart($this->officeConf['tagWraps.'][strtolower ($subTag['attributes']['W:VAL'])],0);
					$textELWraps['append']  .= $this->getWrapPart($this->officeConf['tagWraps.'][strtolower ($subTag['attributes']['W:VAL'])],1);
				break;
				case 'R:FONTS': // Applies a certain font-face:
					if ($this->officeConf['parseOptions.']['renderFonts']) {
						$textELWraps ['styles'] .= 'font-face: '.$subTag['W:ASCII'].' ';
					}
				break;
				case 'W:SZ': // Applies a font size:
					if ($this->officeConf['parseOptions.']['renderFonts']) {
						$textELWraps ['styles'] .= 'font-size: '.$subTag['W:ASCII'].' ';
					}
				break;
				default:
			}
		}
		return $textELWraps;
	}

	/**
	 * Renders a table
	 *
	 * @param	[array]		$tArray: subtags for the table
	 * @return	[string]		rendered HTML output
	 */
	function wordRenderTable($tArray)	{
		foreach($tArray as $subTag)	{
			if ($subTag['tag']=='W:TR')	{
				$rowCells='';
				foreach($subTag['subTags'] as $subTagL2)	{
					$tdParams = '';
					if ($subTagL2['tag']=='W:TC')	{
						foreach ($subTagL2['subTags'] as $subTagL3) {
							if ($subTagL3['tag'] == 'W:TCPR') {
								foreach ($subTagL3['subTags'] as $subTagL4) {
									if ($subTagL4['tag'] == 'W:GRIDSPAN') {
										$tdParams .= ' colspan="'.$subTagL4['attributes']['W:VAL'].'"';
									}
								}
							}
						}
						$cellContent=$this->wordTraverseSection($subTagL2['subTags']);
						$rowCells.='<td'.$tdParams.'>'.$cellContent.'</td>';
					}
				}
				$allRows.='<tr>'.$rowCells.'</tr>';
			}
		}
		return '<table cellspacing="0" class="'.$this->cssBaseClass.'">'.$allRows.'</table>';
	}

	// --- EXCEL FUNCTIONS BELOW ------------------------------------------------------------------------------------

	/**
	 * Traverse the sections. Sub sections lets us go one level deeper and traverse again, cells etc. are
	 * handled by sub functions
	 *
	 * @param	[array]		$explodedSubsection: Array containing subTags
	 * @return	[string]		HTML content
	 */
	function excelTraverseSection($explodedSubsection)	{
		$renderedWorkSheet = 0;
		foreach($explodedSubsection as $value)	{
			switch ($value['tag']) {
				case 'DOCUMENTPROPERTIES':
				case 'EXCELWORKBOOK':
					// not used yet
				break;
				case 'WORKSHEET':
						// We only want the first worksheet in the file to be rendered:
					if (!$renderedWorkSheet) {
						$rows = $this->excelRenderWorkSheet ($value['subTags']);
						$renderedWorkSheet = 1;
					}
 				break;
				case 'STYLES':
					$styles = $this->excelGetStyles ($value['subTags']);
				break;
				default:
			}
		}

			// Now render the table:

		foreach ($rows as $row) {
			$colSpan = $this->colsCount-count ($row);
			$colNr = 0;
			$rowContent = '';
			foreach ($row as $cell) {
				$colNr ++;
				if (!$cell['data']) { $cell['data'] = '&nbsp;'; }
					// Apply style for table cell if wished / neccesary
				$styleCode = $styles[$cell['styleID']] ? ' style="'.$styles[$cell['styleID']].'"' : '';
				if ($colNr == count($row)) {
					$rowContent .= '<td colspan="'.$colSpan.'"'.$styleCode.'>'.$cell['data']."</td>\n";
				} else {
					$rowContent .= '<td'.$styleCode.'>'.$cell['data']."</td>\n";
				}
			}
			$content.='<tr>'.$rowContent.'</tr>';
		}
		$content = '<table cellspacing="0" class="'.$this->cssBaseClass.'">'.$content.'</table>';
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$wArray: ...
	 * @return	[type]		...
	 */
	function excelRenderWorkSheet ($wArray) {

		$rows = array ();
			// First build the datastructure and style structure of the table
		foreach ($wArray as $subTag) {
			switch ($subTag['tag']) {
				case 'TABLE':
					$this->colsCount = $subTag['attributes']['SS:EXPANDEDCOLUMNCOUNT'];
					$this->rowsCount = $subTag['attributes']['SS:EXPANDEDROWCOUNT'];
					foreach ($subTag['subTags'] as $subTagL2) {
						switch ($subTagL2['tag']) {
							case 'COLUMN':
								$columns[] = array ('width' => $subTagL2['attributes']['SS:WIDTH']);
							break;
							case 'ROW':
								$cells = array ();
				                if (is_array($subTagL2['subTags']) && count($subTagL2['subTags'])>0) {
									foreach ($subTagL2['subTags'] as $subTagL3) {
										switch ($subTagL3['tag']) {
											case 'CELL':
												$data = array ();
						                        if (is_array($subTagL3['subTags']) && count($subTagL3['subTags'])>0) {
												  foreach ($subTagL3['subTags'] as $subTagL4) {
													  switch ($subTagL4['tag']) {
														  case 'DATA':
															  $data[] = $subTagL4['value'];
													  	break;
													  }
												  }
						                        }
												$cells[] = array (
													'styleID' => $subTagL3['attributes']['SS:STYLEID'],
													'data' => $data[0],							// We only take the first entry
												);
											break;
										}
									}
				                }
								$rows[] = $cells;
							break;
						}
					}
				break;
			}
		}

		return $rows;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sArray: ...
	 * @return	[type]		...
	 */
	function excelGetStyles ($sArray) {
			// traverse the different style-sets
		foreach ($sArray as $subTag) {
			if ($subTag['tag'] == 'STYLE') {
				$styleID = $subTag['attributes']['SS:ID'];
				foreach ($subTag['subTags'] as $subTagL2) {
					switch ($subTagL2['tag']) {
						case 'ALIGNMENT':
							if ($subTagL2['attributes']['SS:VERTICAL']) {
								$styles[$styleID] .= 'vertical-align:'.strtolower ($subTagL2['attributes']['SS:VERTICAL']).'; ';
							}
							if ($subTagL2['attributes']['SS:HORIZONTAL']) {	// NOT TESTED YET
								$styles[$styleID] .= 'vertical-align:'.strtolower ($subTagL2['attributes']['SS:VERTICAL']).'; ';
							}
						break;
						case 'FONT':
							// not parsed yet
						break;
						case 'INTERIOR':
							if ($subTagL2['attributes']['SS:COLOR'] && $this->officeConf['parseOptions.']['renderBackgroundColors']) {
								$styles[$styleID] .= 'background-color:'.$subTagL2['attributes']['SS:COLOR'].'; ';
							}
						break;
					}
				}
			}
		}
		return $styles;
	}

	// --- HELPER FUNCTIONS BELOW -----------------------------------------------------------------------------------


	/**
	 * Takes care of images. The Images are not really rendered within this function! By default
	 * it will simply return [IMAGE]! If you want to output the real image, you will have to
	 * provide a userfunction. You can pass the userfunction's name by the general TS configuration
	 * of this extension.
	 *
	 * 	Example:
	 * 		userFunctions.renderImage = tx_rlmpofficeimport_pi1->renderImage
	 *
	 * @param	[array]		$iArray: subtags for the image section
	 * @return	[string]		[IMAGE]
	 */
	function renderImage($binDataConf)	{
		if ($this->officeConf['userFunctions.']['renderImage']) {
			$ref = ''; // whatever that is for
			$imgConf = array (
				'imageData' => $binDataConf['value'],
				'nameInfo' => pathinfo($binDataConf['attributes']['W:NAME']),
				'conf' => $this->officeConf
			);
			$content = $this->callUserFunction($this->officeConf['userFunctions.']['renderImage'],$imgConf,$ref,'',0);
		} else {
			$content = '[IMAGE]';
		}

		return $content;
	}

	/**
	 * **********************************************************************
	 * Traverses into an array until it finds $tag and then returns its value
	 *
	 * @param	[array]		$sourceArray: the subparts
	 * @param	[string]		$tag: the tag you're looking for (e.g. 'W:T')
	 * @param	[integer]		$level: Don't set this
	 * @return	[array]		...
	 */
	function traverseToTag ($sourceArray, $tag, $level=0) {
		if ($level < 1000 && is_array ($sourceArray)) {		// you never know ...
			foreach ($sourceArray as $value) {
				if ($value['tag'] == $tag) {
					return $value['value'];
				}
			}
			return $this->traverseToTag ($value['subTags'], $tag, $level+1);
		}
	}

	/**
	 * Calls a userdefined function/method in class
	 *
	 * Usage: 3
	 *
	 * @param	string		Method reference, [class]->[method] or [function]
	 * @param	mixed		Parameters to be pass along (REFERENCE!)
	 * @param	mixed		Reference (can't remember what this is for) (REFERENCE!)
	 * @param	string		Required prefix of class or function name
	 * @param	boolean		If set, not debug() error message is shown if class/function is not present.
	 * @return	mixed		Content from method/function call
	 */
	function callUserFunction($funcName,&$params,&$ref,$checkPrefix='user_',$silent=0)	{
		if ($checkPrefix &&
			!t3lib_div::isFirstPartOfStr(trim($funcName),$checkPrefix) &&
			!t3lib_div::isFirstPartOfStr(trim($funcName),'tx_')
			)	{
			if (!$silent)	debug("Function '".$funcName."' was not prepended with '".$checkPrefix."'",1);
			return $content;
		}
		$parts = explode('->',$funcName);
		if (count($parts)==2)	{	// Class
			if (class_exists($parts[0]))	{
				$classObj = new $parts[0];
				if (method_exists($classObj, $parts[1]))	{
				 	$content = call_user_method($parts[1], $classObj, $params, $ref);
				} else {
					if (!$silent)	debug("<strong>ERROR:</strong> No method name '".$parts[1]."' in class ".$parts[0],1);
				}
			} else {
				if (!$silent)	debug("<strong>ERROR:</strong> No class named: ".$parts[0],1);
			}
		} else {	// Function
			if (function_exists($funcName))	{
			 	$content = call_user_func($funcName, $params, $ref);
			} else {
				if (!$silent)	debug("<strong>ERROR:</strong> No function named: ".$funcName,1);
			}
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_msoffice2003.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_msoffice2003.php']);
}
?>