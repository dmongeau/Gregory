<?php



class UserAuth {
	
	
	protected $_config;
	
	public $auth;
	public $identity;
	
	
	public function __construct($config = array()) {
		
		$this->setConfig($config);
		
		$this->auth = Zend_Auth::getInstance();
		
		if($this->auth->hasIdentity()) {
			$identity = $this->auth->getIdentity();
			$this->setIdentity($identity);
		}
		
	}
	
	public function login($email,$password, $passwordEncoded = false) {
		
		$config = $this->getConfig();
		
		$email = stripslashes($email);
		$password = stripslashes($password);
		
		$authAdapter = new Zend_Auth_Adapter_DbTable(Gregory::get()->db,$config['table'],$config['identityColumn'],$config['passwordColumn']);
		
		if(!$passwordEncoded) $password = $this->passwordHash($password);
		
		// Set the input credential values to authenticate against
		$authAdapter->setIdentity($email);
		$authAdapter->setCredential($password);
		
		
		if(isset($config['valid'])) {
			$select = $authAdapter->getDbSelect();
			foreach($config['valid'] as $key => $value) {
				$select->where($key.' = ?',$value);
			}
		}
		
		Gregory::get()->doAction('auth.login',array($email,$password));
		
		$result = $this->auth->authenticate($authAdapter);
		if ($result->isValid()) {
			
			$data = $authAdapter->getResultRowObject(null, $config['passwordColumn']);
			
			$data = Gregory::get()->doFilter('auth.login.identity',$data);
			
			$this->auth->getStorage()->write($data);
			if($this->auth->hasIdentity()) $this->setIdentity($this->auth->getIdentity());
		
			if($this->hasIdentity() && isset($config['block'])) {
				$identity  = $this->getIdentity();
				foreach($config['block'] as $key => $value) {
					if(isset($identity->$key) && $identity->$key == $value) {
						$this->logout();
						throw new Exception($config['errors']['blocked']);
					}
				}
			}
			
			Gregory::get()->doAction('auth.login.valid',array($data));

		} else {
			
			Gregory::get()->doAction('auth.login.invalid',array($email,$password));
			
			throw new Exception($config['errors']['invalid']);
			
		}
			
		return $this->getIdentity();
		
	}
	
	public function loginWithIdentity($identity) {
		$identity = (object)$identity;
		$this->auth->getStorage()->write($identity);
		if($this->auth->hasIdentity()) {
			$this->setIdentity($identity);
			Gregory::get()->doAction('auth.login.valid',array($identity));
		}
	}
	
	public function logout() {
		
		Gregory::get()->doAction('auth.logout',array($this->getIdentity()));
		
		$this->auth->clearIdentity();
		if(isset($this->identity->sessions)) $this->identity->sessions->unsetAll();
		$this->identity = null;
	}
	
	
	public function getConfig() {
		return $this->_config;
	}
	
	public function setConfig($config) {
		$this->_config = $config;
	}
	
	
	public function setIdentity($identity) {
		$this->identity = $identity;
		$this->auth->getStorage()->write($identity);
	}
	public function getIdentity() {
		return $this->identity;
	}
	public function hasIdentity() {
		$identity = $this->getIdentity();
		return (isset($identity) && !empty($identity)) ? true:false;
	}
	
	
	public function isLogged() {
		return $this->hasIdentity();
	}
	
	
	public function isRole($role) {
		if(!$this->isLogged() || !$this->hasIdentity()) return false;
		$config = $this->getConfig();
		if(!isset($config['roleColumn'])) return false;
		$identity = $this->getIdentity();
		$col = $config['roleColumn'];
		return isset($identity->{$col}) && $identity->{$col} == $role ? true:false;
	}
	
	public function passwordHash($pwd) {
		
		$config = $this->getConfig();
		
		$mode = isset($config['hashMode']) ? $config['hashMode']:'sha1';
		$saltHash = isset($config['hashSalt']) ? $config['hashSalt']:null;
		
		// hash the text // 
		$textHash = hash ($mode, $pwd);
		
		// set where salt will appear in hash // 
		$saltStart = strlen ($pwd);
		
		// if no salt given create random one // 
		if ($saltHash == null) $saltHash = hash($mode, uniqid(rand(), true));
		
		// add salt into text hash at pass length position and hash it // 
		if ($saltStart > 0 && $saltStart < strlen($saltHash)) {
			$textHashStart = substr($textHash, 0, $saltStart);
			$textHashEnd = substr($textHash, $saltStart, strlen($saltHash));
			$outHash = hash($mode, $textHashEnd.$saltHash.$textHashStart);
		} elseif($saltStart>(strlen($saltHash) - 1)) {
			$outHash = hash($mode, $textHash.$saltHash );
		} else {
			$outHash = hash($mode, $saltHash.$textHash);
		}
		
		// put salt at front of hash // 
		$output = $saltHash.$outHash;
		
		return $output;
	}
	
	
}