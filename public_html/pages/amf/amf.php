<?php

require dirname(__FILE__).'/Server.php';


$server = new Zend_Amf_Server();
//$server->setObjectEncoding(Zend_Amf_Constants::AMF3_OBJECT_ENCODING);

$server->setProduction(false);
$server->setClass('Amf_Server');

try {
	$response = $server->handle();
} catch(Exception $e) {
	die('allo');
}

//$request = $server->getRequest();

//var_dump($request);
//exit();

$response->addAmfHeader(new Zend_Amf_Value_MessageHeader('TestHeader',false,'true'));
echo $response;
exit();
