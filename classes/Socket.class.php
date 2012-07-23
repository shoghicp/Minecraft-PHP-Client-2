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

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/



class Socket{
	private $sock, $encrypt, $decrypt, $encryption;
	var $buffer, $connected, $errors;

	function __construct($server, $port, $listen = false){
		$this->errors = array_fill(88,(125 - 88) + 1, true);
		
		if($listen !== true){
			$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
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
		}
	}
	
	function startAES($key){
		console("[DEBUG] [Socket] Secure channel with AES-".(strlen($key)*8)."-CFB8 encryption established", true, true, 2);
		require_once(dirname(__FILE__)."/AES.class.php");
		$this->encrypt = new AES(128, "CFB", 8);
		$this->encrypt->setKey($key);
		$this->encrypt->setIV($key);
		$this->encrypt->init();
		$this->decrypt =& $this->encrypt;
		$this->encryption = true;
	}

	function startRC4($key){
		console("[DEBUG] [Socket] Activating RC4-".(strlen($key)*8)." encryption", true, true, 2);
		require_once("Crypt/RC4.php");
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
		if($len === 1){
			$ret = $this->buffer{0};
		}else{		
			$ret = substr($this->buffer, 0, $len);
		}
		
		$this->buffer = substr($this->buffer, $len);
		return $ret;
		
	}
	
	public function recieve($str){ //Auto write a packet
		if($str != ""){
			$str = $this->encryption === true ? $this->decrypt->decrypt($str):$str;
			$this->buffer .= $str;
		}
	}
	
	public function write($str){
		if($str != ""){
			$str = $this->encryption === true ? $this->encrypt->encrypt($str):$str;
			return @socket_write($this->sock, $str);
		}
	}
	
	function get($len){
		if(!isset($this->buffer{$len}) and $this->connected === true){
			$read = @socket_read($this->sock,$len, PHP_BINARY_READ);
			if($read !== "" and $read !== false){
				$this->recieve($read);
			}elseif($read === false and isset($this->errors[socket_last_error($this->sock)])){
				$this->close(socket_last_error($this->sock));
			}elseif($read === ""){
				$this->close(false);
			}else{
				usleep(10000);
			}
		}
	}
}

?>