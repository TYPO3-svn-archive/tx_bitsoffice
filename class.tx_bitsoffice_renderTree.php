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
 * Plugin 'Extended Office Displayer' for the 'bits_office' extension.
 *
 * @author	Joachim Karl <joachim.karl@bitsafari.de>
 */


require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

class tx_bitsoffice_renderTree extends t3lib_extobjbase {
	
		/***************************************************************************
		*	This will flip the doc elements in the right 
		*	order for rendering the pagetree		
		*	
		*	@data			contains the document elements
		*	@searchLevel 	defines the 
		*	@start			defines the begin of the array index (0)
		*	@end			defines the end of the array index. we get this by count($data-1)
		*	@indexedArray
		*	
		****************************************************************************/
		
		function indexArray (&$data, $searchLevel, $start, $end, &$indexedArray) {
			
				$Arr_Lz = $end;
				if ($start == $end) { 
						$indexedArray[] = $Arr_Lz; 
				} else {
			
						while (($end - $start) > 0) {
							
								// walking through the document from the end, till we will find the next element with the same level
								// then we search for the next level in the next range
							
								do	{
									$level = $data[$Arr_Lz--]['level'];
								}
								while (!($level == $searchLevel) && ($Arr_Lz >= $start));
								
								// if we have found the next element of the same level
								// push the $arr_lz in the indexedArray and call the function recursive with the next higher level
								if ( $level == $searchLevel ) {
										$indexedArray[] = $Arr_Lz+1;
										$this->indexArray($data, $searchLevel+1, $Arr_Lz+2, $end, $indexedArray);
								}
								else {	
										//@todo hinweis das kein level gefunden wurde!
										while ( $end-- > $Arr_Lz ) {
												$indexedArray[] = $end+1;
										}
								}
								$end = $Arr_Lz;
								if ($start == $end ) $indexedArray[] = $end;	  
						}
				}
		}
		
		/**
		 * Main function creating the content for the module.
		 *
		 * @return	string		HTML content for the module, actually a "section" made through the parent object in $this->pObj
		 */
		function renderTree(&$data, $pageId, &$refArray, &$officeConfig)	{
	
				global $LANG;
				$theCode='';
				$pRec = t3lib_BEfunc::getRecord ('pages', $pageId,'uid',' AND '.$GLOBALS['BE_USER']->getPagePermsClause(8));
				$sys_pages = t3lib_div::makeInstance('t3lib_pageSelect');
				$menuItems = $sys_pages->getMenu(18);
				
				if (is_array($pRec))	{
						if (count($data)) {
							
								$indexedArray = array();
								$this->indexArray($data, 1, 0, count($data)-1, $indexedArray); //, $Arr_Index gelöscht
								$pageIndex = 0;
								$sorting = count($data);
								$oldLevel = 1;
								$parentPid = array();
								$i=0;
								$Arr_Lz = 0;	
								while ( $Arr_Lz < count( $indexedArray ) )	{
										$index = $indexedArray[$Arr_Lz++];
										$level = $data[ $index ]['level'];
										if ($level == 1) {
												$currentPid = $pageId;	
												$parentPid[$level] = $pageId;
										}
										elseif ($level > $oldLevel) {
												$currentPid = 'NEW'.($pageIndex-1);
												$parentPid[$level] = $pageIndex-1;
										}
										elseif ($level === $oldLevel) {
												$currentPid = 'NEW'.$parentPid[$level];
										}
										elseif ($level < $oldLevel) {
												$currentPid = 'NEW'.$parentPid[$level];
										}
												
												// Get title and additional field values
										$pageTree['pages']['NEW'.$pageIndex]['title'] = $data[ $index ]['heading'];
										$pageTree['pages']['NEW'.$pageIndex]['id'] = $data[ $index ]['heading_id'];
										$pageTree['pages']['NEW'.$pageIndex]['pid'] = $currentPid;
										$pageTree['pages']['NEW'.$pageIndex]['sorting'] = $sorting--;
										$pageTree['pages']['NEW'.$pageIndex]['hidden'] = t3lib_div::_POST('hidePages') ? 1 : 0;
					
										if (!(empty($data[ $index ]['data']))) {
												$pageTree['tt_content']['NEW'.$pageIndex]['pid'] = 'NEW'.$pageIndex;
												$pageTree['tt_content']['NEW'.$pageIndex]['bodytext'] = implode($data[ $index ]['data']);
										}
															
										$pageIndex++;		
										$oldLevel = $level;
								}
								
								if (count($pageTree['pages']))	{
										reset($pageTree);
										$tce = t3lib_div::makeInstance('t3lib_TCEmain');
										$tce->stripslashes_values=0;
										$tce->start($pageTree,array());
										$tce->process_datamap();
										
										// now create the references with the given pids of the hook object
										$this->writeReference ($refArray);
										
										if(!empty($GLOBALS['lookUp_ids'])) {
												$this->writeLookUp();
										}
									
										t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
								} else {
										$theCode.=$GLOBALS['TBE_TEMPLATE']->rfw($LANG->getLL('wiz_newPageTree_noCreate').'<br /><br />');
								}
				
								// Display result:
								$tree = t3lib_div::makeInstance('t3lib_browseTree');
								$tree->init(' AND pages.doktype < 199 AND pages.hidden = "0"');
								$tree->thisScript = 'index.php';
								$tree->setTreeName('pageTree');
								$tree->ext_IconMode = true;
								$tree->expandAll = true;
								$tree->tree[] = array(
										'row' => $pageId,
										'title' => 'blip',
										'HTML' => t3lib_iconWorks::getIconImage('pages', $thePid, $GLOBALS['BACK_PATH'],'align="top"')
								);
								
								$tree->getTree($thePid);
								$theCode .= $LANG->getLL('wiz_newPageTree_created');
								$theCode .= $tree->printTree();
						}
				} else {
						$theCode.=$GLOBALS['TBE_TEMPLATE']->rfw($LANG->getLL('wiz_newPageTree_errorMsg1'));
				}
			
				return $theCode;
		}
		
		
		
