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

class LagOMeter{
	protected $client, $last, $lag, $start, $ev1, $ev2, $minTime;
	
	function __construct($client, $minTime = 4){
		$this->client = $client;
		$this->lag = false;
		$this->last = microtime(true);
		$this->minTime = $minTime;
		console("[INFO] [LagOMeter] Loaded");
		$this->ev1 = $this->client->event("onRecievedPacket", "handler", $this);
		$this->ev2 = $this->client->event("onTick", "meter", $this);
	}
	
	public function handler($data){
		$this->last = microtime(true);
	}
	
	public function meter(){
		if($this->lag === true){
			if($this->last > $this->start){
				$this->lag = false;
				$this->client->trigger("onLagEnd", microtime(true) - $this->start);
				console("[DEBUG] [LagOMeter] Lag ended (".(microtime(true) - $this->start)." sec)", true, true, 2);
			}else{
				$this->client->trigger("onLag", microtime(true) - $this->start);
			}
		}elseif((microtime(true) - $this->last) >= $this->minTime){
			$this->lag = true;
			$this->start = $this->last;
			$this->client->trigger("onLagStart");
			console("[DEBUG] [LagOMeter] Lag started", true, true, 2);
		}
	}
	
	public function stop(){
		$this->client->deleteEvent("onPacketRecieved", $this->ev1);
		$this->client->deleteEvent("onTick", $this->ev2);
	}


}