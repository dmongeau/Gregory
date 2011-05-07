<?php

/*
 *
 * Gregory.php
 *
 * Single file webapp framework written in PHP
 *
 * http://gentlegreg.org
 *
 * @version 0.1
 * @author David Mongeau-Petitpas <dmp@commun.ca>
 *
 */

define('PATH_GREGORY',dirname(__FILE__));


class Gregory {
	
	protected static $_app;
	protected static $_initialized = false;
	protected static $_sharedMemory;
	protected static $_paths;
	public static $defaultConfig = array(
		'layout' => null,
		'path' => array(
			'pages' => null,
			'plugins' => null
		),
		'route' => array(
			'wildcard' => '*',
			'urlDelimiter' => '/',
			'paramsPrefix' => ':'
		),
		'debug' => array(
			'stats' => true
		),
		'error' => array(
			'404' => null,
			'500' => null
		),
	);
	
	
	protected $_bootstrapped = false;
	protected $_config = array();
	
	protected $_route;
	protected $_routes = array();
	protected $_params = array();
	
	protected $_errors = null;
	protected $_stats = array();
	
	protected $_actions;
	protected $_filters;
	
	protected $_plugins = array();
	protected $_pluginsBootstrap = array();
	protected $_pluginsStandby = array();
	
	protected $_page;
	protected $_data = array();
	protected $_head;
	protected $_scripts = array();
	protected $_stylesheets = array();
	
	public function __construct($config = array()) {
		
		try {
			
			//Put instance in static property
			self::set($this);
			
			
			//Execution time
			$this->_setStats('startTime',(float) array_sum(explode(' ',microtime())));
			
			
			//Set global configuration
			$this->setConfig(array_merge(self::$defaultConfig,$config));
			
			
			//Retrieve errors from session
			$this->_errors = $this->session('errors');
			
			
			//Initialize static Gregory
			self::init();
			
			
			//Update usage stats
			$this->_refreshUsageStats();
			
		} catch(Exception $e) {
			$this->catchError($e);
		}
		
	}
	
	
	public static function init() {
		
		try {
		
			if(!self::$_initialized) {
				
				
				self::_bootstrapSharedMemory();
				
				
				self::$_initialized = true;
			}
		
		} catch(Exception $e) {
			self::error(500);
		}
	}
	
	
	public function bootstrap($modules = array()) {
		
		try {
			$this->_bootstrapPlugins();
			
			$this->doAction('bootstrap');
			$this->_bootstrapped = true;
			
			$this->_refreshUsageStats();
		} catch(Exception $e) {
			$this->catchError($e);
		}
	}
	
	public function run($url = null) {
		
		try {
			$url = !isset($url) ? $_SERVER['REQUEST_URI']:$url;
			
			//Route
			if($this->hasRoutes()) {
				$route = $this->route($url);
				$this->setRoute($route);
				$params = array();
				if(is_array($route) && sizeof($route['route'])) {
					
					if(isset($route['params']) && sizeof($route['params'])) {
						$this->setParams($route['params']);
						$params = $route['params'];
					}
					
					if(isset($route['route']['layout'])) {
						$this->setConfig('layout', $route['route']['layout']);
					}
					
					if(isset($route['route']['function'])) {
						$return = call_user_func_array($route['route']['function'],array($route));
						if($return === false) $this->error(404);
					}
					
					if(isset($route['route']['page'])) {
						$this->setPage($route['route']['page']);
						$this->runPage();
					}
					
				} else if($route === false) {
					
					$this->error(404);
					
				}
			}
			
			$this->doAction('run');
			
			$this->_refreshUsageStats();
			
		} catch(Exception $e) {
			$this->catchError($e);
		}
		
	}
	
