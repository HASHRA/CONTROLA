<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

if (isset($_GET["action"])) {
	require 'actions/'.$_GET["action"].".php";
}

?>


