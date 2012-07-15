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


class NoHunger{
	protected $client, $player, $only_food;
	function __construct($client, $only_food = true){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->client->event("onHealthChange", "handler", $this);
		$this->client->event("onInventoryChanged", "handler", $this);
		$this->only_food = $only_food;
		console("[INFO] [NoHunger] Loaded");
	}
	
	public function setOnlyFood($only_food){
		$this->only_food = $only_food;
	}	
	public function handler($health, $event){
		include("misc/materials.php");
		for($i=36;$i<=44;++$i){
			$slot = $this->client->getInventorySlot($i);
			if($event == "onHealthChange" and isset($food[$slot[0]]) == true and ($health["food"] + $food[$slot[0]]) <= 20){
				$this->client->changeSlot($i-36);
				$this->client->eatSlot();
				$eat = true;
				console("[DEBUG] [NoHunger] Eated ".$slot[0], true, true, 2);
				break;
			}elseif(!isset($food[$slot[0]]) and $this->only_food == true){
				for($a=0;$a<min(3,$slot[1]);++$a){
					$this->client->changeSlot($i-36);
					$this->client->dropSlot();
				}
			}
		}
	}

}
