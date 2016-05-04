<?php
$query =
	strpos($_SERVER['REQUEST_URI'], '?') !== false
	? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'],'?'))
	: '';
header("HTTP/1.0 301 Moved Permanently");
header("Location:../{$query}");