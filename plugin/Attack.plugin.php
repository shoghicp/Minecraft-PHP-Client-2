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
	protected $client, $player, $event, $attack, $special;
	var $boss;
	var $aura;
	function __construct($client){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->aura = false;
		$this->boss = false;
		$this->attack = array();
		$this->special = array();
		$this->event = $this->client->event("onTick", "handler", $this);
		console("[INFO] [Attack] Loaded");
	}
	
	public function addSpecial($lapse, $action){
		$this->special[] = array($lapse, microtime(true), $action);
	}
	
	public function attack($EID){
		$this->attack[$EID] = true;
	}

	public function peace($EID){
		unset($this->attack[$EID]);
	}
	
	function handler($time){
		$pos = $this->player->getPosition();
		$action = false;
		if($this->boss === true){
			foreach($this->special as $i => $info){
				if($info[1] <= ($time - $info[0])){
					$this->special[$i][1] = $time;
					$action = $info[2];
					break;
				}
			}
		}
		$entities = $this->client->query("SELECT EID,class FROM entities WHERE (class = ".ENTITY_PLAYER." AND abs(x - ".$pos["x"].") <= 20 AND abs(y - ".$pos["y"]." <= 4 AND abs(z - ".$pos["z"].") <= 20) <= 20)".($this->aura === true ? " OR ((class = ".ENTITY_MOB." OR class = ".ENTITY_OBJECT.") AND abs(x - ".$pos["x"].") <= 4 AND abs(y - ".$pos["y"]." <= 4 AND abs(z - ".$pos["z"].") <= 4)":"").");");
		if($entities === false or $entities === true){
			return;
		}
		while($entity = $entities->fetchArray(SQLITE3_ASSOC)){
			if(($entity["class"] === ENTITY_PLAYER and isset($this->attack[$entity["EID"]])) or ($entity["class"] === ENTITY_MOB or $entity["class"] === ENTITY_OBJECT)){
				$entity = $client->entities[$entity["EID"]];
				$pos2 = $entity->getPosition();
				$dist = Utils::distance($pos, $pos2);
				if(isset($this->attack[$EID]) and $dist <= 20){
					$this->player->look($pos2);
					if($this->action !== false){
						eval(str_replace("{{PLAYER}}", $entity->name, $action));
					}
				}
				if($dist <= 4){
					$this->client->useEntity($entity->eid);
				}
			}		
		}
	
	}

}


