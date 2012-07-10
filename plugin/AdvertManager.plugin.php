<?php

class AdvertManager{
	protected $client, $event, $timeAds, $timeLapse, $timeLast, $spaceAds;
	function __construct($client, $timeLapse = 15){
		$this->timeAds = array();
		$this->spaceAds = array();
		$this->client = $client;
		$this->timeLapse = $timeLapse;
		$this->timeLast = Utils::microtime();
		$this->event = $this->client->event("onRecievedPacket", "handler", $this);
		console("[INFO] [AdvertManager] Loaded");
	}
	
	public function addTimeAdvert($text){
		$this->timeAds[] = $text;
	}
	
	public function addSpaceAdvert($text, $name){
		if(!isset($this->spaceAds[$name])){
			$this->spaceAds[$name] = array();
		}
		$this->spaceAds[$name][] = $text;
	}
	
	public function handler(){
		$time = Utils::microtime();
		if(($this->timeLast + ($this->timeLapse * 60)) <= $time){
			$this->client->say($this->timeAds[count($this->timeAds)-1]);
			$this->timeLast = $time;
		}
	}
	
	public function getSpace($name){
		if(isset($this->spaceAds[$name])){
			return $this->spaceAds[$name];
		}
		return array();
	}
	
	public function stop(){
		$this->deleteEvent("onPacketRecieved", "handler");
	}

}