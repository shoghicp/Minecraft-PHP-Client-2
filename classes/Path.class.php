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