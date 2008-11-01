<?php

########################################################################
# Extension Manager/Repository config file for ext: "bitsoffice"
#
# Auto generated 01-11-2008 02:10
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Extended Office Import',
	'description' => 'Imports and Displays Open Office Writer documents. This version extends the rlmp_officeimport from Robert Lemke. It render the backend pagetree depending on the headings in your document. Each heading section will render a page with a text element in the backend pagetree. The other functions for Word or Excel file from Microsoft Office 2003 will also be available, but works in the same way like rlmp_officeimport. It supports continue numbering of lists, cross references.',
	'category' => 'plugin',
	'author' => 'Joachim Karl',
	'author_email' => 'joachim.karl@bitsafari.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'cm1',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'tt_content',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.10.0',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:35:{s:9:"ChangeLog";s:4:"fa3d";s:10:"README.txt";s:4:"ee2d";s:27:"class.tx_bitsoffice_cm1.php";s:4:"052d";s:36:"class.tx_bitsoffice_msoffice2003.php";s:4:"cbae";s:34:"class.tx_bitsoffice_openoffice.php";s:4:"3c92";s:34:"class.tx_bitsoffice_renderTree.php";s:4:"5e9d";s:27:"class.tx_bitsoffice_xml.php";s:4:"3932";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"1db4";s:14:"ext_tables.php";s:4:"0b4e";s:14:"ext_tables.sql";s:4:"164a";s:13:"locallang.php";s:4:"becd";s:16:"locallang_db.php";s:4:"81a8";s:9:"utils.php";s:4:"6752";s:13:"cm1/Thumbs.db";s:4:"fcec";s:13:"cm1/clear.gif";s:4:"cc11";s:15:"cm1/cm_icon.gif";s:4:"8074";s:16:"cm1/cm_icon1.gif";s:4:"301b";s:16:"cm1/cm_icon2.gif";s:4:"91f8";s:16:"cm1/cm_icon3.gif";s:4:"d9dc";s:16:"cm1/cm_icon4.gif";s:4:"1ebf";s:24:"cm1/cm_icon_activate.gif";s:4:"c435";s:12:"cm1/conf.php";s:4:"ab43";s:13:"cm1/index.php";s:4:"059c";s:17:"cm1/locallang.php";s:4:"4ae2";s:14:"doc/manual.sxw";s:4:"3a40";s:19:"doc/wizard_form.dat";s:4:"f14e";s:20:"doc/wizard_form.html";s:4:"48bb";s:37:"hooks/class.tx_bitsoffice_dmhooks.php";s:4:"a728";s:31:"pi1/class.tx_bitsoffice_pi1.php";s:4:"239e";s:17:"pi1/locallang.xml";s:4:"0a57";s:24:"pi1/static/editorcfg.txt";s:4:"c488";s:25:"samples/sample_manual.sxw";s:4:"31b0";s:23:"static/ts/constants.txt";s:4:"d41d";s:19:"static/ts/setup.txt";s:4:"63f1";}',
	'suggests' => array(
	),
);

?>