<?php

class LagOMeter{
	protected $client, $last, $lag, $start, $ev1, $ev2, $minTime;
	
	function __construct($client, $minTime = 4){
		$this->client = $client;
		$this->lag = false;
		$this->last = Utils::microtime();
		$this->minTime = $minTime;
		console("[INFO] [LagOMeter] Loaded");
		$this->ev1 = $this->client->event("onRecievedPacket", "handler", $this);
		$this->ev2 = $this->client->event("onTick", "meter", $this);
	}
	
	public function handler($data){
		$this->last = Utils::microtime();
	}
	
	public function meter($data){
		if($this->lag === true){
			if($this->last > $this->start){
				$this->lag = false;
				$this->client->trigger("onLagEnd", Utils::microtime() - $this->start);
				console("[INFO] [LagOMeter] Lag ended (".(Utils::microtime() - $this->start)." sec)");
			}
		}elseif((Utils::microtime() - $this->last) >= $this->minTime){
			$this->lag = true;
			$this->start = $this->last;
			$this->client->trigger("onLagStart");
			console("[INFO] [LagOMeter] Lag started");
		}
	}
	
	public function stop(){
		$this->client->deleteEvent("onPacketRecieved", $this->ev1);
		$this->client->deleteEvent("onTick", $this->ev2);
	}


}