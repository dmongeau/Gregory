<?php

require dirname(__FILE__).'/Factory.php';

$Mail = new Mail_Factory($config['from']);
return $Mail;