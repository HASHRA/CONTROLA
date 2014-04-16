<?php 
	class AccessControl {
		/**
		 * checks if action is allowed
		 */
		static function hasAccess() {
			session_start();
			return (isset($_SESSION["user"]));	
		}
		
		static function getLoggedInUserName () {
			if (isset($_SESSION["user"])) {
				return $_SESSION["user"];
			}else{
				return "guest";
			}
		}
	}
?>