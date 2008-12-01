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

 
require_once(t3lib_extMgm::extPath('libunzipped').'class.tx_libunzipped.php');

/**
 * Class with extended libunzipped functions
 * 
 * 
 * @author	Joachim Karl <joachim.karl@bitsafari.de>
 * 
 */
class tx_bitsoffice_unzip extends tx_libunzipped {

	/**
	 * Traverses the directory $path and stores all files in the database hash table (one file per record)
	 * 
	 * @param	string		The path to the temporary folder created in typo3temp/
	 * @return	void		
	 * @access private
	 */
	function storeFilesInDB($path)	{
		$allFiles=array();
		$cc=0;

		$fileArr = $this->getAllFilesAndFoldersInPath(array(),$path);
		reset($fileArr);
		while(list(,$filePath)=each($fileArr))	{
			if (is_file($filePath))	{
				$fI=pathinfo($filePath);
				$info = @getimagesize($filePath);
				$fArray=array(
					'filemtime'=>filemtime($filePath),
					'filesize'=>filesize($filePath),
					'filetype'=>strtolower($fI['extension']),
					'filename'=>$fI['basename'],
					'filepath'=>substr($filePath,strlen($path)),
					'info' => serialize($info),
					'compressed' => ($this->compressedStorage ? 1 : 0)
				);
				
				$allFiles[]=$fArray;
				
				$fArray['content'] = t3lib_div::getUrl($filePath);
				if ($this->compressedStorage)	$fArray['content']=gzcompress($fArray['content']);

				$fArray['rel_id'] = $this->ext_ID;
				$fArray['hash'] = $this->fileHash;
				
				$query = $this->DBcompileInsert($fArray);
				$res = mysql(TYPO3_db,$query);
				if (mysql_error())	debug(array(mysql_error(),$filePath));
				$cc++;
			}
		
			$this->setFilePath($allFiles);
		}
		return $cc;
	}
	/**
	 * getting external id ($this->ext_ID)
	 * This is usefull if some plugin wants to identify a document not by its filename (which may have been changed) but by its relationship to the plugin.
	 * 
	 * @param	string		String to be hashed. Eg. filename
	 * @return	void		
	 */
	function getExternalID($string)	{
		return $this->ext_ID;	
	}
	
	function setFilePath(&$val) {
		$this->filePath = $val;
	}
	
	function getFilePath() {
		return $this->filePath;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_unzip.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_unzip.php']);
}
?>