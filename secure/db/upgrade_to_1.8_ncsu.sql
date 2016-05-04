ALTER TABLE items CHANGE item_group item_group set('MONOGRAPH','MULTIMEDIA','ELECTRONIC','HEADING','VIDEO') NOT NULL default '';
//#STATEMENT
ALTER TABLE items CHANGE old_id temp_call_num TEXT;
//#STATEMENT
ALTER TABLE `reserves` 
  CHANGE `status` `status` 
  SET('ACTIVE','INACTIVE','IN PROCESS','DENIED','SEARCHING STACKS','UNAVAILABLE','RECALLED','PURCHASING','RESPONSE NEEDED','SCANNING','COPYRIGHT REVIEW', 'NEW', 'RUSH', 'ON ORDER', 'MISSING') 
  CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'ACTIVE';
//#STATEMENT
CREATE TABLE `target_table_mapping` (
  `id` bigint(20) NOT NULL auto_increment,
  `table_name` varchar(128) default NULL,
  `display_name` varchar(256) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (1, 'users', 'instructor');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (2, 'users', 'proxy');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (3, 'users', 'student');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (4, 'reserves', 'reserve');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (5, 'reserves', 'heading');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (6, 'course_aliases', 'cross-listing');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (7, 'course_instances', 'course_instance');
//#STATEMENT
INSERT INTO target_table_mapping(id, table_name, display_name) VALUES (8, 'course_aliases', 'course_alias');
//#STATEMENT
CREATE TABLE `course_instance_audit` (
  `id` bigint(20) NOT NULL auto_increment,
  `course_instance_id` bigint(20) NOT NULL default '0',
  `alias_id` bigint(20) NOT NULL default '0',
  `user_id` bigint(20) NOT NULL default '0',
  `action` enum('add','remove','edit') default NULL,
  `target_object_id` bigint(20) default NULL,
  `target_type_id` int(11) default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `message` varchar(256) default NULL,
  `serialized_object` text,
  PRIMARY KEY  (`id`),
  KEY `course_instance_id` (`course_instance_id`),
  KEY `alias_id` (`alias_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
//#STATEMENT
ALTER TABLE `ils_requests` ADD `date_processed` TIMESTAMP NULL AFTER `date_added`;
//#STATEMENT
ALTER TABLE `ils_requests` CHANGE `date_added` `date_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
//#STATEMENT
ALTER TABLE `users` ADD `copyright_accepted` BOOLEAN NOT NULL DEFAULT 0 AFTER `old_user_id`;
