<?php
require_once '/var/www/config/define.php';
require PATH_CLASS . '/miner.class.php';
require PATH_CLASS . '/cache.class.php';

openlog("UpdateCache", LOG_PID, LOG_LOCAL0);
class UpdateCache {
	
	function device()
	{
		syslog(LOG_INFO, "Updating device cache");
		$array = array(
			'devids'	=> Miner::getAvailableDevice(),
			'bus'		=> Miner::getUsbBus(),
		);
		syslog(LOG_INFO, "Finished device cache");
		$cache = new Cache(PATH_CACHE);
		$cache->set(CACHE_DEVICE, $array);
		return;
	}
	
	function process()
	{
		syslog(LOG_INFO, "Updating process cache");
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
		syslog(LOG_INFO, "Updating stats cache");
		$cache = new Cache(PATH_CACHE);
		$array = array();
		$statsMiners = Miner::getCGMinerStats();

		
		$timeNow = time();
		$proc = $cache->get(CACHE_PROCESS);

		if(isset($statsMiners["devices"]) && is_iterable($statsMiners["devices"])){
			syslog(LOG_INFO, "Got cgminer stats ". count($statsMiners["devices"]));
			foreach($statsMiners["devices"] as $stat)
			{
				//syslog(LOG_INFO, "Got cgminer stats ". json_encode($stat));
				$array[] = $stat; 
			}
		}
		$cache->set(CACHE_STATS, $array);

	}
	
}

$method = $_SERVER['argv'][1];
UpdateCache::$method();
