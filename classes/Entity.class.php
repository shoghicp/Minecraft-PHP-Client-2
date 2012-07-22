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

class Entity{
	var $eid, $type, $name, $object, $position, $dead;
	protected $health, $food;
	
	function __construct($eid, $type, $object = false){ //$type = 0 ---> player
		$this->eid = intval($eid);
		$this->object = $object;
		$this->type = intval($type);
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
	
	public function setCoords($x, $y, $z){
		$this->position["x"] = $x;
		$this->position["y"] = $y;
		$this->position["z"] = $z;
		$this->updateStance();
		return true;
	}
	
	public function move($x, $y, $z, $yaw = 0, $pitch = 0){
		$this->position["x"] += $x;
		$this->position["y"] += $y;
		$this->position["z"] += $z;
		$this->position["yaw"] += $yaw;
		$this->position["yaw"] = $this->position["yaw"] < 0 ? (360 - $this->position["yaw"]):($this->position["yaw"] > 360 ? ($this->position["yaw"] - 360):$this->position["yaw"]);
		$this->position["pitch"] += $pitch;
		$this->position["pitch"] = $this->position["pitch"] > 90 ? 90:($this->position["pitch"] < -90 ? -90:$this->position["pitch"]);
		$this->updateStance();
		return true;
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
		return !isset($this->position) ? array():($round === true ? array_map("round", $this->position):$this->position);
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