#
# Table structure for table 'azure_index'
#
CREATE TABLE tx_azuresearch_index (
  uid int(11) unsigned NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  title varchar(80) DEFAULT '' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);