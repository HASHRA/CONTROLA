<?php

//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];

openlog("single_ltc_monitor", LOG_PID, LOG_LOCAL0);

$count = 0;
foreach($process['ltc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		Miner::shutdownLtcProc($pid);
		writeLog("LTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['ltc'][$pid]);
	}
}

if(empty($process['ltc']) && !empty($devices['bus'])) {
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['ltc'] = array();
	$cache->set(CACHE_STATS, $stats);
	//starting up ltc process
	syslog(LOG_INFO, "Starting single LTC Process");
	$re = Miner::startupLtcProc($config['ltc_url'], $config['ltc_worker'], $config['ltc_pass'], $config['freq']);
	if($re === false) {
		writeLog("LTC process fails to start");
		//Miner::restartPower();
		return;
	}
	//Log
	writeLog("LTC process startup: Pid={$re['pid']} Worker={$config['ltc_worker']} Frequency={$config['freq']} Devices=".implode(',',$re['devids'])." Bus=".implode(',',$devices['bus']));
}
?>