	public function render($return = false) {
		
		try {
			
			$data = $this->getData();
			$data['head'] = $this->dofilter('render.head',$this->getHead());
			$data['scripts'] = $this->dofilter('render.scripts',$this->getScriptsAsHTML());
			$data['stylesheets'] = $this->dofilter('render.stylesheets',$this->getStylesheetsAsHTML());
			$content = $this->dofilter('render.content',$this->getContent());
			
			
			if($layout = $this->getConfig('layout')) {
				$content = self::template($layout,array('content'=>$content),false);
				$content = self::template($content,$data);
			}
			
			$this->doAction('render');
			
			$this->_refreshUsageStats();
			
			if(!$return) echo $content;
			else return $content;
			
			if($this->getConfig('debug.stats') === true) $this->printStats();
			
		} catch(Exception $e) {
			$this->catchError($e);
		}
	}
	
	/*
	 *
	 * Config
	 *
	 */
	public function setConfig($config, $value = null) {
		if(!isset($value) && is_array($config)) $this->_config = $config;
		elseif(isset($value) && !is_array($config)) $this->_config[$config] = $value;
		
	}
	
	public function getConfig($key = null) {
		if(!isset($key)) return $this->_config;
		elseif(isset($this->_config[$key])) return $this->_config[$key];
		elseif(!empty($key) && strpos($key,'.') !== false) {
			$parts = explode('.',$key);
			$lastPart = $this->_config;
			for($i = 0; $i < sizeof($parts); $i++) {
				if(isset($lastPart[$parts[$i]])) $lastPart = $lastPart[$parts[$i]];
				else return null;
			}
			return $lastPart;
		}
		return null;
	}
	
	
	/*
	 *
	 * Page
	 *
	 */
	public function setPage($page, $run = false) {
		$path = $this->getConfig('path.pages').'/';
		$filename = self::nameToFilename($page);
		$this->_page = self::absolutePath($filename,array($path));
		if($run) $this->runPage();
	}
	
	public function getPage() {
		return $this->_page;
	}
	
	public function setContent($content) {
		$this->_content = $content;
	}
	
	public function getContent() {
		return $this->_content;
	}
	
	public function setData($data, $value = null) {
		if(!is_array($data) && isset($value)) $this->_data[$data] = $value;
		else if(is_array($data)) $this->_data = $data;
	}
	
	public function getData() {
		return $this->_data;
	}
	
	public function runPage() {
		
		$page = $this->dofilter('run.page',$this->getPage());
		
		$data = $this->getData();
		
		$content = self::renderFile($page,array('data'=>$data));
		
		$data = array_merge($data,$this->getData());
		
		if(isset($content) && !empty($content)) {
			$content = self::template($content,$data);
			$this->setContent($this->dofilter('run.content',$content));
		}	
	}
	
	public function renderFile($file,$vars = array()) {
		
		if(sizeof($vars) && is_array($vars)) extract($vars);
		
		ob_start();
		include	$file;
		$content = ob_get_clean();
		
		return $content;
		
	}
	
	public function getHead() {
		return $this->_head;
	}
	
	public function setHead($head) {
		$this->_head = $head;
	}
	
	public function addScript($script) {
		$this->_scripts[] = $script;
	}
	
	public function addStylesheet($stylesheet) {
		$this->_stylesheets[] = $stylesheet;
	}
	
	public function clearScript() {
		$this->_scripts = array();
	}
	
	public function clearStylesheet() {
		$this->_stylesheets = array();
	}
	
	public function getScriptsAsHTML() {
		
		$lines = array();
		foreach($this->_scripts as $script) {
			$lines[] = '<script type="text/javascript" src="'.$script.'"></script>';
		}
		return implode("\n",$lines);
	}
	
