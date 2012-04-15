<?php
/*
 *
 * ImageResizer
 *
 * Class to resize image using gd library
 *
 * @author David Mongeau-Petitpas <dmp@commun.ca>
 * @version 0.9
 *
 */
 
ini_set('gd.jpeg_ignore_warning', 1);
ini_set('display_errors', 0);
error_reporting(0);


ini_set('gd.jpeg_ignore_warning', 0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("MagickFilter.php");

class ImageResizer {
	
	protected static $_config;
	
	protected $_filename;
	protected $_imagesize;
	protected $_mime;
	protected $_data;
	
	public function __construct($file) {
		
		if (!file_exists($file)) {
			throw new Exception('Image not found');
		}
		
		$this->_filename = $file;
		$this->_imagesize = getimagesize($this->_filename);
		$this->_mime = $this->_imagesize['mime'];
		
		if (substr($this->_mime, 0, 6) != 'image/') {
			throw new Exception('Image not supported');
		}
		
	}
	
	public function resize($maxwidth = 0,$maxheight = 0,$opts = array()) {
		
		$config = self::getConfig();

		//var_dump($opts);
		//exit();
		
		if($config['cache'] && $cache = $this->getCache()) {
			$this->_data = $cache;
			return $this->_data;
		}
		
		if(is_array($maxwidth)) $opts = $maxwidth;
		else {
			$opts['width'] = $maxwidth;
			$opts['height'] = $maxheight;
		}
		
		$origWidth = $width = $this->_imagesize[0];
		$origHeight = $height = $this->_imagesize[1];
		
		if(isset($opts['color'])) $color = preg_replace('/[^0-9a-fA-F]/', '', (string)$opts['color']);
		else $color = FALSE;

		if ((!isset($opts['width']) || !$opts['width']) && isset($opts['height'])) $opts['width']	= 99999999999999;
		elseif (isset($opts['width']) && (!isset($opts['height']) || !$opts['height'])) $opts['height'] = 99999999999999;
		
		if (!isset($opts['width'])) $opts['width'] = $width;
		if (!isset($opts['height'])) $opts['height'] = $height;
		
		
		$offsetX = 0;
		$offsetY = 0;
		
		if(isset($opts['ratio']) && $opts['ratio']) {
			//$cropRatio = explode(':', (string) $opts['ratio']);
			$cropRatio = array($opts['width'],$opts['height']);
			if(sizeof($cropRatio) == 2) {
				$ratioComputed = $width / $height;
				$cropRatioComputed = (float)$cropRatio[0] / (float)$cropRatio[1];
				if ($ratioComputed < $cropRatioComputed) {
					$origHeight	= $height;
					$height		= $width / $cropRatioComputed;
					$offsetY	= ($origHeight - $height) / 2;
				} else if ($ratioComputed > $cropRatioComputed) {
					$origWidth	= $width;
					$width		= $height * $cropRatioComputed;
					$offsetX	= ($origWidth - $width) / 2;
				}
			}
		}
		
		if(isset($opts["x"])) $offsetX = (integer)$opts["x"];
		if(isset($opts["y"])) $offsetY = (integer)$opts["y"];
		
		
		$xRatio = $opts['width'] / $width;
		$yRatio = $opts['height'] / $height;
		
		if ($xRatio * $height < $opts['height']) {
			$tnHeight	= ceil($xRatio * $height);
			$tnWidth	= $opts['width'];
		} else {
			$tnWidth	= ceil($yRatio * $width);
			$tnHeight	= $opts['height'];
		}
		
		// Determine the quality of the output image
		$quality	= (isset($opts['quality'])) ? (int)$opts['quality']:$config['quality'];
		
		$resizeFactor = (isset($opts['scale']) && (float)$opts['scale'] != 0) ? (float)$opts['scale']:1;
		
		if(isset($opts['crop']) && sizeof($cropRatio) == 2) {
			$dst = imagecreatetruecolor($cropRatio[0]*$resizeFactor, $cropRatio[1]*$resizeFactor);
			//$dst = imagecreatetruecolor($cropRatio[0], $cropRatio[1]);
		} else {
			$dst = imagecreatetruecolor($tnWidth*$resizeFactor, $tnHeight*$resizeFactor);
			//$dst = imagecreatetruecolor($tnWidth, $tnHeight*$resizeFactor);
		}
		
		
		
		switch($this->_mime) {
			case 'image/gif':
				$creationFunction	= 'imagecreatefromgif';
				$outputFunction		= 'imagepng';
				$mime				= 'image/png'; // We need to convert GIFs to PNGs
				$doSharpen			= FALSE;
				$quality			= round(10 - ($quality / 10));
			break;
			
			case 'image/x-png':
			case 'image/png':
				$creationFunction	= 'imagecreatefrompng';
				$outputFunction		= 'imagepng';
				$doSharpen			= FALSE;
				$quality			= round(10 - ($quality / 10)); // PNG needs a compression level of 0 (no compression) through 9
			break;
			
			default:
				$creationFunction	= 'imagecreatefromjpeg';
				$outputFunction	 	= 'imagejpeg';
				$doSharpen			= TRUE;
			break;
		}
		
		// Read in the original image
		$src = $creationFunction($this->_filename);
		
		if(in_array($this->_mime, array('image/gif', 'image/png'))) {
			if(!$color) {
				// If this is a GIF or a PNG, we need to set up transparency
				imagealphablending($dst, false);
				imagesavealpha($dst, true);
			} else {
				// Fill the background with the specified color for matting purposes
				if ($color[0] == '#') $color = substr($color, 1);
				
				$background	= FALSE;
				
				if (strlen($color) == 6) {
					$background	= imagecolorallocate($dst, hexdec($color[0].$color[1]), hexdec($color[2].$color[3]), hexdec($color[4].$color[5]));
				} else if (strlen($color) == 3) {
					$background	= imagecolorallocate($dst, hexdec($color[0].$color[0]), hexdec($color[1].$color[1]), hexdec($color[2].$color[2]));
				}
				if ($background) {
					imagefill($dst, 0, 0, $background);
				}
			}
		}
		
		if (isset($opts['crop']) && !empty($opts['crop'])) {
			
			if ($width>$height) {
				$destW = ($tnWidth/$tnHeight)*$height*($cropRatio[0]/$tnWidth);
				$destH = $height*($cropRatio[0]/$tnWidth);
			} else {
				$destH = ($tnHeight/$tnWidth)*$width*($cropRatio[0]/$tnWidth);
				$destW = $width*($cropRatio[0]/$tnWidth);
			}
		
		} else if (isset($opts['crop_width']) && isset($opts['crop_height'])) {
			
			$destW = (int)$opts['crop_width'];
			$destH = (int)$opts['crop_height'];
			$width = $origWidth;
			$height = $origHeight;
			$offsetX = $offsetX * ($width/$destW);
			$offsetY = $offsetY * ($height/$destH);
		
		} else {
			
			$destH = $tnHeight;
			$destW = $tnWidth;
			
		}
		
		$w = $width;
		$h = $height;
		$dw = $destW*$resizeFactor;
		//$dw = $destW;
		$dh = $destH*$resizeFactor;
		//$dh = $destH;
		
		// Resample the original image into the resized canvas we set up earlier
		imagecopyresampled($dst, $src, 0, 0, $offsetX, $offsetY, $dw, $dh, $w, $h);
		
		if ($doSharpen) {
			$sharpness	= self::findSharp($width, $tnWidth);
			
			$sharpenMatrix	= array(
				array(-1, -2, -1),
				array(-2, $sharpness + 12, -2),
				array(-1, -2, -1)
			);
			$divisor = $sharpness;
			$offset = 0;
			imageconvolution($dst, $sharpenMatrix, $divisor, $offset);
		}
		
		
		if(isset($opts['blackwhite']) && $opts['blackwhite']) {
			for ($c=0;$c<256;$c++) {
				$palette[$c] = imagecolorallocate($dst,$c,$c,$c);
			}
			
			//Creates yiq function
			function yiq($r,$g,$b) {
				return (($r*0.299)+($g*0.587)+($b*0.114));
			} 
			for ($y=0;$y<$destH;$y++) {
				for ($x=0;$x<$destW;$x++) {
					$rgb = imagecolorat($dst,$x,$y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					
					//This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
					$gs = yiq($r,$g,$b);
					imagesetpixel($dst,$x,$y,$palette[$gs]);
				}
			} 
		}
		
		if(isset($opts['rotation']) && !empty($opts['rotation'])) {
			$dst = imagerotate($dst, (float)$opts['rotation'], 0) ;
		}

		try{

		/*

		Filters :

			IMG_FILTER_GRAYSCALE: Converts the image into grayscale.
			IMG_FILTER_BRIGHTNESS: Changes the brightness of the image. Use arg1 to set the level of brightness.
			IMG_FILTER_CONTRAST: Changes the contrast of the image. Use arg1 to set the level of contrast.
			IMG_FILTER_COLORIZE: Like IMG_FILTER_GRAYSCALE, except you can specify the color. Use arg1, arg2 and arg3 in the form of red, blue, green and arg4 for the alpha channel. The range for each color is 0 to 255.
			IMG_FILTER_EDGEDETECT: Uses edge detection to highlight the edges in the image.
			IMG_FILTER_EMBOSS: Embosses the image.
			IMG_FILTER_GAUSSIAN_BLUR: Blurs the image using the Gaussian method.
			IMG_FILTER_SELECTIVE_BLUR: Blurs the image.
			IMG_FILTER_MEAN_REMOVAL: Uses mean removal to achieve a "sketchy" effect.
			IMG_FILTER_SMOOTH: Makes the image smoother. Use arg1 to set the level of smoothness.
			IMG_FILTER_PIXELATE: Applies pixelation effect to the image, use arg1 to set the block size and arg2 to set the pixelation effect mode.
		
		*/
		
			if(isset($opts['IMG_FILTER_NEGATE']) && !empty($opts['IMG_FILTER_NEGATE'])){

				imagefilter($dst, IMG_FILTER_NEGATE);
			}
			if(isset($opts['IMG_FILTER_GRAYSCALE']) && !empty($opts['IMG_FILTER_GRAYSCALE'])){

				imagefilter($dst, IMG_FILTER_GRAYSCALE);
			}
			if(isset($opts['IMG_FILTER_BRIGHTNESS']) && !empty($opts['IMG_FILTER_BRIGHTNESS'])){

				imagefilter($dst, IMG_FILTER_BRIGHTNESS, intval($opts['IMG_FILTER_BRIGHTNESS']));
			}
			if(isset($opts['IMG_FILTER_CONTRAST']) && !empty($opts['IMG_FILTER_CONTRAST'])){
				
				imagefilter($dst, IMG_FILTER_CONTRAST, intval($opts['IMG_FILTER_CONTRAST']));
			}
			if(isset($opts['IMG_FILTER_COLORIZE']) && !empty($opts['IMG_FILTER_COLORIZE'])){

				if(strlen($opts['IMG_FILTER_COLORIZE']) == 12){

					$arg1 = intval(substr($opts['IMG_FILTER_COLORIZE'], 0, 3));
					$arg2 = intval(substr($opts['IMG_FILTER_COLORIZE'], 3, 3));
					$arg3 = intval(substr($opts['IMG_FILTER_COLORIZE'], 6, 3));
					$arg4 = intval(substr($opts['IMG_FILTER_COLORIZE'], 9, 3));

					imagefilter($dst, IMG_FILTER_COLORIZE, $arg1, $arg2, $arg3, $arg4);
				}

				
			}
			if(isset($opts['IMG_FILTER_EDGEDETECT']) && !empty($opts['IMG_FILTER_EDGEDETECT'])){

				imagefilter($dst, IMG_FILTER_EDGEDETECT);
			}
			if(isset($opts['IMG_FILTER_EMBOSS']) && !empty($opts['IMG_FILTER_EMBOSS'])){
				
				imagefilter($dst, IMG_FILTER_EMBOSS);
			}
			if(isset($opts['IMG_FILTER_GAUSSIAN_BLUR']) && !empty($opts['IMG_FILTER_GAUSSIAN_BLUR'])){
				
				imagefilter($dst, IMG_FILTER_GAUSSIAN_BLUR);
			} //: Reverses all colors of the image.
			if(isset($opts['IMG_FILTER_SELECTIVE_BLUR']) && !empty($opts['IMG_FILTER_SELECTIVE_BLUR'])){
				
				imagefilter($dst, IMG_FILTER_SELECTIVE_BLUR);
			} //: Reverses all colors of the image.
			if(isset($opts['IMG_FILTER_MEAN_REMOVAL']) && !empty($opts['IMG_FILTER_MEAN_REMOVAL'])){
				
				imagefilter($dst, IMG_FILTER_MEAN_REMOVAL);
			} //: Reverses all colors of the image.
			if(isset($opts['IMG_FILTER_SMOOTH']) && !empty($opts['IMG_FILTER_SMOOTH'])){
				
				imagefilter($dst, IMG_FILTER_SMOOTH, intval($opts['IMG_FILTER_SMOOTH']));
			} //: Reverses all colors of the image.
			if(isset($opts['IMG_FILTER_PIXELATE']) && !empty($opts['IMG_FILTER_PIXELATE'])){

				if(strlen($opts['IMG_FILTER_PIXELATE']) > 3){
					$arg1 = intval(substr($opts['IMG_FILTER_PIXELATE'], 0, 3));
					$arg2 = ($opts['IMG_FILTER_PIXELATE'] == true);
				}
				else{
					$arg1 = intval($opts['IMG_FILTER_PIXELATE']);
					$arg2 = false;
				}
				
				imagefilter($dst, IMG_FILTER_PIXELATE, $arg1, $arg2);
			} //: Reverses all colors of the image.

			if(isset($opts['overlay']) && !empty($opts['overlay'])){
				
				  $background = imagecreatefrompng("/Users/Nicolas/Repos/v2.camps-odyssee.com/bk.png");


				  // Defining the overlay image to be added or combined.

				  $insert = imagecreatefrompng("/Users/Nicolas/Repos/v2.camps-odyssee.com/over.png");


				  // Select the first pixel of the overlay image (at 0,0) and use
				  // it's color to define the transparent color

				  imagecolortransparent($insert,imagecolorat($insert,0,0));


				  // Get overlay image width and hight for later use

				  $insert_x = imagesx($insert);
				  $insert_y = imagesy($insert);


				  // Combine the images into a single output image. Some people
				  // prefer to use the imagecopy() function, but more often than 
				  // not, it sometimes does not work. (could be a bug)

				  imagecopymerge($dst,$insert,0,0,0,0,$insert_x,$insert_y,100);

			}

		}
		catch(Exception $e){
			var_dump($e);
		}

		
		ob_start();
		$outputFunction($dst, null, $quality);
		$this->_data = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($src);
		imagedestroy($dst);

		if(isset($opts['mino'])){

			$Magick = new Magick_Filter($this->_data);
			$Magick->filterMino();
			$this->_data = $Magick->save();

		}
		else if(isset($opts['bourg'])){
			$Magick = new Magick_Filter($this->_data);
			$Magick->filterBourg();
			$this->_data = $Magick->save();

		}
		else if(isset($opts['trois'])){
			$Magick = new Magick_Filter($this->_data);
			$Magick->filter3S();
			$this->_data = $Magick->save();
		}

		if(isset($opts['cropx']) && !empty($opts['cropx'])){

			if(!isset($opts['cropy']) || empty($opts['cropy'])){
				$opts['cropy'] = 0;
			}

			$Magick = new Magick_Filter($this->_data);
			$Magick->crop($opts['cropx'], $opts['cropy']);
			$this->_data = $Magick->save();
		}
		else if(isset($opts['cropy']) && !empty($opts['cropy'])){

			if(!isset($opts['cropx']) || empty($opts['cropx'])){
				$opts['cropx'] = 0;
			}

			$Magick = new Magick_Filter($this->_data);
			$Magick->crop($opts['cropx'], $opts['cropy']);
			$this->_data = $Magick->save();
		}

		
		if($config['cache']) $this->saveCache($opts);
		
		return $this->_data;
		
	}
	
	public function saveCache($opts) {
		
		$config = self::getConfig();
		
		$url = str_replace('resizer/','',$_SERVER['REQUEST_URI']);
		$path = $config['cachePath'].$url;
		$directory = dirname($path);
		
		if(!is_writable($config['cachePath'])) return false;
		
		if(!file_exists($directory)) mkdir($directory,0777,true);
		if(!file_exists($path)) file_put_contents($path,$this->_data);

	}
	
	public function getCache() {
		
		$config = self::getConfig();
		
		$url = str_replace('resizer/','',$_SERVER['REQUEST_URI']);
		$path = $config['cachePath'].$url;
		
		if(!file_exists($path)) return false;
		else return file_get_contents($path);
	}
	
	public function render() {
		
		header('Content-type: '.$this->getMIME());
		echo $this->_data;
		
		exit();
	}
	
	public function getMIME() {
		return $this->_mime;
	}
	
	
	public static function findSharp($orig, $final) {
		$final	= $final * (750.0 / $orig);
		$a		= 52;
		$b		= -0.27810650887573124;
		$c		= .00047337278106508946;
		
		$result = $a + $b * $final + $c * $final * $final;
		
		return max(round($result), 0);
	}
	
	
	public static function setConfig($config) {
		self::$_config = $config;
	}
	
	public static function getConfig() {
		return self::$_config;
	}
	
	
}

/*
 *
 * imageconvolution function (if not present)
 *
 *
 */
if(!function_exists('imageconvolution')){ 
	function imageconvolution($src, $filter, $filter_div, $offset){ 
		if ($src==NULL) { 
			return false; 
		} 
		
		$sx = imagesx($src); 
		$sy = imagesy($src); 
		$srcback = imagecreatetruecolor ($sx, $sy); 
		imagecopy($srcback, $src,0,0,0,0,$sx,$sy); 
		
		if($srcback==NULL){ 
			return 0; 
		} 
			
		for ($y=0; $y<$sy; ++$y){ 
			for($x=0; $x<$sx; ++$x){ 
				$new_r = $new_g = $new_b = 0; 
				$alpha = imagecolorat($srcback, $pxl[0], $pxl[1]); 
				$new_a = $alpha >> 24; 
				
				for ($j=0; $j<3; ++$j) { 
					$yv = min(max($y - 1 + $j, 0), $sy - 1); 
					for ($i=0; $i<3; ++$i) { 
							$pxl = array(min(max($x - 1 + $i, 0), $sx - 1), $yv); 
						$rgb = imagecolorat($srcback, $pxl[0], $pxl[1]); 
						$new_r += (($rgb >> 16) & 0xFF) * $filter[$j][$i]; 
						$new_g += (($rgb >> 8) & 0xFF) * $filter[$j][$i]; 
						$new_b += ($rgb & 0xFF) * $filter[$j][$i]; 
					} 
				} 
	
				$new_r = ($new_r/$filter_div)+$offset; 
				$new_g = ($new_g/$filter_div)+$offset; 
				$new_b = ($new_b/$filter_div)+$offset; 
	
				$new_r = ($new_r > 255)? 255 : (($new_r < 0)? 0:$new_r); 
				$new_g = ($new_g > 255)? 255 : (($new_g < 0)? 0:$new_g); 
				$new_b = ($new_b > 255)? 255 : (($new_b < 0)? 0:$new_b); 
	
				$new_pxl = imagecolorallocatealpha($src, (int)$new_r, (int)$new_g, (int)$new_b, $new_a); 
				if ($new_pxl == -1) { 
					$new_pxl = imagecolorclosestalpha($src, (int)$new_r, (int)$new_g, (int)$new_b, $new_a); 
				} 
				if (($y >= 0) && ($y < $sy)) { 
					imagesetpixel($src, $x, $y, $new_pxl); 
				} 
			} 
		} 
		imagedestroy($srcback); 
		return true; 
	}
	
} 







/*
 *
 * imagerotate function (if not present)
 *
 *
 */
if(!function_exists("imagerotate")) {
	function imagerotate($src_img, $angle) {
		
		if (!imageistruecolor($src_img)) {
			$w = imagesx($src_img);
			$h = imagesy($src_img);
			$t_im = imagecreatetruecolor($w,$h);
			imagecopy($t_im,$src_img,0,0,0,0,$w,$h);
			$src_img = $t_im;
		}
		
		$src_x = imagesx($src_img);
		$src_y = imagesy($src_img);
		if ($angle == 180) {
			$dest_x = $src_x;
			$dest_y = $src_y;
		} elseif ($src_x <= $src_y) {
			$dest_x = $src_y;
			$dest_y = $src_x;
		} elseif ($src_x >= $src_y) {
			$dest_x = $src_y;
			$dest_y = $src_x;
		}
		
		$rotate=imagecreatetruecolor($dest_x,$dest_y);
		imagealphablending($rotate, false);
		
		switch($angle) {
			case 270:
				for ($y = 0; $y < ($src_y); $y++) {
					for ($x = 0; $x < ($src_x); $x++) {
						$color = imagecolorat($src_img, $x, $y);
						imagesetpixel($rotate, $dest_x - $y - 1, $x, $color);
					}
				}
			break;
			case 90:
				for ($y = 0; $y < ($src_y); $y++) {
					for ($x = 0; $x < ($src_x); $x++) {
						$color = imagecolorat($src_img, $x, $y);
						imagesetpixel($rotate, $y, $dest_y - $x - 1, $color);
					}
				}
			break;
			case 180:
				for ($y = 0; $y < ($src_y); $y++) {
					for ($x = 0; $x < ($src_x); $x++) {
						$color = imagecolorat($src_img, $x, $y);
						imagesetpixel($rotate, $dest_x - $x - 1, $dest_y - $y - 1,$color);
					}
				}
			break;
			default: $rotate = $src_img;
		};
		
		return $rotate;
	}
}