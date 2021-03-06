<?php 
	/**
	 * Configuration management class
	 * @author maxzilla
	 *
	 */
	class ConfigurationManager {
		
		private $systemSettings;
		private $poolSettings;
        private $clockSettings;
		
		/**
		 * factory method
		 * @return ConfigurationManager
		 */
		static function instance () {
			if (!file_exists(FILE_SYSTEM_SETTNGS)) {
				//create default system settings
				fopen(FILE_SYSTEM_SETTNGS , "w");
				
				$settings = array (
					"restartevery" => 0,
					"btccoresdual" => 11,
					"updateurl" => DEFAULT_UPDATE_URL
				);
				file_put_contents(FILE_SYSTEM_SETTNGS, json_encode($settings));
			}
			if (!file_exists(FILE_POOLSETTINGS)) {
				fopen(FILE_POOLSETTINGS , "w");
				//copy from ini file
				$iniArr = parse_ini_file(FILE_CONFIG);

				$ltcpool = array (
					"type" => "scrypt",
					"url" => $iniArr["ltc_url"],
					"worker" => $iniArr["ltc_worker"],
					"password" => $iniArr["ltc_pass"]
				);
				$btcpool = array (
					"type" => "sha",
					"url" => $iniArr["btc_url"],
					"worker" => $iniArr["btc_worker"],
					"password" => $iniArr["btc_pass"]
				);
				
				$pools = array (
					"scrypt" => array($ltcpool),
					"sha" => array($btcpool)
				);
				
				file_put_contents(FILE_POOLSETTINGS , json_encode($pools));
			}

            if(!file_exists(FILE_CLOCK_SETTNGS)) {
                fopen(FILE_CLOCK_SETTNGS, "w");
                file_put_contents(FILE_CLOCK_SETTNGS, json_encode(array()));
            }
			
			$configMan = new ConfigurationManager();
			
			exec('sudo chown www-data:www-data '.FILE_SYSTEM_SETTNGS);
			exec('sudo chmod 755 '.FILE_SYSTEM_SETTNGS);
			exec('sudo chown www-data:www-data '.FILE_POOLSETTINGS);
			exec('sudo chmod 755 '.FILE_POOLSETTINGS);
            exec('sudo chown www-data:www-data '.FILE_CLOCK_SETTNGS);
            exec('sudo chmod 755 '.FILE_CLOCK_SETTNGS);
			
			$configMan->poolSettings = json_decode(file_get_contents(FILE_POOLSETTINGS));
			$configMan->systemSettings = json_decode(file_get_contents(FILE_SYSTEM_SETTNGS));
            $configMan->clockSettings = json_decode(file_get_contents(FILE_CLOCK_SETTNGS), true);
						
			return $configMan;
		}
		
		function getSystemSettings() {
			return $this->systemSettings;
		}

        function getClockSettings() {
            return $this->clockSettings;
        }

        /**
         * sets a clock speed setting for a particular device
         * identified by it's serial id
         * @param $serialId the device's serial number
         * @param $clock clock speed
         */
        function setClockSetting ($serialId , $clock) {
            $this->clockSettings[$serialId] = $clock;
            $this->save();
        }

		/**
		 * 
		 * @param string $type
		 * @param string $url
		 * @param string $worker
		 * @param string $password
		 * @return boolean
		 */
		function setPoolSettings ($id, $type, $url , $worker, $password) {
			syslog(LOG_INFO, "adding pool with id " . $id);
			if($id > -1) {
				foreach ($this->poolSettings->$type as $key=>&$pool) {
					if ($key == $id) {
						//pool exists, edit current
						$pool->url = $url;
						$pool->worker = $worker;
						$pool->password = $password;
						$this->save();
						return true;
					}
				}
			}else{
				//doesn't exist, add it
				$newPool = array (
						"url" => $url,
						"worker" => $worker,
						"password" => $password
				);
				array_push($this->poolSettings->$type, $newPool);
				$this->save();
				return true;
			}
		}

        /**
         * @param $type algo type, scrypt or sha
         * @param $old old priority
         * @param $new new priority
         */
        function rearrangePool($type, $old, $new) {
			$arrPools = array(); 
			foreach ($this->poolSettings->$type as $key=>&$pool) {
				$arrPools[] = $pool;
			}
			$oldSeatholder = $arrPools[$new];
			$target = $arrPools[$old];
			$arrPools[$new] = $target;
			$arrPools[$old] = $oldSeatholder;
			$this->poolSettings->$type = $arrPools;
			$this->save();
		}		
		
		/**
		 * returns the pools for a type
		 * @param string $type
		 */
		function getPools($type = 'scrypt'){
			return $this->poolSettings->$type;
		}
		
		/**
		 * deletes pool
		 * @param String $type
		 * @param String $id
		 */
		function deletePool($type, $id) {
			array_splice($this->poolSettings->$type, $id, 1);
			$this->save();	
		}
	
		
		/**
		 * saves the configuration to file
		 */
		function save() {
			file_put_contents(FILE_POOLSETTINGS, json_encode($this->poolSettings));
			file_put_contents(FILE_SYSTEM_SETTNGS, json_encode($this->systemSettings));
            file_put_contents(FILE_CLOCK_SETTNGS, json_encode($this->clockSettings));
		}
		
		/**
		 * 
		 * @param string $updateUrl
		 * @param string $btccoresdual
		 */
		function setSystemSettings ($restartevery, $updateUrl , $btccoresdual) {
			$this->systemSettings->restartevery = $restartevery;
			$this->systemSettings->btccoresdual = $btccoresdual;
			if(trim($updateUrl) != '') {
				$this->systemSettings->updateurl = $updateUrl;
			}
			$this->save();
		}
		
	}

?>