	public function getStylesheetsAsHTML() {
		
		$lines = array();
		foreach($this->_stylesheets as $stylesheet) {
			$lines[] = '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'"/>';
		}
		return implode("\n",$lines);
	}
	
	
	/*
	 *
	 * Routes
	 *
	 */
	public function route($url,$defaults = array()) {
			
		$routes = $this->getRoutes();
		$delimiter = $this->getConfig('route.urlDelimiter');
		$url = strpos($url,'?') !== false ? substr($url,0,strpos($url,'?')):$url;
		$url = trim($url,$delimiter);
		$urlParts = explode($delimiter,$url);
		
		if(isset($routes) && sizeof($routes)) {
			foreach($routes as $regex => $route) {
				
				$match = true;
				$params = array();
				
				for($i = 0; $i < sizeof($route['parts']); $i++) {
					
					$wildcard = false;
					$u = isset($urlParts[$i]) ? $urlParts[$i]:null;
					$part = $route['parts'][$i];
					
					if(!isset($u)) {
						$match = false;
					} else if(substr($part,0,1) == $this->getConfig('route.paramsPrefix') && strlen($part) > 1) {
						if(strpos($part,'.') !== false) {
							$pos = strpos($u,'.');
							if($pos === false) $match = false;
							else {
								$uext = strtolower(substr($u,$pos));
								$u = substr($u,0,$pos);
								
								$pos = strpos($part,'.');
								$ext = strtolower(substr($part,$pos));
								$name = substr($part,1,$pos-1);
								
								if($ext != $uext) $match = false;
								else $params[$name] = $u;
							}
							
						} else {
							$name = substr($part,1);
							$params[$name] = $u;
						}
					} else if($part == $this->getConfig('route.wildcard')) {
						$wildcard = array_slice($urlParts,$i);
						$params['wildcard'] = implode($this->getConfig('route.urlDelimiter'),$wildcard);
						$wildcard = true;
					} else if(!preg_match('/^'.preg_quote($part).'$/i',$u,$matches)) {
						$match = false;
					}
					
				}
				
				if(sizeof($route['parts'])  != sizeof($urlParts) && !$wildcard) $match = false;
				
				if($match) {
					
					$return = array(
						'url' => $url,
						'regex' => $regex,
						'route' => $route,
						'params' => $params
					);
					
					return $return;
					
				}
				
			}
			return false;
		}
		
		return null;
			
	}
	
	public function hasRoutes() {
		return !isset($this->_routes) || !sizeof($this->_routes) ? false:true;
	}
	
	public function getRoute() {
		return $this->_route;
	}
	
	public function setRoute($route) {
		$this->_route = $route;
	}
	
	public function getRoutes() {
		return $this->_routes;
	}
	
	public function addRoute($routes,$value = null) {
		$routes = is_array($routes) ? $routes:array($routes=>$value);
		
		$delimiter = $this->getConfig('route.urlDelimiter');
		
		foreach($routes as $regex => $route) {
			$route = (is_array($route) ? $route:array('page'=>$route));
			$route['parts'] = explode($delimiter,trim($regex,$delimiter));
			$this->_routes[$regex] = $route;
		}
	}
	
	public function clearRoute() {
		$this->_routes = array();
	}
	
    public function setParams($name,$value = null) {
        if(is_array($name)) $this->_params = $name;
		else if(isset($value)) $this->_params[$name] = $value;
    }

    public function getParams($name = null) {
        if(!isset($name)) return $this->_params;
		else return isset($this->_params[$name]) ? $this->_params[$name]:null;
    }
	
    public function __get($name) {
		$res = $this->getPlugin($name);
		
        if($res === null && $this->hasPlugin($name)) {
			$res = $this->initPlugin($name);
			$this->setPlugin($name,$res);
		}
		
		return $res;
    }
	
	
	 /*
     *
     * Méthodes relatives aux plugins
     *
     */
    public function addPlugin($name, $config = array(), $standby = true) {
		
		$path = $this->getConfig('path.plugins');
		
		$plugin = array();
		$plugin['name'] = strpos($name,'/') !== false ? substr($name,0,strpos($name,'/')):$name;
		$plugin['file'] = self::absolutePath(self::nameToFilename($name),array($path));
		$plugin['config'] = $config;
		$name = $plugin['name'];
		
		if(!file_exists($plugin['file'])) {
			return false;
		}
		
		$plugin = $this->doFilter('plugin.add',$plugin);
		
        if($standby) $this->_pluginsStandby[$name] = $plugin;
		else if(!$this->_bootstrapped) $this->_pluginsBootstrap[$name] = $plugin;
		else {
			$plugin = include $plugin['file'];
			$this->_plugins[$name] = $plugin;
		}
		
    }
	
