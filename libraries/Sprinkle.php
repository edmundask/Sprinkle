<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Sprinkle - Asset management library
 *
 * Sprinkle is an asset management library for CodeIgniter which seeks to
 * simplify the process of loading assets on the page. The library includes
 * key features such as auto-loading, combining, compressing CSS or JS files, 
 * caching, and much more.
 * 
 * @package   			CodeIgniter
 * @subpackage			Sprinkle
 * @category  			Libraries
 * @author    			Edmundas Kondrašovas <as@edmundask.lt>
 * @license   			http://www.opensource.org/licenses/MIT
 * @version   			1.0
 * @copyright 			Copyright (c) 2011 Edmundas Kondrašovas <as@edmundask.lt>
 */

if(!defined('SPRINKLE_ROOT')) define('SPRINKLE_ROOT', dirname(__DIR__));

require_once(SPRINKLE_ROOT . '/libraries/Asset_Interface.php');

spl_autoload_register('Sprinkle::autoload');

class Sprinkle
{
	protected $CI;

	private static $_filters_path;

	private $_assets = array();
	private $_loaded = array();
	private $_groups = array();
	private $_config = array();
	private $_routes = array();
	private $_filters = array();
	private $_cache_info = array();

	/**
	* Constructor
	*/

	public function __construct()
	{
		log_message('debug', 'Sprinkle: library initialized');

		$this->CI =& get_instance();

		$this->_loaded['css'] = array();
		$this->_loaded['js'] = array();

		$this->_groups['stylesheets'] = array();
		$this->_groups['javascripts'] = array();

		$this->_config = $this->CI->config->item('sprinkle');
		self::$_filters_path = $this->_config['filters_path'];

		if($this->_config['use_yaml'])
		{
			// If we decide to use YAML for asset definitions, let's make sure we have a way of parsing these files!
			require_once(SPRINKLE_ROOT .'/vendor/sfYaml/sfYaml.php');

			try
			{
				$assets = sfYaml::load(SPRINKLE_ROOT .'/config/assets.yml');
				if(is_array($assets)) $this->load_collection($assets);
			}
			catch(Exception $e)
			{
				log_message('debug', 'Sprinkle: [WARNING] There was a problem parsing YAML file: assets.yml');
			}

			try
			{
				$routes = sfYaml::load(SPRINKLE_ROOT .'/config/asset_routes.yml');
				if(is_array($routes)) $this->_parse_routes($routes);
			}
			catch(Exception $e)
			{
				log_message('debug', 'Sprinkle: [WARNING] There was a problem parsing YAML file: asset_routes.yml');
			}
		}
		else
		{
			$this->load_collection($this->CI->config->item('assets'));
			$this->_parse_routes($this->CI->config->item('asset_routes'));
		}

		if($this->_config['cache_expiration'] != 0) $this->flush_cache($this->_config['cache_expiration']);
	}

	/**
	* Load assets
	*
	* @access	public
	* @param 	mixed 	asset name or an array of assets to be loaded
	* @param 	string	(optional) asset version
	* @param 	string	(internal) asset group name
	* @return	void
	*/

	public function load($asset = '', $version = 'default', $group = '')
	{
		if(empty($asset)) return;

		// Are we loading multiple assets?
		if(is_array($asset))
		{
			foreach($asset as $a)
			{
				// Does the asset have key=>val pairs?
				if(is_array($a))
				{
					if(!array_key_exists('version', $a) && !array_key_exists('name', $a))
					{
						$this->load(key($a), $a[key($a)], $group);
					}
					else
					{
						$asset_version = (array_key_exists('version', $a)) ? $a['version'] : $version;
						$this->load($a['name'], $asset_version, $group);
					}
				}
				else
				{
					$this->load($a, $version, $group);
				}
			}
		}
		else
		{
			if($this->_asset_is_defined($asset, $version))
			{
				$asset_info = $this->_assets[$asset];

				if($asset_info['type'] == 'group')
				{
					if(!array_key_exists($asset, $this->_groups)) $this->_groups[$asset] = array('assets' => array());
					$this->load($asset_info['assets'], $version, $asset);
				}
				else
				{
					// Avoid asset duplicates
					if(array_key_exists($asset, $this->_loaded[$asset_info['type']]))
					{
						log_message('debug', 'Sprinkle: [NOTICE] attempted to load an already loaded asset \''. $asset .'\' (version: '. $version .')');
						return;
					}

					if(empty($group))
						$asset_info['group'] = ($asset_info['type'] == 'css') ? 'stylesheets' : 'javascripts';
					else
						$asset_info['group'] = $group;
					
					$asset_info['selected_version'] = $version;
					$asset_info = $this->_format_asset($asset_info);

					$this->_load($asset_info, $asset);
				}
			}
		}
	}

