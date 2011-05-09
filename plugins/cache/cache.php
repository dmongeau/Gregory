<?php


$config = array_merge(array(
	
	'cacheDir' => dirname(__FILE__).'/_cache',
	'cache' => array(
		
		'core' => array(
			'frontend' => array(
				'name' => 'Core'
			),
			'backend' => array(
				'name' => 'File'
			)
		)
	
	),
	
	'cacheTemplate' => array(
		'frontend' => array(
			'name' => 'Core',
			'options' => array(
				'lifetime' => null,
				'automatic_serialization' => true
			)
		),
		'backend' => array(
			'name' => 'File',
			'options' => array()
		)
	),
	
	'errors' => array(
		'notWriteable' => 'Le dossier n\'a pas les permissions d\'écriture'
	)
	
),$config);

if(!is_writeable($config['cacheDir'])) {
	throw new Exception($config['errors']['notWriteable'].' ('.$config['cacheDir'].')');
}

$manager = new Zend_Cache_Manager();

foreach($config['cache'] as $cache => $cacheConfig) {
	
	$cacheConfig = array_merge_recursive($config['cacheTemplate'],$cacheConfig);
	if(!isset($cacheConfig['backend']['options']['cache_dir']) || empty($cacheConfig['backend']['options']['cache_dir'])) {
		$path = $config['cacheDir'].'/'.$cache;
		$cacheConfig['backend']['options']['cache_dir'] = $path;
	} else {
		$path = $cacheConfig['backend']['options']['cache_dir'];
	}
	
	if(!file_exists($path)) mkdir($path,0755,true);
	
	if(!is_writeable($path)) throw new Exception($config['errors']['notWriteable'].' ('.$path.')');
	
	$manager->setCacheTemplate($cache, $cacheConfig);
	
}

return $manager;