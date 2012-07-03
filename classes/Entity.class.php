<?php



class Entity{
	var $eid, $type, $name, $object, $position;
	protected $health, $food;
	
	function __construct($eid, $type, $object = false){ //$type = 0 ---> player
		$this->eid = intval($eid);
		$this->object = $object;
		$this->type = intval($type);
		$this->health = 20;
		$this->food = 20;
		$this->position = array(
			"x" => 0,
			"y" => 0,
			"z" => 0,
			"stance" => 0,
			"yaw" => 0,
			"pitch" => 0,
			"ground" => true,
		);
	}
	
	public function setName($name){
		$this->name = $name;
	}
	
	public function setCoords($x, $y, $z){
		$this->position["x"] = $x;
		$this->position["y"] = $y;
		$this->position["z"] = $z;
		return true;
	}
	
	public function move($x, $y, $z){
		$this->position["x"] += $x;
		$this->position["y"] += $y;
		$this->position["z"] += $z;
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
		return true;
	}
	
	public function setGround($ground){
		$this->position["ground"] = $ground;
	}
	
	public function setHealth($health){
		$this->health = $health;
	}
	
	public function getHealth(){
		return $health;
	}
	
	public function setFood($food){
		$this->food = $food;
	}

	public function getFood(){
		return $food;
	}
	
	public function packet($pid){
		switch($pid){
			case "0a":
				return array(
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