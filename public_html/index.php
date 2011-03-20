<?php

define('PATH_GREGORY',dirname(__FILE__).'/../Gregory');
define('PATH_ROOT',dirname(__FILE__));
define('PATH_PAGES',dirname(__FILE__).'/pages');
define('PATH_PLUGINS',dirname(__FILE__).'/plugins');

require PATH_GREGORY.'/Gregory.php';
$config = include PATH_ROOT.'/config.php';

$app = new Gregory($config);

$app->addPlugin('db');
$app->addRoute(array(
	'/' => 'home',
	'/server.amf' => array(
		'page' => 'amf/amf.php',
		'layout' => false
	),
));

$app->bootstrap();
$app->run();
$app->render();
