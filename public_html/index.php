<?php

echo memory_get_usage() . "\n"; 

define('PATH_ROOT',dirname(__FILE__));
define('PATH_PAGES',dirname(__FILE__).'/pages');
define('PATH_PLUGINS',dirname(__FILE__).'/../Gregory/plugins');

require PATH_ROOT.'/../Gregory/Gregory.php';
$config = include PATH_ROOT.'/config.php';

$app = new Gregory($config);

$app->addPlugin('db',array(
	'adapter' => 'pdo_mysql',
	'config' => array(
		'host' => 'localhost',
		'username' => 'pubmtl',
		'password' => 'RvaEhpLXuzCA6QJj',
		'dbname' => 'pubmtl'
	)
));

$app->addRoute(array(
	'/' => 'home.html',
	'/server.amf' => array(
		'page' => 'amf/amf.php',
		'layout' => false
	),
	'/amf/test.html' =>  array(
		'page' => 'amf/test.html',
		'layout' => false
	),
	'/jesus/*' => array(
		'page' => 'jesus.html'
	),
));

$app->bootstrap();
$app->run();
$app->render();

echo '<!--';
print_r($app->getStats());
echo '-->';
