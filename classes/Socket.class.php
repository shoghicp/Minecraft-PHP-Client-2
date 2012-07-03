<?php


define("MAX_BUFFER_BYTES", 1024 * 1024 * 4); //4MB max of buffer
define("MIN_BUFFER_BYTES", 64);
ini_set("memory_limit", "512M");

define("HALF_BUFFER_BYTES", MAX_BUFFER_BYTES / 2);



class Socket{
	private $sock;
	protected $connected;
	var $buffer;

	function __construct($server, $port){
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(@socket_connect($this->sock, $server, $port) === false){
			$this->connected = false;
		}else{
			$this->connected = true;
			$this->unblock();
			socket_set_option($this->sock, SOL_SOCKET, SO_KEEPALIVE, 1);
			socket_set_option($this->sock, SOL_TCP, TCP_NODELAY, 1);
		}	
	}
	
	public function close(){
		$this->connected = false;
		return @socket_close($this->sock);
	}
	
	public function block(){
		socket_set_block($this->sock);
	}

	public function unblock(){
		socket_set_nonblock($this->sock);
	}
	
	public function read($len){
		if($len <= 0){
			return "";
		}
		while(!isset($this->buffer{$len-1}) and $this->connected){
			$this->get();		
		}
		$ret = substr($this->buffer, 0, $len);
		$this->buffer = substr($this->buffer, $len);
		return $ret;
		
	}
	
	public function recieve($str){ //Auto write a packet
		$this->buffer .= $str;
		return true;
	}
	
	public function write($str){
		return @socket_write($this->sock, $str);
	}
	
	function get(){
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
			}elseif(socket_last_error($this->sock) == 104){
				$this->connected = false;
			}		
		}
	}
}

?>