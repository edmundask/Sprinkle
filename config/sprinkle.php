<?php  if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Sprinkle - Asset management library
 *
 * Sprinkle is an asset management library for CodeIgniter which seeks to
 * simplify the process of loading assets on the page. The library includes
 * key features such as auto-loading, combining, minifying CSS or JS files, 
 * caching, compiling LESS into CSS and much more.
 * 
 * @package   			CodeIgniter
 * @subpackage			Sprinkle
 * @category  			Config
 * @author    			Edmundas Kondrašovas <as@edmundask.lt>
 * @license   			http://www.opensource.org/licenses/MIT
 * @version   			1.0b
 * @copyright 			Copyright (c) 2011 Edmundas Kondrašovas <as@edmundask.lt>
 */

/*
|--------------------------------------------------------------------------
| Asset locations
|--------------------------------------------------------------------------
|
| In some cases you may need to look for assets in multiple directories.
| To achieve this, you can set these locations here. Keep in mind that if
| asset with the same name (and extension) exists in both locations, 
| Sprinkle will use that location which was defined first.
|
*/

$config['sprinkle']['asset_locations'] = array
(
	'assets/'
);

/*
|--------------------------------------------------------------------------
| Use YAML
|--------------------------------------------------------------------------
|
| Although it is not mandatory, you should have this option enabled. It
| will enable using yaml configuration files instead of raw php files. 
| This is extremely useful when dealing with asset definitions and asset 
| routes.
|
| If you set this option to FALSE, it will use regular PHP files.
|
*/

$config['sprinkle']['use_yaml'] = TRUE;


/*
|--------------------------------------------------------------------------
| Minify CSS assets
|--------------------------------------------------------------------------
|
| If this option is set to TRUE, all CSS assets will be minified by default.
|
*/

$config['sprinkle']['minify_css'] = TRUE;

/*
|--------------------------------------------------------------------------
| Name of the filter which will be used for minifying CSS assets
|--------------------------------------------------------------------------
|
| If you intend to use your own filter for this, you can change the name
| of the filter here.
|
*/

$config['sprinkle']['minify_css_filter'] = 'cssmin';


/*
|--------------------------------------------------------------------------
| Minify javascript assets
|--------------------------------------------------------------------------
|
| If this option is set to TRUE, all JS assets will be minified by default.
|
*/

$config['sprinkle']['minify_js'] = TRUE;

/*
|--------------------------------------------------------------------------
| Name of the filter which will be used for minifying javascript assets
|--------------------------------------------------------------------------
|
| If you intend to use your own filter for this, you can change the name
| of the filter here.
|
*/

$config['sprinkle']['minify_js_filter'] = 'jsmin';


/*
|--------------------------------------------------------------------------
| Combine assets
|--------------------------------------------------------------------------
|
| If set to TRUE, all assets (CSS & Javascript separately) will be combined.
|
*/

$config['sprinkle']['combine'] = TRUE;


/*
|--------------------------------------------------------------------------
| Cache directory
|--------------------------------------------------------------------------
|
| Usually assets will be cached into minified/combined files, so it is
| needed to have a cache directory for that.
| 
*/

$config['sprinkle']['cache_dir'] =  'assets/cache/';


/*
|--------------------------------------------------------------------------
| Cache expiration
|--------------------------------------------------------------------------
|
| Cache expiration is set in days. If you wish not allow the cache to 
| expire, set the value to 0.
| 
*/

$config['sprinkle']['cache_expiration'] = 0;

/*
|--------------------------------------------------------------------------
| Use cURL
|--------------------------------------------------------------------------
|
| If set to TRUE, cURL will be used for fetching assets from remote servers.
| Otherwise file_get_contents() will be used.
| 
*/

$config['sprinkle']['use_curl'] = FALSE;

/*
|--------------------------------------------------------------------------
| Path to the filters folder
|--------------------------------------------------------------------------
|
| It is strongly advised not to change this. Do it only if you really know
| what you're doing! 
| 
*/

$config['sprinkle']['filters_path'] = 'libraries/filters/';

/* End of file sprinkle.php */
/* Location: ./application/config/sprinkle.php */