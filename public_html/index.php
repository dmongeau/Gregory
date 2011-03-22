<?php

define('PATH_ROOT',dirname(__FILE__));
define('PATH_PAGES',dirname(__FILE__).'/pages');
define('PATH_PLUGINS',dirname(__FILE__).'/../Gregory/plugins');


$config = array(

	'layout' => PATH_PAGES.'/_layout.html',
	
	'path' => array(
		'pages' => PATH_PAGES,
		'plugins' => PATH_PLUGINS
	),
	
	'error' => array(
		'404' => PATH_PAGES.'/404.html'
	)
	
);


require PATH_ROOT.'/../Gregory/Gregory.php';

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


