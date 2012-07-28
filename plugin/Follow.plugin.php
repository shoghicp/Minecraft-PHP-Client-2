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


class FollowPath{
	protected $eid, $start, $path, $event, $event2, $client;
	function __construct($client, $EID){
		$this->eid = $EID;
		$this->client = $client;
		$this->start = microtime(true);
		$this->event = $this->client->event("onEntityMove_".$EID, "onMove", $this);
		$this->event2 = $this->client->event("onTick", "followPath", $this);
		$this->path = array();
	}	
	public function onMove($entity){
		$coords = $entity->getPosition();
		$this->path[] = array("time" => microtime(true) - $this->start, "coords" => array("x" => $coords["x"], "y" => $coords["y"], "z" => $coords["z"]));	
	}
	public function followPath($time, $event, $ob){
		if(count($this->path) === 0){
			return;
		}
		foreach($this->path as $i => $data){
			if(($data["time"] + 2) <= microtime(true) - $this->start){
				$ob->move($data["coords"]["x"], $data["coords"]["y"], $data["coords"]["z"]);
				unset($this->path[$i]);
			}
			break;
		}
		
	}
	public function stop(){
		$this->client->deleteEvent("onEntityMove_".$this->eid, $this->event);
		$this->client->deleteEvent("onTick", $this->event2);
	}
}