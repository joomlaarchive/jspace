CREATE TABLE IF NOT EXISTS `#__jspace_records` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`asset_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'FK to the #__assets table.',
	`title` varchar(1024) NOT NULL,
	`alias` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
	`published` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'The published state of the menu link.',
	`hits` int(10) unsigned NOT NULL DEFAULT 0,
	`language` char(7) NOT NULL COMMENT 'The language code for the record.',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by` int(10) unsigned NOT NULL DEFAULT 0,
	`modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(10) unsigned NOT NULL DEFAULT 0,
	`checked_out` int(10) unsigned NOT NULL DEFAULT 0,
	`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`metadata` text NOT NULL,
	`schema` varchar(255) NOT NULL DEFAULT 'Record' COMMENT 'The schema to load as part of this record.',
	`parent_id` int(10) unsigned NOT NULL DEFAULT 0,
	`ordering` int(11) NOT NULL DEFAULT 0,
	`version` int(10) unsigned NOT NULL DEFAULT 1,
	`access` int(10) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	KEY `idx_jspace_records_access` (`access`),
	KEY `idx_jspace_records_checkout` (`checked_out`),
	KEY `idx_jspace_records_published` (`published`),
	KEY `idx_jspace_records_parent_id` (`parent_id`),
	KEY `idx_jspace_records_schema` (`schema`),
	KEY `idx_jspace_records_createdby` (`created_by`),
	KEY `idx_jspace_records_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jspace_record_ancestors` (
	`decendant` int(11) NOT NULL DEFAULT 0,
	`ancestor` int(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (`decendant`, `ancestor`),
	KEY `idx_jspace_record_ancestors_decendant` (`decendant`),
	KEY `idx_jspace_record_ancestors_ancestor` (`ancestor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jspace_record_categories` (
	`catid` int(10) unsigned NOT NULL DEFAULT 0,
	`record_id` int(10) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY(`catid`, `record_id`),
	KEY `idx_jspace_record_categories_catid` (`catid`),
	KEY `idx_jspace_record_categories_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jspace_assets` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`hash` varchar(255) NOT NULL,
	`metadata` text NOT NULL,
	`derivative` varchar(255) NOT NULL,
	`bundle` varchar(255) NOT NULL,
	`record_id` int(10) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `idx_jspace_assets_uid` (`hash`, `record_id`),
	KEY `idx_jspace_assets_hash` (`hash`),
	KEY `idx_jspace_assets_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `#__content_types` (`type_id`, `type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, 
`content_history_options`) 
VALUES 
(NULL,
'JSpace Category',
'com_jspace.category',
'{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"JTable","config":"array()"},"common":{
"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}',
'',
'{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias",
"core_created_time":"created_time","core_modified_time":"modified_time","core_body":"description", 
"core_hits":"hits","core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params", 
"core_featured":"null", "core_metadata":"metadata", "core_language":"language", "core_images":"null", 
"core_urls":"null", "core_version":"version", "core_ordering":"null", "core_metakey":"metakey", 
"core_metadesc":"metadesc", "core_catid":"parent_id", "core_xreference":"null", "asset_id":"asset_id"}, 
"special":{"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"path","extension":"extension",
"note":"note"}}',
'JSpaceHelperRoute::getCategoryRoute',
'{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml", 
"hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], 
"ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", 
"path"],"convertToInt":["publish_up", "publish_down"], 
"displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},
{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{
"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{
"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}'
),
(NULL, 
'JSpace Record', 
'com_jspace.record', 
'{"special":{"dbtable":"#__jspace_records","key":"id","type":"Record","prefix":"JSpaceTable","config":"array()"}}', 
'', 
'', 
'JSpaceHelperRoute::getRecordRoute', 
'{"hideFields":["asset_id","locked_by","locked_on","version"],"ignoreChanges":["modified_by", "modified_on", 
"locked_by", "locked_on", "version", "hits"],"convertToInt":["publish_up", 
"publish_down"],"displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id",
"displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id",
"displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id",
"displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id",
"displayColumn":"name"} ]}');

INSERT INTO `#__jspace_records` (`title`, `alias`) VALUES ('JSPACE_ROOT_RECORD', 'root');
