CREATE TABLE IF NOT EXISTS `#__jspaceoai_harvests` (
  `catid` INTEGER PRIMARY KEY NOT NULL DEFAULT '0' COMMENT 'Primary key matching associated Category table id.',
  `harvested` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `resumptionToken` VARCHAR(255) DEFAULT NULL,
  `failures` INTEGER NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;