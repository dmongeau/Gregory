<?php

define('PATH_ROOT',dirname(__FILE__));
define('PATH_PAGES',dirname(__FILE__).'/pages');
define('PATH_PLUGINS',dirname(__FILE__).'/plugins');

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
	'/' => 'home',
	'/server.amf' => array(
		'page' => 'amf/amf.php',
		'layout' => false
	),
	'/jesus/:bar' => array(
		'page' => '500.html',
		'layout' => false
	),
));

$app->bootstrap();
$app->run();
$app->render();

