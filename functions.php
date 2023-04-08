<?php
function cutHtml($string, $begin, $end)
{
	$middle = explode($begin, $string);
	$result = explode($end, $middle[1]);
	return $result[0];
}

function cutStr($string, $begin, $end)
{
	$middle = explode($begin, $string);
	$result = explode($end, $middle[1]);
	return strip_tags(trim($result[0]));
}

function cleanStr($str)
{
	$str = str_replace("&nbsp;", " ", $str);
	$str = preg_replace('/\s+/', ' ', $str);
	$str = trim($str);
	return $str;
}

function getLastLog() {
	$log_path = __DIR__ . '/../../crawl_ophim_logs';
	$log_filename = 'log_' . date('d-m-Y') . '.log';
	$log_data = $log_path.'/'.$log_filename;
	return array(
		'log_filename' => $log_filename,
		'log_data' => file_get_contents($log_data)
	);
}
