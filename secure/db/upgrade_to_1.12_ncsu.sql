-- BEFORE RUNNING THIS SCRIPT, PLEASE VERIFY THE RECORD IDS FOR 
-- REPLACE AND DELETE STATEMENTS MATCH THE IDS IN YOUR DB!

ALTER TABLE reports
  MODIFY COLUMN `param_group` 
  SET('term','department','class','term_lib','term_dates','term_usertype','fyear_dates','term_itemgroup')  
  CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL;
//#STATEMENT
REPLACE INTO reports VALUES (
  1,
  'Electronic Items Added By Role',
  'term',
  'SELECT concat( t.term_name, \' \', t.term_year ) AS \'Term\', pl.label AS \'Role\', count( distinct eia.item_id ) AS \'Items Added\'\r\nFROM electronic_item_audit AS eia\r\nJOIN reserves AS r ON r.item_id = eia.item_id\r\nJOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\r\nJOIN terms AS t ON ci.term = t.term_name\r\nAND ci.year = t.term_year\r\nJOIN users AS u ON eia.added_by = u.user_id\r\nJOIN permissions_levels AS pl ON u.dflt_permission_level = pl.permission_id\r\nWHERE t.term_id IN(?)\r\nGROUP BY Term, Role',
  'term_id',
  4,
  0,
  0,
  0
);
//#STATEMENT
REPLACE INTO reports VALUES (2,'Totals: Items And Reserves','term','SELECT \r\n	i.item_group AS \'Item Type\',\r\n	COUNT(DISTINCT i.item_id) AS \'Total Items\',\r\n	COUNT(DISTINCT r.reserve_id) AS \'Total Reserves\'\r\nFROM reserves AS r\r\n	JOIN items AS i ON i.item_id = r.item_id\nWHERE i.item_group<>\'\'\r\nGROUP BY i.item_group',NULL,4,0,1,6);
//#STATEMENT
REPLACE INTO reports VALUES (
  10,
  'Courses With Active Reserves',
  'term',
  'SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nu.last_name, u.first_name, \n(\n  SELECT COUNT(*) FROM reserves AS r WHERE r.course_instance_id = ci.course_instance_id\n) AS \'Number of Reserves\',\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\nJOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\nJOIN courses AS c ON c.course_id = ca.course_id\nLEFT JOIN departments AS d ON c.department_id = d.department_id\nLEFT JOIN access AS a ON a.alias_id = ca.course_alias_id AND a.permission_level = 3\r\nLEFT JOIN users AS u ON a.user_id = u.user_id\r\nJOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?)\r\n  AND EXISTS (\r\n    SELECT course_instance_id\r\n    FROM reserves WHERE course_instance_id = ci.course_instance_id\r\n  )\nGROUP BY ca.course_alias_id\nORDER BY t.term_year ASC, t.term_name ASC, `Number of Reserves` DESC',
  'term_id',
  4,
  0,
  0,
  0
);
//#STATEMENT
REPLACE INTO reports VALUES (
  8,
  'Global: Upload Activity',
  'term_usertype',
  'SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\n	pl.label AS Role,	\r\n	CONCAT(u.last_name, \', \', u.first_name) AS User,\r\n	COUNT(DISTINCT aud.item_id) AS \'Items Added\'\n	FROM electronic_item_audit AS aud\n	JOIN reserves AS r ON r.item_id = aud.item_id\n	JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\n	JOIN terms AS t ON t.term_name = ci.term AND t.term_year = ci.year\r\n	JOIN users AS u ON u.user_id = aud.added_by\r\n	JOIN permissions_levels AS pl ON pl.permission_id = u.dflt_permission_level\r\nWHERE t.term_id IN(?) AND pl.permission_id IN(?)\r\nGROUP BY\r\n	t.term_year,\r\n	t.term_name,\r\n	u.user_id\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\n	pl.label,\n	`Items Added` DESC',
  'term_id,permission_id',
  4,
  0,
  0,
  0
);
//#STATEMENT
REPLACE INTO reports VALUES (
  14,
  'Privately-owned Items',
  'term',
  'SELECT\n	CONCAT(t.term_name, \' \', t.term_year) AS \'Term\',\n	d.abbreviation AS \'DEPT\',\n	c.course_number AS \'NUM\',\n	ca.section AS \'SECTION\',\n	i.title AS \'Document Title\',\n	i.volume_title AS \'Work Title\',\n	i.ISBN,\n	i.OCLC,\n	i.ISSN,\n	CASE WHEN i.mimetype REGEXP(\'[0-9]\') THEN CONCAT(i.item_group, \' (\', mt.file_extension, \')\') ELSE CONCAT(i.item_group, \' (\', i.mimetype, \')\') END AS \'Item Type\',\n	CONCAT(u.first_name, \' \', u.last_name) AS \'Private Owner\',\n	r.reserve_id AS \'Reserve ID\',\n	i.item_id AS \'Item ID\',\n	ci.course_instance_id AS \'COURSE ID\'\nFROM course_instances AS ci\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\n	JOIN items AS i ON i.item_id = r.item_id\n	LEFT JOIN user_view_log AS uvl ON uvl.reserve_id = r.reserve_id\n	LEFT JOIN mimetypes AS m ON m.mimetype_id = i.mimetype\n	LEFT JOIN mimetype_extensions AS mt ON mt.mimetype_id = m.mimetype_id\n	LEFT JOIN users AS u ON u.user_id = i.private_user_id\n	JOIN terms AS t ON ci.year = t.term_year AND ci.term = t.term_name\n	JOIN course_aliases as ca ON ci.primary_course_alias_id = ca.course_alias_id\n	JOIN courses as c ON c.course_id = ca.course_id\n	LEFT JOIN departments as d ON d.department_id = c.department_id\nWHERE t.term_id IN(?) AND i.private_user_id IS NOT NULL\nGROUP BY r.reserve_id\nORDER BY t.term_year, t.term_name, i.title ASC',
  'term_id',
  4,
  0,
  0,
  0
);
//#STATEMENT
REPLACE INTO reports VALUES (15,'Daily Requests By Library','term_lib','\nSELECT l.nickname AS \'Library\',\n	concat(t.term_name, \' \', t.term_year)AS \'Term\',\n	r.date_created AS \'Requested On\', \n	count( i.item_id ) AS \'Num Items Requested\', \n	SUM(CASE i.item_group WHEN \'MONOGRAPH\' THEN 1 ELSE 0 END) AS \'Monograph\',\n	SUM(CASE i.item_group WHEN \'MULTIMEDIA\' THEN 1 ELSE 0 END) AS \'Multimedia\'\nFROM course_instances AS ci\n	JOIN terms AS t ON ( ci.term = t.term_name AND ci.year = t.term_year )\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\n	JOIN courses AS c ON ca.course_id = c.course_id\n	JOIN departments AS d ON d.department_id = c.department_id\n	JOIN libraries AS l ON d.library_id = l.library_id\n	JOIN reserves AS r ON ci.course_instance_id = r.course_instance_id\n	JOIN items AS i ON r.item_id = i.item_id\nWHERE i.item_group <> \'ELECTRONIC\'\n	AND i.item_group <> \'VIDEO\'\n	AND t.term_id IN(?)\n	AND l.library_id IN(?)\n	AND d.status IS NULL AND i.item_type = \'ITEM\'	\nGROUP BY l.nickname, r.date_created\nORDER BY l.nickname, r.date_created desc','term_id, library_id',4,0,1,6);
//#STATEMENT
DELETE FROM reports WHERE report_id = 18;
//#STATEMENT
REPLACE INTO reports VALUES (19,'Totals: Items and Reserves by Term & Library','term_lib','SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\r\n	l.name AS Library,\r\n	i.item_group AS \'Item Type\',\r\n	COUNT(DISTINCT r.item_id) AS \'Total Items\',\r\n	COUNT(DISTINCT r.reserve_id) AS \'Total Reserves\'\r\nFROM terms AS t\r\n	JOIN course_instances AS ci ON (ci.term = t.term_name AND ci.year = t.term_year)\r\n	JOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\r\n	JOIN items AS i ON i.item_id = r.item_id\r\n	LEFT JOIN libraries AS l ON l.library_id = i.home_library\r\nWHERE t.term_id IN(?)\r\n	AND l.library_id IN(?)\n	AND i.item_type <> \'heading\'\n	AND i.item_group <> \'\'\r\nGROUP BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.library_id,\r\n	i.item_group\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\r\n	l.name,\r\n	i.item_group','term_id,library_id',4,0,1,6);
//#STATEMENT
INSERT  INTO reports
  (`title`, param_group, `sql`, parameters, min_permissions, sort_order, cached, cache_refresh_delay) 
