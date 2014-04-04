<?php 
	define('FILE_USERS', PATH_CONFIG.'/users.json');
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
				$userManager->createUser("hashra", "hashra");			
			}
				
			$userManager->users = json_decode(file_get_contents(FILE_USERS));
			//delete root user ... the boss doesn't like "root" ;)
			if ($userManager->getUser("root") != null){
				$userManager->deleteUser("root");
				$userManager->users = json_decode(file_get_contents(FILE_USERS));
				if (count($userManager->users) == 0) {
					//change root user to hashra
					$userManager->createUser("hashra", "hashra");
				}
			}
			return $userManager;
		}
		
		
		function createUser($login, $password) {
			$user = array
			(
					"user" => $login,
					"password" => md5($password)
			);
			$this->users = array($user);
			$this->save();
		}
		
		function login ($username , $password) {
			$user = $this->getUser($username);
			if (empty($user)) {
				return false;
			}else{
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
					break;
				}
			}
			$this->save();
			
		}
		
		function deleteUser($userName) {
			foreach ($this->users as $key=>$user){
				if($user->user == $userName){
					unset($this->users[$key]);
					$this->save();
					break;
				}
			}
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