<?php
require_once 'cgminerclient.class.php';
openlog("MinerClass log", LOG_PID, LOG_LOCAL0);
class Miner {

	
	
	function getDirFile($path, $fileregular = "")
	{
		$path = rtrim($path, "/");
		if(!is_dir($path))
		{
			echo "Not a dir! \n";
			return array();
		}
		$dirHandler  = opendir($path);
		while(false !== ($filename = readdir($dirHandler)))
		{
			$list[] = $filename;
		}
		if($list === false)
		{
			return array();
		}
		$files = array();
		foreach($list as $filename)
		{
			$file = $path."/".$filename;
			if(!is_file($file))
			{
				continue;
			}
			if(!empty($fileregular))
			{
				if(!preg_match($fileregular, $filename))
				{
					continue;
				}
			}
			$files[] = $file;
		}
		return $files;
	}
	
	// Available USB/ACM device IDs
	function getAvailableDevice()
	{
		exec("ls -1 /dev | grep ttyUSB", $devices);
		if(!empty($devices))
		{
			foreach($devices as $k => $device)
			{
				$devices[$k] = (int) substr($device, 6);
			}
		}
		if(count($devices) == 0)
		{
			exec("ls -1 /dev | grep ttyACM", $devices);
			if(!empty($devices))
			{
				foreach($devices as $k => $device)
				{
					$devices[$k] = (int) substr($device, 6);
				}
			}
		}
		if(count ($devices) == 0){
			$devs = Miner::getUsbBus();
			if(is_iterable($devs))
			{
				for($i = 0 ; $i < count($devs) ; $i++)
				{
					$devices[] = $i;
				}
			}
		}
		sort($devices);
		return $devices;
	}
	
	
	// Get Bus:Dev from miners
	function getUsbBus()
	{
		$array = array();
		$temp = false;
		exec('lsusb -d  "0483:5740" | awk \'{print $2,":", $4}\'', $lines);
		if(!empty($lines))
		{
			foreach($lines as $line)
			{
				$aLine = explode(":", $line);
				$cBus = intval($aLine[0]);
				$cDev = intval($aLine[1]);
				$array[] = $cBus.":".$cDev;
			}
		}
		sort($array);
		return $array;
	}
	
	// Running BTC miners
	function getRunningBtcProcess()
	{
		$process = array();
		exec("ps agx | grep " . BIN_BTC . " | grep -v SCREEN | grep -v scrypt | grep -v grep | awk '{print $1}'", $lines);
		if(!empty($lines))
		{
			foreach($lines as $line)
			{
				$pid = trim($line);
				exec("cat /proc/".$pid."/cmdline", $out);
				if(!empty($out))
				{
					$cmdline = $out[0];
					preg_match('/\-u\x00([\.a-zA-Z0-9]+)/', $cmdline, $out);
					$worker = $out[1];
					$process[$pid] = array(
						'pid'		=> $pid,
						'worker'	=> $worker,
					);
				}
				$out = null;
			}
		}
		return $process;
	}
	
	// Running LTC miners
	function getRunningLtcProcess()
	{
		$process = array();
		exec("ps agx | grep " . BIN_LTC . " | grep scrypt | grep -v SCREEN | grep -v sudo | grep -v grep | awk '{print $1}'", $lines);
		if(!empty($lines))
		{
			foreach($lines as $line)
			{
				$pid = trim($line);
				exec("cat /proc/".$pid."/cmdline", $out);
				if(!empty($out))
				{
					$cmdline = $out[0];
					preg_match('/\-u\x00([\.a-zA-Z0-9]+)/', $cmdline, $out);
					$worker = $out[1];
					$process[$pid] = array(
						'pid'		=> $pid,
						'worker'	=> $worker
					);
				}
				$out = null;
			}
		}else{
			//cpu miners?
			exec("ps agx | grep " . BIN_CPUMINER . " | grep -v grep | awk '{print $1}'", $lines);
			if(!empty($lines))
			{
				foreach($lines as $line)
				{
					$pid = trim($line);
					exec("cat /proc/".$pid."/cmdline", $out);
					if(!empty($out))
					{
						$cmdline = $out[0];
						preg_match('/\-u\x00([\.a-zA-Z0-9]+)/', $cmdline, $out);
						$worker = $out[1];
						$process[$pid] = array(
								'pid'		=> $pid,
								'worker'	=> $worker
						);
					}
					$out = null;
				}
			}
		}
		return $process;
	}
	
	
	// Start BTC miner
	function startupBtcProc($freq, $cores = 0)
	{

		$pools = ConfigurationManager::instance()->getPools('sha');
		
		$cmd = 'sudo screen -dmS SHA256 '. BIN_BTC . " --api-listen --syslog --api-allow W:0/0 --api-port 4001 --gridseed-options=baud=115200,freq={$freq},chips=5,modules=1,usefifo=0,btc={$cores}";
		$cmd .= " --hotplug=0 ";
		
		foreach ($pools as &$pool){
			$cmd .= " -o {$pool->url} -u {$pool->worker} -p {$pool->password} ";
		}
		
		$cmd .= " &";
		
		$cache = new Cache(PATH_CACHE);
		
		syslog(LOG_INFO, "waiting for 10 seconds");
		$p = popen($cmd, 'r');
		pclose($p);
		
		exec("sleep 5");
		exec('ps agx | grep ' . BIN_BTC . ' | grep -v grep | awk \'{print $1}\'', $out);
		if(!empty($out))
		{
			$pid = intval(trim($out[0]));
			$waitsec = 120;
			$is_run = false;
			while($waitsec > 0)
			{
				//get answer from cgminerclient
				$summary = CGMinerClient::requestSummary();
				if(count($summary) > 0)
				{
					syslog(LOG_INFO, "Connected to BTC miner, process running"); 
					$is_run = true;
					break;
				}
				$waitsec--;
				exec("sleep 1");
			} 
			if(!$is_run)
			{
				exec("kill -9 {$pid}");
				return false;
			}
			return array('pid' => $pid);
		}
	}
	
