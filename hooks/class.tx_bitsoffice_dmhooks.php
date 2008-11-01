<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 - 2006 Thomas Hempel (thomas@work.de)
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
 * This class contains a hooks for getting data after db operations.
 * Hook for getting the real pid of pages created after db operations.
 *
 * @package TYPO3
 * @subpackage bitsoffice
 *
 * @author 		Joachim Karl <joachim.karl@bitsafari.de>
 */

class tx_bitsoffice_dmhooks	{
	
	function processDatamap_afterDatabaseOperations ($status, $table, $ids, &$fieldArray, &$pObj) {
			$key = $pObj->datamap['pages'][$ids]['id'];
			
			if (is_array($GLOBALS['temp_ids'])) {
				if (array_key_exists($key, $GLOBALS['temp_ids'])&& !empty ($GLOBALS['temp_ids']) && $table == 'pages') {
						$transIDs = $pObj->substNEWwithIDs[$ids]; // new pid
						$GLOBALS['temp_ids'][$key]= $transIDs;
				}
			}
			
			if (is_array($GLOBALS['lookUp_ids'])) {
				if (array_key_exists($key, $GLOBALS['lookUp_ids'])&& !empty ($GLOBALS['lookUp_ids']) && $table == 'pages') {
						$transIDs = $pObj->substNEWwithIDs[$ids];
						$GLOBALS['lookUp_ids'][$key] ['id'] = $transIDs;
				}
			}
		}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/bitsoffice/hooks/class.tx_bitsoffice_dmhooks.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']["ext/bitsoffice/hooks/class.tx_bitsoffice_dmhooks.php"]);
}
?>
