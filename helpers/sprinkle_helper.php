<?php

/**
* isURL
* @access          	public
* @param           	string to be checked
* @return   boolean	Returns TRUE/FALSE
*/

function is_url($string = '')
{
	$pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
	return preg_match($pattern, $string);
}