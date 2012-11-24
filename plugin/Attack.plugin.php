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

class Attack{
	protected $client, $player, $event, $attack, $special;
	var $boss;
	var $aura, $playeraura;
	function __construct($client){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->aura = false;
		$this->boss = false;
		$this->playeraura = false;
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
		if($pos === false){
			return;
		}
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
		$entities = $this->client->query("SELECT EID,class,x,y,z,name FROM entities WHERE EID != ".$this->player->eid." AND ((class = ".ENTITY_PLAYER." AND abs(x - ".$pos["x"].") <= 20 AND abs(y - ".$pos["y"].") <= 4 AND abs(z - ".$pos["z"].") <= 20)".($this->aura === true ? " OR (class = ".ENTITY_MOB." AND abs(x - ".$pos["x"].") <= 4 AND abs(y - ".$pos["y"].") <= 4 AND abs(z - ".$pos["z"].") <= 4)":"").");");
		if($entities === false or $entities === true){
			return;
		}
		while($entity = $entities->fetchArray(SQLITE3_ASSOC)){
			if(($entity["class"] === ENTITY_PLAYER and ($this->playeraura === true or isset($this->attack[$entity["EID"]]))) or ($entity["class"] === ENTITY_MOB)){
				$pos2 = array("x" => $entity["x"], "y" => $entity["y"], "z" => $entity["z"]);
				$dist = Utils::distance($pos, $pos2);
				if((isset($this->attack[$entity["EID"]]) or $this->playeraura === true) and $dist <= 20){
					$this->player->look($pos2);
					if($this->action !== false){
						eval(str_replace("{{PLAYER}}", $entity["name"], $action));
					}
				}
				if($dist <= 4){
					$this->client->useEntity($entity["EID"]);
				}
			}
		}
	
	}

}


