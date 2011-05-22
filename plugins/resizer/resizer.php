<?php


/*
 *
 * Load configuration
 *
 */
$config = array_merge(array(
	'path' => dirname(__FILE__),
	'cachePath' => dirname(__FILE__).'/_cache',
	'quality' => 90,
	'cache' => true,
	'memory_limit' => '500M',
	'size' => array()
),$config);

if(!isset($config['size']['thumb'])) {
	$config['size']['thumb'] = array(
		'width' => 50,
		'height' => 50,
		'ratio' => true
	);
}

/*
 *
 * Load ImageResizer
 *
 */
require dirname(__FILE__).'/ImageResizer.php';

ImageResizer::setConfig($config);

/*
 *
 * Add resizer route
 *
 */
$this->addRoute(array(
	'/resizer/*' => array(
		'function' => 'resizer_route'
	)
));

/*
 *
 * Resizer route function
 *
 */
function resizer_route($route) {
	
	if(!isset($route['params']['wildcard'])) {
		return false;
	}
	
	$config = ImageResizer::getConfig();
	
	$parts = explode('/',$route['params']['wildcard']);
	
	$params = array(
		'w' => 'width',
		'h' => 'height',
		'q' => 'quality',
		'cw' => 'crop_width',
		'ch' => 'crop_height',
		's' => 'scale',
		'r' => 'rotation'
	);
	
	$file = null;
	$options = array();
	for($i = 0; $i < sizeof($parts); $i++) {
		$part = $parts[$i];
		
		if(is_array($file)) $file[] = $part;
		else if($part == 'bw') $options['blackwhite'] = true;
		else if($part == 'ratio') $options['ratio'] = true;
		else if($part == 'crop') $options['crop'] = true;
		else if(preg_match('/^([a-z]+)([0-9\.]+)$/',$part,$matches)) {
			if(isset($params[$matches[1]])) {
				$options[$params[$matches[1]]] = $matches[2];
			} else {
				$options[$matches[1]] = $matches[2];
			}
		}
		else if($part == 'f') $file = array();
		else if(isset($config['size'][$part])) $options = array_merge($options,$config['size'][$part]);
		 
	}
	if(!is_array($file)) return false;
	else $file = Gregory::absolutePath(implode('/',$file),array($config['path']));
	
	if(!$file || empty($file)) return false;
	
	ini_set("memory_limit",$config['memory_limit']);
	
	$Resizer = new ImageResizer($file);
	$Resizer->resize($options);
	$Resizer->render();
	
	return true;
}

