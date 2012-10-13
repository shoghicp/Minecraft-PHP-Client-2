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

class NBT{
	var $data, $source;
	protected $offset = 0;
	function __construct($source, $raw = false){
		if($raw !== true){
			$source = file_get_contents($source);
		}
		$this->source = $source;
	}
	
	protected function read($len){
		$offset += $len;
		return substr($this->source, $offset - $len, $len);
	}
	
	function parse(){
		$data = array();
		$d =& $data;
		$c = array();
		$c[0] =& $data;
		$level = 0;
		while(true){
			$tag = ord($this->read(1));
			$name = $this->read(Utils::readShort($this->read(2)));
			switch($tag){
				case 0:
					$d =& $c[$level];
					--$level;
					break;
				case 1:
					$d[$name] = Utils::readByte($this->read(1));
					break;
				case 2:
					$d[$name] = Utils::readShort($this->read(2));
					break;
				case 3:
					$d[$name] = Utils::readInt($this->read(4));
					break;
				case 4:
					$d[$name] = Utils::readLong($this->read(8));
					break;
				case 5:
					$d[$name] = Utils::readFloat($this->read(4));
					break;
				case 6:
					$d[$name] = Utils::readDouble($this->read(8));
					break;
				case 7:
					$d[$name] = array();
					++$level;
					$c[$level] =& $d;
					$d =& $d[$name];					
					break;
			}
			
		}
	}
}