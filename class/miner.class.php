<?php

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
			$devices = Miner::getUsbBus();
			if(!empty($devices))
			{
				foreach($devices as $k => $device)
				{
					$devices[$k] = $device;
				}
			}
		}
 		sort($devices);
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
		$array = array();
		$temp = false;
		exec("cat /proc/bus/usb/devices", $lines);
		if(!empty($lines))
		{
			foreach($lines as $line)
			{
				if(strstr($line, "T:") !== false)
				{
					preg_match('/Bus\=(\d+)\sLev\=(\d+)\sPrnt\=(\d+)\sPort\=(\d+)\sCnt\=(\d+)\sDev#\=\s+(\d+)/', $line, $out);
					$cBus = intval($out[1]);
					$cDev = intval($out[6]);
					$temp = $cBus.":".$cDev;
				}
				else if(preg_match("/S\:\s\sProduct/", $line) === 1)
				{
					if(strstr($line, "CP210") !== false || strstr($line, "STM32") !== false)
					{
						if($temp !== false)
						{
							$array[] = $temp;
						}
						$temp = false;
					}
				}
			}
		}
		sort($array);
		return $array;
	}
	
	// Running BTC miners
	function getRunningBtcProcess()
	{
		$process = array();
		exec("ps | grep " . BIN_BTC . " | grep -v screen | grep -v grep | awk '{print $1}'", $lines);
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
		exec("ps | grep " . BIN_LTC . " | grep -v screen | grep -v grep | awk '{print $1}'", $lines);
		if(!empty($lines))
		{
			foreach($lines as $line)
			{
				$pid = trim($line);
				exec("cat /proc/".$pid."/cmdline", $out);
				if(!empty($out))
				{
					$cmdline = $out[0];
					preg_match('/\-\-dif\=(\d+\:?\d*)/', $cmdline, $out);
					$devid = $out[1];
					preg_match('/\-u\x00([\.a-zA-Z0-9]+)/', $cmdline, $out);
					$worker = $out[1];
					$process[$pid] = array(
						'pid'		=> $pid,
						'devid'		=> $devid,
						'worker'	=> $worker, 
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
		$cmd = BIN_BTC . " --dif --gridseed-options=baud=115200,freq={$freq},chips=5,modules=1,usefifo=0,btc={$cores}";
		$cmd .= " --hotplug=0 -o {$url} -u {$worker} -p {$password} -l 9999 > " . PATH_LOG . "/btc.log 2>&1 &";
		
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
				$file = PATH_LOG . '/btc.log';
				$log = file_get_contents($file);
				if(strpos($log, 'Network diff set to'))
				{
					$devids = array();
					preg_match_all('/Create\sLTC\sproxy\son\s\d+\/UDP\sfor\s\d+\:\d+\((\d+)\)/', $log, $out);
					$devids = array_values(array_unique($out[1]));
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
	function startupLtcProc($devid, $url, $worker, $password, $freq, $dual = false)
	{
		$logid = str_replace(':' , '', $devid);
		
		$cmd = BIN_LTC . " --dif={$devid} --dual --freq={$freq} -o {$url} -u {$worker} -p {$password} -q 2> " . PATH_LOG . "/ltc{$logid}.log &";
		
		
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
			$cmd = 'killall -9 ' . basename(BIN_BTC);
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
			$cmd = 'killall -9 ' . basename(BIN_LTC);
		}
		else
		{
			$cmd = "kill -9 {$pid}";
		}
		return exec($cmd);
	}
	
	// BTC stats
	function getBtcStats($unlink = true)
	{
		$stats = array();
		$files = Miner::getDirFile(PATH_TEMP.'/btc');
		foreach($files as $file)
		{
			$time = filectime($file);
			list($device, , $accepted, $diff) = explode('|', file_get_contents($file));
			$stats[] = array(
				'time'		=> $time,
				'device'	=> $device,
				'isaccept'	=> $accepted == 'A' ? true : false,
				'diff'		=> $diff,
			);
			if($unlink)
			{
				unlink($file);
			}
		}
		return $stats;
	}
	
	// BTC stats
	function getBtcStatsUI()
	{
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATSUI);
		$btc = array();
		$time = time();
		if(!empty($stats) && array_key_exists('btc', $stats) && !empty($stats['btc']))
		{
			foreach($stats['btc'] as $devid => $btcminer)
			{
				$btc[$devid] = array(
					'valid' => $btcminer['valid'],
					'invalid' => $btcminer['invalid'],
					'hashrate' => (round((((float) pow(2.0, 16)) / (($time - $stats['time']) / $btcminer['shares'])) / 10000000)) * 1.8,
				);
			}
		}
		return $btc;
	}
	
	// LTC stats
	function getLtcStats($unlink = true)
	{
		$stats = array();
		$files = Miner::getDirFile(PATH_TEMP.'/ltc');
		foreach($files as $file)
		{
			$time = filectime($file);
			list($device, , $accepted, $diff) = explode('|', file_get_contents($file));
			$stats[] = array(
				'time'		=> $time,
				'device'	=> $device,
				'isaccept'	=> $accepted == 'A' ? true : false,
				'diff'		=> $diff,
			);
			if($unlink)
			{
				unlink($file);
			}
		}
		return $stats;
	}
	
	function getLtcStatsUI()
	{
		$cache = new Cache(PATH_CACHE);
		$stats = $cache->get(CACHE_STATSUI);
		$ltc = array();
		$time = time();
		if(!empty($stats) && array_key_exists('ltc', $stats) && !empty($stats['ltc']))
		{
			foreach($stats['ltc'] as $devid => $ltcminer)
			{
				$ltc[$devid] = array(
					'valid' => $ltcminer['valid'],
					'invalid' => $ltcminer['invalid'],
					'hashrate' => round((((float) pow(2.0, 16)) / (($time - $stats['time']) / $ltcminer['shares'])) / 1000),
				);
			}
		}
		return $ltc;
	}
}

