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

//check the elapsed time. restart miners after 2 hours

$stats = $cache->get(CACHE_STATS);
if (isset($stats["summary"])){
	$elapsed = intval($stats["summary"]["elapsed"]);
	if ($elapsed > 14400) {
		//restart cgminer
		syslog(LOG_INFO, "Maintenance cgminer restart started");
		Miner::shutdownLtcProc();
		sleep(2);
	}
}
if (isset($stats["devices"])) {
	//monitor devices, restart it when needed
	Miner::deviceMonitor($stats["devices"]);
}

if(count(Miner::getRunningLtcProcess()) == 0 && !empty($devices['bus'])) {
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);

	
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