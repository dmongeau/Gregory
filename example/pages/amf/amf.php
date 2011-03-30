<?php

require dirname(__FILE__).'/Server.php';

$server = new Zend_Amf_Server();

$server->setProduction(false);
$server->setClass('Amf_Server');

$response = $server->handle();
echo $response;
exit();
