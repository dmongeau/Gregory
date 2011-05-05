<?php

require dirname(__FILE__).'/UserAuth.php';

$UserAuth = new UserAuth($config);

return $UserAuth;