<?php


require_once("phpseclib/Crypt/DES.php");
define("PKCS_MD5", 1);
define("PKCS_SHA1", 2);

class PKCSKeyGenerator{

	private $key, $IV, $iterations, $segments, $des;
	
	public function __construct($keystring, $salt = "\0\0\0\0\0\0\0\0", $iterations = 5, $segments = 1, $hash = PKCS_MD5){
		$this->generate($keystring, $salt, (int) $iterations, (int) $segments, $hash);
		$this->des = new Crypt_DES(CRYPT_DES_MODE_CBC);
		$this->des->setKey($this->key);
		$this->des->setIV($this->IV);
	}
	
	private function generate($keystring, $salt, $iterations, $segments, $hash){
		switch($hash){
			default:
			case 1:
				$hashFunction = "md5";
				$hashLenght = 16;
				break;
			case 2:
				$hashFunction = "sha1";
				$hashLenght = 20;
				break;
		}
		
		$data = $keystring . $salt;
		$result = "";
		for($j = 0; $j < $segments; ++$j){
			$result .= $data;
			for($i = 0; $i < $iterations; ++$i){
				$result = $hashFunction($result, true);
			}		
		}
		$this->key = substr($result, 0, 8);
		$this->IV = substr($result, 8, 8);
	}
	
	public function encrypt($plaintext){
		return $this->des->encrypt($plaintext);
	}

	public function decrypt($ciphertext){
		return $this->des->decrypt($ciphertext);
	}
}


class LastLogin{
	private $crypt, $location;
	function __construct(){
		$this->crypt = new PKCSKeyGenerator("passwordfile", "\x0c\x9d\x4a\xe4\x1e\x83\x15\xfc", 5, 1, PKCS_MD5);
		$this->location = LastLogin::getLocation();
	}
	
	public static function getLocation(){
		$home = getenv("home");
		$os = strtolower(php_uname("s"));
		if(strpos($os, "win") !== false){
			$location = getenv("appdata");
			if($location === ""){
				$location = ($home != "") ? $home : getenv("homepath");
			}
			$location .= '/.minecraft/';
		}elseif(strpos($os, "linux") !== false or strpos($os, "unix") !== false or strpos($os, "solaris") !== false or strpos($os, "sunos") !== false){
			$location = $home . '/.minecraft/';
		}elseif(strpos($os, "mac") !== false){
			$location = $home . '/Library/Application Support/minecraft/';
		}else{
			$location = $home . '/.minecraft/';
		}
		return $location . 'lastlogin';
	}
	
	public function get(){
		if(!file_exists($this->location)){
			return array("username" => false, "password" => false);
		}
		$data = $this->crypt->decrypt(file_get_contents($this->location));
		$offset = 0;
		$len = Utils::readShort(substr($data, $offset, 2));
		$offset += 2;
		$username = substr($data, $offset, $len);
		$offset += $len;
		$len = Utils::readShort(substr($data, $offset, 2));
		$offset += 2;
		$password = substr($data, $offset, $len);
		$offset += $len;
		console("[DEBUG] [LastLogin] Got Credentials", true, true, 2);
		return array("username" => $username, "password" => $password);		
	}
	
	public function set($username, $password){
		file_put_contents($this->location, $this->crypt->encrypt(strlen($username).$username.strlen($password).$password));
		console("[DEBUG] [LastLogin] Set Credentials", true, true, 2);
	}

}