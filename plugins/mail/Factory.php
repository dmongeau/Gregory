<?php




class Mail_Factory {
	
	public $from;
	
	public function __construct($from = null) {
		$this->from = $from;
	}
	
	public function create($subject, $to) {
		
		    $Mail = new Zend_Mail("utf-8");
			
			if(isset($this->from)) {
				if(is_array($this->from)) {
					$keys = array_keys($this->from);
					$Mail->setFrom($this->from[$keys[0]],$keys[0]);
				} else {
					$Mail->setFrom($this->from);
				}
			}
			
			if(is_array($to)) {
				$keys = array_keys($to);
				$Mail->addTo($to[$keys[0]],$keys[0]);
			} else {
				$Mail->addTo($to);
			}
			
			$Mail->setSubject($subject);
			
			return $Mail;
		
	}
	
	
}