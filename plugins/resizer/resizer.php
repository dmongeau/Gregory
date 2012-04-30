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
	'memory_limit' => '768M',
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
		'r' => 'rotation',

		//All current GD filters

		'xgr' => 'IMG_FILTER_GRAYSCALE',//: Converts the image into grayscale.
		'xbr' => 'brightness',//: Changes the brightness of the image. Use arg1 to set the level of brightness.
		'xcn' => 'IMG_FILTER_CONTRAST',//: Changes the contrast of the image. Use arg1 to set the level of contrast.
		'xco' => 'IMG_FILTER_COLORIZE',//: Like IMG_FILTER_GRAYSCALE, except you can specify the color. Use arg1, arg2 and arg3 in the form of red, blue, green and arg4 for the alpha channel. The range for each color is 0 to 255.
		'xed' => 'IMG_FILTER_EDGEDETECT',//: Uses edge detection to highlight the edges in the image.
		'xem' => 'IMG_FILTER_EMBOSS',//: Embosses the image.
		'xgb' => 'IMG_FILTER_GAUSSIAN_BLUR',//: Blurs the image using the Gaussian method.
		'xsb' => 'IMG_FILTER_SELECTIVE_BLUR',//: Blurs the image.
		'xmr' => 'IMG_FILTER_MEAN_REMOVAL',//: Uses mean removal to achieve a "sketchy" effect.
		'xsm' => 'IMG_FILTER_SMOOTH',//: Makes the image smoother. Use arg1 to set the level of smoothness.
		'xpx' => 'IMG_FILTER_PIXELATE', //pixelate

		'cx' => 'cropx', //Crop x (horizontal) with Imagick
		'cy' => 'cropy'  //Crop y (vertical) with Imagick

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

