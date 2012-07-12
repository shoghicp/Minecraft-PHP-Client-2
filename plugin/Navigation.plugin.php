<?php


class Navigation{
	protected $client, $player, $map, $materials, $event;
	function __construct($client){
		$this->client = $client;
		$this->player = $this->client->getPlayer();
		$this->map = $this->client->map;
		include("misc/materials.php");
		$this->material = $material;
		$this->client->event("onSpoutBlock", "spoutBlock", $this);
		$this->last = Utils::microtime();
		$this->maxBlocksPerTick = 0.1; //speed
		$this->fly = false;
		$this->speedY = 0;
		$this->event = $this->client->event("onTick", "walker", $this);
		console("[INFO] [Navigation] Loaded");
	}
	
	public function go($x, $y, $z){
		$this->target = array("x" => $x, "y" => $y, "z" => $z);
	}
	
	public function stop(){
		$this->deleteEvent("onTick", $this->event);
	}
	
	public function walker($time){
		$pos = $this->player->getPosition();
		$zone = $this->getZone(1,true);
		if(isset($this->material["nosolid"][$zone[0][0][-1][0]]) and $this->fly === false){ //Air
			$this->speedY += 0.9;
			$pos["y"] -= $this->speedY;
			$pos["ground"] = false;
		}elseif($this->fly === false){
			$pos["y"] = floor($pos["y"]);
			$this->speedY = 0;
			$pos["ground"] = true;
		}
		
		$this->player->setPosition($pos["x"],$pos["y"],$pos["z"],$pos["stance"],$pos["yaw"],$pos["pitch"],$pos["ground"]);
	}
	
	public function spoutBlock($data){
		$this->material[$data["id"]] = $data["info"];
	}
	
	public function getBlockName($id){
		if(isset($this->material[$id])){
			return $this->material[$id];
		}
		return "Unknown";
	}
	
	public function getBlock($x, $y, $z){
		return $this->map->getBlock($x, $y, $z);
	}
	
	public function getColumn($x, $z){
		return $this->map->getColumn($x, $z);
	}
	
	public function getRelativeBlock($x = 0, $y = 0, $z = 0){
		$pos = $this->player->getPosition();
		return $this->map->getBlock($pos["x"] + $x, $pos["y"] + $y, $pos["z"] + $z);
	}
	
	public function getRelativeColumn($x, $z){
		$pos = $this->player->getPosition();
		$data = $this->map->getColumn($pos["x"], $pos["z"]);
		if($relative === true){
			$data2 = array();
			foreach($data as $x => $a1){
				$data2[$x - $pos["x"]] = array();
				foreach($a1 as $z => $a2){
					$data2[$x - $pos["x"]][$z - $pos["z"]] = array();
					foreach($a2 as $y => $block){
						$data2[$x - $pos["x"]][$z - $pos["z"]][$y - $pos["y"]] = $block;
					}
				}
			}
			return $data2;
		}
		return $data;
	}
	
	public function getZone($radius = 16, $relative = false){
		$pos = $this->player->getPosition(true);
		$data = $this->map->getSphere($pos["x"], $pos["y"], $pos["z"], $radius);
		if($relative === true){
			$data2 = array();
			foreach($data as $x => $a1){
				$data2[$x - $pos["x"]] = array();
				foreach($a1 as $z => $a2){
					$data2[$x - $pos["x"]][$z - $pos["z"]] = array();
					foreach($a2 as $y => $block){
						$data2[$x - $pos["x"]][$z - $pos["z"]][$y - $pos["y"]] = $block;
					}
				}
			}
			return $data2;
		}
		return $data;
	}

}


