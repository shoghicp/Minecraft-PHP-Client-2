<?php



class Socket{
	private $sock, $encrypt, $decrypt, $encryption;
	var $buffer, $connected;

	function __construct($server, $port, $listen = false){
		
		
		if($listen !== true){
			$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if(@socket_connect($this->sock, $server, $port) === false){
				$this->connected = false;
			}else{
				$this->connected = true;
				$this->buffer = "";
				$this->unblock();
				$this->encryption = false;
				socket_set_option($this->sock, SOL_SOCKET, SO_KEEPALIVE, 1);
				socket_set_option($this->sock, SOL_TCP, TCP_NODELAY, 1);
				//socket_set_option($this->sock, SOL_TCP, SO_SNDBUF, 1);
			}
		}else{
			$this->sock = socket_create_listen($port);
			while(true){
				if(($sock = socket_accept($this->sock)) !== false){
					break;
				}
			}
			$this->sock = $sock;
				$this->connected = true;
				$this->buffer = "";
				$this->unblock();
				$this->encryption = false;
				socket_set_option($this->sock, SOL_SOCKET, SO_KEEPALIVE, 1);
				socket_set_option($this->sock, SOL_TCP, TCP_NODELAY, 1);
				//socket_set_option($this->sock, SOL_TCP, SO_SNDBUF, 1);		
		}
	}
	
	function startAES($key){
		console("[INFO] [Socket] Secure channel with AES-".(strlen($key)*8)."-CFB8 encryption established");
		require_once(dirname(__FILE__)."/AES.class.php");
		$this->encrypt = new AES(128, "CFB8", 8);
		$this->encrypt->setKey($key);
		$this->encrypt->setIV($key);
		$this->decrypt =& $this->encrypt;
		$this->encryption = true;
	}

	function startRC4($key){
		console("[INFO] [Socket] Activating RC4-".(strlen($key)*8)." encryption");
		require_once("Crypt/RC4.php");
		$this->encrypt = new Crypt_RC4();
		$this->encrypt->setKey($key);
		//$this->encrypt->setIV($key);
		$this->encrypt->enableContinuousBuffer();
		$this->decrypt = new Crypt_RC4();
		$this->decrypt->setKey($key);
		//$this->decrypt->setIV($key);
		$this->decrypt->enableContinuousBuffer();
		$this->encryption = true;
	}	
	
	public function close($error = 125){
		$this->connected = false;
		console("[ERROR] [Socket] Socket closed, Error $error: ".socket_strerror($error));
		return @socket_close($this->sock);
	}
	
	public function block(){
		socket_set_block($this->sock);
	}

	public function unblock(){
		socket_set_nonblock($this->sock);
	}
	
	public function read($len, $unblock = false){
		if($len <= 0 or $this->connected === false){
			return "";
		}
		while(!isset($this->buffer{$len-1}) and $this->connected === true){
			$this->get();
			if($unblock === true){
				break;
			}
		}
		$ret = substr($this->buffer, 0, $len);
		$this->buffer = substr($this->buffer, $len);
		return $ret;
		
	}
	
	public function recieve($str){ //Auto write a packet
		if($str != ""){
			$str = $this->encryption === true ? $this->decrypt->decrypt($str):$str;
			$this->buffer .= $str;
			return true;
		}
	}
	
	public function write($str){
		if($str != ""){
			$str = $this->encryption === true ? $this->encrypt->encrypt($str):$str;
			return @socket_write($this->sock, $str);
		}
	}
	
	function get(){
		$errors = range(88,125);
		if(!isset($this->buffer{HALF_BUFFER_BYTES}) and $this->connected === true){
			/*if(!isset($this->buffer{MIN_BUFFER_BYTES})){
				$this->block();
				$read = MIN_BUFFER_BYTES;
			}else{
				$this->unblock();
				$read = HALF_BUFFER_BYTES;
			}*/
			$read = @socket_read($this->sock,HALF_BUFFER_BYTES, PHP_BINARY_READ);
			if($read !== false and $read !== ""){
				$this->recieve($read);
			}elseif($read === false and in_array(socket_last_error($this->sock), $errors)){
				$this->close(socket_last_error($this->sock));
			}		
		}
	}
}

?>