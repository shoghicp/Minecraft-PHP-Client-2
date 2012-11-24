<?php

/*


           -
         /   \
      /         \
   /   MINECRAFT   \
/         PHP         \
|\       CLIENT      /|
|.   \     2     /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /
         
         
	by @shoghicp



			DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


*/



class Socket{
	private $encrypt, $decrypt, $encryption;
	var $buffer, $connected, $errors, $sock;

	function __construct($server, $port, $listen = false, $socket = false, $timeout = 30){
		$this->errors = array_fill(88,(125 - 88) + 1, true);
		if($socket !== false){
			$this->sock = $socket;
			$this->setTimeout((int) $timeout);
			$this->connected = true;
			$this->buffer = "";
			$this->unblock();
			$this->encryption = false;
			socket_set_option($this->sock, SOL_SOCKET, SO_KEEPALIVE, 1);	
		}else{
			if($listen !== true){
				$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
				$this->setTimeout((int) $timeout);
				if(@socket_connect($this->sock, $server, $port) === false){
					$this->connected = false;
				}else{
					$this->connected = true;
					$this->buffer = "";
					$this->block();
					$this->encryption = false;
					socket_set_option($this->sock, SOL_SOCKET, SO_KEEPALIVE, 1);
				}
			}else{
				$this->sock = socket_create_listen($port);
				$this->unblock();	
			}
		}
	}
	
	public function setTimeout($sec, $usec = 0){
		socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, array(
			"sec" => $sec,
			"usec" => $usec
		));
		socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, array(
			"sec" => $sec,
			"usec" => $usec
		));
	}
	
	function listenSocket(){
		$sock = @socket_accept($this->sock);
		if($sock !== false){
			$sock = new Socket(false, false, false, $sock);
			$sock->unblock();
			return $sock;
		}
		return false;
	}
	
	function startAES($key){
		console("[DEBUG] [Socket] Secure channel with AES-".(strlen($key) >> 3)."-CFB8 encryption established", true, true, 2);
		require_once(dirname(__FILE__)."/AES.class.php");
		$this->encrypt = new AES(128, "CFB", 8);
		$this->encrypt->setKey($key);
		$this->encrypt->setIV($key);
		$this->encrypt->init();
		$this->decrypt =& $this->encrypt;
		$this->encryption = true;
	}

	function startRC4($key){
		console("[DEBUG] [Socket] Activating RC4-".(strlen($key) >> 3)." encryption", true, true, 2);
		require_once("phpseclib/Crypt/RC4.php");
		$this->encrypt = new Crypt_RC4();
		$this->encrypt->setKey($key);
		$this->encrypt->enableContinuousBuffer();
		$this->decrypt = new Crypt_RC4();
		$this->decrypt->setKey($key);
		$this->decrypt->enableContinuousBuffer();
		$this->encryption = true;
	}	
	
	public function close($error = 125){
		$this->connected = false;
		if($error === false){
			console("[ERROR] [Socket] Socket closed, Error: End of Stream");
		}else{
			console("[ERROR] [Socket] Socket closed, Error $error: ".socket_strerror($error));
		}
		return @socket_close($this->sock);
	}
	
	public function block(){
		socket_set_block($this->sock);
	}

	public function unblock(){
		socket_set_nonblock($this->sock);
	}
	
	public function read($len, $unblock = false){
		if($len <= 0){
			return "";
		}elseif($this->connected === false){
			return str_repeat("\x00", $len);
		}
		while(!isset($this->buffer{$len-1}) and $this->connected === true){
			$this->get($len);
			if($unblock === true){
				break;
			}
		}
		if(!isset($this->buffer{$len-1})){
			return "";
		}
		
		if($len === 1){
			$ret = $this->buffer{0};
		}else{
			$ret = substr($this->buffer, 0, $len);
		}
		
		$this->buffer = substr($this->buffer, $len);
		return $ret;
		
	}
	
	public function receive($str){ //Auto write a packet
		if($str != ""){
			$this->buffer .= $this->encryption === true ? $this->decrypt->decrypt($str):$str;
		}
	}
	
	public function write($str){
		if($str != ""){
			return @socket_write($this->sock, ($this->encryption === true ? $this->encrypt->encrypt($str):$str));
		}
	}
	
	function get($len){
		if(!isset($this->buffer{$len}) and $this->connected === true){
			$read = @socket_read($this->sock, $len, PHP_BINARY_READ);
			if($read !== "" and $read !== false){
				$this->receive($read);
			}elseif($read === false and isset($this->errors[socket_last_error($this->sock)])){
				$this->close(socket_last_error($this->sock));
			}elseif($read === ""){
				$this->close(false);
			}else{
				usleep(50000);
			}
		}
	}
}

?>