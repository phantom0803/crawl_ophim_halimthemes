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
