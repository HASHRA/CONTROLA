<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';
require_once 'class/usermanager.class.php';
require_once 'class/configmanager.class.php';
require_once 'class/ajaxutils.class.php';
require_once 'class/accesscontrol.class.php';

if (isset($_GET["action"])) {
	require 'actions/'.$_GET["action"].".php";
}

?>


