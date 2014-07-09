CREATE TABLE IF NOT EXISTS `#__jspaceoai_harvests` (
	`catid` int(10) NOT NULL DEFAULT 0,
	`harvested` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`resumptionToken` varchar(255) NULL DEFAULT null,
	`attempts` int(10) NOT NULL DEFAULT 0,
	PRIMARY KEY(`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- A holding table for records harvested.

CREATE TABLE IF NOT EXISTS `#__jspaceoai_records` (
	`id` varchar(255) NOT NULL,
	`catid` int(10) NOT NULL DEFAULT 0,
	`metadata` TEXT NULL,
	`resumptionToken` varchar(255) NOT NULL DEFAULT '',	
	PRIMARY KEY(`id`, `catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;