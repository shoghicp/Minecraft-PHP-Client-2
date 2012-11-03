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
				Version 2, December 2004

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

			DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


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