	public function show_groups()
	{
		return $this->_groups;
	}

	public function show_loaded()
	{
		return $this->_loaded;
	}

	/**
	* Internal asset loading
	*
	* @access	private
	* @param 	array 	asset array
	* @param 	string	asset name
	* @return	void
	*/

	public function _load($asset_array, $name)
	{
		if($asset_array['load'] !== FALSE)
		{
			switch($asset_array['origin'])
			{
				case 'local':
					$this->_loaded[$asset_array['type']][$name] = new \Sprinkle\FileAsset($asset_array);
				break;

				case 'remote':
					$this->_loaded[$asset_array['type']][$name] = new \Sprinkle\RemoteAsset($asset_array);
				break;

				default:
					$this->_loaded[$asset_array['type']][$name] = new \Sprinkle\Asset($asset_array);
				break;
			}

			$this->_groups[$asset_array['group']]['assets'][$name] = $this->_loaded[$asset_array['type']][$name];

			log_message('debug', 'Sprinkle: asset \''. $name .'\' (version: '. $asset_array['selected_version'] .') loaded');
		}
		else
		{
			log_message('debug', 'Sprinkle: [WARNING] unable to load asset \''. $name .'\'. (version: '. $asset_array['selected_version'] .'). File does not exist.');
		}

	}

	/**
	* Unload assets
	*
	* @access	public
	* @param 	string	asset name
	* @return	void
	*/

	public function unload($asset = '')
	{
		// If no name is specified, unload ALL assets
		if(empty($asset))
		{
			unset($this->_loaded);
			unset($this->_groups);

			$this->_loaded['css'] = array();
			$this->_loaded['js'] = array();
			$this->_groups = array();

			log_message('debug', 'Sprinkle: unloaded all assets');
		}
		elseif(is_array($asset))
		{
			foreach($asset as $a) $this->unload($a);
		}
		else
		{
			foreach($this->_loaded as $k => $v)
			{
				if(array_key_exists($asset, $v))
				{

					if($v[$asset]->type == 'group')
					{
						// Since this is a group, we have to unload all assets that belong to it
						foreach($v[$asset]->assets as $a) $this->unload($a);
						unset($this->_groups[$asset]);
					}
					else
					{
						unset($this->_loaded[$k][$asset]);
						unset($this->_groups[$v[$asset]->group]['assets'][$asset]);
					}

					log_message('debug', 'Sprinkle: unloaded \''. $asset .'\'');

					return;
				}
			}
		}
	}

	/**
	* Replace loaded asset with a different version of it
	*
	* This method simplifies asset replacement as it can be done manually
	* by calling unload() and load() methods.
	*
	* @access	public
	* @param 	string	asset name
	* @param 	string	asset version
	* @return	void
	*/

	public function replace($asset = '', $version = 'default')
	{
		if(empty($asset)) return;

		$this->unload($asset);
		$this->load($asset, $version);
	}

	/**
	* Load CSS asset
	*
	* @access	public
	* @param 	mixed  		source or an array of multiple CSS assets
	* @param 	string 		(optional) media
	* @param 	boolean		minify the asset
	* @param 	boolean		allow the asset to be combined with other assets
	* @return	mixed  		asset name or an array of names
	*/

