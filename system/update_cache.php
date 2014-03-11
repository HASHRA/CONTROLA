<?php
require_once '/var/www/config/define.php';
require PATH_CLASS . '/miner.class.php';
require PATH_CLASS . '/cache.class.php';

openlog("UpdateCache", LOG_PID, LOG_LOCAL0);
class UpdateCache {
	
	function device()
	{

		$array = array(
			'devids'	=> Miner::getAvailableDevice(),
			'bus'		=> Miner::getUsbBus(),
		);

		$cache = new Cache(PATH_CACHE);
		$cache->set(CACHE_DEVICE, $array);
		return;
	}
	
	function process()
	{

		$array = array(
			'btc'	=> Miner::getRunningBtcProcess(),
			'ltc'	=> Miner::getRunningLtcProcess(),
		);
		$cache = new Cache(PATH_CACHE);
		$cache->set(CACHE_PROCESS, $array);
		return;
	}
	
	function stats()
	{

		$cache = new Cache(PATH_CACHE);
		$array = array();
		$statsMiners = Miner::getCGMinerStats();

		
		$timeNow = time();
		$proc = $cache->get(CACHE_PROCESS);

		$array = array(
			"stats" => null,
			"devices" => array()
		);
		
		if(isset($statsMiners["devices"]) && is_iterable($statsMiners["devices"])){
			$cache->set(CACHE_STATS, $statsMiners);
		}

	}
	
}

$method = $_SERVER['argv'][1];
UpdateCache::$method();
