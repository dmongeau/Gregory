<?php

//Default configuration
$config = array_merge(array(
	'table' => 'users',
	'identityColumn' => 'email',
	'passwordColumn' => 'pwd',
	'hashMode' => 'sha1',
	'hashSalt' => '!_GREGORY_!@',
	'block' => array(
		'deleted' => 1,
		'published' => 0
	),
	'errors' => array(
		'invalid' => 'Mauvais courriel ou mot de passe.',
		'blocked' => 'Votre compte est désactivé'
	)
),$config);


require dirname(__FILE__).'/UserAuth.php';

$UserAuth = new UserAuth($config);

return $UserAuth;