    public function setPlugin($name,$value) {
        $this->_plugins[$name] = $value;
    }

    public function getPlugin($name) {
        return isset($this->_plugins[$name]) ? $this->_plugins[$name]:null;
    }

    public function hasPlugin($name) {
        return isset($this->_plugins[$name]) || isset($this->_pluginsStandby[$name]) ? true:false;
    }
	
	public function initPlugin($name) {
		if(isset($this->_pluginsStandby[$name])) {
			$config = $this->_pluginsStandby[$name]['config'];
			$plugin = include $this->_pluginsStandby[$name]['file'];
			unset($this->_pluginsStandby[$name]);
			return $plugin;
		}
		
		return null;
	}
	
	protected function _bootstrapPlugins() {
		if(isset($this->_pluginsBootstrap) && sizeof($this->_pluginsBootstrap)) {
			foreach($this->_pluginsBootstrap as $name => $plugin) {
				$config = $this->_pluginsBootstrap[$name]['config'];
				$plugin = include $plugin['file'];
				unset($this->_pluginsBootstrap[$name]);
				$this->_plugins[$name] = $plugin;
			}
		}
	}
	
	
	
	/*
     *
     * Hook system
     *
     */
	
	public function addAction($action, $function, $params = array()) {
		
		if(!isset($this->_actions[$action])) $this->_actions[$action] = array();
		$this->_actions[$action][] = array(
			'function' => $function,
			'params' => $params
		);
		
	}
	
    public function doAction($action,$params = array()) {
		if(isset($this->_actions[$action])) {
			foreach($this->_actions[$action] as $a) {
				//if(sizeof($a['params'])) {
					//call_user_func_array($a['function'],$a['params']);
				if(sizeof($params)) {
					call_user_func_array($a['function'],$params);
				} else {
					call_user_func_array($a['function'],array());
				}
			}
		}
    }
	
	
	public function addFilter($filter, $function) {
		
		if(!isset($this->_filters[$filter])) $this->_filters[$filter] = array();
		$this->_filters[$filter][] = array(
			'function' => $function
		);
		
	}
	
    public function doFilter($filter,$input) {
		if(isset($this->_filters[$filter])) {
			foreach($this->_filters[$filter] as $a) {
				$input = call_user_func($a['function'],$input);
			}
		}
		
		return $input;
    }
	
	
	/*
     *
     * Errors handling
     *
     */
	
	public function catchError($exception) {
		
		if(is_a($exception,'Zend_Exception') || $exception->getCode() == 500) {
			$this->error(500);
		} else {
			$this->addError($exception->getMessage(), $exception->getCode(), $exception);
		}
		
	}
	
	public function addError($error, $type = null, $exception = null) {
		
		$error = array(
			'message' => $error
		);
		if($type) $error['type'] = $type;
		if($exception) $error['exception'] = $exception;
		
		if(!isset($this->_errors)) $this->_errors = array();
		
		$this->_errors[] = $error;
		$this->session('errors',$this->_errors);
		
	}
	
	public function getErrors($cleanAfter = true) {
		
		$errors = $this->_errors;
		
		if($cleanAfter) {
			$this->_errors = array();
			$this->session('errors',null);
		}
		
		return $errors;
		
	}
	
	public function displayErrors($cleanAfter = true) {
		
		$errors = $this->getErrors($cleanAfter);
		
		$html = array();
		foreach($errors as $error) {
			$html[] = $error['message'];
		}
		
		return '<ul>'.implode("\n",$html).'</ul>';
		
	}
	
