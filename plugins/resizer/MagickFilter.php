<?php
/*

Fonctionne uniquement avec ImageMagick installé as a plugin

//Applique un des filtres des camps et sauvegarde à un endroit x avec une extension x du filename
PATH_ROOT.'/../public_html/statics/photos/

*/

class Magick_Filter{

	//private $path = "";
	//private $pathOverlay = "";
	//private $subext = "";
	//private $filename = "";

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

    public function save(){

    	return $this->image->getImageBlob();

    }


	public function filter3S(){

		$over = new Imagick(PATH_ROOT.'/../app/Gregory/plugins/resizer/3S.png');

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

		$over = new Imagick(PATH_ROOT.'/../app/Gregory/plugins/resizer/Mino.png');

		$d = $this->image->getImageGeometry(); 
		$w = $d['width'];
		$h = $d['height'];

		$over->thumbnailImage($w, $h);

		$this->image->compositeImage($over, imagick::COMPOSITE_DEFAULT, 0, 0);

		$this->image->modulateImage(120, 60, 100);

		$this->image->contrastImage(1);

	}

	public function resize(){

		//$this->image->thumbnailImage(410, 275);
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

		//echo($w);
		//echo($h);
		//exit();

	}
	/*
	public function brightness($pct){

		$this->image->modulateImage(100 + $pct, 100, 100);
	}
	*/
}
