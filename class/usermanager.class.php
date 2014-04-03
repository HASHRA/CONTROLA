<?php 
	require_once 'config/define.php';
	session_start();
	/**
	 * User management class
	 * @author maxzilla
	 *
	 */
	class UserManager {

		private $users;
		
		/**
		 * factory method
		 * @return UserManager
		 */
		static function instance () {
			
			$userManager = new UserManager();
			
			//check if the users file exists
			if (!file_exists(FILE_USERS)){
				//create file and add default values.
				fopen(FILE_USERS , "w");
				$user = array 
				(	
					"user" => "root",
					"password" => md5("root")		
				);
				$users = array($user);
				file_put_contents(FILE_USERS, json_encode($users));				
			}
				
			$userManager->users = json_decode(file_get_contents(FILE_USERS));
			return $userManager;
		}
		
		function login ($username , $password) {
			$user = $this->getUser($username);
			if (empty($user)) {
				return false;
			}else{
				syslog(LOG_INFO, "password ". $user->password . " {$password} " . md5($password));
				return $user->password === md5($password);
			}
		}
		
		function logout () {
			unset($_SESSION["user"]);
		}
		
		function save() {
			file_put_contents(FILE_USERS, json_encode($this->users));
		}
		
		/**
		 * changes the user password, right now only root user exists
		 * @param string $username
		 * @param string $password
		 */
		function changePassword($username , $password) {
			foreach ($this->users as &$user) {
				if ($user->user === $username) {
					$user->password = md5($password);
					syslog(LOG_INFO, "password changed for user " .$username . " password $password ". md5($password) );
					break;
				}
			}
			$this->save();
			
		}
		
		/**
		 * 
		 * @param string $username
		 * @return array|NULL
		 */
		function getUser ($username) {
			foreach ($this->users as $user){
				if($user->user == $username){
					return $user;
					break;
				}
			}
			return null;
		}
	}
?>