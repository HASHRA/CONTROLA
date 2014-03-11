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
	function getAvailableDevice($api = false)
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
		exec("ps agx | grep " . BIN_BTC . " | grep -v SCREEN | grep -v scrypt | grep sudo | grep -v grep | awk '{print $1}'", $lines);
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
		}
		return $process;
	}
	
	
	// Start BTC miner
	function startupBtcProc($url, $worker, $password, $freq, $cores = 0)
	{
		$cmd = 'sudo screen -dmS SHA256 '. BIN_BTC . " --api-listen --api-allow W:0/0 --api-port 4001 --gridseed-options=baud=115200,freq={$freq},chips=5,modules=1,usefifo=0,btc={$cores}";
		$cmd .= " --hotplug=0 -o {$url} -u {$worker} -p {$password} &";
		
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATSUI);
		$stats['btc'] = array();
		$stats['time'] = time();
		$cache->set(CACHE_STATSUI, $stats);
		
		$p = popen($cmd, 'r');
		pclose($p);
		usleep(100000);
		exec('ps | grep ' . BIN_BTC . ' | grep -v grep | awk \'{print $1}\'', $out);
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
						if (strpos($key,'ASC') !== false){
							$devids[] = $val['ID'];
						}
					}
					$is_run = true;
					break;
				}
				$waitsec--;
				sleep(1);
			}
			if(!$is_run)
			{
				exec("kill -9 {$pid}");
				return false;
			}
			return array('pid' => $pid, 'devids' => $devids);
		}
	}
	
	// Start LTC miner
	function startupLtcProc($url, $worker, $password, $freq)
	{
		$cmd = 'sudo screen -dmS SCRYPT '. BIN_LTC . " --scrypt --api-listen --api-allow W:0/0 --api-port 4001 --gridseed-options freq={$freq}";
		$cmd .= " -o {$url} -u {$worker} -p {$password} &";
		
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATS);
		$stats['ltc'] = array();
		$stats['time'] = time();
		$cache->set(CACHE_STATS, $stats);
	
		syslog(LOG_INFO, "about to run  " . $cmd);
		
		$p = popen($cmd, 'r');
		pclose($p);
		
		syslog(LOG_INFO, "Started LTC PRocess waiting 10 seconds to boot cgminer");
		usleep(100000);
		exec('ps | grep ' . BIN_LTC . ' | grep sudo | grep -v grep | awk \'{print $1}\'', $out);
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
						if (strpos($key,'ASC') !== false){
							$devids[] = $val['ID'];
						}
					}
					$is_run = true;
					break;
				}
				$waitsec--;
				sleep(1);
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
	
	// Start LTC miner
	function startupDualProc($devid, $url, $worker, $password, $freq)
	{
		$logid = str_replace(':' , '', $devid);
		
		$cmd = 'sudo ' .BIN_CPUMINER . " --dual -o {$url} -u {$worker} -p {$password} -q 2> " . PATH_LOG . "/ltc{$devid}.log &";
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATSUI);
		$stats['ltc'] = array();
		$stats['time'] = time();
		$cache->set(CACHE_STATSUI, $stats);
		
		$p = popen($cmd, 'r');
		pclose($p);
		usleep(100000);
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
	
	// BTC stats
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
		
		$stats = array(
				"summary" => $summary,
				"devices" => $devices
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
							'hashrate'  => $val['MHS 5s'] * 1000,
							'valid'		=> $val['Accepted'],
							'invalid'	=> $val['Rejected']
					);
				}
			}
			
			//got devs, so get summary
		}
		$sum = CGMinerClient::requestSummary();

		if (isset($sum["SUMMARY"])) {
			$summary["status"] = "RUNNING";
			$summary["elapsed"] = $sum["SUMMARY"]["Elapsed"];
			$summary["mh"] = $sum["SUMMARY"]["MHS 5s"];
			$summary["avgmh"] = $sum["SUMMARY"]["MHS av"];
			$summary["acc"] =  $sum["SUMMARY"]["Accepted"];
			$summary["rej"] = $sum["SUMMARY"]["Rejected"];
			$summary["wu"] = $sum["SUMMARY"]["Work Utility"];
			$summary["hw"] = $sum["SUMMARY"]["Hardware Errors"];
			$summary["found"] = $sum["SUMMARY"]["Found Blocks"];
			$summary["discarded"] = $sum["SUMMARY"]["Discarded"];
			$summary["stale"] = $sum["SUMMARY"]["Stale"];
		}
		
		
		$stats = array(
				"summary" => $summary,
				"devices" => $devices
		);
		
		return $stats;
	}

}