	public function css($src, $media = 'screen', $minify = TRUE, $combine = TRUE)
	{
		if(empty($src)) return;

		if(is_array($src))
		{			
		 	$assets_to_return = array();

			foreach($src as $asset)
			{
				// Check if the asset in the array is an array of its own because
				// we want to have the ability to set the options for each asset individually.
				if(is_array($asset))
				{
					$asset_src    	= (isset($asset['src'])) ? $asset['src'] : '';
					$asset_media  	= (!isset($asset['media'])) ? 'screen' : $asset['media'];
					$asset_minify 	= (!isset($asset['minify'])) ? TRUE : $asset['minify'];
					$asset_combine	= (!isset($asset['combine'])) ? TRUE : $asset['combine'];
				}
				else
				{
					$asset_src    	= $asset;
					$asset_media  	= $media;
					$asset_minify 	= $minify;
					$asset_combine	= $combine;
				}

				$assets_to_return[] = $this->css($asset_src, $asset_media, $asset_minify, $asset_combine);
			}

			return $assets_to_return;
		}
		else
		{
			$asset_name = 'stylesheet:'. $dev_src;
			$asset_name = str_replace('.', '-', $asset_name);

			$asset = array
			(
				'type'            	=> 'css',
				'src'             	=> $src,
				'media'           	=> $media,
				'minify'          	=> $minify,
				'combine'         	=> $combine,
				'group'           	=> 'stylesheets',
				'selected_version'	=> 'default'
			);

			// Arrange the array so that it has all keys and values in the right places.
			$asset = $this->_format_asset($asset);

			// Load it!
			$this->_load($asset, $asset_name);

			// The name of the asset will be usefull if it's going to be put in a different group
			return $asset_name;
		}
	}

	/**
	* Load Javascript asset
	*
	* @access	public
	* @param 	mixed  		source or an array of multiple CSS assets
	* @param 	boolean		minify the asset
	* @param 	boolean		allow the asset to be combined with other assets
	* @return	mixed  		asset name or an array of names
	*/

	public function js($src, $minify = TRUE, $combine = TRUE)
	{
		if(empty($src)) return;

		if(is_array($src))
		{			
		 	$assets_to_return = array();

			foreach($src as $asset)
			{
				// Check if the asset in the array is an array of its own because
				// we want to have the ability to set the options for each asset individually.
				if(is_array($asset))
				{
					$asset_src    	= (isset($asset['src'])) ? $asset['src'] : '';
					$asset_minify 	= (!isset($asset['minify'])) ? TRUE : $asset['minify'];
					$asset_combine	= (!isset($asset['combine'])) ? TRUE : $asset['combine'];
				}
				else
				{
					$asset_src    	= $asset;
					$asset_media  	= $media;
					$asset_minify 	= $minify;
					$asset_combine	= $combine;
				}

				$assets_to_return[] = $this->js($asset_src, $asset_minify, $asset_combine);
			}

			return $assets_to_return;
		}
		else
		{
			$asset_name = 'javascript:'. $dev_src;
			$asset_name = str_replace('.', '-', $asset_name);

			$asset = array
			(
				'type'            	=> 'css',
				'src'             	=> $src,
				'minify'          	=> $minify,
				'combine'         	=> $combine,
				'group'           	=> 'javascripts',
				'selected_version'	=> 'default'
			);

			// Arrange the array so that it has all keys and values in the right places.
			$asset = $this->_format_asset($asset);

			// Load it!
			$this->_load($asset, $asset_name);

			// The name of the asset will be usefull if it's going to be put in a different group
			return $asset_name;
		}
	}

	/**
	* Add assets to a group
	*
	* Having 2 parameters which are basically the same (assets1, assets2) may look
	* like an overkill. However, this is convenient for assigning css/jss assets to
	* a particular group. For example, you can have assets1 for CSS assets and assets2
	* for Javascript assets and vice-versa.
	*
	* @access	public
	* @param 	string	group name
	* @param 	array 	assets to be added to the group
	* @return	void
	*/

	public function group($group, $assets1 = NULL, $assets2 = NULL)
	{
		if((empty($assets1) && empty($assets2))) return FALSE;

		if(!empty($assets1))
		{
			if(is_array($assets1))
			{
				foreach($assets1 as $asset) $this->_add_to_group($group, $asset);
			}
			else
			{
				$this->_add_to_group($group, $assets1);
			}
		}

		if(!empty($assets2))
		{
			if(is_array($assets2))
			{
				foreach($assets2 as $asset) $this->_add_to_group($group, $asset);
			}
			else
			{
				$this->_add_to_group($group, $assets2);
			}
		}
	}

	/**
	* Load available (pre-defined) assets
	*
	* @access	public
	* @param 	array	assets collection
	* @return	void
	*/

	public function load_collection($assets = array())
	{
		$this->_assets = $this->_format_assets($assets);
	}

	/**
	* Format asset array
	*
	* This is an internal method to make sure each asset
	* has all the required information.
	*
	* @access	private
	* @param 	array	asset
	* @return	array	re-arranged asset array
	*/

