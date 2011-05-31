<?php

$config = array_merge(array(
	'url' => 'http://'.$_SERVER['HTTP_HOST'],
	'callback' => 'http://'.$_SERVER['HTTP_HOST'].'/twitter/callback'
),$config);

$oauthOptions = array(
    'version'         => 1.0,
    'signatureMethod' => 'HMAC-SHA1',
    'requestScheme'   => 'header',
    'siteUrl'         => $config['url'], // optional
    'callbackUrl'     => $config['callback'], // optional
    'requestTokenUrl' => 'http://twitter.com/oauth/request_token', // optional
    'authorizeUrl'    => 'http://twitter.com/oauth/authenticate', // optional
    'accessTokenUrl'  => 'http://twitter.com/oauth/access_token', // optional
    'consumerKey'     => $config['consumerKey'],
    'consumerSecret'  => $config['consumerSecret']
);

require_once dirname(__FILE__).'/lib/twitter.php';

$Twitter = new Twitter($oauthOptions);

if(isset($config['token'])) $Twitter->setToken($config['token']['access'],$config['token']['secret']);

return $Twitter;


