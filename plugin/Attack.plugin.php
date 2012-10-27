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

class Attack{
	protected $client, $player, $event, $attack;
	var $aura;
	function __construct($client){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->aura = false;
		$this->attack = array();
		$this->event = $this->client->event("onTick", "handler", $this);
		console("[INFO] [Attack] Loaded");
	}
	
	public function attack($EID){
		$this->attack[$EID] = true;
	}

	public function peace($EID){
		unset($this->attack[$EID]);
	}
	
	function handler(){
		$pos = $this->player->getPosition();
		foreach($this->client->mobs as $EID => $entity){
			if((($entity->class === ENTITY_PLAYER and isset($this->attack[$EID])) or $entity->class === ENTITY_MOB) and ($this->aura === true or isset($this->attack[$EID]))){
				$pos2 = $entity->getPosition();
				$dist = Utils::distance($pos, $pos2);
				if($dist <= 4){
					$this->client->useEntity($EID);	
				}
			}
		
		}
	
	}

}