		function setReferenceLink ($val) {
		
				$this->referenceLink = $val;
		}
		
		function getReferenceLink ($val) {
		
				return $this->referenceLink;
		}
		
		
		/**
		* we get back the global temp_ids array from the hook. now we can write the references into the content elements
		*
		*
		*/
	
		function writeReference (&$refArray) {
				if (is_array($refArray)) {
						foreach ($refArray as $key => $val) {
								$refID = $GLOBALS['temp_ids'][$key];
								if (!empty($refID) && !empty($val['content'])) {
										foreach ($val['content'] as $ckey => $cTitle) {
												$select 	= 'tt_content.*';
												$from		= 'tt_content';
												$where 		= 'pid='.$GLOBALS['temp_ids'][$ckey];
												$groupBy 	=	'';
												$orderBy 	=	'';
												$limit 		= 	'';
												$result 	= $GLOBALS['TYPO3_DB']->exec_SELECTquery( $select, $from, $where, $groupBy, $orderBy, $limit );
												
												if (!$this->getAjaxMode == 1) {
														$needle = array(
																		'###REFID###',
																		'###LINKTEXT###',
																	);
														$replaceVal = array(
																			$refID, //src
																			$refArray[$key]['title']
																	);
							
												} else {
														$needle = array(
																		'###IMPORTID###',
																		'###REFID###',
																		'###LINKTEXT###',
																	);
							
														$replaceVal = array(
																			$this->importID, //href
																			$refID, //src
																			$refArray[$key]['title']
																	);
												}
												
												while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
													$this->output(str_replace($needle, $replaceVal, $this->referenceLink));
													$row['bodytext'] = str_replace("---<".trim($key).">---", str_replace($needle, $replaceVal, $this->referenceLink), $row['bodytext']);
													$GLOBALS['TYPO3_DB']->exec_UPDATEquery($from, $where, $row);	
												}
										}
								}
						}
				}
		}
	
	
		function writeLookUp () {
				
				if (is_array($GLOBALS['lookUp_ids'])) {
				
						foreach($GLOBALS['lookUp_ids'] as $key => $val) {
								$insertArray['lookup_id'] = $val['id'];
								$insertArray['lookup_param'] = $val['lookup_param'];
								$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_bitsoffice_lookup', $insertArray);
						}
				}
		}
		
		
	function output ($val) {
		
			echo '<pre>';
				print_r($val);
			echo '</pre>';
		}
	
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_renderTree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bitsoffice/class.tx_bitsoffice_renderTree.php']);
}
 ?>