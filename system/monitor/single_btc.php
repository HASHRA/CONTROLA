<?php

//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];

openlog("single_btc_monitor", LOG_PID, LOG_LOCAL0);

$hasRunningLTCProc = count(Miner::getRunningLtcProcess()) > 0;

if ($hasRunningLTCProc) {
	syslog(LOG_INFO, "Running BTC Process found, shutting down");
	Miner::shutdownLtcProc();
}

$count = 0;
foreach($process['btc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		Miner::shutdownBtcProc($pid);
		unset($process['btc'][$pid]);
	}
}

//check the elapsed time. restart miners after x hours

$stats = $cache->get(CACHE_STATS);
$systemSettings = ConfigurationManager::instance()->getSystemSettings();
if (isset($stats["summary"]) && $systemSettings->restartevery > 0){
	$elapsed = intval($stats["summary"]["elapsed"]);
	if ($elapsed > ($systemSettings->restartevery * 60 * 60 ) ) {
		syslog(LOG_INFO, "Maintenance restart started");
		Miner::shutdownBtcProc();
		sleep(2);
	}
}

if(empty($process['btc']) && !empty($devices['bus'])) {
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	//starting up btc process
	syslog(LOG_INFO, "Starting single BTC Process");
	$re = Miner::startupBtcProc($config['freq'], 16);
	
	if($re === false) {
		//Miner::restartPower();
		return;
	}
}
?>