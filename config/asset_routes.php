<?php  if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Sprinkle - Asset management library
 * 
 * @author 		Edmundas Kondrašovas <as@edmundask.lt>
 * @license		http://www.opensource.org/licenses/MIT
 */

$config['asset_routes']['(:any)'] = array
(
	'assets' 		=>	array('jquery'),
	'exclude'		=>	array(),
	'replace'		=>	array()
);