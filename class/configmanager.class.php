<?php 
define('FILE_SYSTEM_SETTNGS', PATH_CONFIG.'/systemsettings.json');
define('FILE_POOLSETTINGS', PATH_CONFIG.'/poolsettings.json');
	/**
	 * Configuration management class
	 * @author maxzilla
	 *
	 */
	class ConfigurationManager {
		
		private $systemSettings;
		private $poolSettings;
		
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
					"updateurl" => "https://bitbucket.org/purplefox/hashra-public-firmware.git"
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
			
			$configMan = new ConfigurationManager();
			
			$configMan->poolSettings = json_decode(file_get_contents(FILE_POOLSETTINGS));
			$configMan->systemSettings = json_decode(file_get_contents(FILE_SYSTEM_SETTNGS));
						
			return $configMan;
		}
		
		function getSystemSettings() {
			return $this->systemSettings;
		}
		
		function getPoolSettings ($type) {
			return $this->poolSettings[$type];
		}
		
		/**
		 * 
		 * @param string $type
		 * @param string $url
		 * @param string $worker
		 * @param string $password
		 * @return boolean
		 */
		function setPoolSettings ($type, $url , $worker, $password) {
			//does pool exists? url is identifier
			foreach ($this->poolSettings[$type] as &$pool) {
				if ($pool->url === $url) {
					//pool exists, edit current
					$pool->worker = $worker;
					$pool->password = $password;
					$this->save();
					return true;
				}
			}
			//doesn't exist, add it	
			$pool = array (
				"url" => $url,
				"worker" => $worker,
				"password" => $password
			);
			$this->poolSettings[$type][] = $pool;
			$this->save();
			return true;
		}
		
		/**
		 * saves the configuration to file
		 */
		function save() {
			file_put_contents(FILE_POOLSETTINGS, json_encode($this->poolSettings));
			file_put_contents(FILE_SYSTEM_SETTNGS, json_encode($this->systemSettings));
		}
		
		/**
		 * 
		 * @param string $updateUrl
		 * @param string $btccoresdual
		 */
		function setSystemSettings ($restartevery, $updateUrl , $btccoresdual) {
			$this->systemSettings->restartevery = $restartevery;
			$this->systemSettings->btccoresdual = $btccoresdual;
			$this->systemSettings->updateurl = $updateUrl;
			$this->save();
		}
		
	}

?>