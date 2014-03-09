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
		syslog(LOG_INFO,memory_get_usage(true). " >  getting available devices from system");
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
			syslog(LOG_INFO, memory_get_usage(true). " > no ttyACM or ttmUSB found getting usb bus");
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
 		syslog(LOG_INFO," exiting getting available devices");
		return $devices;
	}
	
	function restartPower( $_intTime = 1000000 )
	{
		@exec('stty -F /dev/ttyATH0 raw speed 9600; echo "O(00,05,0)E" > /dev/ttyATH0 &' );
		usleep( $_intTime );
		@exec('stty -F /dev/ttyATH0 raw speed 9600; echo "O(00,05,1)E" > /dev/ttyATH0 &' );
	}
	
	// Get Bus:Dev from miners
	function getUsbBus()
	{
		syslog(LOG_INFO, "Fetching USB bus");
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
		syslog(LOG_INFO, memory_get_usage(true). " > Found USB devices hello " . count($array));
		sort($array);
		syslog(LOG_INFO, memory_get_usage(true). " > sorted");
		return $array;
	}
	
	// Running BTC miners
	function getRunningBtcProcess()
	{
		syslog(LOG_INFO, "Getting Running BTC Processes");
		$process = array();
		exec("ps agx | grep " . BIN_BTC . " | grep -v screen | grep -v scrypt | grep sudo | grep -v grep | awk '{print $1}'", $lines);
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
		syslog(LOG_INFO, "Getting Running LTC Processes");
		$process = array();
		exec("ps agx | grep " . BIN_LTC . " | grep scrypt | grep -v screen | grep -v sudo | grep -v grep | awk '{print $1}'", $lines);
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
		$cmd = 'sudo '. BIN_BTC . " --api-listen --api-allow W:0/0 --api-port 4001 --gridseed-options=baud=115200,freq={$freq},chips=5,modules=1,usefifo=0,btc={$cores}";
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
		$cmd = 'sudo '. BIN_LTC . " --scrypt --api-listen --api-allow W:0/0 --api-port 4001 --gridseed-options freq={$freq}";
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
				syslog(LOG_INFO, "Requesting devices from cgminer");
				$devs = CGMinerClient::requestDevices();
				
				if(is_iterable($devs))
				{
					$devids = array();
					syslog(LOG_INFO, "found " . count($devs) . " devices");
					foreach ($devs as $key=>$val) {
						if (strpos($key,'ASC') !== false){
							$devids[] = $val['ID'];
						}
					}
					syslog(LOG_INFO, "Continuing process");
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
			$cmd = "kill -9 {$pid}";
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
		syslog(LOG_INFO, "LTC Process shut down with return code ". json_encode($out));
		return $executed;
	}
	
	// BTC stats
	function getCGMinerStats()
	{
		syslog(LOG_INFO, "Getting CGMiner stats");
		$stats = array();
		$ltcProc = Miner::getRunningLtcProcess();
		$btcProc = Miner::getRunningBtcProcess();
		
		if (count($ltcProc) == 0 && count($btcProc) == 0){
			syslog(LOG_INFO, "No mining processes running, waiting for another time");
			return $stats;
		}
		
		syslog(LOG_INFO, "Found a live CGMINER process, getting stats");
		
		$devs = CGMinerClient::requestDevices();
		if (is_iterable($devs)) {
			foreach ($devs as $key=>$val){
				if (strpos($key, 'ASC') !== false) {
					//found device
					$stats[] = array(
							'time'		=> $val['Last Share Time'],
							'device'	=> $val['ID'],
							'diff'		=> $val['Diff1 Work'],
							'hashrate'  => $val['MHS 5s'] * 1000,
							'valid'		=> $val['Accepted'],
							'invalid'	=> $val['Rejected']
					);
				}
			}
		}
		return $stats;
	}

}