	private function _format_asset($asset)
	{
		// We do this check because it's allowed not to define versions.
		if(!array_key_exists('versions', $asset) && $asset['type'] != 'group')
			$asset['versions']['default'] = $v['src'];

		$asset['origin'] = (is_url($asset['versions'][$asset['selected_version']])) ? 'remote' : 'local';
		$asset['src'] = $asset['versions'][$asset['selected_version']];

		$filename = substr(strrchr($asset['src'], '/'), 1);
		$filename = (empty($filename)) ? $asset['src'] : $filename;
		$ext = substr(strrchr($filename, '.'), 1);
		$filename = substr($filename, 0, strlen($filename) - strlen(strrchr($filename, '.')));

		$asset['filename'] = $filename;
		$asset['extension'] = $ext;

		if(!array_key_exists('filters', $asset)) $asset['filters'] = array();
		
		if($asset['origin'] == 'local')
		{
			$find_asset = $this->_find_asset($asset['src']);

			if($find_asset !== FALSE)
			{
				$asset['load'] = TRUE;
				$asset['location'] = $find_asset['location'];
				$asset['full_path'] = $find_asset['file_path'];
			}
			else
			{
				$asset['load'] = FALSE;
			}
		}
		else
		{
			$asset['load'] = TRUE;
			$asset['full_path'] = $asset['src'];

			if(!array_key_exists('use_curl', $asset)) $asset['use_curl'] = $this->_config['use_curl'];
		}

		$asset['cache_dir'] = $this->_config['cache_dir'];

		return $asset;
	}

	/**
	* Format assets array
	*
	* This is an internal method to make sure each asset
	* has all the required information.
	*
	* @access	private
	* @param 	array	assets
	* @return	array	re-arranged assets array
	*/

	private function _format_assets($assets = array())
	{
		foreach($assets as $k => $v)
		{
			// Let's make sure the asset has all required information.
			// We do this because it's allowed not to define versions.
			if(!array_key_exists('versions', $assets[$k]) && $v['type'] != 'group')
				$assets[$k]['versions']['default'] = $v['src'];
		}

		return $assets;
	}

	/**
	* Parse asset routes
	*
	* @access	private
	* @param 	array	asset routes
	* @return	void
	*/

	private function _parse_routes($routes = NULL)
	{
		if(!empty($routes)) $this->_routes = $routes;

		$uri = $this->CI->uri->uri_string();

		// Stole some bits from the original CodeIgniter system file: core/Router.php
		foreach ($this->_routes as $key => $val)
		{
			// Convert wild-cards to RegEx
			$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

			// Does the RegEx match?
			if (preg_match('#^'.$key.'$#', $uri))
			{
				// Load the assets!
				if(array_key_exists('assets', $val) && count($val['assets']) > 0)
				{
					foreach($val['assets'] as $asset)
					{
						if(is_array($asset))
							$this->load(key($asset), $asset[key($asset)]);
						else
							$this->load($asset);
					}
				}

				// Turns out we need to exclude some of them...
				if(array_key_exists('exclude', $val) && count($val['exclude']) > 0)
				{
					foreach($val['exclude'] as $asset) $this->unload($asset);
				}

				// Do we want to swap asset versions?
				if(array_key_exists('replace', $val) && count($val['replace']) > 0)
				{
					foreach($val['replace'] as $name => $version) 
					{
						$this->replace($name, $version);
					}
				}
			}
		}
	}

	/**
	* Assign the asset to a different group
	*
	* @access	private
	* @param 	string	group name
	* @return	string	asset name
	*/

	private function _add_to_group($group, $asset)
	{
		$loaded_asset = $this->_get_loaded_asset($asset);
		if(!$loaded_asset) return;

		$loaded_asset->group = $group;
		$this->_loaded[$loaded_asset->type]->group = $group;
		$this->_groups[$group]['assets'][$asset] = $loaded_asset;
	}

	/**
	* Run filters
	*
	* @access	private
	* @param 	Asset	asset
	* @return	void
	*/

	private function _run_filters(\Sprinkle\Asset $asset)
	{
		if(!$asset->has_filters()) return $asset;

		foreach($asset->filters as $filter) $this->_run_filter($asset, $filter);
	}

	/**
	* Run a specific filter
	*
	* @access	private
	* @param 	Asset 	asset
	* @param 	string	filter name
	* @return	void
	*/

