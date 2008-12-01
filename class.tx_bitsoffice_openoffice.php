<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Joachim Karl (joachim.karl@bitsafari.de)
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
 *   54: class tx_bitsoffice_openoffice extends tx_bitsoffice_xml 
 *   62:     function mainWriter($fileName, $conf)
 *           function array_search_needle()
 *   95:     function mainCalc($content, $conf) 
 *  104:     function prepareStyles()	
 *  181:     function renderOOBody($bodyArray)	
 *  269:     function tableRow($subTags,$tagName='td')	
 *  290:     function getParagraphContent($v)	
 *  385:     function spanFormat($value,$style)	

 *  400:     function noProcessing($v)	
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension 'extdeveval')
 *
 */ 
 
 //@bitsafari
//require_once(t3lib_extMgm::extPath('rlmp_officelib').'oowriter/class.class.tx_rlmpofficelib_oowriterdocument.php');

if (!defined('PATH_tslib')) define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');

require_once (PATH_tslib.'class.tslib_content.php');
require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_xml.php');
require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_renderTree.php');
require_once(t3lib_extMgm::extPath('libunzipped').'class.tx_libunzipped.php');
require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_unzip.php');


/**
 * Class for parsing Open Office documents for the 'rlmpofficeimport' extension.
 * 
 * @author  Joachim Karl <joachim.karl@bitsafari.de>
 * @author	Robert Lemke <rl@robertlemke.com>
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
class tx_bitsoffice_openoffice extends tx_bitsoffice_xml {

		// THIS IS THE NAMES of the styles (from the "Automatic" palette) in OpenOffice Writer 1.0.
		// They are mapped to the common configuration available through TypoScript, defined
		// in the parent class tx_rlmpofficeimport_xml
		
		
		 // INTERNAL:
		var $extKey        = 'bitsoffice';
		var $filesExt = '';
		var $officeBody = array();
		var $officeStyles = array();
		var $parsedStyles = array();
		var $cssBaseClass = 'tx-bitsoffice-pi1';
		var $cObj;
		var $iCount = 0; // index count. used as index for headings
		var $unzipObj;
		var $pageTreeObj;
		var $importODT;
		var $importSXW;
		var $imgData;
		var $contentArray;
		var $refArray;
		var $lookUpArray; // samples the hidden text fields of the document. useful to reference specific topics from the frontend via url parameter
		var $refIDs;
		var $sampleArray = array();
		var $isExportedSXW = ''; // checks whether it is a native sxw document, or it is an exportet sxg file?
		var $mapOOtoCommon = array(
		
					// Bodytext formats
		
				"Text body" => 'paragraph',                            	// "Text body"
				"Text body indent" => 'indented',                    	// "Text body indent"
				"Heading" => 'heading1',                            	// "Heading"
				"Preformatted Text" => 'preformatted',                  // "Preformatted Text" (HTML-menu)
				"First line indent" => 'firstLineIndent',               // "First line indent"
				"Hanging indent" => 'hangingIndent',                    // "Hanging indent"
				"Salutation" => 'paragraph',                            // "Complimentary close"
				"List Indent" => 'paragraph',                           // "List Indent"
				"Marginalia" => 'paragraph',                            // "Marginalia"
				"Signature" => 'paragraph',                            	// "Signature"
				"Standard" => 'paragraph',                              // "Default
		
					// Headers:
		
				"Heading 1" => 'heading1',                                // Heading 1
				"Heading 2" => 'heading2',                                // Heading 2
				"Heading 3" => 'heading3',                                // Heading 3
				"Heading 4" => 'heading4',                                // Heading 4
				"Heading 5" => 'heading5',                                // Heading 5
				"Heading 6" => 'heading6',                                // Heading 6
				"Heading 7" => 'heading7',                                // Heading 7
				"Heading 8" => 'heading8',                                // Heading 8
				"Heading 9" => 'heading9',                                // Heading 9
				"Heading 10" => 'heading10',                            // Heading 10
				
					/**************************
					* odt specific formats
					*
					***************************/
					
				"Heading_20_1" => 'heading1',
				"Heading_20_2" => 'heading2',
				"Heading_20_3" => 'heading3',
				"Heading_20_4" => 'heading4',
				"Heading_20_5" => 'heading5',
		
					// DEFAULT (non-rendered)
		
				"_default" => 'paragraph',                                // [everything else...]
	
		);
		
		var $designConf = array(
	
				'tableParams' => 'cellspacing=0 class="tx-bitsoffice-pi1"'
		);
		
	
		/**
		 * @param	[type]		$content: ...
		 * @param	[type]		$conf: ...
		 * @return	[type]		...
		 */
		function mainWriter($fileName, $conf, $pageId) {
	
				$this->cObj 		= t3lib_div :: makeInstance('tslib_cObj');
				$this->unzipObj 	= t3lib_div :: makeInstance('tx_bitsoffice_unzip');
				$this->pageTreeObj 	= t3lib_div :: makeInstance('tx_bitsoffice_renderTree');
				
				$conf = $this->loadTypoScriptForBEModule($this->extKey);
				// if TS setup is available override the config from class.tx_bitsoffice_xml.php
				if (is_array ($conf)) { $this->officeConf = $conf; }
		
				$files = $this->unzipObj->init($fileName);
				$this->imgData = $this->unzipObj->getFilePath();
				
				//get the type of the documents (.sxw, .odt)
				if (array_key_exists('HTTP_POST_FILES' ,$GLOBALS)) {
		
						$this->filesExt = substr($GLOBALS['HTTP_POST_FILES']['_uploaded_office_file']['name'],-3); //get the extension of the documents (.sxw, .odt)
					
				} else {
					
						$this->filesExt = substr($GLOBALS['_FILES']['_uploaded_office_file']['name'],-3); //get the extension of the documents (.sxw, .odt)
				
				}
	
				if (count($files))    {
		
						$fileInfo = $this->unzipObj->getFileFromXML('content.xml');
						
						$XML_content = $fileInfo['content'];
				
						if ($XML_content)    {
		
								$p = xml_parser_create();
								xml_parse_into_struct($p,$XML_content,$vals,$index);
								xml_parser_free($p);
				
								// Setting the dynamic/automatic styles:            
			
								$this->officeStyles = $this->indentSubTagsRec(array_slice($vals,$index['OFFICE:AUTOMATIC-STYLES'][0]+1,$index['OFFICE:AUTOMATIC-STYLES'][1]-$index['OFFICE:AUTOMATIC-STYLES'][0]-1),2);
								$this->prepareStyles();
			
								// Extracting the document body from the file.
	
								switch ($this->filesExt) {
												
										case 'sxw':
												
												$this->officeBody = array_slice($vals,$index['OFFICE:BODY'][0]+1,$index['OFFICE:BODY'][1]-$index['OFFICE:BODY'][0]-1);
												
												// loops through the document and filter the TEXT:SECTION-SOURCE tag. returns TRUE if it finds a tag with that value.
												
												$this->isExportedSXW = $this->array_search_needle('TEXT:SECTION-SOURCE', $this->officeBody);
												
												// if TEXT:SECTION-SOURCE == FALSE then 
												if ($this->isExportedSXW == 'FALSE') {
													$this->isExportedSXW = $this->array_search_needle('TEXT:SECTION', $this->officeBody);
												}
												
												// check whether it is a native sxw document, or is it an exported sxg file?
												
												if ($this->isExportedSXW == 'TRUE') {
													
														$this->officeBody = $this->indentSubTagsRec($this->officeBody,2);
														$this->officeBody = array_slice($this->officeBody,2); //@todo: check if we could delete this. or maybe we could slice 0-3
														
														$mergedArray = array();
														foreach ($this->officeBody as $val => $key) {
															
																if (is_array($key) && array_key_exists('subTags', $key)) {
																		foreach ($key['subTags'] as $tag) {
																				$mergedArray[] = $tag;
																		}
																}
														}
														
														$res = $this->getArrayForPageTree($mergedArray);
													
									
												} else {
													
														$this->officeBody = array_slice($vals,$index['OFFICE:BODY'][0]+1,$index['OFFICE:BODY'][1]-$index['OFFICE:BODY'][0]-1);
														$this->officeBody = $this->indentSubTagsRec($this->officeBody,1);
														$res = $this->getArrayForPageTree($this->officeBody);
													
												}
											
										break;
										
										case'odt':
										case'sxg':
												
												$this->officeBody = array_slice($vals,$index['OFFICE:BODY'][0]+1,$index['OFFICE:BODY'][1]-$index['OFFICE:BODY'][0]-1);
												$this->isExportedSXW = $this->array_search_needle('TEXT:SECTION-SOURCE', $this->officeBody);
												
												// if TEXT:SECTION-SOURCE == FALSE then 
												if ($this->isExportedSXW == 'FALSE') {
														$this->isExportedSXW = $this->array_search_needle('TEXT:SECTION', $this->officeBody);
												}
												
												// check whether it is a native sxw document, or is it an exported sxg file?
												
												if ($this->isExportedSXW == 'TRUE') {
														$this->officeBody = $this->indentSubTagsRec($this->officeBody,3);
														//$this->officeBody = array_slice($this->officeBody,3);
														$mergedArray = array();
														foreach ($this->officeBody as $val => $key) {
																
																if (is_array($key) && array_key_exists('subTags', $key)) {
																		foreach ($key['subTags'] as $tag) {
																				if (is_array($tag) && array_key_exists('subTags', $tag)) {
																						foreach ($tag['subTags'] as $t) {
																								$mergedArray[] = $t;		
																						}
																				}
																		}
																}
														}
														
														$res = $this->getArrayForPageTree($mergedArray);
													
									
												} else {
													
														$this->officeBody = $this->indentSubTagsRec($this->officeBody,2);
														//$this->officeBody = array_slice($this->officeBody,2);
														$mergedArray = array();
														foreach ($this->officeBody as $val => $key) {
																if (is_array($key)) {
																		foreach ($key['subTags'] as $tag) {
																			$mergedArray[] = $tag;
																		}
																}
														}
														
														$res = $this->getArrayForPageTree($mergedArray);
												}
										break;
									}
							
							
							if (!$this->officeConf['crossRef.']['useAjax'] == 1) {
									$this->pageTreeObj->setReferenceLink($this->officeConf['crossRef.']['ifAjax.']['link']);
							} else {
							
									$this->pageTreeObj->setReferenceLink($this->officeConf['crossRef.']['ifAjax.']['link']);
							}
							
							return  $this->pageTreeObj->renderTree ($res, $pageId, $this->refArray, $this->officeConf);
		
					} return array('ERROR: No XML content found.');
		
				} else return array('No files found in SXW file!!');
		}
		
	
	
	
		function getArrayForPageTree(&$bodyArray)    {
				reset ($bodyArray);
				$listItems ='';
		
				while( list($k,$v) = each($bodyArray ))    {
		
						if ( !empty($v) ) {
			
								switch((string)$v['tag'])    {
				
										case 'TEXT:SECTION':	
										case 'TEXT:SECTION-SOURCE':
										case 'OFFICE:FORMS':
										case 'TEXT:SEQUENCE-DECLS':
										case 'TEXT:SEQUENCE-DECLS':
										case 'TEXT:USER-FIELD-DECLS':
										case 'TEXT:OUTLINE-LEVEL':
										case 'TEXT:S':					
										case 'TEXT:SEQUENCE-DECLS':
										case 'TEXT:TABLE-OF-CONTENT':
					
										break;
									
									case 'TEXT:H':
										
											$HTML_code = '';
											
											// if we get the next heading we have to check whether contentArray is empty. If not, we have to
											// push the content into the headingArr
											if (!empty($this->contentArray)) {
											
													$headingArr[] = $this->contentArray;
													$this->contentArray = array();
											}
					
										 // if subtags exists it could be a reference   
					
											if (is_array($v['subTags']))    {
											
													while( list($c,$t) = each( $v['subTags'] ))    {	//$c = counter $t = tag
														
															switch($t['tag'])    {
																
																/***************************************************
																/ if we have to use hidden text for example we want to address
																/ a specific topic in the frontend
																/***************************************************/
																
																case 'TEXT:HIDDEN-TEXT':
																		
																		if ($this->array_search_needle('TEXT:H', $v['subTags']) == 'FALSE' && !empty($t['attributes']['TEXT:STRING-VALUE'])) {
																		
																				$this->contentArray ['heading'] = $this->getParagraphContent($v);
																				$this->contentArray ['heading_id'] = $this->iCount++;
																				$this->contentArray ['level'] = $this->getLevel($v);
																				$GLOBALS['lookUp_ids'][$this->contentArray ['heading_id']] ['id']= $this->contentArray ['heading_id'];
																				$GLOBALS['lookUp_ids'][$this->contentArray ['heading_id']] ['lookup_param'] = $t['attributes']['TEXT:STRING-VALUE'];
																				$HTML_code[] = $this->wrapItem($v, $this->contentArray ['heading']);
																				
																		} else {
																				
																				if (!empty($t['attributes']['TEXT:STRING-VALUE'])) {
																						$GLOBALS['lookUp_ids'][$this->contentArray ['heading_id']] ['id']= $this->contentArray ['heading_id'];
																						$GLOBALS['lookUp_ids'][$this->contentArray ['heading_id']] ['lookup_param'] = $t['attributes']['TEXT:STRING-VALUE'];
																				}
							
																		}
																		
																	break;
																
																case 'TEXT:BOOKMARK-START':
																case 'TEXT:BOOKMARK-END':
																case 'TEXT:SOFT-PAGE-BREAK':
																case 'TEXT:BOOKMARK-END':
																
																	break;
																
																/***************************************************
																/ in some cases, their are wrong format tags like span around headings
																/ therefore we try to fix it in this way 
																/***************************************************/
																case 'TEXT:SPAN':
																	
																		$this->contentArray ['heading'] = $this->getParagraphContent($t);
																		$this->contentArray ['level'] = $this->getLevel($v);
																		$this->contentArray ['heading_id'] = $this->iCount++;
																		
																		$HTML_code[] = $this->wrapItem($v, $this->contentArray ['heading'] );
																	
																	
																	break;
																case 'TEXT:H':
																case 'TEXT:TITLE':
																case 'TEXT:AUTHOR-NAME':
																case 'TEXT:USER-DEFINED':
							
																		$this->contentArray ['heading'] = $this->getParagraphContent($t);
																		$this->contentArray ['level'] = $this->getLevel($v);
																		
																		if ($v['subTags'][$c - 1]['tag'] == 'TEXT:REFERENCE-MARK-START' ){
																			
																			
																		} else {
																		
																				$this->contentArray ['heading_id'] = $this->iCount++;
																		
																		}
																		
																		//$this->refArray[$this->contentArray ['heading_id']]['title'] = $this->contentArray ['heading'];
																		
																		$HTML_code[] = $this->wrapItem($v, $this->contentArray ['heading'] ); // now get the heading wrapped
								
																break;
																	
																case 'TEXT:REFERENCE-MARK-START':
																	
																		$this->contentArray ['heading_id'] = $t['attributes'] ['TEXT:NAME'];
																		
																		if (!empty($this->contentArray ['heading_id'])) {
																				$this->refArray[$this->contentArray ['heading_id']]['title'] = $this->getHeading($v['subTags']);
																		}
																		
																		$GLOBALS['temp_ids'][$this->contentArray ['heading_id']] = 1;
																	
																break;
																		
															}	
													}
											}
					
											else {
												
													$this->contentArray ['heading'] = $this->getParagraphContent($v);
													$this->contentArray ['heading_id'] = $this->iCount++;
													$this->contentArray ['level'] = $this->getLevel($v);
													$HTML_code[] = $this->wrapItem($v, $this->contentArray ['heading']);
												
											}
											
											/* 
					
											* if the following is a heading or its the last element
					
											* in document, write the content back in the sample contentArray
					
											*/
					
											if ($bodyArray [$k+1] ['tag'] == 'TEXT:H' || $bodyArray [$k+1] ['tag'] == 'TEXT:SECTION-SOURCE' || !$bodyArray [$k+1]) {
					
													$this->contentArray[ 'data' ] = $HTML_code;
													$HTML_code = array();
													$countArrListItem = array();
					
											}
											
									break;
				
				
				
								case 'TEXT:P':
								
										// check wether tag is a paragraph and if it's not an empty paragraph
															
										 if (!empty($v['tag']['TEXT:P']) /*&& !empty($v['tag']['TEXT:P'])*/) {
										 
												$HTML_code[] = $this->wrapItem($v);
					
										 }
										
										// if the next element is a heading or it doesn't exist a next element
					
										// we have found all paragraphs of one content element. so write it to the array.
					
										if ($bodyArray [$k+1] ['tag'] == 'TEXT:H' || $bodyArray [$k+1] ['tag'] == 'TEXT:SECTION-SOURCE' || !$bodyArray [$k+1] ) {
					
												$this->contentArray[ 'data' ] = $HTML_code;
												$HTML_code = array();
												$count = array();
												$countArrListItem = array(); //we have to set this in cause of continueing numbering of lists inside a content element. if the last element is a p tag we have to reset this too.
										}
				
									break;
				
				
				
								case 'TEXT:LIST':
								case 'TEXT:UNORDERED-LIST':
								case 'TEXT:ORDERED-LIST':
				
										if (is_array($v['subTags']))    {
												//$tempArr samples the <li> elements
												$tempArr = array();
												$listItems = $this->indentSubTagsRec($v['subTags'],2);
												reset($listItems);
					
												while( list($kk, $vv) = each($listItems) )    {
						
														// saved in listItemValue, because we get back a multi dimension array
														// so the way by implode doesnt work any more
														$vv['subTags'][0]['listItemValue'] ='1';
						
														$listItemValue = $this->getArrayForPageTree($vv['subTags']);
							
														if ($vv['tag']=='TEXT:LIST-ITEM' && is_array($vv['subTags']))    {
							
																$tempArr[]='<li>'.$listItemValue[0]['data'][0].'</li>';                
								
																$countArrListItem[] = 1; // count the listItems.
							
														} elseif ($vv['tag']=='TEXT:LIST-HEADER' && is_array($vv['subTags'])) {
							
																$tempArr[]=implode(chr(10),$this->getArrayForPageTree($vv['subTags']));
							
														} else $this->noProcessing($vv);
											}
					
											if ($v['attributes']['TEXT:CONTINUE-NUMBERING'] == 'true') {
					
													$count = count($countArrListItem) - count($tempArr); // subtract the value of actual list items to get the rigth starting point.
													
													if ($v ['attributes'] ['TEXT:STYLE-NAME'] == $this->officeConf['bulletList']) { $lT = 'ul';}
															else { $lT = 'ol'; }
													
													$HTML_code[]='<'.$lT.' start="'.($count + 1).'">'.implode(chr(10),$tempArr).'</'.$lT.'>';
					
											} else {
					
													if ($v ['attributes'] ['TEXT:STYLE-NAME'] == $this->officeConf['bulletList']) { $lT = 'ul';}
															else { $lT = 'ol'; }
															
													$HTML_code[]='<'.$lT.'>'.implode(chr(10),$tempArr).'</'.$lT.'>';
					
											}                        
					
											if ($bodyArray [$k+1] ['tag'] == 'TEXT:H' || $bodyArray [$k+1] ['tag'] == 'TEXT:SECTION-SOURCE' || !$bodyArray [$k+1]) {
					
													$this->contentArray[ 'data' ] = $HTML_code;
													$HTML_code = array();
													$countArrListItem = array();
											}
					
										} else $this->noProcessing($v);
					
								break;
	
								case 'TABLE:TABLE':
								case 'TABLE:SUB-TABLE':
				
										if (is_array($v['subTags']))    {
											
												$tableItems=$this->indentSubTagsRec($v['subTags'],1);
												
												$tableRows=array();
						
												$tableHeadRows=array();
						
												$columnCount=0;
						
												reset($tableItems);
						
												while(list($kk,$vv)=each($tableItems))    {
						
														if ($vv['tag']=='TABLE:TABLE-COLUMN')    $columnCount++;
							
														if ($vv['tag']=='TABLE:TABLE-HEADER-ROWS')    {
															
																$HRrows = $this->indentSubTagsRec($vv['subTags'],1);
																reset($HRrows);
																while(list(,$vvv)=each($HRrows))    {
																		if (!empty($vvv['subTags'])) {
																				$tableHeadRows[]='<tr>'.$this->tableRow($vvv['subTags'],'th').'</tr>';
																		} else {}
																}
														}
							
														if ($vv['tag']=='TABLE:TABLE-ROW')    {
							
																$tableRows[]='<tr>'.$this->tableRow($vv['subTags']).'</tr>';
							
														}
						
												}
						
												$tableData = '<table '.$this->designConf['tableParams'].'>'.
												(count($tableHeadRows) ? '<thead>'.implode(chr(10),$tableHeadRows).'</thead>' : '').
												'<tbody>'.implode(chr(10),$tableRows).'</tbody>'.
												'</table>';
	
												$HTML_code[]= $this->cObj->stdWrap($tableData,$this->officeConf['tableWrap.']);
						
										} else $this->noProcessing($v);
					
										if ($bodyArray [$k+1] ['tag'] == 'TEXT:H' || $bodyArray [$k+1] ['tag'] == 'TEXT:SECTION-SOURCE' || !$bodyArray [$k+1]) {
												$this->contentArray[ 'data' ] = $HTML_code;
												$HTML_code = array();
												$count = array();
										}
								break;
				
									// Non-rendered / processed elements:
				
								default:
				
										$HTML_code[]='<p>NOT RENDERED: <em>'.$v['tag'].'</em></p>';
				
								break;
				
								}
						}
				}       
			
				if (!empty($this->contentArray)) {
						$headingArr[] = $this->contentArray;
				}
			
				return $headingArr;
		}
		
		
		
		function getHeading  ($val) {
												
				if(is_array($val)) {
					
						while( list($c,$t) = each( $val ))    {	//$c = counter $t = tag
							
								switch($t['tag'])    {
				
										case 'TEXT:BOOKMARK-START':
										case 'TEXT:BOOKMARK-END':
										case 'TEXT:SOFT-PAGE-BREAK':
										case 'TEXT:BOOKMARK-END':
										case 'TEXT:REFERENCE-MARK-START':
										case 'TEXT:REFERENCE-MARK-END':
										case 'TEXT:SPAN':
										
										break;
										
										case 'TEXT:H':
												return ($this->pValue($t['value']));
										break;
								}
						}
					
				} else {}
	
		}
		
		
		/**
		 * This processed the content inside a paragraph or header.
		 * 
		 * @param	[type]		$v: ...
		 * @return	[type]		...
		 */
		 function getParagraphContent($v)    {
			
				$content='';    
				
				if (array_key_exists('value',$v)) {
						$content.=$this->pValue($v['value']); // punkt wurde entfernt
				}
		
				if (is_array($v['subTags']))    {
						if ($this->filesExt == 'sxw') {
								$v['subTags'] = $this->indentSubTags($v['subTags']); // returns the subtags of the bodyArray
						}
						
						reset($v['subTags']);
			
						while(list($ff,$subV)=each($v['subTags']))    {
			
								switch($subV['tag'])    {
				
										case 'TEXT:SOFT-PAGE-BREAK':					
												break;
											
										case 'TEXT:H':
										case 'TEXT:AUTHOR-NAME':
										case 'TEXT:TITLE':
										case 'TEXT:USER-DEFINED':
					
												if (t3lib_div::inList('complete,cdata',$subV['type']))    {
													
														if ($subV['type'] == 'cdata') {
																//$this->contentArray ['heading_id'] = $this->iCount++;
																 $content.=$this->pValue($subV['value']);
														} else {
																$content.=$this->pValue($subV['value']);
														}
												} else $this->noProcessing($subV);
					
												break;
						
										case 'TEXT:S':
					
												// Extra SPACE!
						
												$cc=t3lib_div::intInRange($subV['attributes']['TEXT:C'],1);
						
												for ($a=0; $a<$cc; $a++)    {
														$content.='&nbsp;';
												}
					
												break;
										
					
										case 'TEXT:P':
											
												if (t3lib_div::inList('complete,cdata',$subV['type']))    {
														$content.=$this->pValue($subV['value']);
												} else {
														$content=$this->spanFormat($this->getParagraphContent($subV),$subV['attributes']['TEXT:STYLE-NAME']);
												}
					
												break;
					
					
										case 'TEXT:SPAN':
											
												if (t3lib_div::inList('complete,cdata',$subV['type']))    {
														if(!$subV['subTags']){
															
																$content.=$this->spanFormat($this->pValue($subV['value']),$subV['attributes']['TEXT:STYLE-NAME']);
														
														} else {
												
																$content.=$this->spanFormat($this->getParagraphContent($subV),$subV['attributes']['TEXT:STYLE-NAME']);
														}
					
												} else $this->noProcessing($subV);
					
												break;
					
										
										case 'TEXT:REFERENCE-REF':
											
												if (t3lib_div::inList('complete,cdata',$subV['type']))    {
						
														if (!empty($this->contentArray ['heading'])) {
														
							
																// push the heading in the refArray - the refArray key equals the refHeading value
																
																$index = $subV['attributes']['TEXT:REF-NAME'];
								
																if (array_key_exists($index, $this->refArray) && !empty($this->contentArray ['heading_id'])) {
		
																		//$this->refArray[$index][] = $this->contentArray ['heading_id'];
									
																		$this->refArray[$index]['content'][$this->contentArray ['heading_id']] = $this->contentArray ['heading'];
								
																} else {
								
																		if (!empty($this->contentArray ['heading_id'])) {
									
																				$this->refArray[$index]['content'][$this->contentArray ['heading_id']] = $this->contentArray ['heading'];
									
																		}
								
																}
																
																/*** 
																* @$GLOBALS['temp_contheadids'] samples the headings of pages
																* which contains the references. we fill it later in the hook object
																* with pids we search for.
																****/ 
								
																$GLOBALS['temp_ids'][$this->contentArray ['heading_id']] = 0;
							
														}
							
														//wrap the content for strreplace to get sure it is a reference.
	
														if ($subV['attributes']['TEXT:REFERENCE-FORMAT'] == 'chapter') {
																$this->noProcessing($subV);
														} else {
																$content.='---<'.$index.'>---';
														}
						 
												} else $this->noProcessing($subV);    
					
												break;
	
										
										case 'TEXT:TAB-STOP':
												$content.=$this->chr10BR?chr(9):'&nbsp;&nbsp;&nbsp;&nbsp;';
												break;
					
										case 'TEXT:LINE-BREAK':
												$content.=$this->chr10BR?chr(10):'<br />';
												break;
	
										
										case 'TEXT:A':
					
												if (t3lib_div::inList('complete,cdata',$subV['type']))    {
						
														$content.='<a href="'.$subV['attributes']['XLINK:HREF'].'">'.$this->getParagraphContent($subV).'</a>';
						
												} else $this->noProcessing($subV);
					
												break;

										case 'DRAW:IMAGE':
											
												$imgClass = $subV['subTags'][0]['value'];
	
												if ($subV['attributes']['XLINK:HREF'])    {
														$fI = pathinfo($subV['attributes']['XLINK:HREF']);
														if (t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],strtolower($fI['extension'])))    {
																$imgData = $this->getImgData($subV);
																if (is_array($imgData))    {
																		$imgInfo = unserialize($imgData['info']);
																		if (is_array($imgInfo))    {
																				
																				$writefile='typo3temp/tx_oodocs_'.t3lib_div::shortmd5($imgData['filepath']).'.'.$imgData['filetype'];
																				t3lib_div::writeFile(PATH_site.$writefile,$imgData['content']);
																				$maxW=$this->officeConf['imageCObject_scaledImage.']['file.']['width'] ? $this->officeConf['imageCObject_scaledImage.']['file.']['width'] : 600;
										
																				if ($imgInfo[0]>$maxW)    {
										
																						$needle = array(
																								'###IMGHREF###',
																								'###IMGSRC###',
																								'###IMGCLASS###',
																								'###IMGWIDTH###',
																								'###IMGHEIGHT###',
																								'###IMGTITLE###'
																							
																							);
													
																							$replaceVal = array(
																									$writefile, //href
																									$writefile, //src
																									$imgClass,
																									$maxW,
																									floor($imgInfo[1]/($imgInfo[0]/$maxW)), //height
																									'Click to open '.($imgInfo[0].'x'.$imgInfo[1]).'pixel window' //title
																							);
																							
																							$imgData = str_replace($needle, $replaceVal, $this->officeConf['images.']['imgPath.']['greaterMaxW']);
																							$content.=$this->cObj->stdWrap($imgData,$this->officeConf['imgWrap.']);
																					
							
																				} else {
																						$needle = array(
																								'###IMGSRC###',
																								'###IMGCLASS###',
																								'###IMGWIDTH###',
																								'###IMGHEIGHT###',
							
																						);
												
																						$replaceVal = array(
																								$writefile, //src
																								$imgClass,
																								$imgInfo[0],
																								$imgInfo[1], //height
																						);
																						
																						$imgData = str_replace($needle, $replaceVal, $this->officeConf['images.']['imgPath.']['lowerMaxW']);
																						$content .= $this->cObj->stdWrap($imgData,$this->officeConf['imgWrap.']);
																				}
																		}
																}
								
														} else $this->noProcessing($subV);
												}
					
												break;
										

										case 'DRAW:TEXT-BOX';
					
												$content.='<table border="0" cellpadding="0" cellspacing="0"><tr><td>'.implode('',$this->getArrayForPageTree($this->indentSubTags($subV['subTags']))).'</td></tr></table>';
					
												break;
					
											// Nothing happens:
					
										case 'TEXT:SEQUENCE':
					
										case 'OFFICE:ANNOTATION':
					
												break;
					
										default:
					
												$this->noProcessing($subV);
					
												break;
									}
							}
					}
			
				if (!strcmp(trim(strip_tags($content,'<img>')),''))    $content='&nbsp;';
	
				return $content;
	
		}
	
	
		/**
		 * This function returns the image data. Maybe in future versions we can do this in a smarter way.
		 * But for now I have to do this very dynamic because I don't know the right filepath every time.
		 * I have observed different behaviors on different systems or different doc types which I couldn't know all at this time.
		 * Maybe using the filename instead of filepath in the getFileFromXML function would be a better way.
		 *
		 * @param	[type]		$subV: image tag
		 * @return	[type]		$imgData: contains the image data
		 */
		 
		function getImgData ($subV) {
				$str = substr($subV['attributes']['XLINK:HREF'], -36);
				foreach ($this->imgData  as $key => $val ) {
						
						if (substr_count  ( $val['filepath']  , $str ) == 1) {
								
								return $imgData = $this->unzipObj->getFileFromXML(substr($subV['attributes']['XLINK:HREF'], - strlen($this->imgData[$key]['filepath'])));
										
						}
				}
		}
		
		
		
		function getHeadingId ($v) {
			
			 while(list($ff,$subV)=each($v['subTags']))    {
				
					switch($subV['tag']) {
							case 'TEXT:REFERENCE-MARK-START':
									return $subV['attributes'] ['TEXT:NAME'];
									break;
				 
					}
				} 
		}
		
		
	
		/**
		 * @param	[type]		$content: ...
		 * @param	[type]		$conf: ...
		 * @return	[type]		...
		 */
		function mainCalc($content, $conf) {
				// Could merge the arrays, but I don't do that yet:
				if (is_array ($conf)) {
					$this->officeConf = $conf;
				}
		
				return '[DOCUMENT TYPE NOT SUPPORTED YET]';
		
					// Unzipping SXC file, getting filelist:
				$this->unzipObj = t3lib_div::makeInstance('tx_libunzipped');
				$files = $this->unzipObj->init($fileName);
				if (count($files))	{
						$fileInfo = $this->unzipObj->getFileFromXML('content.xml');
						$XML_content = $fileInfo['content'];
						
						if ($XML_content)	{
								$p = xml_parser_create();
								xml_parse_into_struct($p,$XML_content,$vals,$index);
								xml_parser_free($p);
					
									// Setting the dynamic/automatic styles:			
								//$this->officeStyles = $this->indentSubTagsRec(array_slice($vals,$index['OFFICE:AUTOMATIC-STYLES'][0]+1,$index['OFFICE:AUTOMATIC-STYLES'][1]-$index['OFFICE:AUTOMATIC-STYLES'][0]-1),2);
								$this->prepareStyles();
				
									// Extracting the document body from the file.
								$this->officeBody = array_slice($vals,$index['OFFICE:BODY'][0]+1,$index['OFFICE:BODY'][1]-$index['OFFICE:BODY'][0]-1);
								$this->officeBody = $this->indentSubTagsRec($this->officeBody,1);
					
								$res = $this->renderOOBody($this->officeBody);
								
								return implode(chr(10),$res);
						} return array('ERROR: No XML content found.');
				} else return array('No files found in SXW file!!');
		}
	
		/**
		 * This prepares the automatic styles from the document
		 * 
		 * @return	[type]		...
		 */
		function prepareStyles()	{
				reset($this->officeStyles);
				while(list($k,$v)=each($this->officeStyles))	{
						$v['_wrap']=array();
						if ($v['attributes']['STYLE:PARENT-STYLE-NAME'])	{
								$v['_stylepointer']=$v['attributes']['STYLE:PARENT-STYLE-NAME'];
								$v['_wrap']=explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon[$v['_stylepointer']]]);
									// No matching style was found in the mapOOtoCommon array, so try to apply a custom style:
								if (count ($v['_wrap']) < 2) {
									$v['_wrap']=explode ('|',$this->officeConf['tagWraps.'][strtolower($v['_stylepointer'])]);
								}
								if (count ($v['_wrap']) < 2) {
								$v['_wrap']=explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon['_default']]);
								}
							
						}
						if ($v['subTags'][0]['tag']=='STYLE:PROPERTIES')	{
								$styleProp = $v['subTags'][0]['attributes'];
								$cssP=array();
	
								// ***********************
								// HERE we try to use regular HTML B/I/U tags for bold, italic and underline. Alternatively these could be rendered with style='' attributes OR with strong/em
								// ***********************
								
									// Bold:
								if ($styleProp['FO:FONT-WEIGHT'])	{
										if ($styleProp['FO:FONT-WEIGHT']=='bold')	{
												$v['_wrap'][0].= $this->getWrapPart ($this->officeConf['tagWraps.']['bold'],0);
												$v['_wrap'][1]= $this->getWrapPart ($this->officeConf['tagWraps.']['bold'],1).$v['_wrap'][1];
										} else {
												$cssP[]='font-style: '.$styleProp['FO:FONT-WEIGHT'].';';
										}
								}
									// Italic:
								if ($styleProp['FO:FONT-STYLE'])	{
										if ($styleProp['FO:FONT-STYLE']=='italic')	{
												$v['_wrap'][0].=$this->getWrapPart ($this->officeConf['tagWraps.']['italic'],0);
												$v['_wrap'][1]=$this->getWrapPart ($this->officeConf['tagWraps.']['italic'],1).$v['_wrap'][1];
										} else {
												$cssP[]='font-style: '.$styleProp['FO:FONT-STYLE'].';';
										}
								}
									// Underline:
								if ($styleProp['STYLE:TEXT-UNDERLINE'])	{
										if ($styleProp['STYLE:TEXT-UNDERLINE']=='single')	{
												$v['_wrap'][0].=$this->getWrapPart ($this->officeConf['tagWraps.']['underlined'],0);
												$v['_wrap'][1]=$this->getWrapPart ($this->officeConf['tagWraps.']['underlined'],1).$v['_wrap'][1];
										} else {
												$cssP[]='text-decoration: '.$styleProp['STYLE:TEXT-UNDERLINE'].';';
										}
								}
				
				
								// ***********************				
								// style='' attributes
								// ***********************
									// Background color
								if ($styleProp['STYLE:TEXT-BACKGROUND-COLOR'])	{
										$cssP[]='background-color: '.$styleProp['STYLE:TEXT-BACKGROUND-COLOR'].';';
								}
									// Background color
								if ($styleProp['FO:BACKGROUND-COLOR'])	{
										$cssP[]='background-color: '.$styleProp['FO:BACKGROUND-COLOR'].';';
								}
									// color
								if ($styleProp['FO:COLOR'])	{
										$cssP[]='color: '.$styleProp['FO:COLOR'].';';
								}
				
								if (count($cssP))	{
										$v['_wrap'][0].='<span style="'.implode('',$cssP).'">';
										$v['_wrap'][1]='</span>'.$v['_wrap'][1];
								}
						}
					
					$this->parsedStyles[$v['attributes']['STYLE:NAME']]	= $v;
				}
		}
		
		/**
		 * Processing of table rows
		 * 
		 * @param	[type]		$subTags: ...
		 * @param	[type]		$tagName: ...
		 * @return	[type]		...
		 */
		function tableRow($subTags,$tagName='td')	{
				if(!empty($subTags)) {} else { echo 'the subTag for tableRow is empty!!!';}
				$cells = $this->indentSubTagsRec($subTags,2);
				reset($cells);
				$cellOutput=array();
				while(list($k,$v)=each($cells))	{
						if ($v['tag']!='TABLE:COVERED-TABLE-CELL')	{
							$content = $this->getArrayForPageTree($v['subTags']);
							$content = implode(chr(10),$content[0] ['data']);
							
							$cellOutput[]='<'.$tagName.($v['attributes']['TABLE:NUMBER-COLUMNS-SPANNED']>1?' colspan="'.$v['attributes']['TABLE:NUMBER-COLUMNS-SPANNED'].'"':'').'>'.
								$content.
								'</'.$tagName.'>';
						}
				}
				return implode('',$cellOutput);
		}	
	
		/**
		 * Wrapping spans in the code they need.
		 * 
		 * @param	[type]		$value: ...
		 * @param	[type]		$style: ...
		 * @return	[type]		...
		 */
		function spanFormat($value,$style)	{
				
				if (t3lib_div::inList('P',substr($style,0,1)) && t3lib_div::testInt(substr($style,1)))    {
						
						$wrap = $this->parsedStyles[$style]['_wrap'];
		
				} else {
					
						if ($this->mapOOtoCommon[$style]) {
								
								$wrap = explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon[$style]]);
			
						} else {        // there is no style which could be mapped ...
							
								if ($this->officeConf['tagWraps.'][strtolower($style)]) {
										
										$wrap = explode ('|',$this->officeConf['tagWraps.'][strtolower($style)]);
				
								} else {        // no, but really no matching style found. So apply the default one.
										
										$wrap = explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon['_default']]);
				
								}
						}
				}
				
				// $wrap = $this->parsedStyles[$style]['_wrap'];
				if (is_array($wrap) && count($wrap)>1)	{
						return $wrap[0].$value.$wrap[1];
				} else {
						$this->noProcessing(array('STYLE'=>$style));
						return $value;
				}
		}
		
		
		
		
		/*****************************************************************
		* this function searchs for wrap information and wraps the content
		* 
		* 
		*
		******************************************************************/
	
		function wrapItem ($val, $content = '') {
			
				$sN = $val['attributes']['TEXT:STYLE-NAME'];
				if (t3lib_div::inList('P',substr($sN,0,1)) && t3lib_div::testInt(substr($sN,1)))    {
						$wrap = $this->parsedStyles[$sN]['_wrap'];
				} else {
						if ($this->mapOOtoCommon[$sN]) {
								$wrap = explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon[$sN]]);
						} else {        // there is no style which could be mapped ...
							
								if ($this->officeConf['tagWraps.'][strtolower($sN)]) {
										$wrap = explode ('|',$this->officeConf['tagWraps.'][strtolower($sN)]);
								} else {        // no, but really no matching style found. So apply the default one.
										$wrap = explode ('|',$this->officeConf['tagWraps.'][$this->mapOOtoCommon['_default']]);
								}
						}
				}
		
				if (count($wrap)>1)    {
						$this->chr10BR=$wrap[2];
						if (!empty($content)) {
								return $wrap[0].$content.$wrap[1];
						} else {
								return $wrap[0].$this->getParagraphContent($val).$wrap[1];	
						}
					//$this->chr10BR=0;
				} else $this->noProcessing($val);
	
		}
		
		
		
		
		/*****************************************************************
		* this function searchs an array for the needle
		* we need this to filter the TEXT:SECTION-SOURCE tag to know whether it
		* is a native sxw or an exported sxg file
		*
		******************************************************************/
	
		function array_search_needle($needle, $haystack, $nodes=array()) {
				
				foreach ($haystack as $key1 => $value1) {
						if (($value1['tag'] == $needle)) {
								return 'TRUE';	
										break;
						} 
				}
				
				return 'FALSE';
		}
		
		
		/**
		 * returns the document specific attribute for the level
		 * @param	[type]		$v: ...
		 * @return	[type]		...
		 */
		function getLevel ($val) {
										
				switch ($this->filesExt) {
						case 'sxw':
								return $val['attributes']['TEXT:LEVEL'];
								break;
						
						case 'odt':
								return $val['attributes']['TEXT:OUTLINE-LEVEL'];
								break;
				}
		}
		
		function output ($val) {
				echo '<pre>';
						print_r($val);
				echo '</pre>';
		}
		
		/**
		 * @param	[type]		$v: ...
		 * @return	[type]		...
		 */
		function noProcessing($v)	{
				//debug('Didn\'t know processing for this:',-1);
				//debug($v);
		}
		
		
			/**
			 * Loads the TypoScript for the given extension prefix, e.g. tx_cspuppyfunctions_pi1, for use in a backend module.
			 *
			 * @param string $extKey
			 * @return array
			 */
			function loadTypoScriptForBEModule($extKey) {
					require_once(PATH_t3lib . 'class.t3lib_page.php');
					require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
					require_once(PATH_t3lib . 'class.t3lib_tsparser_ext.php');
					list($page) = t3lib_BEfunc::getRecordsByField('pages', 'pid', 0);
					$pageUid = intval($page['uid']);
					$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
					$rootLine = $sysPageObj->getRootLine($pageUid);
					$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
					$TSObj->tt_track = 0;
					$TSObj->init();
					$TSObj->runThroughTemplates($rootLine);
					$TSObj->generateConfig();
					return $TSObj->setup['plugin.'][$extKey . '.'];
			}
		
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_openoffice.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_openoffice.php']);
}
?>