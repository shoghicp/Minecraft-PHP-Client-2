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

class MinecraftInterface{
	var $pstruct, $name, $server, $protocol;
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = 25565, $listen = false){
		$this->server = new Socket($server, $port, (bool) $listen);
		$this->protocol = (int) $protocol;
		require("pstruct/".$this->protocol.".php");
		require("pstruct/packetName.php");
		$this->pstruct = $pstruct;
		$this->name = $packetName;
	}
	
	public function close(){
		return $this->server->close();
	}
	
	protected function getPID($chr){
		return Utils::strToHex($chr);
	}
	
	protected function getStruct($pid){
		if(isset($this->pstruct[$pid])){
			return $this->pstruct[$pid];
		}
		return false;
	}
	
	protected function writeDump($pid, $raw, $data, $origin = "client"){
		if(LOG === true and DEBUG >= 2){
			$p = "[".microtime(true)."] [".($origin === "client" ? "CLIENT->SERVER":"SERVER->CLIENT")."]: ".$this->name[$pid]." (0x$pid) [lenght ".strlen($raw)."]".PHP_EOL;
			$p .= Utils::hexdump($raw);
			if(is_array($data)){
				foreach($data as $i => $d){
					$p .= $i ." => ".(!is_array($d) ? $this->pstruct[$pid][$i]."(".(($this->pstruct[$pid][$i] === "byteArray" or $this->pstruct[$pid][$i] === "newChunkArray" or $this->pstruct[$pid][$i] === "chunkArray" or $this->pstruct[$pid][$i] === "chunkInfo" or $this->pstruct[$pid][$i] === "multiblockArray" or $this->pstruct[$pid][$i] === "newMultiblockArray") ? Utils::strToHex($d):$d).")":$this->pstruct[$pid][$i]."(***)").PHP_EOL;
				}
			}
			$p .= PHP_EOL;
			logg($p, "packets", false);
		}
	
	}
	
	public function readPacket($mode = false){
		if($this->server->connected === false){
			return array("pid" => "ff", "data" => array(0 => 'Connection error', 1 => true));
		}
		$pid = $this->getPID($this->server->read(1, $mode));
		if($pid == ""){
			return false;
		}
		$struct = $this->getStruct($pid);
		if($struct === false){
			$this->server->unblock();
			$p = "[".microtime(true)."] [SERVER->CLIENT]: Error, bad packet id 0x$pid".PHP_EOL;
			$p .= Utils::hexdump(Utils::hexToStr($pid).$this->server->read(1024, true));
			$p .= PHP_EOL . "--------------- (1024 byte max extract) ----------" .PHP_EOL;
			logg($p, "packets", true, 3);
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Bad packet id '.$pid.''));
			$this->writePacket("ff", array(0 => 'Bad packet id '.$pid.''));
			return array("pid" => "ff", "data" => array(0 => 'Bad packet id '.$pid.''));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->protocol = $this->protocol;
		$packet->parse();
		
		$this->writeDump($pid, $packet->raw, $packet->data, "server");		
		return array("pid" => $pid, "data" => $packet->data, "raw" => $packet->raw);
	}
	
	public function writePacket($pid, $data = array(), $raw = false){
		$struct = $this->getStruct($pid);
		if($this->protocol >= 32){
			if($pid === "01"){
				$struct = array();
			}elseif($pid === "09"){
				$struct = array();
			}
		}
		if($raw === false){
			$packet = new Packet($pid, $struct);
			$packet->protocol = $this->protocol;
			$packet->data = $data;
			$packet->create();
			$write = $this->server->write($packet->raw);
			$this->writeDump($pid, $packet->raw, $data, "client");
		}else{
			$write = $this->server->write($data);
			$this->writeDump($pid, $data, false, "client");
		}		
		return true;
	}
	
}

?>