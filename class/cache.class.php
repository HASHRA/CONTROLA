<?php

require_once "JSON.php";
require_once "FastJSON.class.php";

class Cache
{
	var $path = NULL;
	var $json = NULL;		
	
	function Cache($path)
	{
		$this->json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		if(is_dir($path))
		{
			$this->path = $path;
		}
	}
	
	function object_to_array($obj)
	{
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		if(is_array($_arr) && !empty($_arr))
		{
			foreach ($_arr as $key => $val)
			{
				$val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
				$arr[$key] = $val;
			}
		}
		return isset($arr) ? $arr : array();
	}
	
	function get($key)
	{
		if(is_null($this->path)) return false;
		$file = $this->_getCacheFile($key);
		if(!file_exists($file)) return false;
		$array = $this->json->decode(file_get_contents($file));
		if($array === false) return false;
		if($array['timeout'] != 0 && $array['timeout'] < time())
		{
			$this->delete($key);
			return false;
		}
		return $array['data'];
	}
	
	function set($key, $value = array(), $timeout = 0)
	{
		if(is_null($this->path)) return false;
		$array = array(
			'timeout'	=> $timeout > 0 ? time()+$timeout : 0,
			'data'		=> $value,
		);
		$json = $this->json->encode($array);
		$file = $this->_getCacheFile($key);
		
		$fp = fopen($file, "w");
		fwrite($fp, $json);
		fclose($fp);
		return true;
	}
	
	function delete($key)
	{
		if(is_null($this->path)) return false;
		$file = $this->_getCacheFile($key);
		if(file_exists($file))
		{
			unlink($file);
		}
		return true;
	}
	
	function _getCacheFile($key)
	{
		$file = $this->path . '/' . $key . '.cache';
		return $file;
	}
}
