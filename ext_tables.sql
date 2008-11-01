#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_bitsoffice_tx_bitsoffice_office_file blob NOT NULL
);

CREATE TABLE tx_bitsoffice_lookup (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	lookup_id int(11) DEFAULT '0' NOT NULL,
	lookup_param text NOT NULL,
	PRIMARY KEY (uid),
);