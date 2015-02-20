DELETE FROM `#__content_types` WHERE `type_alias` LIKE 'com_jspace.%';

DROP TABLE IF EXISTS `#__jspace_records`;

DROP TABLE IF EXISTS `#__jspace_record_identifiers`;

DROP TABLE IF EXISTS `#__jspace_record_ancestors`;

DROP TABLE IF EXISTS `#__jspace_assets`;

DROP TABLE IF EXISTS `#__jspace_references`;

DROP TABLE IF EXISTS `#__jspace_harvests`;

DROP TABLE IF EXISTS `#__jspace_cache`;