VALUES (
  'Annual Electronic Views',
  'fyear_dates',
  'SELECT count(*) AS `Electronic Reserves Viewed`, \nstart_date AS `Start Date`, \nend_date AS `End Date` \nFROM user_view_log u \nJOIN (SELECT \'?\' AS start_date) as s\nJOIN (SELECT \'?\' AS end_date) as e\nWHERE timestamp_viewed >=  start_date  AND timestamp_viewed <= end_date',
  'begin_date,end_date',
  4,
  0,
  0,
  0
);
//#STATEMENT
INSERT  INTO reports
  (`title`, param_group, `sql`, parameters, min_permissions, sort_order, cached, cache_refresh_delay) 
VALUES (
  'Annual PDF Views',
  'fyear_dates',
  'SELECT \ncount(*) AS `Electronic Reserves Viewed`, \nstart_date AS `Start Date`, \nend_date AS `End Date` \nFROM user_view_log u \nJOIN (SELECT \'?\' AS start_date) as s \nJOIN (SELECT \'?\' AS end_date) as e \nJOIN reserves AS r ON r.reserve_id = u.reserve_id\nJOIN items AS i ON r.item_id = i.item_id\nWHERE timestamp_viewed >= start_date \n  AND timestamp_viewed <= end_date\n  AND (i.mimetype = 1 OR (i.url LIKE \'%.pdf\' AND i.url NOT LIKE \'http:%\'))',
  'begin_date,end_date',
  4,
  0,
  0,
  0
);
//#STATEMENT
INSERT  INTO reports
  (`title`, param_group, `sql`, parameters, min_permissions, sort_order, cached, cache_refresh_delay) 
