DELETE FROM `access` WHERE TRUE;
//#STATEMENT
DELETE FROM `circ_rules` WHERE TRUE;
//#STATEMENT
DELETE FROM `course_aliases` WHERE TRUE;
//#STATEMENT
DELETE FROM `course_instance_audit` WHERE TRUE;
//#STATEMENT
DELETE FROM `course_instances` WHERE TRUE;
//#STATEMENT
DELETE FROM `courses` WHERE TRUE;
//#STATEMENT
DELETE FROM `departments` WHERE TRUE;
//#STATEMENT
DELETE FROM `electronic_item_audit` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_art_tags` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_art_to_art` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_art_to_role` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_articles` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_cat_to_role` WHERE TRUE;
//#STATEMENT
DELETE FROM `help_categories` WHERE TRUE;
//#STATEMENT
DELETE FROM `inst_loan_periods` WHERE TRUE;
//#STATEMENT
DELETE FROM `inst_loan_periods_libraries` WHERE TRUE;
//#STATEMENT
DELETE FROM `items` WHERE TRUE;
//#STATEMENT
DELETE FROM `libraries` WHERE TRUE;
//#STATEMENT
DELETE FROM `mimetype_extensions` WHERE TRUE;
//#STATEMENT
DELETE FROM `mimetypes` WHERE TRUE;
//#STATEMENT
DELETE FROM `news` WHERE TRUE;
//#STATEMENT
DELETE FROM `notes` WHERE TRUE;
//#STATEMENT
DELETE FROM `permissions_levels` WHERE TRUE;
//#STATEMENT
DELETE FROM `reports` WHERE TRUE;
//#STATEMENT
DELETE FROM `requests` WHERE TRUE;
//#STATEMENT
DELETE FROM `reserves` WHERE TRUE;
//#STATEMENT
DELETE FROM `skins` WHERE TRUE;
//#STATEMENT
DELETE FROM `special_users` WHERE TRUE;
//#STATEMENT
DELETE FROM `special_users_audit` WHERE TRUE;
//#STATEMENT
DELETE FROM `staff_libraries` WHERE TRUE;
//#STATEMENT
DELETE FROM `target_table_mapping` WHERE TRUE;
//#STATEMENT
DELETE FROM `terms` WHERE TRUE;
//#STATEMENT
DELETE FROM `user_view_log` WHERE TRUE;
//#STATEMENT
DELETE FROM `users` WHERE TRUE;
//#STATEMENT
INSERT INTO `access` (`access_id`,`user_id`,`alias_id`,`permission_level`,`enrollment_status`,`autofeed_run_indicator`) VALUES 
 (1,1,1,3,'APPROVED',NULL),
 (2,1,2,3,'APPROVED',NULL),
 (3,3,3,3,'APPROVED',NULL),
 (4,3,4,3,'APPROVED',NULL),
 (5,2,3,0,'APPROVED',NULL),
 (6,2,2,0,'APPROVED',NULL),
 (7,3,5,3,'APPROVED',NULL),
 (8,2,5,0,'APPROVED',NULL);
//#STATEMENT
INSERT INTO `circ_rules` VALUES  (1,'2HR-RES','2HR-RES','yes'),
 (2,'3DAY-RES','3DAY-RES','no'),
 (3,'1DAY-RES','1DAY-RES','no'),
 (4,'1HR-RES','1HR-RES','no'),
 (5,'8DAY-RES','8DAY-RES','no'),
 (6,'MEDIA','MEDIA','no');
//#STATEMENT
INSERT INTO `course_aliases` (`course_alias_id`,`course_id`,`course_instance_id`,`course_name`,`section`,`registrar_key`,`override_feed`) VALUES 
 (1,1,1,'Intro 2 Art','001',NULL,0),
 (2,2,2,'Remedial Art','001',NULL,0),
 (3,3,3,'Some Art','001',NULL,0),
 (4,4,3,'Cross listed Art','003',NULL,0),
 (5,5,5,'Basic Java','001',NULL,0);
//#STATEMENT
INSERT INTO `course_instance_audit` (`id`,`course_instance_id`,`alias_id`,`user_id`,`action`,`target_object_id`,`target_type_id`,`timestamp`,`message`,`serialized_object`) VALUES 
 (1,0,0,1,'add',2,8,'2012-05-17 15:37:17','Added new course alias','{\"courseID\":\"2\",\"courseAliasID\":\"2\",\"name\":null,\"deptID\":\"0\",\"department\":null,\"courseNo\":null,\"section\":null,\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"}'),
 (2,0,0,1,'add',2,7,'2012-05-17 15:37:17','Added Remedial Art','{\"courseInstanceID\":\"2\",\"crossListings\":[],\"course\":{\"courseID\":\"2\",\"courseAliasID\":\"2\",\"name\":\"Remedial Art\",\"deptID\":\"2\",\"department\":null,\"courseNo\":\"200\",\"section\":\"001\",\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"},\"courseList\":[],\"instructorList\":[],\"instructorIDs\":[],\"primaryCourseAliasID\":\"2\",\"term\":\"\",\"year\":\"0\",\"activationDate\":\"0000-00-00\",\"expirationDate\":\"0000-00-00\",\"status\":\"\",\"enrollment\":\"OPEN\",\"proxies\":[],\"proxyIDs\":[],\"students\":[],\"containsHeading\":false,\"duplicates\":[],\"reviewedBy\":null,\"reviewedOn\":null}'),
 (3,2,2,1,'add',1,1,'2012-05-17 15:37:17','Added Admin, Admin','{\"userID\":\"1\",\"userName\":\"admin\",\"firstName\":\"Admin\",\"lastName\":\"Admin\",\"email\":\"noreply@your.library.com\",\"dfltRole\":\"5\",\"lastLogin\":null,\"external_user_key\":null}'),
 (4,0,0,1,'add',3,8,'2012-05-17 15:46:40','Added new course alias','{\"courseID\":\"3\",\"courseAliasID\":\"3\",\"name\":null,\"deptID\":\"0\",\"department\":null,\"courseNo\":null,\"section\":null,\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"}'),
 (5,0,0,1,'add',3,7,'2012-05-17 15:46:40','Added Some Art','{\"courseInstanceID\":\"3\",\"crossListings\":[],\"course\":{\"courseID\":\"3\",\"courseAliasID\":\"3\",\"name\":\"Some Art\",\"deptID\":\"2\",\"department\":null,\"courseNo\":\"300\",\"section\":\"001\",\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"},\"courseList\":[],\"instructorList\":[],\"instructorIDs\":[],\"primaryCourseAliasID\":\"3\",\"term\":\"\",\"year\":\"0\",\"activationDate\":\"0000-00-00\",\"expirationDate\":\"0000-00-00\",\"status\":\"\",\"enrollment\":\"OPEN\",\"proxies\":[],\"proxyIDs\":[],\"students\":[],\"containsHeading\":false,\"duplicates\":[],\"reviewedBy\":null,\"reviewedOn\":null}'),
 (6,3,3,1,'add',3,1,'2012-05-17 15:46:40','Added Instructor, Test','{\"userID\":\"3\",\"userName\":\"tinstructor\",\"firstName\":\"Test\",\"lastName\":\"Instructor\",\"email\":\"tinstructor@spam.spam.spam\",\"dfltRole\":\"3\",\"lastLogin\":null,\"external_user_key\":null}'),
 (7,0,0,1,'add',4,8,'2012-05-17 15:47:06','Added new course alias','{\"courseID\":\"4\",\"courseAliasID\":\"4\",\"name\":null,\"deptID\":\"0\",\"department\":null,\"courseNo\":null,\"section\":null,\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"}'),
 (8,0,0,1,'add',4,7,'2012-05-17 15:47:06','Added Cross listed Art','{\"courseInstanceID\":\"4\",\"crossListings\":[],\"course\":{\"courseID\":\"4\",\"courseAliasID\":\"4\",\"name\":\"Cross listed Art\",\"deptID\":\"2\",\"department\":null,\"courseNo\":\"300\",\"section\":\"003\",\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"},\"courseList\":[],\"instructorList\":[],\"instructorIDs\":[],\"primaryCourseAliasID\":\"4\",\"term\":\"\",\"year\":\"0\",\"activationDate\":\"0000-00-00\",\"expirationDate\":\"0000-00-00\",\"status\":\"\",\"enrollment\":\"OPEN\",\"proxies\":[],\"proxyIDs\":[],\"students\":[],\"containsHeading\":false,\"duplicates\":[],\"reviewedBy\":null,\"reviewedOn\":null}'),
 (9,4,4,1,'add',3,1,'2012-05-17 15:47:06','Added Instructor, Test','{\"userID\":\"3\",\"userName\":\"tinstructor\",\"firstName\":\"Test\",\"lastName\":\"Instructor\",\"email\":\"tinstructor@spam.spam.spam\",\"dfltRole\":\"3\",\"lastLogin\":null,\"external_user_key\":null}'),
 (10,3,4,1,'add',4,6,'2012-05-17 15:47:38','Added Cross listed Art','{\"courseID\":\"4\",\"courseAliasID\":\"4\",\"name\":\"Cross listed Art\",\"deptID\":\"2\",\"department\":null,\"courseNo\":\"300\",\"section\":\"003\",\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"}'),
 (11,3,3,1,'add',2,3,'2012-05-17 15:49:46','Added Student, Test','{\"courseInstances\":[],\"courseList\":[],\"userID\":\"2\",\"userName\":\"tstudent\",\"firstName\":\"Test\",\"lastName\":\"Student\",\"email\":\"noreply@spam.spam.spam\",\"dfltRole\":\"0\",\"lastLogin\":null,\"external_user_key\":null}'),
 (12,2,2,1,'add',2,3,'2012-05-17 15:50:13','Added Student, Test','{\"courseInstances\":[],\"courseList\":[],\"userID\":\"2\",\"userName\":\"tstudent\",\"firstName\":\"Test\",\"lastName\":\"Student\",\"email\":\"noreply@spam.spam.spam\",\"dfltRole\":\"0\",\"lastLogin\":null,\"external_user_key\":null}'),
 (13,0,0,1,'add',5,8,'2012-05-17 15:50:39','Added new course alias','{\"courseID\":\"5\",\"courseAliasID\":\"5\",\"name\":null,\"deptID\":\"0\",\"department\":null,\"courseNo\":null,\"section\":null,\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"}'),
 (14,0,0,1,'add',5,7,'2012-05-17 15:50:39','Added Basic Java','{\"courseInstanceID\":\"5\",\"crossListings\":[],\"course\":{\"courseID\":\"5\",\"courseAliasID\":\"5\",\"name\":\"Basic Java\",\"deptID\":\"1\",\"department\":null,\"courseNo\":\"116\",\"section\":\"001\",\"uniformTitle\":\"\",\"registrarKey\":null,\"override_feed\":\"0\"},\"courseList\":[],\"instructorList\":[],\"instructorIDs\":[],\"primaryCourseAliasID\":\"5\",\"term\":\"\",\"year\":\"0\",\"activationDate\":\"0000-00-00\",\"expirationDate\":\"0000-00-00\",\"status\":\"\",\"enrollment\":\"OPEN\",\"proxies\":[],\"proxyIDs\":[],\"students\":[],\"containsHeading\":false,\"duplicates\":[],\"reviewedBy\":null,\"reviewedOn\":null}'),
 (15,5,5,1,'add',3,1,'2012-05-17 15:50:39','Added Instructor, Test','{\"userID\":\"3\",\"userName\":\"tinstructor\",\"firstName\":\"Test\",\"lastName\":\"Instructor\",\"email\":\"tinstructor@spam.spam.spam\",\"dfltRole\":\"3\",\"lastLogin\":null,\"external_user_key\":null}'),
 (16,5,5,1,'add',2,3,'2012-05-17 15:50:58','Added Student, Test','{\"courseInstances\":[],\"courseList\":[],\"userID\":\"2\",\"userName\":\"tstudent\",\"firstName\":\"Test\",\"lastName\":\"Student\",\"email\":\"noreply@spam.spam.spam\",\"dfltRole\":\"0\",\"lastLogin\":null,\"external_user_key\":null}'),
 (17,5,5,1,'add',1,4,'2012-05-17 16:03:51','Added Test-driven development : by example / Kent Beck.','{\"author\":\"Beck, Kent.\",\"source\":\"Boston : Addison-Wesley, c2003.\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"1601963\",\"URL\":null,\"mimeTypeID\":\"7\",\"homeLibraryID\":\"1\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":null,\"status\":\"ACTIVE\",\"itemID\":\"1\",\"title\":\"Test-driven development : by example \\/ Kent Beck.\",\"itemGroup\":\"MONOGRAPH\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (18,5,5,1,'add',2,4,'2012-05-17 16:05:05','Added Working Effectively With Legacy Cod','{\"author\":\"Feathers\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"9999999\",\"URL\":null,\"mimeTypeID\":\"7\",\"homeLibraryID\":\"1\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":null,\"status\":\"ACTIVE\",\"itemID\":\"2\",\"title\":\"Working Effectively With Legacy Cod\",\"itemGroup\":\"MONOGRAPH\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (19,2,2,1,'add',2,4,'2012-05-17 16:05:36','Added Working Effectively With Legacy Cod','{\"author\":\"Feathers\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"9999999\",\"URL\":null,\"mimeTypeID\":\"7\",\"homeLibraryID\":\"1\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":null,\"status\":\"ACTIVE\",\"itemID\":\"2\",\"title\":\"Working Effectively With Legacy Cod\",\"itemGroup\":\"MONOGRAPH\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (20,5,5,1,'add',3,4,'2012-05-17 16:19:25','Added Google','{\"author\":\"\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"\",\"URL\":\"google.com\",\"mimeTypeID\":\"7\",\"homeLibraryID\":\"0\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":\"images\\/doc_type_icons\\/doctype-link.gif\",\"status\":\"ACTIVE\",\"itemID\":\"3\",\"title\":\"Google\",\"itemGroup\":\"ELECTRONIC\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (21,5,5,1,'add',4,4,'2012-05-17 16:21:00','Added I\'M IN UR LIBRARY','{\"author\":\"The Internet\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"\",\"URL\":\"a0\\/a03e370786731b4f7a4de11927eb917d_4.gif\",\"mimeTypeID\":\"7\",\"homeLibraryID\":\"0\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":\"\",\"status\":\"ACTIVE\",\"itemID\":\"4\",\"title\":\"I\'M IN UR LIBRARY\",\"itemGroup\":\"ELECTRONIC\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (22,5,5,1,'add',6,4,'2012-05-17 16:34:51','Added Testing Images','{\"author\":\"\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":\"\",\"URL\":\"d2\\/d2c9779059e14e9eb4a92b90cba14c91_6.gif\",\"mimeTypeID\":\"8\",\"homeLibraryID\":\"0\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":\"\",\"status\":\"ACTIVE\",\"itemID\":\"6\",\"title\":\"Testing Images\",\"itemGroup\":\"ELECTRONIC\",\"creationDate\":\"2012-05-17\",\"lastModDate\":\"2012-05-17\",\"itemType\":\"ITEM\"}'),
 (23,3,3,3,'add',19,4,'2012-05-18 15:27:52','Added Title','{\"author\":\"sdf\",\"source\":\"\",\"volumeTitle\":\"\",\"volumeEdition\":\"\",\"pagesTimes\":\"\",\"performer\":\"\",\"localControlKey\":null,\"URL\":null,\"mimeTypeID\":\"7\",\"homeLibraryID\":\"2\",\"privateUserID\":null,\"privateUser\":null,\"physicalCopy\":null,\"itemIcon\":null,\"status\":\"ACTIVE\",\"itemID\":\"19\",\"title\":\"Title\",\"itemGroup\":\"MONOGRAPH\",\"creationDate\":\"2012-05-18\",\"lastModDate\":\"2012-05-18\",\"itemType\":\"ITEM\"}');
//#STATEMENT
INSERT INTO `course_instances` (`course_instance_id`,`primary_course_alias_id`,`term`,`year`,`activation_date`,`expiration_date`,`status`,`enrollment`,`reviewed_date`,`reviewed_by`) VALUES 
 (1,1,'Summer',2012,'2012-04-14','2012-08-01','ACTIVE','OPEN',NULL,NULL),
 (2,2,'Summer',2012,'2012-04-14','2012-08-01','ACTIVE','OPEN',NULL,NULL),
 (3,3,'Summer',2012,'2012-04-14','2012-08-01','ACTIVE','OPEN',NULL,NULL),
 (4,4,'Summer',2012,'2012-04-14','2012-08-01','ACTIVE','OPEN',NULL,NULL),
 (5,5,'Summer',2012,'2012-04-14','2012-08-01','ACTIVE','OPEN',NULL,NULL);
//#STATEMENT
INSERT INTO `courses` (`course_id`,`department_id`,`course_number`,`uniform_title`,`old_id`) VALUES 
 (1,2,'100','',NULL),
 (2,2,'200','',NULL),
 (3,2,'300','',NULL),
 (4,2,'300','',NULL),
 (5,1,'116','',NULL);
//#STATEMENT
INSERT INTO `departments` (`department_id`,`abbreviation`,`name`,`library_id`,`status`) VALUES 
 (1,'CSC','COMPUTER SCIENCE',1,NULL),
 (2,'ART','ART',2,NULL),
 (3,'ENG','ENGLISH',1,NULL);
//#STATEMENT
INSERT INTO `electronic_item_audit` (`audit_id`,`item_id`,`date_added`,`added_by`,`date_reviewed`,`reviewed_by`) VALUES 
 (1,1,'2012-05-17',1,NULL,NULL),
 (2,2,'2012-05-17',1,NULL,NULL),
 (3,3,'2012-05-17',1,NULL,NULL),
 (4,4,'2012-05-17',1,NULL,NULL),
 (5,5,'2012-05-17',1,NULL,NULL),
 (6,6,'2012-05-17',1,NULL,NULL),
 (7,7,'2012-05-17',1,NULL,NULL),
 (8,8,'2012-05-17',1,NULL,NULL),
 (9,9,'2012-05-17',1,NULL,NULL),
 (10,10,'2012-05-17',1,NULL,NULL),
 (11,11,'2012-05-17',1,NULL,NULL),
 (12,12,'2012-05-17',1,NULL,NULL),
 (13,13,'2012-05-17',1,NULL,NULL),
 (14,14,'2012-05-17',1,NULL,NULL),
 (15,15,'2012-05-17',1,NULL,NULL),
 (16,16,'2012-05-17',1,NULL,NULL),
 (17,17,'2012-05-17',1,NULL,NULL),
 (18,18,'2012-05-17',1,NULL,NULL);
//#STATEMENT
INSERT INTO `help_art_tags` (`article_id`,`tag`,`user_id`) VALUES 
 (5,'email',1073),
 (5,'phone',1073),
 (5,'requests',13139),
 (6,'phone',1073),
 (6,'website',1073),
 (11,'statistics',4048),
 (12,'instructor',4048),
 (13,'proxies',4048),
 (14,'users',4048),
 (15,'add',4048),
 (15,'materials',4048),
 (15,'new',4048),
 (16,'links',1073),
 (16,'url',4048),
 (17,'fax',4048),
 (18,'upload',4048),
 (19,'search',4048),
 (20,'copy',4048),
 (21,'copyright',4048),
 (33,'editing',4048),
 (40,'contact',1073),
 (40,'email',1073);
//#STATEMENT
INSERT INTO `help_art_to_art` (`article1_id`,`article2_id`,`relation_2to1`) VALUES 
 (1,2,'child'),
 (2,3,'sibling'),
 (1,4,'child'),
 (5,6,'sibling'),
 (15,20,'sibling'),
 (15,38,'sibling'),
 (15,33,'sibling'),
 (15,21,'sibling'),
 (15,28,'sibling'),
 (15,19,'child'),
 (15,17,'child'),
 (15,18,'child'),
 (15,16,'child'),
 (16,18,'sibling'),
 (16,19,'sibling'),
 (16,17,'sibling'),
 (17,18,'sibling'),
 (17,19,'sibling'),
 (18,19,'sibling'),
 (20,38,'sibling'),
 (20,33,'sibling'),
 (21,19,'sibling'),
 (21,16,'sibling'),
 (21,18,'sibling'),
 (21,17,'sibling'),
 (28,36,'sibling'),
 (36,35,'sibling'),
 (36,39,'sibling'),
 (36,34,'sibling'),
 (35,39,'sibling'),
 (35,34,'sibling'),
 (39,34,'sibling'),
 (33,38,'sibling'),
 (33,35,'child'),
 (23,38,'child'),
 (23,20,'child'),
 (23,33,'child'),
 (23,15,'child'),
 (23,39,'child'),
 (23,34,'child'),
 (23,35,'child'),
 (23,36,'child'),
 (23,30,'sibling'),
 (23,29,'sibling'),
 (23,12,'sibling'),
 (23,13,'sibling'),
 (13,12,'sibling'),
 (22,23,'child'),
 (22,25,'child'),
 (22,26,'child'),
 (22,31,'child'),
 (22,32,'child'),
 (22,30,'sibling'),
 (22,27,'sibling'),
 (27,30,'sibling'),
 (14,13,'child'),
 (14,12,'child'),
 (25,26,'sibling'),
 (25,31,'sibling'),
 (25,32,'sibling'),
 (26,31,'sibling'),
 (26,32,'sibling'),
 (31,32,'sibling'),
 (40,5,'sibling'),
 (41,33,'sibling'),
 (41,38,'sibling');
//#STATEMENT
INSERT INTO `help_art_to_role` (`article_id`,`permission_level`,`can_view`,`can_edit`) VALUES 
 (15,1,0,0),
 (15,0,0,0),
 (14,3,1,0),
 (14,2,1,0),
 (13,3,1,0),
 (13,2,1,0),
 (13,1,0,0),
 (13,0,0,0),
 (6,3,1,0),
 (6,2,1,0),
 (12,0,0,0),
 (12,1,0,0),
 (12,2,1,0),
 (12,3,1,0),
 (6,1,1,0),
 (6,0,1,0),
 (14,0,0,0),
 (5,3,1,0),
 (5,2,1,0),
 (5,1,0,0),
 (5,0,0,0),
 (14,1,0,0),
 (11,0,0,0),
 (11,1,0,0),
 (11,2,1,0),
 (11,3,1,0),
 (15,2,1,0),
 (15,3,1,0),
 (16,0,0,0),
 (16,1,0,0),
 (16,2,1,0),
 (16,3,1,0),
 (17,0,0,0),
 (17,1,0,0),
 (17,2,1,0),
 (17,3,1,0),
 (18,0,0,0),
 (18,1,0,0),
 (18,2,1,0),
 (18,3,1,0),
 (19,0,0,0),
 (19,1,0,0),
 (19,2,1,0),
 (19,3,1,0),
 (20,0,0,0),
 (20,1,0,0),
 (20,2,1,0),
 (20,3,1,0),
 (21,0,1,0),
 (21,1,1,0),
 (21,2,1,0),
 (21,3,1,0),
 (22,0,1,0),
 (22,1,1,0),
 (22,2,1,0),
 (22,3,1,0),
 (23,0,0,0),
 (23,1,0,0),
 (23,2,1,0),
 (23,3,1,0),
 (40,0,1,0),
 (40,1,1,0),
 (40,2,1,0),
 (40,3,1,0),
 (25,0,0,0),
 (25,1,0,0),
 (25,2,1,0),
 (25,3,1,0),
 (26,0,0,0),
 (26,1,0,0),
 (26,2,1,0),
 (26,3,1,0),
 (27,0,1,0),
 (27,1,1,0),
 (27,2,1,0),
 (27,3,1,0),
 (28,0,0,0),
 (28,1,0,0),
 (28,2,1,0),
 (28,3,1,0),
 (29,0,0,0),
 (29,1,0,0),
 (29,2,1,0),
 (29,3,1,0),
 (30,0,0,0),
 (30,1,0,0),
 (30,2,1,0),
 (30,3,1,0),
 (31,0,0,0),
 (31,1,0,0),
 (31,2,1,0),
 (31,3,1,0),
 (32,0,0,0),
 (32,1,0,0),
 (32,2,1,0),
 (32,3,1,0),
 (33,0,0,0),
 (33,1,0,0),
 (33,2,1,0),
 (33,3,1,0),
 (34,0,0,0),
 (34,1,0,0),
 (34,2,1,0),
 (34,3,1,0),
 (35,0,0,0),
 (35,1,0,0),
 (35,2,1,0),
 (35,3,1,0),
 (36,0,0,0),
 (36,1,0,0),
 (36,2,1,0),
 (36,3,1,0),
 (38,0,0,0),
 (38,1,0,0),
 (38,2,1,0),
 (38,3,1,0),
 (39,0,0,0),
 (39,1,0,0),
 (39,2,1,0),
 (39,3,1,0),
 (41,0,0,0),
 (41,1,0,0),
 (41,2,1,0),
 (41,3,1,0);
//#STATEMENT
INSERT INTO `help_articles` (`id`,`category_id`,`title`,`body`,`date_created`,`date_modified`) VALUES 
 (12,3,'Managing Instructors','Instructors have full ownership of their classes and may reactivate old classes, create new classes, and edit every aspect of the class and its associated reserve materials.<p>\r\n\r\nA class can have as many instructors as are necessary. This functionality is especially suited to team teaching situations. Each instructor has full access to the class to add and edit materials as well as all other class functions.<p>\r\n\r\n<b>To add an instructor to a class</b>:<br>\r\n1.  From the â€œMy Coursesâ€ tab, click on the class you wish to edit.<br>\r\n2.  On the \"Edit Class\" screen, click on the \"Edit\" link to the right of â€œInstructor(s).â€ On the next screen, current instructors are listed on the right, and you may choose a new instructor by searching for either Last Name or Username in the box on the left. <br>\r\n3.  In the drop-down menu, choose the instructor you would like to add and click \"Add Instructor\".<p>\r\n \r\n<b>To remove an instructor</b>: 	<br>\r\nCheck the box next to the name of the instructor you wish to remove (on the right side of the screen) and click \"Remove Instructor.\"<p>\r\nIf you do not see the instructor you are looking for in the drop-down menu, they are not yet in the system. Please contact your Reserves Desk staff to add them to the list of instructors. (woodruff.reserves@gmail.com)','2006-11-30','2006-11-30'),
 (5,4,'Library Reserves Staff','Put contact information for reserves staff at your library/libraries here.','2006-06-03','2006-12-15'),
 (6,4,'IT Help Desk','Put contact information for your technical help desk (usually campus IT, if users contact campus IT for help with passwords, etc.)','2006-06-04','2006-12-15'),
 (11,6,'About Viewing Statistics','From the â€œView Statisticsâ€ tab, you have the option to look at the â€œItem View Log for a class.â€  When you click on this link, it will take you to a list of your classes.  Select the class you wish to view the statistics for and click â€œContinue.â€<p>\r\n	\r\nThis screen will show you a list of the reserve items in the class that have been viewed (a student has clicked on the link to open that reserve item).  On the right-hand side, there are two columns: â€œTotal Hitsâ€ and â€œUnique Hits.â€<p>\r\n\r\nâ€œTotal Hitsâ€ tells you how many times that particular item has been opened.  â€œUnique Hitsâ€ tells you the number of individual students who have opened the item.  If a student has opened the item more than once, your â€œTotal Hitsâ€ column will be greater than your â€œUnique Hitsâ€ column.','2006-11-30','2006-11-30'),
 (13,3,'Managing Proxies','Proxies are \"assistants\" to the class for the duration of the current semester or until they are removed by the instructor. They may do everything an instructor can do within a given class, except for create other proxies. Proxies only have access to the course or courses to which they are specifically assigned by an instructor or by Reserves staff.  You may have as many proxies as you like for any given class.<p>\r\n	\r\n<b>To add a Proxy</b>:<br>\r\n1.  From the â€œMy Coursesâ€ tab, click on the class you wish to edit.<br>\r\n2.  On the \"Edit Class\" screen, click on the \"Edit\" link next to â€œProxies.â€ On the next screen, current proxies are listed on the right.  You may search a list of all users in the system in the box on the left, using either Last Name or Username.<br>\r\n3.  The drop-down menu will fill with the names of users matching your search.<br>\r\n4.  Choose a name in the drop-down menu and click \"Add Proxy\"<p>\r\n\r\n<b>To remove a proxy</b>: <br>\r\n1.  Check the box next to the name of the proxy you wish to remove (on the right side of the screen) and click \"Remove Selected Proxies.\"<p>\r\n\r\nA person must have logged into ReservesDirect at least once to be available to be made a proxy. If the name of the person you are looking for does not appear in your search results, please ask the person to log into the system.','2006-11-30','2006-11-30'),
 (14,3,'Managing Users','The Manage Users tab allows you to do three things: manage your own user profile (name and email address); and add or delete proxies from your classes. Clicking on either the \"Add Proxy\" or \"Delete Proxy\" links on this page will take you to a screen that asks you to choose one of your current classes. You will then be taken to the Add/Remove proxy screen. For more about how to use this function, consult the \"Managing Proxies\" article.','2006-11-30','2006-11-30'),
 (15,2,'Adding New Materials','There are two ways you can add new materials to a class.<p>\r\n\r\n--From the Edit Class page, click on the Ã¯Â¿Â½add new materialsÃ¯Â¿Â½ link above the reserves list for the class.<br>\r\n--From the Add a Reserve tab, select the class you want to add the reserve to and click Ã¯Â¿Â½continueÃ¯Â¿Â½.<p>\r\n	\r\nEither option will take you to the same screen.  From here, you can add a reserve by searching for the item, uploading a document, adding a URL, or faxing a document.  For details on how to use these options, click the appropriate link in the Ã¯Â¿Â½Follow-UpÃ¯Â¿Â½ help section.;','2006-11-30','2006-12-15'),
 (16,2,'Adding a URL','You can also add a URL to your reserves list, which links the reserve list for your class to an item located on the web.  This feature is often used for items such as newspaper articles, scholarly articles on sites like JSTOR, or music and videos associated with a web address.<p>\r\n	\r\nTo add a URL to your reserves list, choose the â€œAdd a URLâ€ link from the main â€œAdding New Materialsâ€ page.  Then simply type (or cut & paste) the web address from the address bar of your browser into the â€œURLâ€ box.<p>\r\n\r\n<b>Describing Your File</b><br>\r\nYou have a number of options for describing your file. The most basic descriptors are document title and author (title is required for display to students in the class). Title will display most prominently to students in the class; the other fields will appear below the title. When describing your documents, try to be as thorough as possible so that students can identify materials and cite them if necessary.<p>\r\n\r\nIf you are linking to one of multiple chapters from a book, it is generally best to put the chapter or movement title in the \"Title\" field and use the remaining fields to describe the main work that the selection is taken from.<p>\r\n\r\nThe various fields, such as Volume/Edition, can be used for different purposes depending on the type of document you are linking to (book chapter, journal article, musical work, etc.). The fields will accept whatever text you enter into them.<p>\r\n\r\nOnce you have finished describing your file, click â€œSave URL.â€  The URL will now appear as a reserve item in your class.  When a student clicks on the title of the item in the reserves list, she will be taken to the linked URL page.<p>\r\n\r\n\r\n<b>Copyright</b><br>\r\nReservesDirect operates under the Fair Use provision of United States copyright law.  By clicking â€œSave Documentâ€ you acknowledge that you have read the libraryâ€™s copyright notice and certify that to the best of your knowledge your use of the document falls within those guidelines.  Please be responsible in observing fair use when posting copyrighted materials. If you have questions about copyright and the materials you would like to use for your class, please contact the reserves staff at your library and consult your library\'s copyright policy.<p>\r\n\r\n<u>It is always preferable to link to a journal article rather than downloading it to your computer and then uploading it to the ReservesDirect system.</u>  If you need assistance creating a link to an article or other item, please contact the reserves staff at your library.  (woodruff.reserves@gmail.com)','2006-11-30','2006-11-30'),
 (17,2,'Faxing a Document','On the \"Add Reserve\" tab, when you click on the option to \"Fax a Document\", you will see a screen that gives you instructions for sending a fax to the ReservesDirect server. You do not have to go to this page first before you fax in a document; you can simply send faxes to 404-727-9089. The server will automatically convert your fax to an Adobe PDF and place it in a holding queue so that you can \"claim\" it and add it to one of your classes.<p>\r\n\r\n<b>Number of Faxed Pages & File Size</b><br>\r\nPlease limit your faxes to 25 sheets; faxes exceeding 25 will be split into separate files.<p>\r\n\r\n<b>Claiming Your Fax</b><br>\r\nYou must â€œclaimâ€ your fax once it has been transmitted to the system.  Faxed documents will remain available in the \"claim\" queue until midnight of the day that they are faxed in. At midnight all faxed documents are deleted.<p>\r\n\r\nTo view the fax queue, click on the button that says \"After your fax has finished transmitting, click here.\" This page displays all faxes that are currently waiting to be claimed. <p>\r\n\r\nYou can identify your fax by the number you faxed it from as well as the time stamp; you may also click the \"preview\" button to view your document.<p>\r\n\r\nCheck the box next to your document and click the \"Continue\" button.<p>\r\n\r\n<b>Describing Your File</b><br>\r\nYou have a number of options for describing your file. The most basic descriptors are title and author (title is required for display to students in the class). Title will display most prominently to students in the class; the other fields will appear below the title. When describing your documents, try to be as thorough as possible so that students can identify materials and cite them if necessary.<p>\r\n \r\nIf you are faxing more than one chapter from a book, it is generally best to put the chapter or movement title in the \"Title\" field and use the remaining fields to describe the main work that the selection is taken from.<p>\r\n\r\nThe various fields, such as Volume/Edition, can be used for different purposes depending on the type of document you are faxing (book chapter, journal article, musical work, etc.). The fields will accept whatever text you enter into them.<p>\r\n\r\n<b>Copyright</b><br>\r\nReservesDirect operates under the Fair Use provision of United States copyright law.  By clicking â€œSave Documentâ€ you acknowledge that you have read the libraryâ€™s copyright notice and certify that to the best of your knowledge your use of the document falls within those guidelines.  Please be responsible in observing fair use when posting copyrighted materials. If you have questions about copyright and the materials you would like to use for your class, please contact the reserves staff at your library and consult your library\'s copyright policy.  (woodruff.reserves@gmail.com)<p>\r\n\r\n<u>Always include the original copyright notice and publication information for all articles and book chapters that you fax to the system.</u>  We recommend copying the title page and the copyright page from the front of a book or article.<p>\r\n\r\n<b>Availability of Materials</b><br>\r\nFaxed documents are converted to PDF and placed in the \"claim\" queue as soon as they are received by the server; by the time your fax machine prints a confirmation sheet, your document should be available to claim in ReservesDirect.<p>\r\n\r\nYour document will not be available to students until you claim it.<br>\r\nDocuments are available to students immediately upon being claimed by you.','2006-11-30','2006-11-30'),
 (18,2,'Uploading a Document','At the Upload a Document screen, you will see a form you must fill out with the citation information for the item you wish to upload.  Required fields are Document Title and File, but we encourage instructors to add as much bibliographic information as possible.  On this screen you can also write a note that will be appended to this item anytime it appears in the reserve list for the class.<p>\r\n\r\n	\r\n<b>Select a File</b><br>\r\nSelecting a file to upload is much like attaching a file to an email. To select a file, simply click on the \"Browse\" button next to the \"File\" field in the upload form. This will open a file browser window on your computer. Navigate to the file you wish to upload and select it. This will automatically fill in the file path on the upload form.<p>\r\n\r\n<b>File Type and Size</b><br>\r\nYou may upload any file type to ReservesDirect. The most common file types currently in use are Adobe Acrobat (PDF), Word (.doc), Excel (.xls), and PowerPoint (.ppt). You may also upload other popular files such as JPEG, TIFF, and mP3, as well as SPSS data sets and much more.<p>\r\n\r\nIf you would like to put sound or video on reserve, please make use of our streaming media services. For audio, contact the Helibrun Music and Media Library, genmus@libcat1.cc.emory.edu. For video, contact Andy Ditzler, aditzle@emory.edu.<p>\r\n\r\nWhen uploading PDFs, we recommend keeping file size to about 2 megabytes (2 MB)--or about 25 clear, clean sheets--to optimize downloading and printing times.<p>\r\n\r\nReservesDirect will accept files up to 10 megabytes (10 MB) in size.<p>\r\n\r\n<b>Copyright</b><br>\r\nReservesDirect operates under the Fair Use provision of United States copyright law.  By clicking â€œSave Documentâ€ you acknowledge that you have read the libraryâ€™s copyright notice and certify that to the best of your knowledge your use of the document falls within those guidelines.  Please be responsible in observing fair use when posting copyrighted materials. If you have questions about copyright and the materials you would like to use for your class, please contact the reserves staff at your library and consult your library\'s copyright policy.  (woodruff.reserves@gmail.com)\r\n<p>\r\n\r\n<u>Always include the original copyright notice and publication information for all articles and book chapters that you upload to the system.</u>  We recommend copying the title page and the copyright page from the front of a book or article.<p>\r\n\r\n<b>Describing Your File</b><br>\r\nYou have a number of options for describing your file. The most basic descriptors are title and author (title is required for display to students in the class). Title will display most prominently to students in the class; the other fields will appear below the title. When describing your documents, try to be as thorough as possible so that students can identify materials and cite them if necessary.<p>\r\n \r\nIf you are uploading more than one chapter from a book, it is generally best to put the chapter or movement title in the \"Title\" field and use the remaining fields to describe the main work that the selection is taken from.<p>\r\n\r\nThe various fields, such as Volume/Edition, can be used for different purposes depending on the type of document you are uploading (book chapter, journal article, musical work, etc.). The fields will accept whatever text you enter into them.<p>\r\n\r\n<b>Availability of Materials</b><br>\r\nUploaded materials are available to your students as soon as you click \"Save Document\" and receive a confirmation that the upload was successful.<p>\r\n\r\nFor information on how to manipulate your reserve items once they are uploaded (for example, sorting materials or hiding them from student view for a period of time), see the topics under \"Editing a Class\".','2006-11-30','2006-11-30'),
 (19,2,'Searching for an Item','ReservesDirect has an Archive of over 70,000 items that have been digitized since electronic reserves began in 1999. The bulk of the content you will find here is articles and book chapters in PDF format, but the Archive also contains a good deal of streaming audio as well as documents in a variety of formats. You may search the entire Archive for materials suitable for your class.<p>\r\n\r\n	\r\n<b>You may search for an item in 3 ways.  You may search for Archived Materials, by Instructor, or through EUCLID.</b><p>\r\n\r\nSearching the Archived Materials will show you reserves that have previously been posted to the ReservesDirect system by other instructors, which you can add directly to your class.  You can search for these materials by title or by author by using the drop-down menu.  The author/title search is a keyword search that will return materials that have your search terms anywhere in the author or title.  You may view the reserve item by clicking on it.  If your search returns more than 20 results, navigate multiple pages of results by using the \"Next | Previous\" links.<p>\r\n\r\nSearching for materials by instructor will show you all the reserves that instructor has previously posted to the system.  Selecting an instructorâ€™s name from the drop-down menu will take you to a list of their previous reserve materials, which you can then add directly to your class.  You may view the reserve item by clicking on it.  If your search returns more than 20 results, navigate multiple pages of results by using the \"Next | Previous\" links.<p>\r\n\r\nTo add an item to your class, click the check-box next to the item.  When you have selected all the items you wish to add to your class, click â€œAdd Selected Materials.â€  <b>Digital items</b> that you add to your class are immediately available for use by students, as long as the class is active for the current semester. <b>Physical materials</b> that you request by searching the archive generate a request that gets sent to Reserves staff for processing. Requests will show a status of \"In Process\" until the item has been retrieved by staff and successfully added to your class. Please allow time for staff to retrieve the item from the shelves and add it to your class. Some items may take longer to obtain if they must be recalled from another patron. If you have questions about the availability of an item, please contact Reserves staff at your library.<p>\r\n\r\nYou can also search for materials through EUCLID by clicking on the link at the bottom of the screen.  This will take you directly to the EUCLID system.<p>\r\n\r\nOnce you have located the item you wish to place on reserve, click the â€œRequestâ€ button at the bottom of the screen.  You will then be prompted to enter your Emory ID number.  At the following screen, click on â€œWoodruff Reserveâ€”Instructor Use Only.â€  Fill out the form on the next screen, including whether you would like the item to be placed on physical reserve at the reserve desk in the library and/or if you would like it to be placed on electronic reserve in the Reserves Direct system.  Then click â€œSubmit Request.â€','2006-11-30','2006-11-30'),
 (20,2,'Copying Materials to Another Class','From the â€œEdit Classâ€ page that appears when you click on the name of one of your classes, you can select reserve items to copy to another class.<p>\r\n	\r\nSelect the items you wish to copy by checking the box to the right of the item(s).  When you have finished selecting the materials to be copied, click the box that says â€œCopy Selected to Another Class.â€<p>\r\n\r\nOn the next page, choose the class into which you wish to copy the materials by selecting the bullet to the left of the class name.  Then click â€œContinue.â€<p>\r\n\r\nThe reserve items should then appear in the new class exactly as they appeared in the class from which you copied them.','2006-11-30','2006-12-04'),
 (21,2,'Copyright Policy','ReservesDirect operates under the Fair Use provision of United States copyright law. <b>Please be responsible in observing fair use when posting copyrighted materials.</b> If you have questions about copyright and the materials you would like to use for your class, please contact the reserves staff at your library and consult your library\'s copyright policy. (woodruff.reserves@gmail.com)<p>\r\n\r\n<u>Always include the original copyright notice and publication information for all articles and book chapters that you add to the system.</u>  We recommend copying the title page and the copyright page from the front of a book or article.<p>\r\n	\r\nNB: It is always better to link to an article in a database (e.g. â€“ JSTOR, EBSCO, etc.) than to download it to your computer and re-upload it to the system.  If you need assistance creating a stable link to an article for your class, please contact the reserves staff.<p>\r\n\r\nFind out more about Fair Use \r\n<a href=http://web.library.emory.edu/services/circulation/reserves/copyright.html>here.</a>','2006-11-30','2006-11-30'),
 (22,5,'Working with the My Courses tab/list','The \"My Courses\" tab is the main control screen for setting up your classes and managing your reserves lists. The â€œMy Coursesâ€ tab automatically displays all of the classes for which you are the instructor or proxy and any classes in which you are currently enrolled.  You can switch between viewing classes you are teaching and classes in which you are enrolled by clicking on the tabs.  You can also click on the circles next to each semester in the â€œYou are teaching:â€ tab to view your classes for that semester.<p>\r\n\r\nIn the â€œYou are enrolled in:â€ tab, you can join a class or leave a class by clicking on the appropriate option in the upper right-hand corner.<p>\r\n	\r\nIn the â€œYou are teaching:â€ tab, the icon next to each class identifies its current status in the system.  A pencil icon means that the class is active (students can enroll in the class and view the reserves).  A triangle/exclamation point icon means that the class has been created by the registrar but is not yet active in the system (students cannot enroll in the class or view the reserves).  An unavailable icon means that the course has been cancelled by the registrar.<p>\r\n\r\nThe right-hand column next to a class identifies its enrollment status.  If a class is listed as OPEN, students may look up the class and join it (in the ReservesDirect system onlyâ€”not through the registrar).  If the class is listed as MODERATED, students may request to be added to the class, but must be approved by the instructor or proxy before gaining access to it.  If the class is listed as CLOSED, students may not join the class and may not request to be added.<p>\r\n\r\nClicking on the name of a class in the â€œYou are teaching:â€ tab will redirect you to the Edit Class page.  Clicking on the name of a class in the â€œYou are enrolled in:â€ list will take you to the reserves list for that class.','2006-11-30','2006-11-30'),
 (23,5,'Editing a Class','Clicking on the name of a class in the â€œYou are teaching:â€ tab will redirect you to the Edit Class page.  This screen shows you all the materials you have placed on reserve for the class.  This is the main screen you will use to edit, add, and remove materials and information pertaining to the class.  You can also view enrollment for the class by clicking on the â€œEnrollmentâ€ tab.','2006-11-30','2006-11-30'),
 (25,5,'Creating a New Class','Creating a new class to add reserves to is a quick and easy process.<p>\r\n \r\n1.  Simply click on the \"Create New Class\" link.<br>\r\n2.  Choose the Department of the course from the drop-down menu and fill in the course number, section, and course name as they appear in OPUS.<br>\r\n3.  Select the semester you will be teaching the course (you may choose the current semester, next semester, or either of the 2 following semesters).<br>\r\n4.  Click \"Create Course.<p>\r\n\r\nYour course has been established and you are now ready to begin adding materials. Follow the links provided to begin adding materials or just click on the \"Add a Reserve\" tab.','2006-11-30','2006-11-30'),
 (26,5,'Reactivating a Class','At the end of each semester, all classes in ReservesDirect are archived for future use. You can view a list of all your past classes by clicking on the \"Reactivate Class\" link on the \"My Courses\" tab.<p>\r\n\r\n1.  To see what the reserve list for any of your courses was click on the \"preview\" link.<br> \r\n2.  After determining which class you would like to reactivate, select the bullet to the left of it and click â€œContinue.â€<br>\r\n3.  Select the name of the class you will be teaching in the current or upcoming semester.  If the class name does not appear, click the â€œCreate New Classâ€ link.<br>\r\n4.  On the next screen, by default, all of the boxes next to reserve items will be checked.  If the box next to an item is checked, it will reactivate with the class.  De-select any materials you do not want imported from the last time the class was taught.<p>\r\n\r\nYour class will now be set up to become active on the first day of the semester you specified with all imported class materials appearing as they did the last time you taught the class. Requests to add physical items that are in the reserve list will be sent to Reserves staff to process. Please allow some time for physical materials to be retrieved from the shelves or recalled from other patrons who may have the materials checked out.','2006-11-30','2006-11-30'),
 (27,5,'Joining or Leaving a Class','<b>Joining Classes</b><br> \r\nTo join a class, simply click on the \"Join a Class\" link on the right-hand side of the â€œYou are enrolled in:â€ screen under the â€œMy Coursesâ€ tab. Lookup your class by instructor or department.  When searching by instructor name, use the drop-down list to find instructor names matching your search.  The search results screen will display all classes active for the current semester. Find your class and select the bullet to the left of the class name.  You may preview classes by clicking on the â€œpreviewâ€ link on the right-hand side of the class name.  When you have selected your class, click â€œContinue.â€  If the class enrollment status is Open, it will appear immediately in the â€œYou are enrolled in:â€ list.  If the class enrollment is Moderated, the class will appear as pending approval in the â€œYou are enrolled in:â€ list.<p>\r\n\r\n<b>Leaving Classes</b><br> \r\nTo leave class you are enrolled in, just click on \"Leave a Class\" from the â€œYou are Enrolled in:â€ screen under the â€œMy Coursesâ€ tab.  Select the bullet to the left of the class you would like to leave.  Then click â€œContinue.â€ You may not leave any classes for which you are the instructor or proxy. These will automatically disappear from your list after the class expiration date.','2006-11-30','2006-11-30'),
 (28,5,'Previewing a Class & Previewing Student View','Anytime you reactivate a class, export a class, or add a reserve, you have the option to preview the classes in the list by clicking on the â€œpreviewâ€ link to the right of the class.  Doing so will open a new window that shows you the reserve list for that class.<p>\r\n	\r\nTo preview the student view for a class you are teaching, select that class from the â€œMy Coursesâ€ list and then click the link in the upper right-hand corner that says â€œPreview Student View.â€  A new window will open that shows you the reserve list for that class as the students in the class will see it.','2006-11-30','2006-11-30'),
 (29,5,'Editing Cross-Listings','You may crosslist classes under multiple course names and numbers to reflect the crosslistings that appear in OPUS. For example, ILA 135 may be crosslisted with ARTH 110.<p>\r\n\r\nTo create a crosslisting, click on the course you wish to edit from the â€œMy Coursesâ€ tab if one of the crosslisted classes already exists in ReservesDirect. If none of the crosslisted classes exist yet, first create a class and then select the class you just created from the â€œMy Coursesâ€ tab.<p>\r\n\r\nOn the \"Edit Class\" screen you will see a list of all current Crosslistings for the class.<p>\r\n\r\nClick on the â€œEditâ€ link next to â€œCrosslistingsâ€ to create a new crosslisting (or delete an old one).<p>\r\n\r\nThis screen will show you the primary listing for the class and all current crosslistings under the \"Class Title and Crosslistings\" box, where you may edit the title and course number/section of existing crosslistings.<p>\r\n\r\n<b>To add a new crosslisting</b>:<br>	\r\n1.  Select the Department of the crosslisting from the drop-down menu in the \"Add New Crosslisting\" box and enter the number, section, and title of the crosslisting.<br> \r\n2.  Click on \"Add Crosslisting\"\r\nThe crosslisting will immediately be available for students to add to their list of classes.<p>\r\n\r\nNote: You may not delete a crosslisting if any students have added it to their list of classes. If you try to do so, an error message will appear informing you that students are currently \"enrolled\" in the class.','2006-11-30','2006-11-30'),
 (30,5,'About Class Enrollment','To manage your class enrollment, click on the â€œManage Class Enrollmentâ€ link under the â€œMy Coursesâ€ tab.  Then select the class you wish to manage.  Alternatively, you may select a class from the â€œMy Coursesâ€ home and then click on the â€œEnrollmentâ€ tab located next to the â€œCourse Materialsâ€ tab.<p>\r\n	\r\nOn the â€œEnrollmentâ€ screen you can adjust the enrollment status of the class.  If a class is listed as OPEN, students may look up the class and join it (in the ReservesDirect system onlyâ€”not through the registrar).  If the class is listed as MODERATED, students may request to be added to the class, but must be approved by the instructor or proxy before gaining access to it.  If the class is listed as CLOSED, students may not join the class and may not request to be added.<p>\r\n\r\nTo add a student to the class (to give a student access to the reserves list for that class) simply type either the studentâ€™s name or Emory username into the box.  The box will produce a list of auto-completed names as you type; you may select the name from the drop-down list once you have located the student you wish to add.  Then click â€œAdd Student to Roll.â€  The student should immediately gain access to the class.<p>\r\n\r\nIf a student has requested to be added to the class, a notice will appear next to the â€œEnrollmentâ€ tab for that class that says â€œ! students requesting to join class !â€   At the â€œEnrollmentâ€ tab, you may approve or deny students to join the class.  You can approve or deny them individually or all at once by clicking the appropriate link.<p>\r\n\r\nYou may also remove students from the class by clicking the â€œremoveâ€ link next to their names under the â€œCurrently enrolled in this classâ€ list on the â€œEnrollmentâ€ tab.  If a student was automatically added to the class by the registrar, you cannot remove them from the class in Reserves Direct.','2006-11-30','2006-11-30'),
 (31,5,'Exporting a Class','You may export your reserves list for any of your classes to Blackboard, Learnlink, or to a personal web page. Exporting your reserve list involves pasting a piece of code into the Blackboard class or page where you want your reserves list to appear. This creates a live feed of the list into your Blackboard class (through RSS), which is updated automatically. Any changes that are made to your list in ReservesDirect appear instantly in Blackboard.<p>\r\n\r\n<b>To export your class from the Manage Classes tab:</b><br>\r\n1.  Click on \"Export Class\" under the â€œMy Coursesâ€ tab.  (Alternatively, if you are in the â€œEdit Classâ€ view, you can click the link in the upper right-hand corner that says â€œExport Readings to Courseware.â€)  Select where you would like to export your reserves to (Blackboard, Learnlink, or a personal web page).<br> \r\n2.  Follow the on-screen instructions.','2006-11-30','2006-11-30'),
 (32,5,'Exporting a Class to Spreadsheet','To export a class to spreadsheet, simply click on the name of the class from the â€œMy Coursesâ€ tab.  Then click on the link in the upper right-hand side of the page that says â€œExport Class to Spreadsheet.â€  A window will pop up asking whether you want to open or save the file.  Choose which one you want to do and the file will appear on your computer.','2006-11-30','2006-11-30'),
 (33,5,'Editing Materials','FIX THIS ARTICLE FROM STAGING SITE <p>\r\n\r\nAfter you add an item to your class, you may edit all of the information associated with the item (author, title, etc.) and add notes to the item that will display to students in the class. Your reserves list is your to edit as you wish. The changes that you make','2006-11-30','2006-12-15'),
 (34,5,'Sorting the Main List','You may sort the items in your reserve list by title, author, or by a custom order of your choosing, such as syllabus order. You may also add headings to further divide your class into syllabus order or subject/topic area.<p>\r\n\r\n<b>To sort by author or title</b>:<br>\r\n1.  From the â€œMy Coursesâ€ tab, select the class you wish to sort.  Then click on the \"Sort Main List\" link just above the reserve list.<br>\r\n2.  On the sort screen, click on \"title\" or \"author\" in the \"Sort By\" box. The list will automatically sort by title or author.<br>\r\n3.  Click \"Save Order\" to save the new order.<p>\r\n\r\n<b>To sort materials in a custom order</b>:<br>\r\n1.  Go to the sort screen as described in step 1 above.<br>\r\n2.  On the right side of your readings, you will see boxes with numbers inside of them. These are the \"sort order\" your readings appear in.<br> \r\n3.  To change the position of a reading, simply type the new position number into the text box and hit the \"Tab\" key or click in a new box. The order number of all of the readings will automatically update to reflect the change.<br>\r\n4.  Continue assigning numbers to the readings. If you make an error and would like to put the readings back in their original positions, you may do so by clicking \"Reset to Saved Order.\"<br>\r\n5.  When you are finished, click \"Save Order.\"<p>\r\n\r\n<b>Sorting headings</b>:<br>\r\n1.  Headings show up in the reserve list as a divider with text in it. To position your heading where you want it to appear (for instance, above all the Week 1 readings), click on the \"sort\" link. You may use the custom sort numbers (described above) to position the heading wherever you want.<br>\r\n2.  If you have added reserve items to a heading, they will automatically be moved with their associated heading whenever you rearrange the sort order of the heading.  To rearrange the sort order of items within a heading, click on the stacked-paper â€œSortâ€ icon next to the heading and sort the items as described above.','2006-11-30','2006-12-04'),
 (35,5,'Adding Headings','Headings help organize your list of materials into topics or weeks. Headings can stand alone, or you can add items to them.  You can use any heading you want that you think would be useful to your students in identifying a group of readings that belong together, e.g. \"Week 1\" or \"Byzantium\".<p>  \r\n	\r\nTo add a heading to your reserve list for a class, select that class from the â€œYou are Teaching:â€ list in the â€œMy Coursesâ€ tab and click on the link that says â€œAdd New Heading.â€  Type the name of the heading into the box and click â€œSave Heading.â€<p>\r\n\r\nTo add an item to a heading (like you would to a folder), go to the Edit Class screen, check the box next to the items to add to the heading, and click â€œEdit Selected.â€  On the â€œEdit Multiple Reservesâ€ screen, select which heading to add the materials to and click the \"Submit\" button.  Those items will then appear under the heading you have chosen.  When sorting the main list, any files that are listed under a separate heading will act like â€œfilesâ€ within the â€œfolderâ€ of the heading.<p>\r\n\r\nYou may edit an existing heading by selecting the check box next to it or by clicking the pencil icon next to it.  You may also add a note to headings in the same way you can add a note to reserve items.  For example, you could add a note that says â€œAll of the readings for this week are required.â€  You may continue adding as many headings as you like and positioning them in your list of materials.  For information on positioning headings in the reserve list, consult the â€œSorting the Main Listâ€ help article.','2006-11-30','2006-11-30'),
 (36,5,'Hiding Items from Student View','You can \"hide\" items from student view and have them automatically appear to the whole class on a given date. For example, if you had a number of take-home tests for the class and only wanted the students to have access to them during the week of the test, you could upload all of the tests at once and set dates for each one to appear to the students. You can also hide readings or labs until the week they will be covered in class if you do not want students to work ahead. By default, all items have an Activation Date of the first day of the semester.<p>\r\n\r\n<b>To hide an item</b>:<br>	\r\n1.  From the Ã¯Â¿Â½My CoursesÃ¯Â¿Â½ tab, click on the class you wish to edit.<br>\r\n2.  This will take you to the \"Edit Class\" screen, where you will see a list of all your reserve items. Check the box(es) next to the item(s) you wish to hide and click Ã¯Â¿Â½Edit Selected.Ã¯Â¿Â½<br> \r\n3.  Enter the date you want the item to appear to students in the \"Activation Date\" Ã¯Â¿Â½From:Ã¯Â¿Â½ field in the format YYYY-MM-D. This date must fall during the current semester.  Enter the date you want the item to disappear in the Ã¯Â¿Â½To:Ã¯Â¿Â½ field.  Clicking the Ã¯Â¿Â½Reset DatesÃ¯Â¿Â½ link will reset the dates to the start and end of the semester during which that class takes place.\'','2006-11-30','2006-12-15'),
 (38,5,'Deleting Materials','From the Ã¯Â¿Â½Edit ClassÃ¯Â¿Â½ page that appears when you click on the name of one of your classes, you can select reserve items to delete.<p>\r\n	\r\nSelect the items you wish to delete by checking the box to the right of the item(s).  When you have finished selecting the materials to be deleted, click the box that says Ã¯Â¿Â½Delete Selected.Ã¯Â¿Â½<p>\r\n\r\nThe reserve items will then be permanently deleted from the class and will not appear in the reserve list.','2006-11-30','2006-12-13'),
 (39,5,'Highlighting Reserve Links','To highlight reserve links in a class, select that class from the â€œYou are Teaching:â€ list in the â€œMy Coursesâ€ tab and click on the link that says â€œHighlight Reserve Links.â€  All of the links to your reserve items will then be highlighted in yellow.  You may use this function to help locate the URL for items in order to copy & paste them into an email, Blackboard, etc.','2006-11-30','2006-12-04'),
 (40,4,'Contact the Reserves Desk','Put your main reserves contact point information here (listserv, phone, etc.)','2006-12-06','2006-12-15'),
 (41,5,'Editing Multiple Items','If you wish to edit multiple reserve items at the same time, use the check-box option and choose all of the reserve items you wish to edit. Editing multiple items at the same time means that whatever values you enter into their information fields will appear identical for each of the individual items. When editing multiple items, you are only able to edit the following fields: Status, Active Dates, Heading, and Note.','2006-12-12','2006-12-13');
//#STATEMENT
INSERT INTO `help_cat_to_role` (`category_id`,`permission_level`,`can_view`,`can_edit`) VALUES 
 (3,1,0,0),
 (3,2,1,0),
 (3,3,1,0),
 (3,0,0,0),
 (4,0,1,0),
 (4,1,1,0),
 (4,2,1,0),
 (4,3,1,0),
 (1,0,1,0),
 (1,1,1,0),
 (1,2,1,0),
 (1,3,1,0),
 (5,0,1,0),
 (5,1,1,0),
 (5,2,1,0),
 (5,3,1,0),
 (2,0,1,0),
 (2,1,1,0),
 (2,2,1,0),
 (2,3,1,0),
 (6,0,0,0),
 (6,1,0,0),
 (6,2,1,0),
 (6,3,1,0);
//#STATEMENT
INSERT INTO `help_categories` (`id`,`title`,`description`) VALUES 
 (1,'Tutorials','Tutorials'),
 (2,'Adding Materials','Instructions on how to add reserve materials to courses.'),
 (3,'Managing Users','Managing your user profile & managing proxies.'),
 (4,'Contacts','Articles about how to contact staff for help with the system.'),
 (5,'Managing Courses','An overview of the \"My Courses\" tab options and how to manage individual courses.'),
 (6,'Viewing Statistics','About viewing statistics for a course.');
//#STATEMENT
INSERT INTO `inst_loan_periods` (`loan_period_id`,`loan_period`) VALUES 
 (1,'1 Day');
//#STATEMENT
INSERT INTO `inst_loan_periods_libraries` (`id`,`library_id`,`loan_period_id`,`default`) VALUES 
 (1,1,1,'true'),
 (2,2,1,'true');
//#STATEMENT
INSERT INTO `items` (`item_id`,`title`,`author`,`source`,`volume_title`,`content_notes`,`volume_edition`,`pages_times`,`performer`,`local_control_key`,`creation_date`,`last_modified`,`url`,`mimetype`,`home_library`,`private_user_id`,`item_group`,`item_type`,`item_icon`,`ISBN`,`ISSN`,`OCLC`,`status`,`temp_call_num`) VALUES 
 (1,'Test-driven development : by example / Kent Beck.','Beck, Kent.','Boston : Addison-Wesley, c2003.','',NULL,'','','','1601963','2012-05-17','2012-05-17',NULL,7,1,NULL,'MONOGRAPH','ITEM',NULL,'0321146530','','50479239','ACTIVE',NULL),
 (2,'Working Effectively With Legacy Code','Feathers','','',NULL,'','','','9999999','2012-05-17','2012-05-17',NULL,7,1,NULL,'MONOGRAPH','ITEM','',NULL,'','','ACTIVE',NULL),
 (3,'Google','','','',NULL,'','','','','2012-05-17','2012-05-17','google.com',7,0,NULL,'ELECTRONIC','ITEM','public/images/doc_type_icons/doctype-link.gif',NULL,'','','ACTIVE',NULL),
 (4,'I\'M IN UR LIBRARY','The Internet','','',NULL,'','','','','2012-05-17','2012-05-17','a0/a03e370786731b4f7a4de11927eb917d_4.gif',8,0,NULL,'ELECTRONIC','ITEM','public/images/doc_type_icons/doctype-image.gif',NULL,'','','ACTIVE',NULL),
 (5,'Testing Images','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (6,'Testing Images','','','',NULL,'','','','','2012-05-17','2012-05-17','d2/d2c9779059e14e9eb4a92b90cba14c91_6.gif',8,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (7,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (8,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (9,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (10,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (11,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (12,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (13,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (14,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (15,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (16,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (17,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (18,'Testing Uploads','','','',NULL,'','','','','2012-05-17','2012-05-17',NULL,7,0,NULL,'ELECTRONIC','ITEM','',NULL,'','','ACTIVE',NULL),
 (19,'Title','sdf','','',NULL,'','','',NULL,'2012-05-18','2012-05-18',NULL,7,2,NULL,'MONOGRAPH','ITEM',NULL,NULL,'','','ACTIVE','');
//#STATEMENT
INSERT INTO `libraries` (`library_id`,`name`,`nickname`,`ils_prefix`,`reserve_desk`,`url`,`contact_email`,`monograph_library_id`,`multimedia_library_id`,`copyright_library_id`) VALUES 
 (1,'Main Library','MAIN','MAIN','Circulation Desk','your.library.com','noreply@your.library.com',1,1,NULL),
 (2,'Branch Library','BRANCH','BRANCH','Branch Circulation','branch.your.library.com','branch-noreply@your.library.com',1,1,NULL);
//#STATEMENT
INSERT INTO `mimetype_extensions` (`id`,`mimetype_id`,`file_extension`) VALUES 
 (1,1,'pdf'),
 (2,2,'ram'),
 (3,3,'mov'),
 (4,4,'doc'),
 (5,5,'xcl'),
 (6,6,'ppt'),
 (7,7,''),
 (8,4,'docx'),
 (9,5,'xlsx'),
 (10,6,'pptx'),
 (11,6,'ppsx'),
 (12,7,'html'),
 (13,7,'htm'),
 (14,7,'xhtml'),
 (15,8,'gif');
//#STATEMENT
INSERT INTO `mimetypes` (`mimetype_id`,`mimetype`,`helper_app_url`,`helper_app_name`,`helper_app_icon`) VALUES 
 (1,'application/pdf','http://www.adobe.com/products/acrobat/readstep2.html','Adobe Acrobat Reader','public/images/doc_type_icons/doctype-pdf.gif'),
 (2,'audio/x-pn-realaudio','http://www.real.com/','RealPlayer','public/images/doc_type_icons/doctype-sound.gif'),
 (3,'video/quicktime','http://www.apple.com/quicktime/','Quicktime Player','images/doc_type_icons/doctype-movie.gif'),
 (4,'application/msword','http://office.microsoft.com/Assistance/9798/viewerscvt.aspx','Microsoft Word','public/images/doc_type_icons/doctype-text.gif'),
 (5,'application/vnd.ms-excel','http://office.microsoft.com/Assistance/9798/viewerscvt.aspx','Microsoft Excel','public/images/doc_type_icons/doctype-text.gif'),
 (6,'application/vnd.ms-powerpoint','http://office.microsoft.com/Assistance/9798/viewerscvt.aspx','Microsoft Powerpoint','public/images/doc_type_icons/doctype-text.gif'),
 (7,'text/html',NULL,'Link','public/images/doc_type_icons/doctype-link.gif'),
 (8,'image/gif',NULL,'Image','public/images/doc_type_icons/doctype-image.gif');
//#STATEMENT
INSERT INTO `news` (`news_id`,`news_text`,`font_class`,`permission_level`,`begin_time`,`end_time`,`sort_order`) VALUES 
 (1,'asdfasdf','notice',NULL,'2012-05-15 03:18:00','1969-12-31 19:00:00',1);
//#STATEMENT
INSERT INTO `notes` (`note_id`,`type`,`target_id`,`note`,`target_table`) VALUES 
 (1,'Instructor',1,'derp','requests');
//#STATEMENT
INSERT INTO `permissions_levels` (`permission_id`,`label`) VALUES 
 (0,'student'),
 (1,'custodian'),
 (2,'proxy'),
 (3,'instructor'),
 (4,'staff'),
 (5,'admin');
//#STATEMENT
INSERT INTO `reports` VALUES  (1,'Electronic Items Added By Role','term','SELECT concat( t.term_name, \' \', t.term_year ) AS \'Term\', pl.label AS \'Role\', count( distinct eia.item_id ) AS \'Items Added\'\r\nFROM electronic_item_audit AS eia\r\nJOIN items AS i ON i.item_id = eia.item_id\r\nJOIN reserves AS r ON r.item_id = eia.item_id\r\nJOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\r\nJOIN terms AS t ON ci.term = t.term_name\r\nAND ci.year = t.term_year\r\nJOIN users AS u ON eia.added_by = u.user_id\r\nJOIN permissions_levels AS pl ON u.dflt_permission_level = pl.permission_id\r\nWHERE t.term_id IN(?) AND (i.item_group = \'ELECTRONIC\' OR i.item_group = \'VIDEO\') \r\nGROUP BY Term, Role','term_id',4,0,0,0),
 (2,'Totals: Items And Reserves','term','SELECT \r\n	i.item_group AS \'Item Type\',\r\n	COUNT(DISTINCT i.item_id) AS \'Total Items\',\r\n	COUNT(DISTINCT r.reserve_id) AS \'Total Reserves\'\r\nFROM reserves AS r\r\n	JOIN items AS i ON i.item_id = r.item_id\nWHERE i.item_group<>\'\'\r\nGROUP BY i.item_group',NULL,4,0,1,6),
 (3,'Totals: Courses',NULL,'SELECT\r\n	COUNT(DISTINCT primary_course_alias_id) AS \'Total Number of Courses\'\r\nFROM course_instances',NULL,4,0,1,6),
 (4,'Totals: Users',NULL,'SELECT\r\n	pl.label AS \'User Role\',\r\n	COUNT(DISTINCT u.user_id) AS \'User Count\'\r\nFROM users AS u \r\n	JOIN permissions_levels AS pl ON pl.permission_id = u.dflt_permission_level\r\nGROUP BY pl.label',NULL,4,0,1,6),
 (6,'Global: Courses And Users','term_lib','SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\r\n	l.name AS Library,\r\n	d.name AS Department,\r\n	COUNT(DISTINCT ci.course_instance_id) AS Courses,\r\n	COUNT(DISTINCT a_i.user_id) AS Instructors,\r\n	COUNT(DISTINCT a_p.user_id) AS Proxies,\r\n	COUNT(DISTINCT a_s.user_id) AS Students\r\nFROM terms AS t\r\n	JOIN course_instances AS ci ON (ci.term = t.term_name AND ci.year = t.term_year)\r\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\r\n	LEFT JOIN access AS a_i ON a_i.alias_id = ca.course_alias_id AND a_i.permission_level = 3\r\n	LEFT JOIN access AS a_p ON a_p.alias_id = ca.course_alias_id AND a_p.permission_level = 2\r\n	LEFT JOIN access AS a_s ON a_s.alias_id = ca.course_alias_id AND a_s.permission_level = 0\r\n	JOIN courses AS c ON ca.course_id = c.course_id\r\n	JOIN departments AS d ON d.department_id = c.department_id\r\n	JOIN libraries AS l ON l.library_id = d.library_id\r\nWHERE t.term_id IN(?)\r\n	AND l.library_id IN(?)\r\n	AND d.status IS NULL	\r\nGROUP BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.library_id,\r\n	d.department_id\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.name,\r\n	d.name	','term_id,library_id',4,1,1,6),
 (7,'Global: Items And Reserves','term_lib','SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\r\n	l.name AS Library,\r\n	d.name AS Department,\r\n	i.item_group AS \'Item Type\',\r\n	COUNT(DISTINCT r.item_id) AS \'Utilized Items\',\r\n	COUNT(DISTINCT r.reserve_id) AS \'Available Reserves\',\r\n	COUNT(DISTINCT uvl.reserve_id) AS \'Opened Reserves\'\r\nFROM terms AS t\r\n	JOIN course_instances AS ci ON (ci.term = t.term_name AND ci.year = t.term_year)\r\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\r\n	JOIN items AS i ON i.item_id = r.item_id\r\n	LEFT JOIN user_view_log AS uvl ON uvl.reserve_id = r.reserve_id\r\n	\r\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\r\n	JOIN courses AS c ON c.course_id = ca.course_id\r\n	JOIN departments AS d ON d.department_id = c.department_id\r\n	JOIN libraries AS l ON l.library_id = d.library_id\r\nWHERE t.term_id IN(?)\r\n	AND l.library_id IN(?)\r\n	AND d.status IS NULL\r\nGROUP BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.library_id,\r\n	d.department_id,\r\n	i.item_group\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.name,\r\n	d.name,\r\n	i.item_group','term_id,library_id',4,1,1,6),
 (8,'Global: Upload Activity','term_dates_usertype','SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\n	pl.label AS Role,	\r\n	CONCAT(u.last_name, \', \', u.first_name) AS User,\r\n	COUNT(DISTINCT aud.item_id) AS \'Items Added\'\n	FROM electronic_item_audit AS aud\n	JOIN reserves AS r ON r.item_id = aud.item_id\nJOIN items AS i ON i.item_id = aud.item_id\n	JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\n	JOIN terms AS t ON t.term_name = ci.term AND t.term_year = ci.year\r\n	JOIN users AS u ON u.user_id = aud.added_by\r\n	JOIN permissions_levels AS pl ON pl.permission_id = u.dflt_permission_level\r\nWHERE (aud.date_added BETWEEN ? AND ?) AND pl.permission_id IN(?) AND ((i.item_group = \'ELECTRONIC\' AND TRIM(i.url) <> \'\' AND i.url NOT LIKE \'%http://%\' AND i.url NOT LIKE \'%https://%\' AND i.url NOT LIKE \'%ftp://%\' ) OR i.item_group = \'VIDEO\') \r\n GROUP BY t.term_year,\r\n	t.term_name,\r\n	u.user_id\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\n	pl.label,\n	`Items Added` DESC','begin_date,end_date,permission_id',4,0,0,0),
 (9,'Item View Log for a class by date','class','SELECT\r\n	i.title as \'Title\',\r\n	DATE(uvl.timestamp_viewed) AS \'Date\',\r\n	COUNT(uvl.user_id) as \'Total Hits\',\r\n	COUNT(DISTINCT uvl.user_id) as \'Unique Hits\'\r\nFROM course_instances AS ci\r\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\r\n	JOIN items AS i ON i.item_id = r.item_id\r\n	JOIN user_view_log AS uvl ON uvl.reserve_id = r.reserve_id\r\nWHERE ci.course_instance_id = ?\r\nGROUP BY r.reserve_id, \'Date\'\r\nORDER BY Title, \'Date\'','ci',4,2,0,0),
 (10,'Courses With Active Reserves','term','SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nu.last_name, u.first_name, \n(\n  SELECT COUNT(*) FROM reserves AS r WHERE r.course_instance_id = ci.course_instance_id\n) AS \'Number of Reserves\',\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\nJOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\nJOIN courses AS c ON c.course_id = ca.course_id\nLEFT JOIN departments AS d ON c.department_id = d.department_id\nLEFT JOIN access AS a ON a.alias_id = ca.course_alias_id AND a.permission_level = 3\r\nLEFT JOIN users AS u ON a.user_id = u.user_id\r\nJOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?)\r\n  AND EXISTS (\r\n    SELECT course_instance_id\r\n    FROM reserves WHERE course_instance_id = ci.course_instance_id\r\n  )\nGROUP BY ca.course_alias_id\nORDER BY t.term_year ASC, t.term_name ASC, `Number of Reserves` DESC','term_id',4,0,0,0),
 (11,'New Items','term_dates','SELECT\r\n  IF(c.uniform_title IS NOT NULL AND c.uniform_title <> \'\',\n    CONCAT(d.abbreviation, \' \', c.course_number, \' - \',  c.uniform_title),\n    CONCAT(d.abbreviation, \' \', c.course_number, \' - \',  ca.course_name)\n  ) AS \'Course\',\r\n  ci.status AS \'Course Status\',\r\n  CONCAT(i.item_group, \' (\', me.file_extension, \')\') AS \'Item Type\',\r\n  i.title AS \'Document Title\',\r\n  i.volume_title AS \'Work Title\',\r\n  i.ISBN,\r\n  i.OCLC,\r\n  i.ISSN,\r\n  i.pages_times AS \'Pages/Times\',\r\n  u2.username AS \'Added By\',\n  pl.label AS \'Role\',\r\n  i.creation_date AS \'Date Created\',\r\n  IF (i.private_user_id IS NOT NULL,\n    CONCAT(u.first_name, \' \', u.last_name),\n    \'n/a\'\n  ) AS \'Private Owner\',\r\n  r.reserve_id AS \'Reserve ID\',\r\n  i.item_id AS \'Item ID\'\r\nFROM items AS i\r\n  JOIN reserves AS r ON r.item_id = i.item_id\r\n  JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\r\n  JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\r\n  JOIN courses AS c ON c.course_id = ca.course_id\r\n  JOIN departments AS d ON d.department_id = c.department_id\r\n  LEFT JOIN mimetypes AS m ON m.mimetype_id = i.mimetype\n  LEFT JOIN (\n    SELECT mimetype_id, GROUP_CONCAT(file_extension SEPARATOR \', \') AS file_extension\n    FROM mimetype_extensions mecon\n    WHERE \'\' <> file_extension\n    GROUP BY mimetype_id\n  )AS me ON me.mimetype_id = m.mimetype_id\n  LEFT JOIN users AS u ON u.user_id = i.private_user_id\r\n  LEFT JOIN electronic_item_audit AS eia ON eia.item_id = i.item_id\r\n  LEFT JOIN users AS u2 ON u2.user_id = eia.added_by\r\n  LEFT JOIN permissions_levels AS pl ON pl.permission_id = u2.dflt_permission_level\r\nWHERE r.activation_date BETWEEN ? AND ?\r\n  AND r.reserve_id = (\r\n    SELECT MIN(r2.reserve_id)\r\n    FROM reserves AS r2\r\n    WHERE r2.item_id = r.item_id\r\n  )\r\nORDER BY Course ASC','begin_date,end_date',4,0,0,0),
 (12,'Re-activated Items','term_dates','SELECT \n  IF(c.uniform_title IS NOT NULL AND c.uniform_title <> \'\',\n    CONCAT(d.abbreviation, \' \', c.course_number, \' - \',  c.uniform_title),\n    CONCAT(d.abbreviation, \' \', c.course_number, \' - \',  ca.course_name)\n  ) AS \'Course\', \n  ci.status AS \'Course Status\', \n  CONCAT(i.item_group, \' (\', me.file_extension, \')\') AS \'Item Type\', \n  i.title AS \'Document Title\', \n  i.volume_title AS \'Work Title\', \n  i.ISBN, \n  i.OCLC, \n  i.ISSN, \n  i.pages_times AS \'Pages/Times\', \n  pl.label AS \'Added By\', \n  i.creation_date AS \'Date Created\', \n  IF (i.private_user_id IS NOT NULL,\n    CONCAT(u.first_name, \' \', u.last_name),\n    \'n/a\'\n  ) AS \'Private Owner\', \n  r.reserve_id AS \'Reserve ID\', \n  i.item_id AS \'Item ID\' \n  FROM items AS i \n  JOIN reserves AS r ON r.item_id = i.item_id \n  JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id \n  JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id \n  JOIN courses AS c ON c.course_id = ca.course_id \n  JOIN departments AS d ON d.department_id = c.department_id \n  LEFT JOIN mimetypes AS m ON m.mimetype_id = i.mimetype \n  LEFT JOIN (\n    SELECT mimetype_id, GROUP_CONCAT(file_extension SEPARATOR \', \') AS file_extension\n    FROM mimetype_extensions mecon\n    WHERE \'\' <> file_extension\n    GROUP BY mimetype_id\n  )AS me ON me.mimetype_id = m.mimetype_id\n  LEFT JOIN users AS u ON u.user_id = i.private_user_id \n  LEFT JOIN electronic_item_audit AS eia ON eia.item_id = i.item_id \n  LEFT JOIN users AS u2 ON u2.user_id = eia.added_by \n  LEFT JOIN permissions_levels AS pl ON pl.permission_id = u2.dflt_permission_level \n  WHERE r.activation_date BETWEEN ? AND ? \n    AND r.reserve_id <> ( \n      SELECT MIN(r2.reserve_id) \n      FROM reserves AS r2 \n      WHERE r2.item_id = r.item_id\n    )\n  ORDER BY Course ASC','begin_date,end_date',4,0,0,0),
 (13,'Item View Log','class','SELECT\r\n	i.title AS \'Title\',\r\n	COUNT(uvl.user_id) as \'Total Views\',\r\n	COUNT(DISTINCT uvl.user_id) as \'Total Students\'\r\nFROM course_instances AS ci\r\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\r\n	JOIN items AS i ON i.item_id = r.item_id\r\n	LEFT JOIN user_view_log AS uvl ON uvl.reserve_id = r.reserve_id\r\nWHERE ci.course_instance_id = ? and i.item_type = \"ITEM\"\r\nGROUP BY r.reserve_id\r\nORDER BY i.title ASC','ci',3,3,0,0),
 (14,'Privately-owned Items','term','SELECT\n	CONCAT(t.term_name, \' \', t.term_year) AS \'Term\',\n	d.abbreviation AS \'DEPT\',\n	c.course_number AS \'NUM\',\n	ca.section AS \'SECTION\',\n	i.title AS \'Document Title\',\n	i.volume_title AS \'Work Title\',\n	i.ISBN,\n	i.OCLC,\n	i.ISSN,\n	CASE WHEN i.mimetype REGEXP(\'[0-9]\') THEN CONCAT(i.item_group, \' (\', mt.file_extension, \')\') ELSE CONCAT(i.item_group, \' (\', i.mimetype, \')\') END AS \'Item Type\',\n	CONCAT(u.first_name, \' \', u.last_name) AS \'Private Owner\',\n	r.reserve_id AS \'Reserve ID\',\n	i.item_id AS \'Item ID\',\n	ci.course_instance_id AS \'COURSE ID\'\nFROM course_instances AS ci\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\n	JOIN items AS i ON i.item_id = r.item_id\n	LEFT JOIN user_view_log AS uvl ON uvl.reserve_id = r.reserve_id\n	LEFT JOIN mimetypes AS m ON m.mimetype_id = i.mimetype\n	LEFT JOIN mimetype_extensions AS mt ON mt.mimetype_id = m.mimetype_id\n	LEFT JOIN users AS u ON u.user_id = i.private_user_id\n	JOIN terms AS t ON ci.year = t.term_year AND ci.term = t.term_name\n	JOIN course_aliases as ca ON ci.primary_course_alias_id = ca.course_alias_id\n	JOIN courses as c ON c.course_id = ca.course_id\n	LEFT JOIN departments as d ON d.department_id = c.department_id\nWHERE t.term_id IN(?) AND i.private_user_id IS NOT NULL\nGROUP BY r.reserve_id\nORDER BY t.term_year, t.term_name, i.title ASC','term_id',4,0,0,0),
 (15,'Daily Requests By Library','term_lib','\nSELECT l.nickname AS \'Library\',\n	concat(t.term_name, \' \', t.term_year)AS \'Term\',\n	r.date_created AS \'Requested On\', \n	count( i.item_id ) AS \'Num Items Requested\', \n	SUM(CASE i.item_group WHEN \'MONOGRAPH\' THEN 1 ELSE 0 END) AS \'Monograph\',\n	SUM(CASE i.item_group WHEN \'MULTIMEDIA\' THEN 1 ELSE 0 END) AS \'Multimedia\'\nFROM course_instances AS ci\n	JOIN terms AS t ON ( ci.term = t.term_name AND ci.year = t.term_year )\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\n	JOIN courses AS c ON ca.course_id = c.course_id\n	JOIN departments AS d ON d.department_id = c.department_id\n	JOIN libraries AS l ON d.library_id = l.library_id\n	JOIN reserves AS r ON ci.course_instance_id = r.course_instance_id\n	JOIN items AS i ON r.item_id = i.item_id\nWHERE i.item_group <> \'ELECTRONIC\'\n	AND i.item_group <> \'VIDEO\'\n	AND t.term_id IN(?)\n	AND l.library_id IN(?)\n	AND d.status IS NULL AND i.item_type = \'ITEM\'	\nGROUP BY l.nickname, r.date_created\nORDER BY l.nickname, r.date_created desc','term_id, library_id',4,0,1,6),
 (17,'Classes Needing Review','term','SELECT DISTINCT CONCAT(ci.term, \' \',ci.year) AS `Term`, \n	CONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\n	u.last_name, u.first_name,\n	concat( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\n	JOIN courses AS c ON c.course_id = ca.course_id\n	LEFT JOIN departments AS d ON c.department_id = d.department_id\n	LEFT JOIN access AS a ON ca.course_alias_id = a.alias_id\r\n		AND a.permission_level = 3\r\n	LEFT JOIN users AS u ON a.user_id = u.user_id\r\n	JOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?)\r\n	AND ci.reviewed_date IS NULL\r\n	AND EXISTS (\r\n		SELECT course_instance_id\r\n		FROM reserves WHERE course_instance_id = ci.course_instance_id\n	)\nORDER BY t.term_year DESC, t.term_name, d.abbreviation, c.course_number, ca.section, u.last_name','term_id',4,17,1,6),
 (19,'Totals: Items and Reserves by Term & Library','term_lib','SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\r\n	l.name AS Library,\r\n	i.item_group AS \'Item Type\',\r\n	COUNT(DISTINCT r.item_id) AS \'Total Items\',\r\n	COUNT(DISTINCT r.reserve_id) AS \'Total Reserves\'\r\nFROM terms AS t\r\n	JOIN course_instances AS ci ON (ci.term = t.term_name AND ci.year = t.term_year)\r\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\r\n	JOIN items AS i ON i.item_id = r.item_id\r\n	LEFT JOIN libraries AS l ON l.library_id = i.home_library\r\nWHERE t.term_id IN(?)\r\n	AND l.library_id IN(?)\n	AND i.item_type <> \'heading\'\n	AND i.item_group <> \'\'\r\nGROUP BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.library_id,\r\n	i.item_group\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.name,\r\n	i.item_group','term_id,library_id',4,0,1,6),
 (20,'Annual Electronic Views','fyear_dates','SELECT count(*) AS `Electronic Reserves Viewed`, \nstart_date AS `Start Date`, \nend_date AS `End Date` \nFROM user_view_log u \nJOIN (SELECT \'?\' AS start_date) as s\nJOIN (SELECT \'?\' AS end_date) as e\nWHERE timestamp_viewed >=  start_date  AND timestamp_viewed <= end_date','begin_date,end_date',4,0,0,0),
 (21,'Annual PDF Views','fyear_dates','SELECT \ncount(*) AS `Electronic Reserves Viewed`, \nstart_date AS `Start Date`, \nend_date AS `End Date` \nFROM user_view_log u \nJOIN (SELECT \'?\' AS start_date) as s \nJOIN (SELECT \'?\' AS end_date) as e \nJOIN reserves AS r ON r.reserve_id = u.reserve_id\nJOIN items AS i ON r.item_id = i.item_id\nWHERE timestamp_viewed >= start_date \n  AND timestamp_viewed <= end_date\n  AND (i.mimetype = 1 OR (i.url LIKE \'%.pdf\' AND i.url NOT LIKE \'http:%\'))','begin_date,end_date',4,0,0,0),
 (22,'Manually Created Courses','term','SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nci.status AS `Status`,\nci.enrollment AS `Enrollment`,\n(SELECT CONCAT(COUNT(*), IF(ci.primary_course_alias_id = ca.course_alias_id, \'\', \' (aliased)\')) FROM access AS a WHERE a.permission_level = 0 AND a.alias_id = ci.primary_course_alias_id) AS `# of Students` ,\nCONCAT(ur.first_name, \' \', ur.last_name, \' (\', plr.label ,\')\') AS `Reviewed By`,\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', \n  ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\nFROM course_instances AS ci\nJOIN course_aliases AS ca ON ca.course_instance_id = ci.course_instance_id\nJOIN courses AS c ON c.course_id = ca.course_id\nJOIN departments AS d ON c.department_id = d.department_id\nJOIN terms AS t ON t.term_id IN(?) AND t.term_name = ci.term AND t.term_year = ci.year\nLEFT JOIN users AS ur ON ur.user_id  = ci.reviewed_by\nLEFT JOIN permissions_levels AS plr ON ur.dflt_permission_level = plr.permission_id\nWHERE ca.registrar_key IS NULL','term_id',4,0,0,0),
 (23,'Courses With Active Reserves, by Type','term_itemgroup','SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\ni.item_group AS `Type`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nu.last_name, u.first_name, \nCOUNT(r.reserve_id) AS \'Number of Reserves\',\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\nJOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\nJOIN courses AS c ON c.course_id = ca.course_id\nJOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\nJOIN items AS i ON r.item_id = i.item_id\nLEFT JOIN departments AS d ON c.department_id = d.department_id\nLEFT JOIN access AS a ON a.alias_id = ca.course_alias_id AND a.permission_level = 3\r\nLEFT JOIN users AS u ON a.user_id = u.user_id\r\nJOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?) AND i.item_group IN(?)\r\n  AND EXISTS (\r\n    SELECT course_instance_id\r\n    FROM reserves WHERE course_instance_id = ci.course_instance_id\r\n  )\nGROUP BY i.item_group,ca.course_alias_id\nORDER BY t.term_year ASC, t.term_name ASC, `Course` ASC','term_id,item_group',4,0,0,0);
