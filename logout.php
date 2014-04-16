<?php 

	require_once 'class/usermanager.class.php';
	
	UserManager::instance()->logout();
	header('Location: index.php');

?>