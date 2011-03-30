<?php

define('PATH_ROOT',dirname(__FILE__));
define('PATH_PAGES',dirname(__FILE__).'/pages');
define('PATH_PLUGINS',dirname(__FILE__).'/../plugins');


$config = array(

	'layout' => PATH_PAGES.'/_layout.html',
	
	'path' => array(
		'pages' => PATH_PAGES,
		'plugins' => PATH_PLUGINS
	),
	
	'error' => array(
		'404' => PATH_PAGES.'/404.html',
		'500' => PATH_PAGES.'/500.html'
	)
	
);


require PATH_ROOT.'/../Gregory.php';

$app = new Gregory($config);

$app->addPlugin('db',array(
	'adapter' => 'pdo_mysql',
	'config' => array(
		'host' => 'localhost',
		'username' => 'test',
		'password' => 'RvaEhpLXuzCA6QJj',
		'dbname' => 'test'
	)
));

$app->addPlugin('resizer/resizer.php',array(
	'path' => dirname(__FILE__).'/statics/photos',
	'cachePath' =>dirname(__FILE__).'/statics/photos/_cache'
),false);


$app->addRoute(array(
	'/' => 'home.html',
	'/about.html' => array(
		'page' => 'about.html'
	),
	'/contact.html' => array(
		'page' => 'contact.php'
	),
	'/server.amf' => array(
		'page' => 'amf/amf.php',
		'layout' => false
	),
	'/amf/test.html' =>  array(
		'page' => 'amf/test.html',
		'layout' => false
	),
));

$app->addStylesheet('/statics/css/commons.css');
$app->addStylesheet('/statics/css/styles.css');

$app->bootstrap();
$app->run();
$app->render();


