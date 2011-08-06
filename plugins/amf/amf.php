<?php

//Default configuration
$config = array_merge(array(
	'table' => 'users'
),$config);


require dirname(__FILE__).'/AMFServer.php';

$Server = new AMFServer($config);

return $Server;