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
		writeLog("BTC process shutdown: Pid={$pid} Worker={$proc['worker']}");
		unset($process['btc'][$pid]);
	}
}


//å�œæ­¢è¿›ç¨‹ - LTC
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
		//å¯¹åº”æ— æ•ˆè®¾å¤‡çš„è¿›ç¨‹
		if(!in_array($busid, $devices['bus'])) {
			Miner::shutdownLtcProc($pid);
			writeLog("LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
			unset($process['ltc'][$pid]);
		}
	}
	foreach($excess as $busid => $pids) {
		//æ£€æŸ¥é‡�å¤�å¼€å�¯çš„è¿›ç¨‹
		if(count($pids) > 1) {
			for($i=1; $i<count($pids); $i++) {
				$pid = $pids[$i];
				$proc = $process['ltc'][$pid];
				Miner::shutdownLtcProc($pid);
				writeLog("LTC process shutdown: Bus={$busid} Pid={$pid} Worker={$proc['worker']}");
				unset($process['ltc'][$pid]);
			}
		}
	}
}


//å�¯åŠ¨è¿›ç¨‹ - BTC
if(empty($process['btc'])) {
	//å†™ç¼“å­˜
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['btc'] = array();
	$cache->set(CACHE_STATS, $stats);
	//å�¯åŠ¨
	$re = Miner::startupBtcProc($config['btc_url'], $config['btc_worker'], $config['btc_pass'], $config['freq'], 16);
	if($re === false) {
		writeLog("BTC process fails to start");
		//å…³é—­æ‰€æœ‰è¿›ç¨‹
		Miner::shutdownBtcProc();
		Miner::shutdownLtcProc();
		writeLog("All process shutdown");
		//é‡�å�¯USBç”µæº�
		Miner::closePower();
		writeLog("Close the USB controller power");
		usleep(1000000);
		Miner::openPower();
		writeLog("Open the USB controller power");
		return;
	}
	//Log
	writeLog("BTC process startup: Pid={$re['pid']} Worker={$config['btc_worker']} Frequency={$config['freq']} Devices=".implode(',',$re['devids'])." Bus=".implode(',',$devices['bus']));
}



//æ›´æ–°LTCå�¯ç”¨çŸ¿å·¥
$workers = $unusedWorkers = explode(',', $config['ltc_workers']);
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
//å�¯åŠ¨ - LTC
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
	//å†™ç¼“å­˜
	$runtime = array('runtime' => time());
	$cache->set(CACHE_RUNTIME, $runtime);
	$stats = $cache->get(CACHE_STATS);
	$stats['lastcommit']['ltc'] = array();
	$cache->set(CACHE_STATS, $stats);
	foreach($unusedBus as $bus) {
		//å�¯åŠ¨
		$worker = !empty($unusedWorkers) ? array_shift($unusedWorkers) : $workers[0];
		$pid = Miner::startupLtcProc(
			$bus,
			$config['ltc_url'],
			$worker,
			$config['ltc_pass'],
			$config['freq'],
			true
		);
		writeLog("LTC process startup: Bus={$bus} Pid={$pid} Worker={$worker} Frequency={$config['freq']}");
	}
}


//å®•æœºæ£€æµ‹ - BTC
$freezeTime = 900;
$timeNow = time();
$arr = $cache->get(CACHE_RUNTIME);
if($arr===false) {
	$arr = array('runtime' => $timeNow);
}
if(($arr['runtime'] + $freezeTime) > $timeNow) {
	return;
}
$arr = $cache->get(CACHE_STATS);
$diedBus = array();
foreach($devices['bus'] as $bus) {
	if(!isset($arr['lastcommit']['btc'][$bus])) {
		$diedBus[] = $bus;
		continue;
	}
	if(($arr['lastcommit']['btc'][$bus] + $freezeTime) < $timeNow) {
		$diedBus[] = $bus;
		continue;
	}
}
if(!empty($diedBus)) {
	writeLog("Device downtime (BTC): DiedBus=".implode(',',$diedBus));
	//å…³é—­æ‰€æœ‰è¿›ç¨‹
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	writeLog("All process shutdown");
	//é‡�å�¯USBç”µæº�
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPowe();
	writeLog("Open the USB controller power");
	return;
}


//å®•æœºæ£€æµ‹ - LTC
$freezeTime = 600;
$timeNow = time();
$runtime = $cache->get(CACHE_RUNTIME);
if($runtime===false) {
	$runtime = array('runtime' => $timeNow);
}
if(($runtime['runtime'] + $freezeTime) > $timeNow) {
	return;
}
$stats = $cache->get(CACHE_STATS);
$diedBus = array();
foreach($devices['bus'] as $bus) {
	if(!isset($stats['lastcommit']['ltc'][$bus])) {
		$diedBus[] = $bus;
		continue;
	}
	if(($stats['lastcommit']['ltc'][$bus] + $freezeTime) < $timeNow) {
		$diedBus[] = $bus;
		continue;
	}
}
if(!empty($diedBus)) {
	writeLog("Device downtime (LTC): DiedBus=".implode(',',$diedBus));
	//å…³é—­æ‰€æœ‰è¿›ç¨‹
	Miner::shutdownBtcProc();
	Miner::shutdownLtcProc();
	writeLog("All process shutdown");
	//é‡�å�¯USBç”µæº�
	Miner::closePower();
	writeLog("Close the USB controller power");
	usleep(1000000);
	Miner::openPower();
	writeLog("Open the USB controller power");
}