//#STATEMENT
INSERT INTO `requests` (`request_id`,`reserve_id`,`item_id`,`user_id`,`date_requested`,`date_processed`,`date_desired`,`priority`,`course_instance_id`,`max_enrollment`,`type`) VALUES 
 (1,7,19,3,'2012-05-18',NULL,'2012-06-01',NULL,3,NULL,'PHYSICAL');
//#STATEMENT
INSERT INTO `reserves` (`reserve_id`,`course_instance_id`,`item_id`,`activation_date`,`expiration`,`status`,`sort_order`,`date_created`,`last_modified`,`requested_loan_period`,`parent_id`) VALUES 
 (1,5,1,'2012-04-14','2012-08-01','ACTIVE',3,'2012-05-17','2012-05-17',NULL,NULL),
 (2,5,2,'2012-04-14','2012-08-01','ACTIVE',5,'2012-05-17','2012-05-17',NULL,NULL),
 (3,2,2,'2012-04-14','2012-08-01','ACTIVE',1,'2012-05-17','2012-05-17',NULL,NULL),
 (4,5,3,'2012-04-14','2012-08-01','ACTIVE',1,'2012-05-17','2012-05-17',NULL,NULL),
 (5,5,4,'2012-04-14','2012-08-01','ACTIVE',2,'2012-05-17','2012-05-17',NULL,NULL),
 (6,5,6,'2012-04-14','2012-08-01','ACTIVE',4,'2012-05-17','2012-05-17',NULL,NULL),
 (7,3,19,'2012-04-14','2012-08-01','IN PROCESS',1,'2012-05-18','2012-05-18','1 Day',NULL);
