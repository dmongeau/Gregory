<?php


class Amf_Server {
	
	protected $_keySignature = '$djlk9mfl;_xKk';
	
	public function __construct() {
		
		
		
	}
	
	public function getAgencies($obj) {
		
		$this->_verifySignature($obj->signature,get_class($this).'.getAgencies',$obj->timestamp);
		
		$db = Gregory::get()->db;
		$items = $db->fetchAll($db->select()->from(array('a'=>'agencies'),array('*')));
		//var_dump($items);
		//exit();
		$return = new StdClass();
		$return->items = $items;
		return $return;
		
	}
	
	public function test($obj) {
		
		$this->_verifySignature($obj->signature,get_class($this).'.test',$obj->timestamp);
		
		$return = new StdClass();
		$return->name = $obj->data->name;
		$return->type = $obj->data->type;
		$return->data = 'blablablabla';
		return $return;	
	}
	
	protected function _verifySignature($signature,$method,$timestamp) {
		
		if($signature != md5($method.$timestamp.$this->_keySignature)) {
			throw new Exception('Bad signature');	
		} else {
			return true;
		}
		
	}
	
}