	public function hasErrors() {
		
		if(isset($this->_errors) && sizeof($this->_errors)) return true;
		else return false;
		
	}
	
	public function error($code = 500) {
		
		$this->doAction('error.'.$code);
		
		//header("HTTP/1.0 404 Not Found");
		header('Content-type: text/html; charset="utf-8"');
		
		$file = $this->getConfig('error.'.$code);
		//if(file_exists($file)) echo file_get_contents($file);
		//exit();
		
		$this->setPage($file,true);
		
		
	}
	

	/*
	 *
	 * Stats
	 *
	 */
	protected function _setStats($data, $value = null) {
		if(!isset($value) && is_array($data)) $this->_stats = $data;
		elseif(isset($value) && !is_array($data)) $this->_stats[$data] = $value;
		
	}
	
	protected function _refreshUsageStats() {
		//$this->_setStats('maxMemory',round(memory_get_peak_usage(true)/(1024*1024),2).' mb');
		//$this->_setStats('maxMemory',memory_get_peak_usage(true).' mb');
		$this->_setStats('maxMemory',round(memory_get_peak_usage()/1024,2).' kb');
		$this->_setStats('endTime',(float) array_sum(explode(' ',microtime())));
		$this->_setStats('executionTime',round(($this->getStats('endTime') - $this->getStats('startTime'))*1000,2).' msec.');
	}
	
	public function getStats($key = null) {
		if(!isset($key)) return $this->_stats;
		elseif(isset($this->_stats[$key])) return $this->_stats[$key];
		elseif(!empty($key) && strpos($key,'.') !== false) {
			$parts = explode('.',$key);
			$lastPart = $this->_stats;
			for($i = 0; $i < sizeof($parts); $i++) {
				if(isset($lastPart[$parts[$i]])) $lastPart = $lastPart[$parts[$i]];
				else return null;
			}
			return $lastPart;
		}
		return null;
	}
	
	public function printStats() {
		
		$stats = $this->getStats();
		
		unset($stats['startTime']);
		unset($stats['endTime']);
		
		echo '<!--'."\n\n";
		echo '    Gregory Stats'."\n\n";
		$content = print_r($stats,true);
		echo substr($content,8,strlen($content)-10);
		echo "\n".'-->';
	}
	
	
	
	/*
     *
     * Méthodes statiques pour un accès global à l'application
     *
     */
    public static function get() {
        return self::$_app;
    }

    public static function set(&$app) {
        self::$_app = $app;
    }
	
	
	
	
	/*
	 *
	 * Statics methods for shared memory
	 *
	 */
	protected static function _bootstrapSharedMemory() {
		
		$key = ftok(__FILE__,'g');
		self::$_sharedMemory = array(
			'key' => $key,
			'shm' => shm_attach($key, 50000),
			'mutex' => sem_get($key, 1),
			'data' => array()
		);
		
		self::refreshSharedMemory();
		
	}
	
	protected static function refreshSharedMemory($key = null) {
		
		sem_acquire(self::$_sharedMemory['mutex']);
		$data = @shm_get_var(self::$_sharedMemory['shm'], self::$_sharedMemory['key']);    
		sem_release(self::$_sharedMemory['mutex']);
		
		$data = @unserialize($data);
		
		self::$_sharedMemory['data'] = isset($data) && sizeof($data) ? $data:array();
	}
	
	protected static function getSharedData($key = null) {
		
		if(!isset($key)) return self::$_sharedMemory['data'];
		else if(isset($key) && isset(self::$_sharedMemory['date'][$key])) return self::$_sharedMemory['data'][$key];
		else return null;
	}
	
	protected static function setSharedData($data, $value = null) {
		
		if(isset($value)) {
			self::$_sharedMemory['data'][$data] = $value;
		} else {
			self::$_sharedMemory['data'] = $data;
		}
		
		$data = serialize(self::$_sharedMemory['data']);
		
		sem_acquire(self::$_sharedMemory['mutex']);
		shm_put_var(self::$_sharedMemory['shm'], self::$_sharedMemory['key'], $data);
		sem_release(self::$_sharedMemory['mutex']);
		
		
	}
	
	
	
