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


class Path{
	protected $path;
	function __construct(){
		$this->path = array();
	}
	
	public function get($i){
		$j = 0;
		foreach($this->path as $value){
			if($j === $i){
				return $value;
			}
			++$j;
		}
		return null;
	}
	
	public function add($pos){
		$x = (int) $pos["x"];
		$y = (int) $pos["y"];
		$z = (int) $pos["z"];
		$this->path[$x.".".$z.".".$y] = $pos;
	}
	
	public function remove($pos){
		$x = (int) $pos["x"];
		$y = (int) $pos["y"];
		$z = (int) $pos["z"];
		unset($this->path[$x.".".$z.".".$y]);
	}
	
	public function contains($pos){
		$x = (int) $pos["x"];
		$y = (int) $pos["y"];
		$z = (int) $pos["z"];
		return isset($this->path[$x.".".$z.".".$y]);
	}
	
	public function size(){
		return count($this->path);
	}
	
	public function reverse(){
		array_reverse($this->path);
	}
	
	public function toArray(){
		return $this->path;
	}

}