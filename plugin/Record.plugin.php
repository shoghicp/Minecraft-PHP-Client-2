<?php


class RecordPath{
	protected $eid, $start, $path, $event, $client;
	function __construct($client, $EID){
		$this->eid = $EID;
		$this->client = $client;
		$this->start = Utils::microtime();
		$this->event = $this->client->event("onEntityMove_".$EID, "onMove", $this);
		$this->path = array();
	}	
	public function onMove($entity){
		$coords = $entity->getPosition();
		$this->path[] = array("time" => Utils::microtime() - $this->start, "coords" => array("x" => $coords["x"], "y" => $coords["y"], "z" => $coords["z"]));	
	}
	public function getPath(){
		return $this->path;
	}
	public function stop(){
		$this->client->deleteEvent("onEntityMove_".$this->eid, $this->event);
	}
}


class PlayPath{
	protected $start, $path, $event, $client;
	function __construct($client, $path){
		$this->client = $client;
		$this->start = Utils::microtime();
		$this->event = $this->client->event("onRecievedPacket", "followPath", $this);
		$this->path = $path;
	}	
	public function followPath($time, $event, $ob){
		foreach($this->path as $i => $data){
			if($data["time"] <= Utils::microtime() - $this->start){
				$ob->move($data["coords"]["x"], $data["coords"]["y"], $data["coords"]["z"]);
				unset($this->path[$i]);
			}
			break;
		}
		if(count($this->path) == 0){
			$this->stop();
		}
	}
	public function stop(){
		$this->client->deleteEvent("onRecievedPacket", $this->event);
	}
}