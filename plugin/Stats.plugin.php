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


class Stats{
	var $stats;
	protected $client, $file;
	function __construct($client, $file){
		$this->client = $client;
		$this->file = $file;
		$this->load();
		console("[INFO] [Stats] Loaded");
	}
	
	public function delete($stat){
		unset($this->stats[$stat]);
		$this->save();
	}
	
	public function reset(){
		$this->stats = array();
		$this->save();
	}
	
	public function set($stat, $value){
		$this->stats[$stat] = $value;
		$this->save();
	}
	
	public function get($stat){
		if(isset($this->stats[$stat])){
			return $this->stats[$stat];
		}
		return 0;
	}

	public function increment($stat, $value = 1){
		if(!isset($this->stats[$stat])){
			$this->stats[$stat] = 0;
		}
		$this->stats[$stat] += $value;
		$this->save();
	}
	
	public function add($stat, $value){
		if(!isset($this->stats[$stat])){
			$this->stats[$stat] = array();
		}
		$this->stats[$stat][] = $value;
		$this->save();
	}
	
	protected function load(){
		if(file_exists($this->file)){
			$this->stats = unserialize(file_get_contents($this->file));
		}else{
			$this->stats = array();
		}
	}
	
	protected function save(){
		file_put_contents($this->file, serialize($this->stats));
	}


}