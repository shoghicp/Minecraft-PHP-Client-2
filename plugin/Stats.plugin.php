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


class Stats{
	var $stats;
	protected $file;
	function __construct($client, $file){
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