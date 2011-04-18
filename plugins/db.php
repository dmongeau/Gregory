<?php

$adapter = $config["adapter"];
$config = $config["config"];

require 'Zend/Db.php';

try {
	$db = Zend_Db::factory($adapter,$config);
	$db->getConnection();
} catch (Zend_Db_Adapter_Exception $e) {
	var_dump($e);
	exit();
	throw new Exception("Erreur de connexion à la base de données",500);
} catch (Zend_Exception $e) {
	throw new Exception("Erreur d'initialisation de la base de données",500);
}

$encoding = isset($config["encoding"]) ? $config["encoding"]:'utf8';

$db->query("SET NAMES '".$encoding."'");
Zend_Db_Table::setDefaultAdapter($db);

return $db;