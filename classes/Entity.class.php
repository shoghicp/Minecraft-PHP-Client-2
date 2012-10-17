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


define("ENTITY_PLAYER", 0);
define("ENTITY_MOB", 1);
define("ENTITY_OBJECT", 2);
define("ENTITY_ITEM", 3);
define("ENTITY_PAINTING", 4);
define("ENTITY_EXPERIENCE", 5);

class Entity{
	var $eid, $type, $name, $position, $dead, $metadata, $class;
	protected $health, $food;
	
	function __construct($eid, $class, $type = 0){ //$type = 0 ---> player
		$this->eid = (int) $eid;
		$this->type = (int) $type;
		$this->class = (int) $class;
		$this->metadata = array();
		include("misc/entities.php");
		switch($this->class){
			case ENTITY_PLAYER:
			case ENTITY_ITEM:
			case ENTITY_EXPERIENCE:
				break;
				
			case ENTITY_MOB:
				$this->name = isset($mobs[$this->type]) ? $mobs[$this->type]:$this->type;
				break;
			case ENTITY_OBJECT:
				$this->name = isset($objects[$this->type]) ? $objects[$this->type]:$this->type;
				break;
		}
		
		$this->health = 20;
		$this->food = 20;
		$this->dead = false;
	}
	
	public function getEID(){
		return $this->eid;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function setName($name){
		$this->name = $name;
	}
	
	public function setMetadata($metadata){
		foreach($metadata as $key => $value){
			$this->metadata[$key] = $value;
			switch($key){
				case 0:
					$this->metadata["onFire"] = ($value & 0x01) === 0x01 ? true:false;
					$this->metadata["crouched"] = ($value & 0x02) === 0x02 ? true:false;
					$this->metadata["riding"] = ($value & 0x04) === 0x04 ? true:false;
					$this->metadata["sprinting"] = ($value & 0x08) === 0x08 ? true:false;
					$this->metadata["action"] = ($value & 0x10) === 0x10 ? true:false;
					break;
				case 1:
					$this->metadata["air"] = $value;
					break;
				case 8:
					$this->metadata["effectColor"] = $value;
					break;
				case 12:
					$this->metadata["grow"] = $value;
					break;
				case 16:
					switch($this->class){
						case ENTITY_PLAYER:
							$this->metadata["showCape"] = $value === 1 ? true:false;
							break;
						case ENTITY_MOB:
							switch($this->type){
								case 50:
									$this->metadata["status"] = $value;
									break;
								case 52:
								case 59:
								case 56: //Ghast
								case 61: //Blaze
									$this->metadata["aggresive"] = $value === 1 ? true:false;
									break;
								case 55:
								case 62:
									$this->metadata["size"] = $value;
									break;
								case 58:
									$this->metadata["item"] = $value;
									break;
								case 63:
								case 64: //Wither
									$this->metadata["health"] = $value;
									break;
								case 90:
									$this->metadata["saddle"] = $value === 1 ? true:false;
									break;
								case 91:
									$this->metadata["color"] = $value & 0x0F;
									$this->metadata["sheared"] = ($value & 0x10) === 0x10 ? true:false;
									break;
								case 95:
									$this->metadata["sit"] = ($value & 0x01) === 0x01 ? true:false;
									$this->metadata["aggresive"] = ($value & 0x02) === 0x02 ? true:false;
									$this->metadata["tamed"] = ($value & 0x04) === 0x04 ? true:false;
									break;
								case 98:
									$this->metadata["sit"] = ($value & 0x01) === 0x01 ? true:false;
									$this->metadata["tamed"] = ($value & 0x04) === 0x04 ? true:false;								
									break;
								case 99:
									$this->metadata["flower"] = $value === 1 ? true:false;
									break;
								case 120:
									$this->metadata["type"] = $value;
									break;
							}
							
							break;
						case ENTITY_OBJECT:
							switch($this->type){
								case 60:
									$this->metadata["recoverable"] = $value === 1 ? true:false;
									break;
								case 10:
								case 11:
								case 12:
									$this->metadata["fuel"] = ($value & 0x01) === 0x01 ? true:false;
									break;
							}
							break;					
					}
					break;
				case 17:
					switch($this->class){							
						case ENTITY_MOB:
							switch($this->type){
								case 50:
									$this->metadata["charged"] = $value === 1 ? true:false;
									break;
								case 58:
									$this->metadata["metadata"] = $value;
									break;
								case 95:
								case 98:
									$this->metadata["player"] = $value;							
									break;
								case 99:
									$this->metadata["flower"] = $value === 1 ? true:false;
									break;
								case 120:
									$this->metadata["type"] = $value;
									break;
							}
							
							break;
						case ENTITY_OBJECT:
							switch($this->type){
								case 10:
								case 11:
								case 12:
									//$this->metadata[17] = $value;
									break;
								case 1:
									$this->metadata["hit"] = $value;
									break;
							}
							break;					
					}
					break;
				case 18:
					switch($this->class){							
						case ENTITY_MOB:
							switch($this->type){
								case 58:
									$this->metadata["aggresive"] = $value === 1 ? true:false;
									break;
								case 95:
								case 98:
									$this->metadata["health"] = $value;
									break;
							}
							
							break;
						case ENTITY_OBJECT:
							switch($this->type){
								case 10:
								case 11:
								case 12:
									//$this->metadata[18] = $value;
									break;
								case 1:
									$this->metadata["direction"] = $value;
									break;
							}
							break;					
					}
					break;
				case 19:
					switch($this->class){							
						case ENTITY_MOB:
							switch($this->type){
								case 95:
									$this->metadata[19] = $value === 1 ? true:false;
									break;
							}
							
							break;
						case ENTITY_OBJECT:
							switch($this->type){
								case 10:
								case 11:
								case 12:
								case 1:
									$this->metadata["damage"] = $value;
									break;
							}
							break;					
					}
					break;
				case 20:
					switch($this->class){							
						case ENTITY_MOB:
							switch($this->type){
								case 64: //Wither
									$this->metadata["counter"] = $value;
									break;
							}
							
							break;
						case ENTITY_OBJECT:
							switch($this->type){
							}
							break;					
					}
					break;
			}
		}
	}
	
	public function setCoords($x, $y, $z){
		if(!isset($this->position)){
			$this->position = array(
				"x" => 0,
				"y" => 0,
				"z" => 0,
				"stance" => 0,
				"yaw" => 0,
				"pitch" => 0,
				"ground" => 0,
			);		
		}
		$this->position["x"] = $x;
		$this->position["y"] = $y;
		$this->position["z"] = $z;
		$this->updateStance();
	}
	
	public function move($x, $y, $z, $yaw = 0, $pitch = 0){
		if(!isset($this->position)){
			$this->position = array(
				"x" => 0,
				"y" => 0,
				"z" => 0,
				"stance" => 0,
				"yaw" => 0,
				"pitch" => 0,
				"ground" => 0,
			);		
		}
		$this->position["x"] += $x;
		$this->position["y"] += $y;
		$this->position["z"] += $z;
		$this->position["yaw"] += $yaw;
		$this->position["yaw"] = abs($this->position["yaw"] % 360);
		$this->position["pitch"] += $pitch;
		$this->position["pitch"] = abs($this->position["pitch"] % 90);
		$this->updateStance();
	}
	
	public function setPosition($x, $y, $z, $stance, $yaw, $pitch, $ground){
		$this->position = array(
			"x" => $x,
			"y" => $y,
			"z" => $z,
			"stance" => $stance,
			"yaw" => $yaw,
			"pitch" => $pitch,
			"ground" => $ground,
		);
		$this->updateStance();
		return true;
	}
	
	protected function updateStance(){
		$this->position["stance"] = $this->position["y"] + 1.3;
	}
	
	public function getPosition($round = false){
		return !isset($this->position) ? false:($round === true ? array_map("floor", $this->position):$this->position);
	}
	
	public function setGround($ground){
		$this->position["ground"] = $ground;
	}
	
	public function setHealth($health){
		$this->health = $health;
	}
	
	public function getHealth(){
		return $this->health;
	}
	
	public function setFood($food){
		$this->food = $food;
	}

	public function getFood(){
		return $this->food;
	}
	
	public function packet($pid){
		if(!isset($this->position)){
			return array();
		}
		switch($pid){
			case "0a":
				return array(
					$this->position["ground"],
				);
				break;
			case "0b":
				return array(
					$this->position["x"],
					$this->position["y"],
					$this->position["stance"],
					$this->position["z"],
					$this->position["ground"],
				);
				break;
			case "0d":
				return array(
					$this->position["x"],
					$this->position["y"],
					$this->position["stance"],
					$this->position["z"],
					$this->position["yaw"],
					$this->position["pitch"],
					$this->position["ground"],
				);
				break;
		
		}
		
	}


}

?>