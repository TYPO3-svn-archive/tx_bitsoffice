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
 *   50: class tx_rlmpofficeimport_xml 
 *  107:     function indentSubTags($officeBody)	
 *  149:     function indentSubTagsRec($officeBody,$depth=1)	
 *  170:     function pValue($v)	
 *  189:     function wrap($content,$wrap,$char='|')	
 *  203:     function getWrapPart ($wrap, $part) 
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */ 
 

 
/**
 * Class with basic XML related functions for use when parsing Office XML.
 * Additionally some helper functions for office import in general are included.
 * 
 * @author	Robert Lemke <rl@robertlemke.com>
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
class tx_bitsoffice_xml {

		// The following configuration is not really related with the XML functions
		//	of this class. It is the default configuration for the output styles,
		//	used by both, open office and microsoft office rendering.

	var $officeConf = array (
		'imageCObject_scaledImage.' => array (
			'file.'=> array (
				'width' => 300,
				'import' => array (
					'current' => 1,
				),
			),
			'imageLinkWrap' => 1,
			'imageLinkWrap.' => array (
				'width' => 800,
				'JSwindow' => 1,
				'enable' => 1,
			),					
			'wrap' => '<div style=>"text-align:center; margin-bottom: 10px;"> | </div>',
		),
		
		
		'imgWrap.' => array (
			'wrap' => '<div class="show" id="images">|</div>',
		),
		
		'tableWrap.' => array (
			'wrap' => '<div class="show" id="table"><div class="n1"><div class="n2"><div class="n3"><div class="n4"><div class="n5"><div class="spacer"></div> | <p id="customize"></p></div></div></div></div></div></div>
',
		),
		
		'bulletList' => 'ILIAS List Bullet', // enter here your bullet list format defined in your document. we need this, to switch between ol and ul lists. OOo Writer only uses TEXT:ORDERED-LIST
		
		/*******************************
		 *
		 * here you have to configure your own 
		 * document formats which you have used in your document
		 *
		 *******************************/
		
		'tagWraps.' => array (

			"img-icon" 					=> '',
			"img-scrshot" 				=> '',
			"zw_ueberschrift" 			=> '<h3 class="zw_ueberschrift">|</h3>',
			"zw_ueberschrift_kursiv" 	=> '<h3 class="zw_ueberschrift_kursiv">|</h3>',
			"description" 				=> '<p class="description">|</p>',
			"description-end"			=> '<p class="description-end">|</p>',
			"descr-ol" 					=> '<p class="descr-ol">|</p>',
			"descr-ol-list"				=> '<p class="descr-ol-list">|</p>',
			"ha-beg" 					=> '<p class="ha-beg">|</p>',
			"ha-result" 				=> '<p class="ha-result">|</p>',
			"anschrift" 				=> '<p class="anschrift">|</p>',
			"kontakt" 					=> '<p class="kontakt">|</p>',
			"mod-heading" 				=> '<p class="mod-heading">|</p>',
			"ilias additional" 			=> '<div class="hint">|</div>',
			"keyboard-shortcut"			=> '<span class="shrtct">|</span>',
			"qomet"						=> '<span class="qomet">|</span>',
			'bold' 						=> '<span class="bold">|</span>',
			"kursiv"					=> '<span class="kursiv">|</span>',
			

			'bitsOffice' => '<h1>|</h1>',
			'heading1' => '<h1 class="bitsoffice-h1">|</h1>',
			'heading2' => '<h2 class="bitsoffice-h2">|</h2>',
			'heading3' => '<h3 class="bitsoffice-h3">|</h3>',
			'heading4' => '<h4 class="bitsoffice-h4">|</h4>',
			'heading5' => '<h5 class="bitsoffice-h5">|</h5>',
			'paragraph' => '<p class="bitsoffice-p">|</p>',
			'italic' => '<em>|</em>',
			'underlined' => '<span style="text-decoration: underline;">|</span>',
			'unorderedlist' => '<ul>|</ul>',
			'listitem' => '<li>|</li>',
			'superscript' => '<sup>|</sup>',
			'subscript' => '<sub>|</sub>',
			'preformatted' => '<pre>|</pre>',
			'indented' => '<blockquote>|</blockquote>',
			'firstLineIndent' => '<p>|</p>',
		),
		'parseOptions.' => array (
			'renderMicrosoftSmartTags' => 1,
			'renderColors' => 1,
			'renderBackgroundColors' => 1,
			'renderFontFaces' => 1,
		)
	);


	/**
	 * Processes the XML structure for open tags and 'indents' them in the array
	 * 
	 * @param	[type]		$officeBody: ...
	 * @return	[type]		...
	 */
	function indentSubTags(&$officeBody)	{
		$newStruct=array();
		$subStruct=array();
		$currentTag='';
		$currentLevel=0;
		reset($officeBody);
		while(list($k,$v)=each($officeBody))	{
			if ($currentTag)	{
				if (!strcmp($v['tag'],$currentTag))	{	// match...
					if ($v['type']=='close')	$currentLevel--;
					if ($v['type']=='open')		$currentLevel++;
				}
				if ($currentLevel<=0)	{	// should never be LESS than 0, but for safety...
					$currentTag='';
					$subStruct['type']='complete';
					$newStruct[]=$subStruct;
				} else {
					$subStruct['subTags'][]=$v;
				}
			} else {	// On root level:
				if (t3lib_div::inList('complete,cdata',$v['type']))	{
					$newStruct[]=$v;
				}
				if ($v['type']=='open')	{
					$currentLevel=1;	
					$currentTag = $v['tag'];
					
					$subStruct=$v;
					$subStruct['subTags']=array();
				}
			}
		}
		return $newStruct;
	}
	
	
	
	
	function indentRefSubTags($officeBody)	{
		$newStruct=array();
		$subStruct=array();
		$currentTag='';
		$currentLevel=0;
		reset($officeBody);
		while(list($k,$v)=each($officeBody))	{
			if ($currentTag)	{
				if (!strcmp($v['tag'],$currentTag))	{	// match...
					if ($v['tag']['TEXT:REFERENCE-MARK-END']=='close')	$currentLevel--;
					if ($v['tag']['TEXT:REFERENCE-MARK-START']=='open')		$currentLevel++;
				}
				if ($currentLevel<=0)	{	// should never be LESS than 0, but for safety...
					$currentTag='';
					$subStruct['type']='complete';
					$newStruct[]=$subStruct;
				} else {
					$subStruct['subTags'][]=$v;
				}
			} else {	// On root level:
				if (t3lib_div::inList('complete,cdata',$v['type']))	{
					$newStruct[]=$v;
				}
				if ($v['type']=='open')	{
					$currentLevel=1;	
					$currentTag = $v['tag'];
					
					$subStruct=$v;
					$subStruct['subTags']=array();
				}
			}
		}
		return $newStruct;
	}
	
	
	/**
	 * Also indents open tags, but does so recursively to a certain number of levels
	 * 
	 * @param	[type]		$officeBody: ...
	 * @param	[type]		$depth: ...
	 * @return	[type]		...
	 */
	function indentSubTagsRec($officeBody,$depth=1)	{
		if ($depth<1)	return $officeBody;		
		$officeBody = $this->indentSubTags($officeBody);

		if ($depth>1)	{
			reset($officeBody);
			while(list($k,$v)=each($officeBody))	{
				if (is_array($officeBody[$k]['subTags']))	{
					$officeBody[$k]['subTags'] = $this->indentSubTagsRec($officeBody[$k]['subTags'],$depth-1);
				}
			}
		}
		return $officeBody;
	}

	/**
	 * Returns the value of an element ready for output in HTML
	 * 
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function pValue($v)	{
		$v = str_replace(
			array("â€œ","â€ ","â€™","â€“","â€¦"),
			array('"','"','´','–','...'),
		$v);
		$v = htmlentities(utf8_decode($v));
		return $v;
	}	

	/**
	 * Wrapping a string.
	 * Example: $content = "HELLO WORLD" and $wrap = "<b> | </b>", result: "<b>HELLO WORLD</b>"
	 * 
	 * @param	string		The content to wrap
	 * @param	string		The wrap value, eg. "<b> | </b>"
	 * @param	string		The char used to split the wrapping value, default is "|"
	 * @return	string		Wrapped input string
	 * @see noTrimWrap()
	 */
	function wrap($content,$wrap,$char='|')	{
		if ($wrap)	{
			$wrapArr = explode($char, $wrap);
			return trim($wrapArr[0]).$content.trim($wrapArr[1]);
		} else return $content;
	}

	/**
	 * Returns the left or right part of a wrap
	 * 
	 * @param	[string]		$wrap: the wrap to be exploded, must be separated by |
	 * @param	[boolean]		$part: 0=left, 1=right
	 * @return	[string]		Part of the wrap
	 */
	function getWrapPart ($wrap, $part) {
		if ($wrap) {
			$wrapArr = explode('|', $wrap);
		}
		return $part ? trim($wrapArr[1]) : trim($wrapArr[0]);		
	}


	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_xml.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_xml.php']);
}
?>