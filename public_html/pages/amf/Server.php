<?php


class Amf_Server {
	
	protected $_keySignature = '$djlk9mfl;_xKk';
	
	public function test($obj) {
		
		$this->_verifySignature($obj->signature,$obj->timestamp);
		
		$return = new StdClass();
		$return->name = $obj->data->name;
		return $return;	
	}
	
	protected function _verifySignature($signature,$timestamp) {
		
		if($signature != md5($timestamp.$this->_keySignature)) {
			throw new Exception('Bad signature');	
		} else {
			return true;
		}
		
	}
	
}