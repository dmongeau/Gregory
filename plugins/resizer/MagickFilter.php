<?php
/*

Add custom Imagick filters here

*/

class Magick_Filter{


	private $image;

	public function __construct($data) {

		try{
	        $this->image = new Imagick();
	        $this->image->readImageBlob($data);
    	}
    	catch(Exception $e){
    		var_dump($e);
    		exit();
    	}
    }

    public function getBlob(){
    	return $this->image->getImageBlob();
    }

	public function filter3S(){

		$over = new Imagick(PATH_ROOT.'/../app/Gregory/plugins/resizer/extras/3S.png');

		$d = $this->image->getImageGeometry(); 
		$w = $d['width'];
		$h = $d['height'];

		$over->thumbnailImage($w, $h);

		$this->image->compositeImage($over,imagick::COMPOSITE_DEFAULT, 0, 0);
	}

	public function filterBourg(){
		$this->image->modulateImage(100, 140, 100);
	}

	public function filterMino(){

		$over = new Imagick(PATH_ROOT.'/../app/Gregory/plugins/resizer/extras/Mino.png');

		$d = $this->image->getImageGeometry(); 
		$w = $d['width'];
		$h = $d['height'];

		$over->thumbnailImage($w, $h);

		$this->image->compositeImage($over, imagick::COMPOSITE_DEFAULT, 0, 0);

		$this->image->modulateImage(120, 60, 100);

		$this->image->contrastImage(1);
	}

	public function resize(){

		//unneeded
	}

	public function crop($x, $y, $maxwidth = 410, $maxheight = 275){

		$d = $this->image->getImageGeometry(); 
		$w = $d['width'];
		$h = $d['height'];


		$width = 0;
		$height = 0;

		if($w > $maxwidth){
			$width = 410;
		}

		if($h > $maxheight){
			$height = 275;
		}

		$this->image->cropImage($maxwidth,$maxheight,$x,$y);

		$d = $this->image->getImageGeometry(); 
		$w = $d['width'];
		$h = $d['height'];

	}
	/*
	public function brightness($pct){

		$this->image->modulateImage(100 + $pct, 100, 100);
	}
	*/
}
