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




/**
 * Addition of an item to the clickmenu
 *
 * @author	Joachim Karl <joachim.karl@bitsafari.de>
 * @package	TYPO3
 * @subpackage	tx_bitsoffice
 */
class tx_bitsoffice_cm1 {
	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA,$LANG;

		$localItems = Array();
		if (!$backRef->cmLevel)	{
			
				// Returns directly, because the clicked item was not from the pages table 
			if ($table!='pages')	return $menuItems;
			
			$LL = $this->includeLL();
			
			$localItems['moreoptions_tx_bitsoffice_cm1']=$backRef->linkItem(
				$GLOBALS['LANG']->getLLL('cm1_title_activate',$LL),
				$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath('bitsoffice').'cm1/cm_icon_activate.gif" width="15" height="12" border="0" align="top" alt="" />'),
				"top.loadTopMenu('".t3lib_div::linkThisScript()."&cmLevel=1&subname=moreoptions_tx_bitsoffice_cm1');return false;",
				0,
				1
			);
			
				// Find position of 'delete' element:
			reset($menuItems);
			$c=0;
			while(list($k)=each($menuItems))	{
				$c++;
				if (!strcmp($k,'delete'))	break;
			}
				// .. subtract two (delete item + divider line)
			$c-=2;
				// ... and insert the items just before the delete element.
			array_splice(
				$menuItems,
				$c,
				0,
				$localItems
			);
		} elseif (t3lib_div::GPvar('subname')=='moreoptions_tx_bitsoffice_cm1') {
				// Adds the regular item:
			$LL = $this->includeLL();
			
				// Repeat this (below) for as many items you want to add!
				// Remember to add entries in the localconf.php file for additional titles.
			for($docType=1; $docType<=3; $docType++)	{
				$url = t3lib_extMgm::extRelPath('bitsoffice').'cm1/index.php?id='.$uid.'&doctype='.$docType;
				$localItems[] = $backRef->linkItem(
					$GLOBALS['LANG']->getLLL('cm1_title'.$docType,$LL),
					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath('bitsoffice').'cm1/cm_icon'.$docType.'.gif" width="16" height="16" border="0" align="top" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
				);
			}
			
			$menuItems=$localItems;
		}
		return $menuItems;
	} 
	
	/**
	 * Includes the [extDir]/locallang.php and returns the $LOCAL_LANG array found in that file.
	 * 
	 * @return	[type]		...
	 */
	function includeLL()	{
		include(t3lib_extMgm::extPath('bitsoffice').'locallang.php');
		return $LOCAL_LANG;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_cm1.php']);
}

?>