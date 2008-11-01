<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Joachim Karl <joachim.karl@bitsafari.de>
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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_msoffice2003.php');
require_once(t3lib_extMgm::extPath('bitsoffice').'class.tx_bitsoffice_openoffice.php');



/**
 * Plugin 'bitsoffice' for the 'bitsoffice' extension.
 *
 * @author	Joachim Karl <joachim.karl@bitsafari.de>
 * @package	TYPO3
 * @subpackage	tx_bitsoffice
 */
class tx_bitsoffice_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_bitsoffice_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_bitsoffice_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'bitsoffice';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

			// Read XML file:
		$file = t3lib_div::getFileAbsFileName('uploads/tx_bitsoffice/'.$this->cObj->data['tx_bitsoffice_office_file']);
		if (@is_file($file))	{
			$pI = pathinfo($file);
			$fileContent = t3lib_div::getUrl($file);
			$firstFewBytes = substr($fileContent,0,200);

			if (strtolower($pI['extension'])=='xml' && strstr($firstFewBytes,'<?mso-application progid="Word.Document"?>'))	{	// Word2003
				$content = $this->parseMSOffice2003Word($fileContent, $conf);
			} elseif (strtolower($pI['extension'])=='xml' && strstr($firstFewBytes,'<?mso-application progid="Excel.Sheet"?>'))	{
				$content = $this->parseMSOffice2003Excel($fileContent, $conf);
			} elseif (strtolower($pI['extension'])=='sxw' || strtolower($pI['extension'])=='odt')	{
				
				$content = $this->parseOpenOfficeWriter($file, $conf);
				
			} elseif (strtolower($pI['extension'])=='sxc')	{
				$content = $this->parseOpenOfficeCalc($file, $conf);
			} else {
				$content = 'Sorry, the fileformat ".'.strtolower($pI['extension']).'" was not recognized as an Office file. If you have tried MS Word or Excel files in the traditional formats (".doc" and ".xsl") then open them in Office 2003 again and save them as ".xml" files. Then upload again and it should work.';
				$content = $this->parseOpenOfficeWriter($fileContent, $conf);
				
			}
		}
		
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * parses Microsoft Office 2003 Word XML Documents and returns nicely rendered HTML
	 * 
	 * @param	[blob]		$content: XML Data from the original file
	 * @param	[type]		$conf: the extension's configuration
	 * @return	[type]		nicely rendered HTML
	 */
	function parseMSOffice2003Word ($content, $conf) {
		$conf['userFunctions.']['renderImage'] = 'tx_bitsoffice_pi1->renderImage';

		$this->msOffice2003 = t3lib_div::makeInstance ('tx_bitsoffice_msoffice2003');
		$content = $this->msOffice2003->mainWord ($content, $conf);

		return $content;		
	}

	/**
	 * parses Microsoft Office 2003 Excel XML Documents and returns nicely rendered HTML
	 * 
	 * @param	[blob]		$content: XML Data from the original file
	 * @param	[type]		$conf: the extension's configuration
	 * @return	[type]		nicely rendered HTML
	 */
	function parseMSOffice2003Excel ($content, $conf) {
		$conf['userFunctions.']['renderImage'] = 'tx_bitsoffice_pi1->renderImage';

		$this->msOffice2003 = t3lib_div::makeInstance ('tx_bitsoffice_msoffice2003');
		$content = $this->msOffice2003->mainExcel ($content, $conf);

		return $content;				
	}

	/**
	 * Parses OpenOffice Writer documents
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function parseOpenOfficeWriter($file, $conf)	{
		$this->openOffice = t3lib_div::makeInstance ('tx_bitsoffice_openoffice');
		return $this->openOffice->mainWriter($file, $conf, $pageId);
	}

	/**
	 * Parses OpenOffice Calc Spreadsheets
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function parseOpenOfficeCalc($file, $conf)	{
		$this->openOffice = t3lib_div::makeInstance ('tx_bitsoffice_openoffice');
		return $this->openOffice->mainCalc($file, $conf);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$imgConf: ...
	 * @return	[type]		...
	 */
	function renderImage (&$imgConf) {
		$cObj = t3lib_div::makeInstance ('tslib_cobj');
		if (t3lib_div::inList('gif,jpeg,jpg,png',strtolower($imgConf['nameInfo']['extension'])))	{
		
			$fileName = PATH_site.'typo3temp/'.$this->extKey.'_'.substr(md5($imgConf['imageData']),0,10).'.'.strtolower($imgConf['nameInfo']['extension']);
			if (!@is_file($fileName))	{
				t3lib_div::writeFile($fileName,base64_decode($imgConf['imageData']));
			}
			$iInfo=@getimagesize($fileName);
			if (is_array($iInfo))	{
				$lConf = $imgConf['conf']['imageCObject_scaledImage.'];
				$cObj->setCurrentVal(substr($fileName,strlen(PATH_site)));
				return $cObj->IMAGE($lConf);
			}
		}	
	}
	
	
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/pi1/class.tx_bitsoffice_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/pi1/class.tx_bitsoffice_pi1.php']);
}

?>