VALUES (
  'Manually Created Courses',
  'term',
  'SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nci.status AS `Status`,\nci.enrollment AS `Enrollment`,\n(SELECT CONCAT(COUNT(*), IF(ci.primary_course_alias_id = ca.course_alias_id, \'\', \' (aliased)\')) FROM access AS a WHERE a.permission_level = 0 AND a.alias_id = ci.primary_course_alias_id) AS `# of Students` ,\nCONCAT(ur.first_name, \' \', ur.last_name, \' (\', plr.label ,\')\') AS `Reviewed By`,\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', \n  ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\nFROM course_instances AS ci\nJOIN course_aliases AS ca ON ca.course_instance_id = ci.course_instance_id\nJOIN courses AS c ON c.course_id = ca.course_id\nJOIN departments AS d ON c.department_id = d.department_id\nJOIN terms AS t ON t.term_id IN(?) AND t.term_name = ci.term AND t.term_year = ci.year\nLEFT JOIN users AS ur ON ur.user_id  = ci.reviewed_by\nLEFT JOIN permissions_levels AS plr ON ur.dflt_permission_level = plr.permission_id\nWHERE ca.registrar_key IS NULL',
  'term_id',
  4,
  0,
  0,
  0
);
//#STATEMENT
INSERT  INTO reports
  (`title`, param_group, `sql`, parameters, min_permissions, sort_order, cached, cache_refresh_delay) 
VALUES (
  'Courses With Active Reserves, by Type',
  'term_itemgroup',
  'SELECT \nCONCAT(ci.term, \' \',ci.year) AS `Term`,\ni.item_group AS `Type`,\nCONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\nu.last_name, u.first_name, \nCOUNT(r.reserve_id) AS \'Number of Reserves\',\nCONCAT( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\nJOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\nJOIN courses AS c ON c.course_id = ca.course_id\nJOIN reserves AS r ON r.course_instance_id = ci.course_instance_id\nJOIN items AS i ON r.item_id = i.item_id\nLEFT JOIN departments AS d ON c.department_id = d.department_id\nLEFT JOIN access AS a ON a.alias_id = ca.course_alias_id AND a.permission_level = 3\r\nLEFT JOIN users AS u ON a.user_id = u.user_id\r\nJOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?) AND i.item_group IN(?)\r\n  AND EXISTS (\r\n    SELECT course_instance_id\r\n    FROM reserves WHERE course_instance_id = ci.course_instance_id\r\n  )\nGROUP BY i.item_group,ca.course_alias_id\nORDER BY t.term_year ASC, t.term_name ASC, `Course` ASC',
  'term_id,item_group',
  4,
  0,
  0,
  0
);

