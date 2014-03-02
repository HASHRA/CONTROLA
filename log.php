<?php
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/tail.class.php';

$iniArr = parse_ini_file(FILE_CONFIG);
if($iniArr["model"] == 2)
{
	$devices = Miner::getAvailableDevice();
}
else if($iniArr["model"] == 3)
{
	$devices = Miner::getUsbBus();
}

$array = array();
if(!empty($devices))
{
	foreach($devices as $device)
	{
		$array["LTC{$device}"] = PATH_LOG."/ltc{$device}.log";
	}
}
if($iniArr["model"] == 1 || $iniArr["model"] == 3)
{
	$array["BTC"] = PATH_LOG."/btc.log";
}
$tail = new Tail($array, 3000);

/**
 * We're getting an AJAX call
 */
if(isset($_GET['ajax']))  {
	echo $tail->getNewLines($_GET['tab'], $_GET['lastsize'], $_GET['grep'], $_GET['invert']);
	die();
}
/**
 * Regular GET/POST call, print out the GUI
 */
$tail->generateGUI();