	// Start LTC miner
	function startupLtcProc($freq)
	{
		$configMan = ConfigurationManager::instance();
		$pools = $configMan->getPools('scrypt');
        $chipcount = $configMan->getProductSettings()->chipcount;
        if (!isset($chipcount)) {
            $chipcount = CHIP_AMOUNT;
        }
		
		$devList = '';
		
		$devs = Miner::getAvailableDevice();
		foreach ( $devs as $dev) {
			$devList .= " -S /dev/ttyUSB$dev";  
		}
		
		$cmd = 'sudo screen -dmS SCRYPT '. BIN_LTC . " --api-listen --syslog --api-allow W:0/0 --api-port 4001 --chips-count ".$chipcount." $devList --ltc-clk {$freq} --failover-only";
		
		foreach ($pools as &$pool){
			$cmd .= " -o {$pool->url} -u {$pool->worker} -p {$pool->password} ";
		}
		
		$cmd .= " &";
		
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATS);
		$stats['ltc'] = array();
		$stats['time'] = time();
		$cache->set(CACHE_STATS, $stats);
	
		syslog(LOG_INFO, "about to run  " . $cmd);
		
		$p = popen($cmd, 'r');
		pclose($p);

        exec('sleep 5');
		
		syslog(LOG_INFO, "Started LTC PRocess waiting 10 seconds to boot cgminer");
		exec('ps agx | grep ' . BIN_LTC . ' | grep -v SCREEN | grep -v grep | awk \'{print $1}\'', $out);

