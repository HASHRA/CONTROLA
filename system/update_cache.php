<?php
require '../config/define.php';
require PATH_CLASS . '/miner.class.php';
require PATH_CLASS . '/cache.class.php';

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
		$array = $cache->get(CACHE_STATS);
		if($array === false)
		{
			$array = array(
				'lastcommit'	=> array(
					'btc'	=> array(),
					'ltc'	=> array(),
				),
			);
		}
		$statsBtc = Miner::getBtcStats();
		$statsLtc = Miner::getLtcStats();
		$timeNow = time();
		foreach($statsBtc as $stat)
		{
			$device = $stat['device'];
			if(!isset($array['lastcommit']['btc'][$device]))
			{
				$array['lastcommit']['btc'][$device] = $stat['time'];
			}
			elseif($stat['time'] > $array['lastcommit']['btc'][$device])
			{
				$array['lastcommit']['btc'][$device] = $stat['time'];
			}
		}
		foreach($statsLtc as $stat)
		{
			$devid = $stat['device'];
			if(!isset($array['lastcommit']['ltc'][$devid]))
			{
				$array['lastcommit']['ltc'][$devid] = $stat['time'];
			}
			elseif($stat['time'] > $array['lastcommit']['ltc'][$devid])
			{
				$array['lastcommit']['ltc'][$devid] = $stat['time'];
			}
		}
		ksort($array['lastcommit']['btc']);
		ksort($array['lastcommit']['ltc']);
		$cache->set(CACHE_STATS, $array);
		
		$array = $cache->get(CACHE_STATSUI);
		if($array === false)
		{
			$array = array(
				'btc'		=> array(),
				'ltc'		=> array(),
				'time'		=> $timeNow,
			);
		}
		foreach($statsLtc as $stat)
		{
			if(!isset($array['ltc'][$stat['device']]))
			{
				$array['ltc'][$stat['device']] = array('valid' => 0, 'invalid' => 0, 'shares' => 0);
			}
			if($stat['isaccept'])
			{
				$array['ltc'][$stat['device']]['valid']++;
				$array['ltc'][$stat['device']]['shares'] += $stat['diff'];
			}
			else
			{
				$array['ltc'][$stat['device']]['invalid']++;
			}
		}
		foreach($statsBtc as $stat)
		{
			if(!isset($array['btc'][$stat['device']]))
			{
				$array['btc'][$stat['device']] = array('valid' => 0, 'invalid' => 0, 'shares' => 0);
			}
			if($stat['isaccept'])
			{
				$array['btc'][$stat['device']]['valid']++;
				$array['btc'][$stat['device']]['shares'] += $stat['diff'];
			}
			else
			{
				$array['btc'][$stat['device']]['invalid']++;
			}
		}
		ksort($array['btc']);
		ksort($array['ltc']);
		$cache->set(CACHE_STATSUI, $array);

		$tick_interval = 1 * 60;
		$history = 24 * 60 * 60;
		$statsUi = Miner::getLtcStatsUI();
		$array = $cache->get("graphui");
		$total = 0;
		if($array === false)
		{
			$array = array("individual" => array(), "total" => array());
			foreach($statsUi as $k => $stat)
			{
				$array["individual"][$k] = array($timeNow => $stat["hashrate"]);
				$total += $stat["hashrate"];
			}
			$array["total"] = array($timeNow => $total);
			$array["lastcommit"] = $timeNow;
		}
		else
		{
			if($timeNow - $array["lastcommit"] >= $tick_interval)
			{
				foreach($array["individual"] as $k => $device)
				{
					$times = array_keys($device);
					while($times[count($times) - 1] - $times[0] >= $history)
					{
						unset($array["individual"][$k][$times[0]]);
						$times = array_keys($device);
					}
				}
				$times = array_keys($array["total"]);
				while($times[count($times) - 1] - $times[0] >= $history)
				{
					unset($array["total"][$times[0]]);
					$times = array_keys($array["total"]);
				}
				foreach($statsUi as $k => $stat)
				{
					$array["individual"][$k][$timeNow] = $stat["hashrate"];
					$total += $stat["hashrate"];
				}
				$array["total"][$timeNow] = $total;
				$array["lastcommit"] = $timeNow;
			}
		}
		$cache->set("graphui", $array);
	}
	
}

$method = $_SERVER['argv'][1];
UpdateCache::$method();