	/*
     *
     * Méthodes statiques utilitaire
     *
     */
	
	public static function template($layout, $data = array(),$clean = true) {
		if(strlen($layout) < 1024 && file_exists($layout)) {
			$layout = Gregory::get()->renderFile($layout);
		}
		$html = $layout;
		if(isset($data) && is_array($data)) {
			foreach($data as $key => $content) {
				if(is_array($content)) {
					
				} else {
					$html = str_replace('%{'.strtoupper($key).'}',$content,$html);
				}
			}
		}
		if($clean) $html = preg_replace('/\%\{[^\}]+\}/','',$html);
		
		return $html;		
	}
	
    public static function nameToFilename($name, $ext = 'php') {
    	if(strpos($name,'.') === false) return $name.'.'.$ext;	
		else return $name;
    }
	
    public static function absolutePath($file,$paths = array()) {
		
		if(isset(self::$_paths[$file])) return self::$_paths[$file];
		
		if(file_exists($file)) {
			self::$_paths[$file] = $file;
			return $file;
		}
		
		$currentPath = dirname(__FILE__);
    	if(!in_array($currentPath, $paths)) $paths[] = $currentPath;
		foreach($paths as $path) {
			$path = rtrim($path,'/');
			$path = $path.'/'.$file;
			if(file_exists($path)) {
				self::$_paths[$file] = $path;
				return $path;
			}
		}
		return false;
    }
	
	
	/*
     *
     * Request functions
     *
     */
	
	public static function redirect($url, $code = 301) {
		
		header('Location: '.$url,true,$code);
		exit();
			
	}
	
	public static function isAJAX() {
		
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return true;
		}
		
		return false;
			
	}
	
	public static function JSON($obj, $return = false) {
		
		$json = json_encode($obj);
		
		if(!$return) {
			header('Content-type: text/plain; charset="utf-8"',true);
			echo $json;
			exit();
		} else {
			return $json;
		}
			
	}
	
	
	/*
     *
     * Gregory session
     *
     */
	
	
	public function session($key) {
		
		if(class_exists('Zend_Session')) Zend_Session::start();
		else session_start();
		
		if(func_num_args() == 2) {
			if(class_exists('Zend_Session')) {
				$session = new Zend_Session_Namespace('Gregory');
				$session->$key = func_get_arg(1);
			} else {
				$_SESSION['Gregory_'.$key] = func_get_arg(1);
			}
		} else {
			if(class_exists('Zend_Session')) {
				$session = new Zend_Session_Namespace('Gregory');
				return isset($session->$key) ? $session->$key:null;
			} else {
				return isset($_SESSION['Gregory_'.$key]) ? $_SESSION['Gregory_'.$key]:null;
			}
		}
		
	}
	
	
	
	/*
     *
     * Autoload class
     *
     */
	
	public static function _autoload($class) {
		
		if(strtolower(substr($class,0,4)) == 'zend') {
		
			/*//Zend Framework
			$paths = array();
			if(defined(PATH_ZEND)) $paths[] = PATH_ZEND;
			$path = Gregory::absolutePath('Zend/', $paths);
			
			if($path) $path = trim(str_replace('/Zend', '', $path), '/');
			else return false;
			
			$file = '/'.$path.'/'.str_replace('_','/',$class).'.php';
			if (!file_exists($file)) return false;*/
			$file = str_replace('_','/',$class).'.php';
			require_once $file;
			
		} else {
			return false;
		}	
	}
		
}



define('PATH_ZEND',PATH_GREGORY);
set_include_path(get_include_path().PATH_SEPARATOR.PATH_ZEND);
spl_autoload_register(array('Gregory','_autoload'));
