-- BEFORE RUNNING THIS SCRIPT, PLEASE VERIFY THE RECORD IDS FOR 
-- REPLACE AND DELETE STATEMENTS MATCH THE IDS IN YOUR DB!

REPLACE INTO reports VALUES (
	17,
	'Classes Needing Review',
	'term',
	'SELECT DISTINCT CONCAT(ci.term, \' \',ci.year) AS `Term`, \n	CONCAT(d.abbreviation, \' \', c.course_number, \' (\',ca.section, \')\') AS `Course`,\n	u.last_name, u.first_name,\n	concat( \'<a href=\"index.php%3Fcmd%3DeditClass%26ci%3D\', ci.course_instance_id, \'\" target=\"new\">edit class</a>\' ) AS link\r\nFROM course_instances AS ci\r\n	JOIN course_aliases AS ca ON ca.course_alias_id = ci.primary_course_alias_id\n	JOIN courses AS c ON c.course_id = ca.course_id\n	LEFT JOIN departments AS d ON c.department_id = d.department_id\n	LEFT JOIN access AS a ON ca.course_alias_id = a.alias_id\r\n		AND a.permission_level = 3\r\n	LEFT JOIN users AS u ON a.user_id = u.user_id\r\n	JOIN terms AS t on ci.term = t.term_name AND ci.year = t.term_year\r\nWHERE t.term_id IN(?)\r\n	AND ci.reviewed_date IS NULL\r\n	AND EXISTS (\r\n		SELECT course_instance_id\r\n		FROM reserves WHERE course_instance_id = ci.course_instance_id\n	)\nORDER BY t.term_year DESC, t.term_name, d.abbreviation, c.course_number, ca.section, u.last_name',
	'term_id',
	4,
	17,
	1,
	6
);
