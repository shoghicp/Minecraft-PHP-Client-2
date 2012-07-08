<?php

require_once(dirname(__FILE__)."/Utils.class.php");
require_once(dirname(__FILE__)."/Packet.class.php");

require_once(dirname(__FILE__)."/../misc/functions.php");

class MinecraftDissector{
	private $server;
	protected $protocol;
	var $pstruct;
	
	function __construct($file, $protocol = CURRENT_PROTOCOL){
		$this->server = new FileSocket($file);
		$this->protocol = intval($protocol);
		include(dirname(__FILE__)."/../pstruct/".$this->protocol.".php");
		$this->pstruct = $pstruct;
		$this->pstruct["c3"] = array(
			"short",
			"short",
			"int",
			"byteArray",	
		);
	}
	
	public function close(){
		return $this->server->close();
	}
	
	protected function getPID($chr){
		return Utils::strToHex($chr{0});
	}
	
	protected function getStruct($pid){
		if(isset($this->pstruct[$pid])){
			return $this->pstruct[$pid];
		}
		return false;
	}
	
	public function readPacket(){		
		$pid = $this->getPID($this->server->read(1));
		$struct = $this->getStruct($pid);
		if($struct === false){
			$p = "==".time()."==> ERROR Bad packet id $pid :".PHP_EOL;
			$p .= hexdump(Utils::hexToStr($pid).$this->server->read(512), false, false, true);
			$p .= PHP_EOL . "--------------- (512 byte extract) ----------" .PHP_EOL .PHP_EOL;
			logg($p, "packets");
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Kicked from server, "Bad packet id '.$pid.'"'));
			$this->writePacket("ff", array(0 => Utils::writeString('Kicked from server, "Bad packet id '.$pid.'"')));
			return array("pid" => "ff", "data" => array(0 => 'Kicked from server, "Bad packet id '.$pid.'"'));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->parse();
		
		$len = strlen($packet->raw);
		$p = "==".time()."==> RECIEVED Packet $pid, lenght $len:".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL .PHP_EOL;
		logg($p, "packets", false);
		
		return array("pid" => $pid, "data" => $packet->data);
	}
	
	public function writePacket($pid, $data = array(), $raw = false){
		$struct = $this->getStruct($pid);
		$packet = new Packet($pid, $struct);
		$packet->data = $data;
		$packet->create($raw);		
		$len = strlen($packet->raw);
		$p = "==".time()."==> SENT Packet $pid, lenght $len:".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL .PHP_EOL;
		logg($p, "packets", false);		
		return true;
	}
	



}


define("MAX_BUFFER_BYTES", 1024 * 1024 * 4); //4MB max of buffer
define("MIN_BUFFER_BYTES", 64);
ini_set("memory_limit", "512M");

define("HALF_BUFFER_BYTES", MAX_BUFFER_BYTES / 2);

class FileSocket{
	protected $sock, $buffer;
	function __construct($file){
		$this->sock = fopen($file, "r");
		$this->buffer = "";
	}
	public function close(){
		fclose($this->sock);
	}
	public function recieve($str){ //Auto write a packet
		$this->buffer .= $str;
		return true;
	}
	function get(){
		if(!isset($this->buffer{HALF_BUFFER_BYTES})){
			/*if(!isset($this->buffer{MIN_BUFFER_BYTES})){
				$this->block();
				$read = MIN_BUFFER_BYTES;
			}else{
				$this->unblock();
				$read = HALF_BUFFER_BYTES;
			}*/
			$read = fread($this->sock,HALF_BUFFER_BYTES);
			$this->recieve($read);
		}
	}	
	public function read($len){
		if($len <= 0){
			return "";
		}
		while(!isset($this->buffer{$len-1})){
			$this->get();		
		}
		$ret = substr($this->buffer, 0, $len);
		$this->buffer = substr($this->buffer, $len);
		return $ret;
		
	}

}