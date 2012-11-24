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