		if(!empty($out))
		{
			$pid = intval(trim($out[0]));
			$waitsec = 120;
			$is_run = false;

			while($waitsec > 0)
			{
				//get answer from cgminerclient
				$devs = CGMinerClient::requestDevices();

				if(is_iterable($devs))
				{
					$devids = array();
					foreach ($devs as $key=>$val) {
						if (strpos($key,'PGA') !== false){
							$devids[] = $val['ID'];
						}
					}
					$is_run = true;
					break;
				}
				$waitsec--;
				exec('sleep 1');
			}
			if(!$is_run)
			{
				syslog(LOG_INFO, "Process not running, cgminer returns nothing");
				exec("kill -9 {$pid}");
				return false;
			}
			return array('pid' => $pid, 'devids' => $devids);
		}
	}
	
	// Start CPU miner
	function startupCPUMinerProc($devid, $url, $worker, $password, $freq)
	{
		$logid = str_replace(':' , '', $devid);
		
		$cmd = BIN_CPUMINER . " --syslog --dual -o {$url} -u {$worker} -p {$password}  &";
		syslog(LOG_INFO, "starting cpu miner with cmd: " .$cmd);
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATSUI);
		$stats['ltc'] = array();
		$stats['time'] = time();
		$cache->set(CACHE_STATSUI, $stats);
		
		$p = popen($cmd, 'r');
		pclose($p);
		exec("sleep 5");
		exec('ps | grep ' . BIN_LTC . ' | grep \'dif=' . $devid . '\' | grep -v grep | awk \'{print $1}\'', $out);
		if(!empty($out))
		{
			$pid = intval(trim($out[0]));
			return $pid;
		}
	}
	
	// Kill BTC miner(s)
	function shutdownBtcProc($pid = -1)
	{
		if($pid == -1)
		{
			$cmd = 'sudo killall -9 ' . basename(BIN_BTC);
		}
		else
		{
			$cmd = "sudo kill -9 {$pid}";
		}
		return exec($cmd);
	}
	
	// Kill LTC miner(s)
	function shutdownLtcProc($pid = -1)
	{
		if($pid == -1)
		{
			$cmd = 'sudo killall -9 ' . basename(BIN_LTC);
		}
		else
		{
			$cmd = "sudo kill -9 {$pid}";
		}
		syslog(LOG_INFO, "Shutting down LTC process with this command: " . $cmd);
		$executed = exec($cmd , $out);
		return $executed;
	}
	
	// Kill LTC miner(s)
	function shutdownCPUMinerProc($pid = -1)
	{
		if($pid == -1)
		{
			$cmd = 'sudo killall -9 ' . basename(BIN_CPUMINER);
		}
		else
		{
			$cmd = "sudo kill -9 {$pid}";
		}
		syslog(LOG_INFO, "Shutting down CPU Miner process with this command: " . $cmd);
		$executed = exec($cmd , $out);
		return $executed;
	}
	
	//bfgminer
	function getBFGMinerStats()
	{
		
		$summary = array (
			"status" => "OFFLINE",
			"elapsed" => 0,
			"mh" => 0,
			"avgmh" => 0,
			"acc" => 0,
			"rej" => 0,
			"hw" => 0,
			"wu" => 0,
			"hw" => 0,
			"found" => 0,
			"discarded" => 0,
			"stale" => 0
		);
		
		$devices = array();
		$pools = array();
		
		$stats = array(
				"summary" => $summary,
				"devices" => $devices,
				"pools" => $pools
		);
		
		$ltcProc = Miner::getRunningLtcProcess();
		$btcProc = Miner::getRunningBtcProcess();
		
		if (count($ltcProc) == 0 && count($btcProc) == 0){
			return $stats;
		}
		
		$devs = CGMinerClient::requestDevices();
		$sum = CGMinerClient::requestSummary();

		if (is_iterable($devs) && isset($sum["SUMMARY"])) {
			foreach ($devs as $key=>$val){
				if (strpos($key, 'PGA') !== false) {
					//found device
					$devices[] = array(
							'time'		=> $val['Last Share Time'],
							'device'	=> $val['ID'],
							'diff'		=> $val['Diff1 Work'],
							'hashrate'  => Miner::calculateSCRYPTHashrate($sum["SUMMARY"], $val, BY_CORE),
                            'poolhashrate' => Miner::calculateSCRYPTHashrate($sum["SUMMARY"], $val, BY_DIFF1),
							'valid'		=> $val['Accepted'],
							'invalid'	=> $val['Rejected'],
							'enabled'	=> $val["Enabled"],
                            'hw'        => $val["Hardware Errors"]
                    );
				}
			}
		
			//got devs, so get summary
		}
		
		if (isset($sum["SUMMARY"])) {
			$summary["status"] = "RUNNING";
			$summary["elapsed"] = $sum["SUMMARY"]["Elapsed"];
			
			$avgmh = $sum["SUMMARY"]["MHS av"];
			if (CALCULATE_HASHRATE_SCRYPT === BY_DIFF1) {
				$mh = 0;
				foreach ($devices as $d) {
					$mh += $d["hashrate"];
				}
				//$avgmh = $mh;
			}
			
			$summary["mh"] = $mh;
			$summary["avgmh"] = $avgmh;
			$summary["acc"] =  $sum["SUMMARY"]["Accepted"];
			$summary["rej"] = $sum["SUMMARY"]["Rejected"];
			$summary["wu"] = $sum["SUMMARY"]["Work Utility"];
			$summary["hw"] = $sum["SUMMARY"]["Hardware Errors"];
			$summary["found"] = $sum["SUMMARY"]["Found Blocks"];
			$summary["discarded"] = $sum["SUMMARY"]["Discarded"];
			$summary["stale"] = $sum["SUMMARY"]["Stale"];
		}
		
		$pls = CGMinerClient::requestPools();
		if(is_iterable($pls)) {
			foreach ($pls as $pl) {
				if(isset($pl["POOL"])){
					$pools [] = array(
							"id" => $pl["POOL"],
							"URL" => $pl['URL'],
							"Status" => ($pl["Stratum Active"] == 'true'? "ALIVE" : ($pl["Status"] =='Dead' ? "DEAD" : "SLEEPING"))
					);
				}
			}
		}
		
		$stats = array(
				"summary" => $summary,
				"devices" => $devices,
				"pools" => $pools
		);
		
		return $stats; 
	}
	
	//cgminer
	function getCGMinerStats()
	{
	
	
		$summary = array (
				"status" => "OFFLINE",
				"elapsed" => 0,
				"mh" => 0,
				"avgmh" => 0,
				"acc" => 0,
				"rej" => 0,
				"hw" => 0,
				"wu" => 0,
				"hw" => 0,
				"found" => 0,
				"discarded" => 0,
				"stale" => 0
		);
	
		$devices = array();
		$pools = array();
	
		$stats = array(
				"summary" => $summary,
				"devices" => $devices,
				"pools" => $pools
		);
	
		$ltcProc = Miner::getRunningLtcProcess();
		$btcProc = Miner::getRunningBtcProcess();
	
		if (count($ltcProc) == 0 && count($btcProc) == 0){
			return $stats;
		}
	
		$devs = CGMinerClient::requestDevices();
		if (is_iterable($devs)) {
			foreach ($devs as $key=>$val){
				if (strpos($key, 'ASC') !== false) {
					//found device
					$devices[] = array(
							'time'		=> $val['Last Share Time'],
							'device'	=> $val['ID'],
							'diff'		=> $val['Diff1 Work'],
							'hashrate'  => Miner::calculateSHAHashrate($val),
							'valid'		=> $val['Accepted'],
							'invalid'	=> $val['Rejected'],
							'enabled'	=> $val["Enabled"],
                            'hw'        => $val["Hardware Errors"]
					);
				}
			}
				
			//got devs, so get summary
		}
		$sum = CGMinerClient::requestSummary();
	
		if (isset($sum["SUMMARY"])) {
			
			$avgmh = $sum["SUMMARY"]["MHS av"];
			if (CALCULATE_HASHRATE_SHA === BY_DIFF1) {
				$mh = 0;
				foreach ($devices as $d) {
					$mh += $d["hashrate"];
				}
				$avgmh = $mh;
			}
			
			$summary["status"] = "RUNNING";
			$summary["elapsed"] = $sum["SUMMARY"]["Elapsed"];
			$summary["mh"] = $mh;
			$summary["avgmh"] = $avgmh;
			$summary["acc"] =  $sum["SUMMARY"]["Accepted"];
			$summary["rej"] = $sum["SUMMARY"]["Rejected"];
			$summary["wu"] = $sum["SUMMARY"]["Work Utility"];
			$summary["hw"] = $sum["SUMMARY"]["Hardware Errors"];
			$summary["found"] = $sum["SUMMARY"]["Found Blocks"];
			$summary["discarded"] = $sum["SUMMARY"]["Discarded"];
			$summary["stale"] = $sum["SUMMARY"]["Stale"];
		}
	
		$pls = CGMinerClient::requestPools();
		
		if(is_iterable($pls)) {
			foreach ($pls as $pl) {
				
			if(isset($pl["POOL"])){
					$pools [] = array(
							"id" => $pl["POOL"],
							"URL" => $pl['URL'],
							"Status" => ($pl["Stratum Active"] == 'true'? "ALIVE" : ($pl["Status"] =='Dead' ? "DEAD" : "SLEEPING"))
					);
				}
				
			}
		}
		
		$stats = array(
				"summary" => $summary,
				"devices" => $devices,
				"pools" => $pools
		);
	
		return $stats;
	}
	
	function calculateSCRYPTHashrate ($aSummary, $aStats, $method) {
		$multiplier = 1000;
		if (SCRYPT_UNIT === MHS) {
			$multiplier = 1;
		}
		if ($method === BY_CORE) {
			return $aStats['MHS 5s'] * $multiplier;
		}
		return round(pow(2,32) * ((int)$aStats['Diff1 Work']) / ((int)$aSummary['Elapsed']) / 1E6, 2) * $multiplier;
	}
	
	function calculateSHAHashrate ($aStats) {
	
		if (CALCULATE_HASHRATE_SHA === BY_CORE) {
			return $aStats['MHS 5s'] * 1000;
		}
		return round(pow(2,32) * $aStats['Diff1 Work'] / $aStats['Device Elapsed'] / 1E9, 2); 
	} 
	
	function restartMiner() {
		
		CGMinerClient::restart();
		
	}

}