	private function _run_filter(\Sprinkle\Asset $asset, $filter)
	{
		if(!$asset->has_filters()) return;

		// Instantiate the class only once
		if(!array_key_exists($filter, $this->_filters))
		{
			$class = '\Sprinkle\Filters\\'. $filter;
			$this->_filters[$filter] = new $class;
		}

		$this->_filters[$filter]->output($asset);
	}

	/**
	* Output generated HTML code
	*
	* @access	public
	* @param 	string	type/group
	* @return	string	HTML
	*/

	public function output($type = 'all')
	{
		$output = '';

		switch($type)
		{
			default:
				if(array_key_exists($type, $this->_groups)) $output = $this->_output_group($type);
			break;

			case 'css':
				$output = $this->_output_assets('css');
			break;

			case 'js':
				$output = $this->_output_assets('js');
			break;

			case 'all':
				$output = $this->_output_assets('css');
				$output .= $this->_output_assets('js');
			break;
		}

		return $output;
	}

	/**
	* Internal method for outputting the assets
	*
	* @access	private
	* @param 	string	type
	* @param 	array 	assets
	* @return	string	HTML
	*/

	private function _output_assets($type, $assets = '')
	{
		$output = '';

		$assets = (empty($assets)) ? $this->_loaded[$type] : $assets;
		if(empty($assets)) return NULL;

		$prepared = array();
		$modified = array();

		foreach($assets as $name => $asset)
		{
			if($asset->type == $type)
			{
				if($this->_config['minify_'. $type] && $asset->minify)
				{
					$asset->add_filter($this->_config['minify_'. $type .'_filter']);
				}

				if($asset->has_filters() && !$asset->is_cached())
				{
					$this->_run_filters($asset);
					$asset->cache();
				}

				if($this->_config['combine'] && $asset->combine)
				{
					$prepared[$asset->group][] = $asset;
					$modified[$asset->group][] = $asset->get_last_modified();
				}
				else
				{
					$output .= $this->_create_tags($asset);
				}
			}

		}

		// Looks like we have some assets that need to be combined
		if(count($prepared) > 0)
		{
			foreach($prepared as $group => $assets)
			{
				if(!empty($assets))
				{
					$last_modified = max($modified[$group]);

					$filename = $group . '-'. $last_modified .'.'. $type;
					if(!file_exists($this->_config['cache_dir'] . $filename))
					{
						/*// We may have missed some assets since not every asset is minified
						foreach($assets as $asset)
							if(empty($asset->tmp_file)) $this->_run_filters($asset);*/

						$this->_combine($filename, $assets);
					}

					$output .= $this->_create_tags_from_multpile($filename, $assets[0]);
				}
			}
		}

		return $output;
	}

	/**
	* Output asset group
	*
	* @access	private
	* @param 	string	group name
	* @return	HTML
	*/

	private function _output_group($group)
	{
		$output = '';

		$output .= $this->_output_assets('css', $this->_groups[$group]['assets']);
		$output .= $this->_output_assets('js', $this->_groups[$group]['assets']);

		return $output;
	}

	/**
	* Combine given assets into one file
	*
	* @access	private
	* @param 	string	filename
	* @param 	array 	Asset objects
	* @return	void
	*/

	private function _combine($filename, $assets)
	{
		$contents = '';
		foreach($assets as $asset)
		{
			$contents .= $asset->get_contents();
			$contents .= "\n\n";
		}

		@file_put_contents($this->_config['cache_dir'] . $filename, $contents);
	}

	/**
	* Check if asset exists
	*
	* @access	private
	* @param 	string	asset name
	* @param 	string	asset version
	* @return	boolean
	*/

	private function _asset_is_defined($name = '', $version = 'default')
	{
		if(array_key_exists($name, $this->_assets))
		{
			$asset = $this->_assets[$name];

			if($asset['type'] != 'group')
			{
				if(array_key_exists($version, $asset['versions'])) return TRUE;

				// Asset itself is defined, but we don't have the necessary version!
				log_message('debug', 'Sprinkle: [WARNING] asset \''. $name .'\' (version: '. $version .') not defined');
				return FALSE;
			}

			// It's a group and it's defined
			return TRUE;
		}

		// If we got here, it means the asset is not defined. Bummer!
		log_message('debug', 'Sprinkle: [WARNING] asset \''. $name .'\' (version: '. $version .') not defined');
		return FALSE;
	}

	/**
	* Look for a particular asset in all available locations
	*
	* @access	private
	* @param 	string	relative path to the asset
	* @return	mixed 	FALSE if the asset was not found, asset location & full path otherwise
	*/

