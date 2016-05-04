UPDATE skins SET skin_stylesheet = REPLACE(skin_stylesheet, 'css/', '' );
//#STATEMENT
CREATE TABLE `encoding_queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `hash` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` text NOT NULL,
  `original_filename` text NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user` (`user_id`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
//#STATEMENT
CREATE TABLE `encoding_queue_item` (
  `encoding_queue_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `ready_for_encoding` tinyint(1) NOT NULL DEFAULT '0',
  `job_id` int(11) DEFAULT NULL,
  `ready_for_processing` tinyint(1) NOT NULL DEFAULT '0',
  KEY `fk_encoding_queue` (`encoding_queue_id`),
  KEY `fk_item` (`item_id`),
  CONSTRAINT `fk_encoding_queue` FOREIGN KEY (`encoding_queue_id`) REFERENCES `encoding_queue` (`id`),
  CONSTRAINT `fk_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=FIXED;
//#STATEMENT
ALTER TABLE `users` ADD `secret` text NULL AFTER `copyright_accepted`;
//#STATEMENT
INSERT  INTO reports
  (`title`, param_group, `sql`, parameters, min_permissions, sort_order, cached, cache_refresh_delay) 
VALUES (
	'Guest User Audit',
	NULL,
	'SELECT \nCONCAT(\'<a href=\"%3Fcmd%3DsetGuest%26uid%3D\',u.user_id, \'\" target=\"new\">edit access</a>\') AS link, \nusername, CONCAT (first_name, \' \', last_name) AS `name`, \np.label AS role, last_login, expiration\nFROM users AS u \nJOIN special_users AS s ON u.user_id = s.user_id\nJOIN permissions_levels AS p ON p.permission_id = u.dflt_permission_level\nORDER BY expiration;',
	NULL,
	4,
	0,
	0,
	6
);
//#STATEMENT
INSERT INTO `mimetypes` (`mimetype_id`,`mimetype`,`helper_app_url`,`helper_app_name`,`helper_app_icon`) VALUES 
 (8,'image/gif',NULL,'Image','public/images/doc_type_icons/doctype-image.gif'),
 (9,'image/bmp',NULL,'Image','public/images/doc_type_icons/doctype-image.gif'),
 (10,'image/jpg',NULL,'Image','public/images/doc_type_icons/doctype-image.gif'),
 (11,'image/png',NULL,'Image','public/images/doc_type_icons/doctype-image.gif');
//#STATEMENT
INSERT INTO `mimetype_extensions` (`mimetype_id`,`file_extension`) VALUES 
 (8,'gif'),
 (9,'bmp'),
 (10,'jpg'),
 (10,'jpeg'),
 (11,'png');
//#STATEMENT
CREATE TABLE `action_audit` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `action` text NOT NULL,
  `when` datetime NOT NULL,
  `table` text DEFAULT NULL,
  `table_id` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user` (`user`),
  KEY `fk_level` (`level`),
  CONSTRAINT `fk_level` FOREIGN KEY (`level`) REFERENCES `permissions_levels` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

