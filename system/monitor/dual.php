<?php
/**
 * ç›‘æŽ§å™¨ - å�ŒæŒ–æ¨¡å¼�
 */

//Init
if(!defined('DEF_MONITOR')) return;
$config		= $GLOBALS['config'];
$cache		= $GLOBALS['cache'];
$devices	= $GLOBALS['devices'];
$process	= $GLOBALS['process'];

openlog("dual_monitor", LOG_PID, LOG_LOCAL0);

// //å�œæ­¢è¿›ç¨‹ - BTC
// if(!empty($process['btc']) && !empty($devices['devids'])) {
// 	writeLog("Please remove power, one of more miners are hanging");
// 	foreach($process['btc'] as $pid => $proc) {
// 		Miner::shutdownBtcProc($pid);
// 		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
// 	}
// 	$process['btc'] = array();
// }
$count = 0;
foreach($process['btc'] as $pid => $proc) {
	$count++;
	if($count > 1) {
		//å�œæ­¢é‡�å¤�è¿›ç¨‹
		Miner::shutdownBtcProc($pid);
		syslog(LOG_INFO, "BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['btc'][$pid]);
	}
}


//shutdown LTC
if(empty($process['btc'])) {
	foreach($process['ltc'] as $pid => $proc) {
		Miner::shutdownLtcProc($pid);
		writeLog("LTC process shutdown: Bus={$proc['devid']} Pid={$pid} Worker={$proc['worker']}");
	}
	$process['ltc'] = array();
} else {
	$excess = array();
	foreach($process['ltc'] as $pid => $proc) {
		$busid = $proc['devid'];
		$excess[$busid][] = $pid;
		//excess devices
		if(!in_array($busid, $devices['bus'])) {
			Miner::shutdownLtcProc($pid);
			writeLog("LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
			unset($process['ltc'][$pid]);
		}
	}
	foreach($excess as $busid => $pids) {
		//shutting down excess devices
		if(count($pids) > 1) {
			for($i=1; $i<count($pids); $i++) {
				$pid = $pids[$i];
				$proc = $process['ltc'][$pid];
				Miner::shutdownLtcProc($pid);
				syslog(LOG_INFO, "LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
				unset($process['ltc'][$pid]);
			}
		}
	}
}


//startup BTC
if(empty($process['btc'])) {
	//å†™ç¼“å­˜
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['btc'] = array();
	$cache->set(CACHE_STATS, $stats);
	//startup btc instance
	$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq'], 13);
	//Log
	syslog(LOG_INFO, "BTC process startup: Pid={$re['pid']} Worker={$config['btc_worker']} Frequency={$config['freq']} Devices=".implode(',',$re['devids'])." Bus=".implode(',',$devices['bus']));
}



//start LTC
$workers = $unusedWorkers = explode(',', $config['ltc_worker']);
foreach($unusedWorkers as $k => $worker) {
	$unusedWorkers[$k] = trim($worker);
	if(empty($unusedWorkers[$k])) {
		unset($unusedWorkers[$k]);
	}
}
foreach($process['ltc'] as $proc) {
	$idx = array_search($proc['worker'], $unusedWorkers);
	if($idx !== false) {
		unset($unusedWorkers[$idx]);
	}
}
sort($unusedWorkers);
//check for free miners - LTC
$usedBus = array();
$unusedBus = array();
foreach($process['ltc'] as $proc) {
	$usedBus[] = $proc['devid'];
}
foreach($devices['bus'] as $bus) {
	if(!in_array($bus, $usedBus)) {
		$unusedBus[] = $bus;
	}
}
if(!empty($unusedBus)) {
	//found free miners
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['ltc'] = array();
	$cache->set(CACHE_STATS, $stats);
	foreach($unusedBus as $bus) {
		//starting cpu miner
		$worker = !empty($unusedWorkers) ? array_shift($unusedWorkers) : $workers[0];
		$pid = Miner::startupCPUMinerProc(
			$bus,
			$config['ltc_url'],
			$worker,
			$config['ltc_pass'],
			$config['freq'],
			true
		);
		syslog(LOG_INFO, "LTC process startup: Bus={$bus} Pid={$pid} Worker={$worker} Frequency={$config['freq']}");
	}
}
