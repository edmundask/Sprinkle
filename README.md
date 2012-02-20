# Sprinkle - Asset Management Library

Sprinkle is an asset management library for CodeIgniter which seeks to simplify the process of loading assets on the page. The library includes key features such as auto-loading, combining, compressing CSS or JS files, caching, and much more.

# Requirements

* PHP 5.3.x
* [CodeIgniter](http://codeigniter.com/) 2.x

# How To Use It

## 1. Load the library

`$this->load->spark('sprinkle');`

## 2. Define assets

```yaml
jquery:
  type: js
  minify: false
  combine: false
  src: http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.js
```

## 3. Set up asset routes or load assets from the controller

### Asset routes

```yaml
'welcome/(:any)':
  assets:
    - jquery
```

### Load from the controller

`$this->sprinkle->load('jquery');`

## 4. Output!

```
echo $this->sprinkle->output();
```

See the [wiki](https://github.com/edmundask/Sprinkle/wiki) for more information.

# CHANGELOG

### 1.0.5

* Made group() method more compact and flexible. It now supports a desired amount of params: the first one must be the name of a group (string) and rest can be either strings or arrays of strings with LOADED asset names.
* Added 3 new wildcards for the routes: (:!any), (:default) and (:home). See the wiki for more information.
* Yet another attempt at fixing critical problems with asset route parsing. Expect more improvements in the future.
* The method output() now accepts a second parameter which lets you specify what type of assets to output for a group. For example, `$this->sprinkle->output('my_group', 'css');` will only output CSS assets from that group. Thanks to Ammon Casey for adding this feature.

### 1.0.4

* Fixed a bug which caused some of the routes not to be parsed correctly.

### 1.0.3

* (:any) wildcard in the asset routes file now also takes the homepage (default CI route) into account.
* Added asset filter auto-loading. You can set these filters in the configuration file.
* When defining assets (or loading them from a controller), you can now exclude specific filters.

# Known Issues

* Specifying https protocol in the asset source will prevent Sprinkle from fetching the asset contents.
* After changing some of the settings in Sprinkle configuration or asset definitions file you may not get the result you want. To do avoid this, you have to clear the cache first.

# COPYRIGHT

Copyright (c) 2011-2012 Edmundas Kondrašovas

Permission is hereby granted, free of charge, to any person obtaining a copy 
of this software and associated documentation files (the "Software"), to deal 
in the Software without restriction, including without limitation the rights 
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
copies of the Software, and to permit persons to whom the Software is 
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in 
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
THE SOFTWARE.

