<?php  if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Sprinkle - Asset management library
 * 
 * @author 		Edmundas Kondrašovas <as@edmundask.lt>
 * @license		http://www.opensource.org/licenses/MIT
 */

$config['assets']['jquery'] = array
(
	'type'    			=>	'js',
	'combine' 			=>	false,
	'minify'  			=>	false,
	'versions'			=>	array
	(
		'default'		=>	'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.js',
		'1.5.1'  		=>	'http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.js',
		'1.7.1'  		=>	'http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js'
	)
);

/*
|-------------------------------------------------------------------------
| Asset groups
|-------------------------------------------------------------------------
*/

$config['assets']['js-libs'] = array
(
	'type'  		=>	'group',
	'assets'		=>	array('jquery')
);