<?php

require dirname(__FILE__).'/lib/facebook.php';

$facebook = new Facebook($config);

return $facebook;