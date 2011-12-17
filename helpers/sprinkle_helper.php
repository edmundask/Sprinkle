<?php

/**
* Check if given string is a URL
*
* @access	public
* @param 	string to be checked
* @return	boolean	Returns TRUE/FALSE
*/

if(!function_exists('is_url'))
{
	function is_url($string = '')
	{
		$pattern = '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@';
		return preg_match($pattern, $string);
	}
}

/* End of file sprinkle_helper.php */
/* Location: Location: ./sparks/sprinkle/1.0.0/helpers/sprinkle_helper.php */