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

class ImageResizer {
	
	protected static $_config;
	
	protected $_filename;
	protected $_imagesize;
	protected $_mime;
	protected $_data;
	
	public function __construct($file) {
		
		if (!file_exists($file)) {
			throw new App_Exception('Image not found');
		}
		
		$this->_filename = $file;
		$this->_imagesize = getimagesize($this->_filename);
		$this->_mime = $this->_imagesize['mime'];
		
		if (substr($this->_mime, 0, 6) != 'image/') {
			throw new Exception('Image not supported');
		}
		
	}
	
	public function resize($maxwidth,$maxheight = 0,$opts = array()) {
		
		$config = self::getConfig();
		
		if($config['cache'] && $cache = $this->getCache()) {
			$this->_data = $cache;
			return $this->_data;
		}
		
		if(is_array($maxwidth)) $opts = $maxwidth;
		else {
			$opts['width'] = $maxwidth;
			$opts['height'] = $maxheight;
		}
		
		$width = $this->_imagesize[0];
		$height = $this->_imagesize[1];
		
		if(isset($opts['color'])) $color = preg_replace('/[^0-9a-fA-F]/', '', (string)$opts['color']);
		else $color = FALSE;

		if (!$opts['width'] && $opts['height']) $opts['width']	= 99999999999999;
		elseif ($opts['width'] && !$opts['height']) $opts['height'] = 99999999999999;
		elseif ($color && !$opts['width'] && !$opts['height']) {
			$opts['width']	= $width;
			$opts['height']	= $height;
		}
		
		
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
		
		
		if(isset($opts['crop']) && sizeof($cropRatio) == 2) {
			$dst = imagecreatetruecolor($cropRatio[0], $cropRatio[1]);
		} else {
			$dst = imagecreatetruecolor($tnWidth, $tnHeight);
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
		
		} else {
			
			$destH = $tnHeight;
			$destW = $tnWidth;
			
		}
		
		// Resample the original image into the resized canvas we set up earlier
		imagecopyresampled($dst, $src, 0, 0, $offsetX, $offsetY, $destW, $destH, $width, $height);
		
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
		
		
		ob_start();
		$outputFunction($dst, null, $quality);
		$this->_data = ob_get_contents();
		ob_end_clean();
		
		imagedestroy($src);
		imagedestroy($dst);
		
		if($config['cache']) $this->saveCache();
		
		return $this->_data;
		
	}
	
	public function saveCache() {
		
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
		
		//exit();
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