//#STATEMENT
INSERT INTO `skins` (`id`,`skin_name`,`skin_stylesheet`,`default_selected`) VALUES 
 (1,'general','ReservesStyles.css','yes'),
 (2,'ncsu','ReservesStyles-ncsu.css','no'),
 (3,'','','no');
//#STATEMENT
INSERT INTO `special_users` (`user_id`,`password`,`expiration`) VALUES 
 (1,'a152e841783914146e4bcd4f39100686',NULL), -- password "asdfgh"
 (2,'0dfc4a2a56999a5cff58314eaef63896',NULL),
 (3,'fe778d00fd93e5df4b9576eab69e1694',NULL);
//#STATEMENT
INSERT INTO `special_users_audit` (`user_id`,`creator_user_id`,`date_created`,`email_sent_to`) VALUES 
 (1,1,'2012-05-17 15:29:26',NULL),
 (2,1,'2012-05-17 15:38:59',NULL),
 (2,1,'2012-05-17 15:38:59',NULL),
 (3,1,'2012-05-17 15:46:12',NULL),
 (3,1,'2012-05-17 15:46:12',NULL);
//#STATEMENT
INSERT INTO `staff_libraries` (`staff_library_id`,`user_id`,`library_id`,`permission_level_id`) VALUES 
 (1,1,1,5);