	private function _find_asset($asset_path = '')
	{
		foreach($this->_config['asset_locations'] as $loc)
		{
			$file_path = realpath($loc . $asset_path);

			if(file_exists($file_path)) return array('location' => $loc, 'file_path' => $file_path);
		}

		return FALSE;
	}

	/**
	* Get Asset object from the loaded assets list if only the name is known
	*
	* @access	private
	* @param 	string	asset name
	* @return	mixed 	Asset object or FALSE if asset was not found
	*/

	private function _get_loaded_asset($name)
	{
		if(array_key_exists($name, $this->_loaded['css']))
			return $this->_loaded['css'][$name];
		elseif(array_key_exists($name, $this->_loaded['js']))
			return $this->_loaded['js'][$name];
		else
			return FALSE;
	}

	/**
	* Create HTML tags for CSS/JS assets
	*
	* @access	private
	* @param 	Asset 	asset object
	* @param 	string	filename
	* @return	string	HTML
	*/

	private function _create_tags(\Sprinkle\Asset $asset, $file = '')
	{
		if(empty($file))
		{
			$file = $asset->src;

			if(!is_url($file))
				$file = ($asset->is_cached()) ? base_url() . $asset->cached_file : base_url() . $asset->location . $asset->src;
			else
				$file = ($asset->is_cached()) ? base_url() . $asset->cached_file : $file;
		}
		else
		{
			$file = base_url() . $this->_config['cache_dir'] . $file;
		}

		switch($asset->type)
		{
			case 'js':

				$html = "\t \t<script src=\"". $file ."\"></script>" . "\n";

			break;

			case 'css':

				$html = "\t \t<link href=\"". $file ."\" rel=\"stylesheet\" type=\"text/css\" media=\"". $asset->media ."\"> \n";

			break;
		}

		return $html;
	}

	/**
	* Create HTML tags for CSS/JS assets
	*
	* @access	private
	* @param 	string	filename
	* @param 	Asset 	asset object (first one in the group)
	* @return	string	HTML
	*/

	private function _create_tags_from_multpile($filename, \Sprinkle\Asset $asset)
	{
		return $this->_create_tags($asset, $filename);
	}

	/**
	* Flush the assets cache
	*
	* When the time parameter is set, only those cache files that are
	* older than the set time (lifespan) will be removed.
	*
	* @access	public
	* @param 	integer	time in seconds 
	* @return	void
	*/

	public function flush_cache($time = NULL)
	{
		$dir = realpath($this->_config['cache_dir']);

		if(!$dirhandle = @opendir($dir))
			return;

		while(FALSE !== ($filename = readdir($dirhandle)))
		{
			if($filename != '.' && $filename != '..')
			{
				$filename = $dir . '/' . $filename;

				if(!empty($time))
				{
					if((filemtime($filename) + $time) <= time())
						@unlink($filename);
				}
				else
				{
					@unlink($filename);
				}
			}
		}
	}

	/**
	* Clean up the folder in which we stored temporary files
	*
	* @access	private
	* @return	void
	*/

	private function _cleanup()
	{
		$tmp_dir = SPRINKLE_ROOT .'/tmp/';

		if(!$dirhandle = @opendir($tmp_dir))
			return;

		while(FALSE !== ($filename = readdir($dirhandle)))
		{
			if($filename != '.' && $filename != '..')
			{
				$filename = $tmp_dir . '/' . $filename;
				@unlink($filename);
			}
		}
	}

	/**
	* Destructor
	*/

	public function __destruct()
	{
		$this->_cleanup();
	}

	/**
	* Autoload function used for spl_autoload_register()
	*
	* For now its sole purpose is to autoload filters.
	*
	* @access	public
	* @param 	string	class
	* @return	void
	*/

	public static function autoload($class)
	{
		// Avoid CodeIgniter-specific classes
		if (strstr($class, 'CI_') OR strstr($class, config_item('subclass_prefix'))) return;
		
		$pieces = explode('\\', $class);

		if(count($pieces) > 0)
		{
			if(file_exists($file = SPRINKLE_ROOT . '/' . self::$_filters_path . strtolower($pieces[count($pieces) - 1]) .'.php'))
				require_once($file);
			else
				return;
		}
	}
}
// End Class

/* End of file Sprinkle.php */
/* Location: ./application/libraries/Sprinkle.php */