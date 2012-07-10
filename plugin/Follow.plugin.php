<?php


class FollowPath{
	protected $eid, $start, $path, $event, $event2, $client;
	function __construct($client, $EID){
		$this->eid = $EID;
		$this->client = $client;
		$this->start = Utils::microtime();
		$this->event = $this->client->event("onEntityMove_".$EID, "onMove", $this);
		$this->event2 = $this->client->event("onRecievedPacket", "followPath", $this);
		$this->path = array();
	}	
	public function onMove($entity){
		$coords = $entity->getPosition();
		$this->path[] = array("time" => Utils::microtime() - $this->start, "coords" => array("x" => $coords["x"], "y" => $coords["y"], "z" => $coords["z"]));	
	}
	public function followPath($time, $event, $ob){
		if(count($this->path) == 0){
			return;
		}
		foreach($this->path as $i => $data){
			if(($data["time"] + 2) <= Utils::microtime() - $this->start){
				$ob->move($data["coords"]["x"], $data["coords"]["y"], $data["coords"]["z"]);
				unset($this->path[$i]);
			}
			break;
		}
		
	}
	public function stop(){
		$this->client->deleteEvent("onEntityMove_".$this->eid, $this->event);
		$this->client->deleteEvent("onRecievedPacket", $this->event2);
	}
}