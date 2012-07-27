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


class CacheFile{
	protected $client;
	function __construct($client){
		$this->client = $client;
		$this->client->event("onSpoutCache", "handler", $this);
		$this->client->event("onPlayerSpawn", "handler", $this);
		console("[INFO] [CacheFile] Loaded");
	}

	public function handler($data, $event){
		switch($event){
			case "onSpoutCache":
				$dir = "spout/";
				$file = $data["file"];
				break;
			case "onPlayerSpawn":
				$dir = "skin/";
				$file = "http://s3.amazonaws.com/MinecraftSkins/".$data->name.".png";
				break;
		}
		if(isset($file) and !file_exists(FILE_PATH . "/data/".$dir . basename($file))){
			@mkdir(FILE_PATH . "/data/".$dir, 0777, true);
			@file_put_contents(FILE_PATH . "/data/".$dir . basename($file), @file_get_contents($file));
		}
	}

}
