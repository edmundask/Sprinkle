<?php  if(!defined('BASEPATH')) exit('No direct script access allowed');

$config['assets']['jquery'] = array
(
	'type'    			=>	'js',
	'combine' 			=>	false,
	'minify'  			=>	true,
	'versions'			=>	array
	(
		'default'		=>	'http://ajax.googleapis.com/ajax/libs/jquery/jquery-1.6.4.js',
		'1.5.1'  		=>	'http://ajax.googleapis.com/ajax/libs/jquery/jquery-1.5.1.js',
		'1.7.1'  		=>	'http://ajax.googleapis.com/ajax/libs/jquery/jquery-1.7.1.js'
	)
);

$config['assets']['js-libs'] = array
(
	'type'  		=>	'group',
	'assets'		=>	array('jquery')
);