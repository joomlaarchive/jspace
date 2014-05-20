CREATE TABLE IF NOT EXISTS `#__joai_harvests` (
	`catid` int(10) unsigned NOT NULL DEFAULT 0,
	`url` varchar(255) NOT NULL,
	`metadataFormat` varchar(32) NOT NULL,
	`harvestParams` TEXT NULL,
	PRIMARY KEY(`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;