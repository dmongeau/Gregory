<?php




class Twitter {
	
	
	protected $_options = array();
	protected $_accessToken;
	protected $_client;
	
	
	public function __construct($options) {
		
		$this->_options = $options;
		
		$this->_accessToken = new Zend_Oauth_Token_Access();
		
	}
	
	
	public function setToken($token,$secret) {
		
		$this->_accessToken->setToken($token)->setTokenSecret($secret);
		
	}
	
	
	public function getClient() {
		
		if(!$this->_client) {
			$this->_client = new Zend_Service_Twitter();
			$this->_client->setLocalHttpClient($this->_accessToken->getHttpClient($this->_options));
		}
		
		return $this->_client;
		
		
	}
	
	
}