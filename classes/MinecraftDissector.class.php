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
	
	protected function writeDump($pid, $raw, $data, $origin = "client"){
		if(LOG === true and DEBUG >= 2){
			$len = strlen($raw);
			$p = "[".Utils::microtime()."] [".($origin === "client" ? "CLIENT->SERVER":"SERVER->CLIENT")."]: ".$this->name[$pid]." (0x$pid) [lenght $len]".PHP_EOL;
			$p .= hexdump($raw, false, false, true);
			foreach($data as $i => $d){
				$p .= $i ." => ".(!is_array($d) ? $this->pstruct[$pid][$i]."(".(($this->pstruct[$pid][$i] === "byteArray" or $this->pstruct[$pid][$i] === "newChunkArray" or $this->pstruct[$pid][$i] === "chunkArray" or $this->pstruct[$pid][$i] === "chunkInfo" or $this->pstruct[$pid][$i] === "multiblockArray" or $this->pstruct[$pid][$i] === "newMultiblockArray") ? Utils::strToHex($d):$d).")":$this->pstruct[$pid][$i]."(***)").PHP_EOL;
			}
			$p .= PHP_EOL;
			logg($p, "packets", false);
		}
	
	}	
	public function readPacket(){		
		$pid = $this->getPID($this->server->read(1));
		$struct = $this->getStruct($pid);
		if($struct === false){
			$this->server->unblock();
			$p = "[".round(Utils::microtime(),4)."] [ERROR]: Bad packet id 0x$pid".PHP_EOL;
			$p .= hexdump(Utils::hexToStr($pid).$this->server->read(1024, true), false, false, true);
			$p .= PHP_EOL . "--------------- (1024 byte max extract) ----------" .PHP_EOL;
			logg($p, "packets");
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Bad packet id '.$pid.''));
			$this->writePacket("ff", array(0 => 'Bad packet id '.$pid.''));
			return array("pid" => "ff", "data" => array(0 => 'Bad packet id '.$pid.''));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->parse();
		
		$this->writeDump($pid, $packet->raw, $packet->data, "server");
		
		return array("pid" => $pid, "data" => $packet->data);
	}
	
	public function writePacket($pid, $data = array(), $raw = false){
		$struct = $this->getStruct($pid);
		$packet = new Packet($pid, $struct);
		$packet->data = $data;
		$packet->create($raw);		
		$this->writeDump($pid, $packet->raw, $data, "client");		
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