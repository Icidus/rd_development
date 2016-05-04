-- BEFORE RUNNING THIS SCRIPT, PLEASE VERIFY THE RECORD IDS FOR 
-- REPLACE AND DELETE STATEMENTS MATCH THE IDS IN YOUR DB!

ALTER TABLE reports
  MODIFY COLUMN `param_group` 
  SET('term','department','class','term_lib','term_dates','term_usertype','term_dates_usertype','fyear_dates','term_itemgroup')  
  CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL;
//#STATEMENT

REPLACE INTO reports VALUES (
  1,
  'Electronic Items Added By Role',
  'term',
  'SELECT concat( t.term_name, \' \', t.term_year ) AS \'Term\', pl.label AS \'Role\', count( distinct eia.item_id ) AS \'Items Added\'\r\nFROM electronic_item_audit AS eia\r\nJOIN items AS i ON i.item_id = eia.item_id\r\nJOIN reserves AS r ON r.item_id = eia.item_id\r\nJOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\r\nJOIN terms AS t ON ci.term = t.term_name\r\nAND ci.year = t.term_year\r\nJOIN users AS u ON eia.added_by = u.user_id\r\nJOIN permissions_levels AS pl ON u.dflt_permission_level = pl.permission_id\r\nWHERE t.term_id IN(?) AND (i.item_group = \'ELECTRONIC\' OR i.item_group = \'VIDEO\') \r\nGROUP BY Term, Role',
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
  'term_dates_usertype',
  'SELECT\r\n	CONCAT(t.term_name, \' \', t.term_year) as Term,\n	pl.label AS Role,	\r\n	CONCAT(u.last_name, \', \', u.first_name) AS User,\r\n	COUNT(DISTINCT aud.item_id) AS \'Items Added\'\n	FROM electronic_item_audit AS aud\n	JOIN reserves AS r ON r.item_id = aud.item_id\nJOIN items AS i ON i.item_id = aud.item_id\n	JOIN course_instances AS ci ON ci.course_instance_id = r.course_instance_id\n	JOIN terms AS t ON t.term_name = ci.term AND t.term_year = ci.year\r\n	JOIN users AS u ON u.user_id = aud.added_by\r\n	JOIN permissions_levels AS pl ON pl.permission_id = u.dflt_permission_level\r\nWHERE (aud.date_added BETWEEN ? AND ?) AND pl.permission_id IN(?) AND ((i.item_group = \'ELECTRONIC\' AND TRIM(i.url) <> \'\' AND i.url NOT LIKE \'%http://%\' AND i.url NOT LIKE \'%https://%\' AND i.url NOT LIKE \'%ftp://%\' ) OR i.item_group = \'VIDEO\') \r\n GROUP BY t.term_year,\r\n	t.term_name,\r\n	u.user_id\r\nORDER BY\r\n	t.term_year,\r\n	t.term_name,\n	pl.label,\n	`Items Added` DESC',
  'begin_date,end_date,permission_id',
  4,
  0,
  0,
  0
);
