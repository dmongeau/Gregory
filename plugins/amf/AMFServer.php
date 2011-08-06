<?php



class AMFServer extends Zend_Amf_Server {
	
	protected $_keySignature = '4$h%jfu*1k!';
	protected $_rekeySignature = 'gfh04th0G(&Gho24i';
	protected $_keyExpire = 15;
	
	/*
	 *
	 * Factory function
	 *
	 */
	public function createServer() {
		
		$server = new self();
		
		return $server;	
		
	}
	
	
	public function __construct() {
		
		parent::__construct();
		
		if(!isset($_SESSION[$this->_getSessionNamespace()])) {
			$_SESSION[$this->_getSessionNamespace()] = array(
				'keys' => array(),
				'uuid' => array()
			);
		}
		
	}
	
	
	protected function _dispatch($method, $params = null, $source = null) {
		
		$data = $this->_verifySignature($source.'.'.$method,$params[0]);
		
		$params = array((array)$data);
		
		try {
			$return = parent::_dispatch($method, $params, $source);
		} catch(Zend_Exception $e) {
			throw $e;
		} catch(Exception $e) {
			$return = array(
				'success' => false,
				'error' => $e->getMessage()
			);
		}
		
		if(is_array($return)) {
			$obj = new StdClass();
			foreach($return as $key => $value) {
				$obj->{$key} = $value;	
			}
			$return = $obj;
		}
		
		return $this->_return($return);
		
		
	}
	
	/*
	 *
	 * Return
	 *
	 */
	protected function _return($return) {
		
		$obj = new StdClass();
		$obj->data = $return;
		$obj->key = $this->_generateKey();
		
		return $obj;
		
	}
	
	
	/*
	 *
	 * Return error
	 *
	 */
	protected function _error($error) {
		
		$obj = new StdClass();
		$obj->error = $error;
		$obj->key = $this->_generateKey();
		
		return $obj;
		
	}
	
	/*
	 *
	 * Generate new key
	 *
	 */
	protected function _generateKey() {
		
		$timestamp = microtime(true);
		$key = md5($this->_rekeySignature.'_'.$timestamp);
		
		$obj = new StdClass();
		$obj->timestamp = $timestamp;
		$obj->key = $key;
		$_SESSION[$this->_getSessionNamespace()]['keys'][$key] = $obj;
		
		return $obj;
		
	}
	
	
	/*
	 *
	 * Verify request signature
	 *
	 */
	protected function _verifySignature($method,$obj) {
		
		if(strpos($method,'::') !== false) $method = str_replace('::','.',$method);
		else if(strpos($method,'.') === false) $method = get_class($this).'.'.$method;
		
		$this->_cleanExpiredKeys();
		
		$timestamp = $obj->timestamp;
		$signature = $obj->signature;
		
		$sig = md5($method.$timestamp.$this->_keySignature);
		if(
			(!sizeof($_SESSION[$this->_getSessionNamespace()]['keys']) && $signature == $sig) || 
			(!in_array($obj->uuid,$_SESSION[$this->_getSessionNamespace()]['uuid']) && $signature == $sig)
		) {
			
			$_SESSION[$this->_getSessionNamespace()]['uuid'][] = $obj->uuid;
			return $obj->data;
			
		} else if((int)$timestamp >= (time()-$this->_keyExpire)) {
			
			foreach($_SESSION[$this->_getSessionNamespace()]['keys'] as $key) {
				if($signature == md5($method.$timestamp.$key->key)) {
					return $obj->data;
				}
			}
			
		}
		
		//throw new Exception('Bad signature');
		
		return $obj->data;
		
	}
	
	
	
	/*
	 *
	 * Clean expired keys
	 *
	 */
	protected function _cleanExpiredKeys() {
		
		$newKeys = array();
		foreach($_SESSION[$this->_getSessionNamespace()]['keys'] as $key) {
			if((float)$key->timestamp >= (microtime(true)-$this->_keyExpire)) {
				$newKeys[$key->key] = $key;
			}
		}
		$_SESSION[$this->_getSessionNamespace()]['keys'] = $newKeys;
	}
	
	
	/*
	 *
	 * Get session namespace
	 *
	 */
	protected function _getSessionNamespace() {
		return 	'amf_'.get_class($this);
	}
	
	
}