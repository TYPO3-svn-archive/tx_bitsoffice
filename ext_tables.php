<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=="BE")	{
	$GLOBALS["TBE_MODULES_EXT"]["xMOD_alt_clickmenu"]["extendCMclasses"][]=array(
		"name" => "tx_bitsoffice_cm1",
		"path" => t3lib_extMgm::extPath($_EXTKEY)."class.tx_bitsoffice_cm1.php"
	);
}

$tempColumns = Array (
	"tx_bitsoffice_office_file" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:bitsoffice/locallang_db.php:tt_content.tx_bitsoffice_office_file",		
		"config" => Array (
			"type" => "group",
			"internal_type" => "file",
			"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
			"max_size" => 500,	
			"uploadfolder" => "uploads/tx_bitsoffice",
			"size" => 1,	
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
);


t3lib_div::loadTCA("tt_content");
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='tx_bitsoffice_office_file;;;;1-1-1';

t3lib_extMgm::addPlugin(array('LLL:EXT:bitsoffice/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","bitsoffice");
t3lib_extMgm::addStaticFile($_EXTKEY,'static/setup.txt/', 'setup.txt');

?>