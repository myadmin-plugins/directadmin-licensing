<?php

require_once(__DIR__ . '/../../../include/functions.inc.php');
$result = activate_directadmin($ip, $ostype, $pass, $email, $name, $domain);
print_r($result);