//#STATEMENT
INSERT INTO `target_table_mapping` (`id`,`table_name`,`display_name`) VALUES 
 (1,'users','instructor'),
 (2,'users','proxy'),
 (3,'users','student'),
 (4,'reserves','reserve'),
 (5,'reserves','heading'),
 (6,'course_aliases','cross-listing'),
 (7,'course_instances','course_instance'),
 (8,'course_aliases','course_alias');
//#STATEMENT
INSERT INTO `terms` (`term_id`,`sort_order`,`term_name`,`term_year`,`begin_date`,`end_date`) VALUES 
 (1,1,'Fall','2009','2009-08-26','2009-12-18'),
 (2,2,'Spring','2010','2010-01-01','2010-05-31'),
 (3,3,'Summer','2010','2010-05-15','2010-08-16'),
 (4,4,'Summer','2012','2012-05-14','2012-08-01'),
 (5,5,'Fall','2012','2012-08-08','2012-12-01'),
 (6,6,'Spring','2013','2013-01-08','2013-05-22');
//#STATEMENT
INSERT INTO `user_view_log` (`id`,`user_id`,`reserve_id`,`timestamp_viewed`) VALUES 
 (1,2,5,'2012-05-18 09:05:44'),
 (2,2,5,'2012-05-18 09:05:46');
//#STATEMENT
INSERT INTO `users` (`user_id`,`username`,`first_name`,`last_name`,`email`,`external_user_key`,`dflt_permission_level`,`last_login`,`old_id`,`old_user_id`,`copyright_accepted`) VALUES 
 (1,'admin','Admin','Admin','noreply@your.library.com',NULL,5,'2012-05-18',NULL,NULL,0),
 (2,'tstudent','Test','Student','noreply@spam.spam.spam',NULL,0,'2012-05-18',NULL,NULL,0),
 (3,'tinstructor','Test','Instructor','tinstructor@spam.spam.spam',NULL,3,'2012-05-18',NULL,NULL,1);
 
 