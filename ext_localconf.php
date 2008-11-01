<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_bitsoffice_pi1 = < plugin.tx_bitsoffice_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_bitsoffice_pi1.php','_pi1','list_type',1);

// Hooks for datamap procesing
// for processing the order sfe, when changing the pid
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:bitsoffice/hooks/class.tx_bitsoffice_dmhooks.php:tx_bitsoffice_dmhooks';


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamap_afterDatabaseOperations'][] = 'EXT:bitsoffice/hooks/class.tx_bitsoffice_dmhooks.php:tx_bitsoffice_dmhooks';



?>