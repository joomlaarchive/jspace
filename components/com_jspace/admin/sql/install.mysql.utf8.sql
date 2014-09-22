CREATE TABLE IF NOT EXISTS `#__jspace_records` (
	`id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
	`asset_id` INTEGER NOT NULL DEFAULT 0 COMMENT 'FK to the #__assets table.',
	`title` VARCHAR(1024) NOT NULL,
	`alias` VARCHAR(255) NOT NULL DEFAULT '',
	`published` TINYINT NOT NULL DEFAULT 0 COMMENT 'The published state of the record.',
	`language` CHAR(7) NOT NULL COMMENT 'The language code for the record.',
	`created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by` INTEGER NOT NULL DEFAULT 0,
	`modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` INTEGER NOT NULL DEFAULT 0,
	`checked_out` INTEGER NOT NULL DEFAULT 0,
	`checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`metadata` TEXT NOT NULL,
    `level` INTEGER NOT NULL DEFAULT 0,
    `path` VARCHAR(1024) NOT NULL COMMENT 'The computed path of the record based on the alias field.',
    `lft` INTEGER NOT NULL DEFAULT 0 COMMENT 'Nested set lft.',
    `rgt` INTEGER NOT NULL DEFAULT 0 COMMENT 'Nested set rgt.',
	`schema` VARCHAR(255) NOT NULL DEFAULT 'Record' COMMENT 'The schema to load as part of this record.',
	`parent_id` INTEGER NOT NULL DEFAULT 0,
	`ordering` INTEGER NOT NULL DEFAULT 0,
	`version` INTEGER NOT NULL DEFAULT 1,
	`access` INTEGER NOT NULL DEFAULT 0,
	`catid` INTEGER NOT NULL,
	KEY `idx_jspace_records_access` (`access`),
	KEY `idx_jspace_records_checkout` (`checked_out`),
    KEY `idx_jspace_records_published` (`published`),
    KEY `idx_jspace_records_parent_id` (`parent_id`),
    KEY `idx_jspace_records_schema` (`schema`),
    KEY `idx_jspace_records_createdby` (`created_by`),
    KEY `idx_jspace_records_language` (`language`),
    KEY `idx_jspace_record_categories_catid` (`catid`),
    KEY `idx_jspace_record_left_right` (`lft`,`rgt`),
    KEY `idx_jspace_record_id_left_right` (`id`,`lft`,`rgt`),
    KEY `idx_path` (`path`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Provides a table for storing alternative external identifiers against a record (E.g. handle.net).
CREATE TABLE IF NOT EXISTS `#__jspace_record_identifiers` (
    `id` VARCHAR(255) NOT NULL,
    `record_id` INTEGER NOT NULL,
    PRIMARY KEY (`id`, `record_id`),
    KEY `idx_jspace_record_identifiers_id` (`id`),
    KEY `idx_jspace_record_identifiers_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__jspace_record_ancestors` (
	`decendant` INTEGER NOT NULL DEFAULT 0,
	`ancestor` INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY (`decendant`, `ancestor`),
	KEY `idx_jspace_record_ancestors_decendant` (`decendant`),
	KEY `idx_jspace_record_ancestors_ancestor` (`ancestor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- physical files being archived.

CREATE TABLE IF NOT EXISTS `#__jspace_assets` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `contentType` VARCHAR(255) NOT NULL,
    `contentLength` INTEGER NOT NULL COMMENT 'In bytes',
	`hash` VARCHAR(255) NOT NULL,
	`metadata` TEXT NOT NULL,
	`derivative` VARCHAR(255) NOT NULL,
	`bundle` VARCHAR(255) NOT NULL,
	`record_id` INTEGER NOT NULL,
	PRIMARY KEY (`id`),
	KEY `idx_jspace_assets_hash` (`hash`),
	KEY `idx_jspace_assets_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- references (links) to assets

CREATE TABLE IF NOT EXISTS `#__jspace_references` (
	`id` INTEGER NOT NULL COMMENT 'The id of the context as it appears in the related component table.',
	`context` VARCHAR(255) NOT NULL COMMENT 'The component and type name, E.g. com_jspace.ref, com_weblinks.weblink, com_newsfeeds.newsfeed, etc',
	`bundle` VARCHAR(255) NULL,
	`record_id` INTEGER NOT NULL,
	PRIMARY KEY (`id`, `context`),
	KEY `idx_jspace_reference_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- A generic harvesting table. @todo Perhaps this should be more generalized, I.e. jspace_imports.

CREATE TABLE IF NOT EXISTS `#__jspace_harvests` (
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `originating_url` VARCHAR(255) NOT NULL,
    `harvester` VARCHAR(255) NOT NULL COMMENT 'The harvester to use for this harvest.',
    `frequency` INTEGER NOT NULL DEFAULT 0 COMMENT 'The number of times the harvester should run.',
    `total` INTEGER NOT NULL DEFAULT 0 COMMENT 'The number of times the harvester has run.',
    `harvested` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `params` TEXT NOT NULL,
    `state` TINYINT NOT NULL DEFAULT 0 COMMENT 'The published state of the harvest.',
    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` INTEGER NOT NULL DEFAULT 0,
    `modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` INTEGER NOT NULL DEFAULT 0,
    `checked_out` INTEGER NOT NULL DEFAULT 0,
    `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `catid` INTEGER NOT NULL DEFAULT '0' COMMENT 'Harvest into this category.',
    KEY `idx_jspace_harvests_checkout` (`checked_out`),
    KEY `idx_jspace_harvests_published` (`state`),
    KEY `idx_jspace_harvests_createdby` (`created_by`),
    KEY `idx_jspace_record_categories_catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- A holding table for records harvested.

CREATE TABLE `#__jspace_cache` (
    `id` VARCHAR(255), 
    `data` TEXT NULL, 
    `harvest_id` INTEGER NOT NULL DEFAULT 0, 
    PRIMARY KEY(`id`, `harvest_id`),
    KEY `idx_jspace_cache_harvest_id` (`harvest_id`)
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
"core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params", 
"core_featured":"null", "core_metadata":"metadata", "core_language":"language", "core_images":"null", 
"core_urls":"null", "core_version":"version", "core_ordering":"null", "core_metakey":"metakey", 
"core_metadesc":"metadesc", "core_catid":"parent_id", "core_xreference":"null", "asset_id":"asset_id"}, 
"special":{"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"path","extension":"extension",
"note":"note"}}',
'JSpaceHelperRoute::getCategoryRoute',
'{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml", 
"hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], 
"ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", 
"path"],"convertToInt":["publish_up", "publish_down"], 
"displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},
{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{
"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{
"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}'
),
(NULL, 
'JSpace Record', 
'com_jspace.record', 
'{"special":{"dbtable":"#__jspace_records","key":"id","type":"Record","prefix":"JSpaceTable","config":"array()"},"common":{
"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}', 
'', 
'{"common":{"core_content_item_id":"id","core_title":"title","core_state":"state","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description", "core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"params", "core_featured":"featured", "core_language":"language", "core_images":"images", "core_urls":"urls", "core_version":"version", "core_ordering":"ordering", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"catid", "core_xreference":"xreference", "asset_id":"asset_id"}, "special":{"metadata":"metadata"}}', 
'JSpaceHelperRoute::getRecordRoute', 
'{"formFile":"administrator\/components\/com_jspace\/models\/forms\/record.xml", "hideFields":["metadata","asset_id","checked_out","checked_out_time","version","lft","rgt","level","path"],"ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time", "version", "lft","rgt","level","path"],"convertToInt":["publish_up", 
"publish_down","ordering"],"displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id",
"displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id",
"displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id",
"displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id",
"displayColumn":"name"},{"sourceColumn":"parent_id","targetTable":"#__jspace_records","targetColumn":"id",
"displayColumn":"title"}]}');

INSERT INTO `#__jspace_records` (`asset_id`, `title`, `alias`, `published`, `language`, `created`, `created_by`, `metadata`, `path`, `lft`, `rgt`, `catid`) VALUES (0, 'JSpace_Record_Root', 'root', 1, '*', '2011-01-01 00:00:01', 42, '{}', '', 0, 1, 0);