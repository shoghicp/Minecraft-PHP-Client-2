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


class CacheFile{
	protected $client;
	function __construct($client){
		$this->client = $client;
		$this->client->event("onSpoutCache", "handler", $this);
		$this->client->event("onPlayerSpawn", "handler", $this);
		$this->client->event("onPlayerPing", "handler", $this);
		console("[INFO] [CacheFile] Loaded");
	}

	public function handler($data, $event){
		$redo = false;
		switch($event){
			case "onPluginMessage_MC|TPack":
				$dir = "texturepack/";
				$file = substr($data, 0, -2);
				break;
			case "onSpoutCache":
				$dir = "spout/";
				$file = $data["file"];
				break;
			case "onPlayerSpawn":
				$dir = "skin/";
				$file = "http://s3.amazonaws.com/MinecraftSkins/".$data->name.".png";
				$redo = true;
				break;
			case "onPlayerPing":
				$dir = "skin/";
				$file = "http://s3.amazonaws.com/MinecraftSkins/".$data["name"].".png";
				$redo = true;
				break;
		}
		if(isset($file) and ($redo === true or !file_exists(FILE_PATH . "/data/".$dir . basename($file)))){
			@mkdir(FILE_PATH . "/data/".$dir, 0777, true);
			$cnt = @file_get_contents($file);
			if($cnt != ""){
				@file_put_contents(FILE_PATH . "/data/".$dir . basename($file), $cnt);
			}
		}
	}

}
