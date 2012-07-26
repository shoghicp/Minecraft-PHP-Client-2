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


class Anvil{
	protected $block, $raw;
	
	function __construct(){
		$this->block = array();
	}
	
	protected function splitColumns($data, $bitmask, $X, $Z){
		$offset = 0;
		$blockData = "";
		$metaData = "";
		$len = HEIGHT_LIMIT >> 4;
		$lastBlock = HEIGHT_LIMIT;
		for($i = 0; $i < $len; ++$i){
			if ($bitmask & (1 << $i)){
				$blockData .= substr($data, $offset, 4096);
				$offset += 4096;
				$lastBlock = $i << 4;
			}elseif(isset($this->block[$X][$Z])){
				$blockData .= substr($this->block[$X][$Z][0], $i * 4096, 4096);
				$lastBlock = $i << 4;
			}else{
				$blockData .= str_repeat("\x00", 4096);
			}
		}
		for($i=0; $i < $len; ++$i){
			if ($bitmask & 1 << $i){
				$metaData .= substr($data, $offset, 2048);
				$offset += 2048;
			}elseif(isset($this->block[$X][$Z])){
				$metaData .= substr($this->block[$X][$Z][1], $i * 2048, 2048);
			}else{
				$metaData .= str_repeat("\x00", 2048);
			}
		}
		if(!isset($this->block[$X])){
			$this->block[$X] = array();
		}
		$this->block[$X][$Z] = array(0 => $blockData, 1 => $metaData, 2 => $lastBlock);
		console("[DEBUG] [Anvil] Parsed X ".$X.", Z ".$Z, true, true, 2);
	}
	
	public function addChunk($X, $Z, $data, $bitmask, $compressed = true){
		$X *= 16;
		$Z *= 16;
		$this->splitColumns(($compressed === true ? gzinflate(substr($data,2)):$data), $bitmask, $X, $Z);
		console("[INTERNAL] [Anvil] Loaded X ".$X.", Z ".$Z, true, true, 3);
	}
	
	public function unloadChunk($X, $Z){
		$X *= 16;
		$Z *= 16;
		unset($this->block[$X][$Z]);
		unset($this->raw[$X][$Z]);
		console("[DEBUG] [Anvil] Unloaded X ".$X." Z ".$Z, true, true, 2);
	}
	
	public function getIndex($x, $y, $z){
		$X = ($x >> 4) << 4;
		$Z = ($z >> 4) << 4;
		$aX = $x - $X;
		$aZ = $z - $Z;
		$index = $y * HEIGHT_LIMIT + $aZ * 16 + $aX;
		return array($X, $Z, $index);
	}
	
	public function getFloor($x, $z){
		$index = $this->getIndex($x, HEIGHT_LIMIT, $z);
		if(!isset($this->block[$index[0]][$index[1]])){
			return array(0, 0, 0);
		}
		$index = $this->getIndex($x, $this->block[$index[0]][$index[1]][2] + 16, $z);
		include("misc/materials.php");
		$b =& $this->block[$index[0]][$index[1]][0];
		for($y = $this->block[$index[0]][$index[1]][2] + 16; $y > 0; --$y){
			if(!isset($material["nosolid"][ord($b{$index[2]})])){
				break;
			}
			$index[2] -= HEIGHT_LIMIT;
		}
		$block = $this->getBlock($x, $y, $z, $index);
		return array($y, $block[0], $block[1]);
	}
	
	public function getBlock($x, $y, $z, $index = false){
		if($index === false){
			$index = $this->getIndex($x, $y, $z);
		}
		if(!isset($this->block[$index[0]][$index[1]])){
			return array(0, 0);
		}
		$block = ord($this->block[$index[0]][$index[1]][0]{$index[2]});
		$meta = ord($this->block[$index[0]][$index[1]][1]{$index[2] >> 1});
		if($index[2] % 2 === 0){
			$meta = $meta & 0x0F;
		}else{
			$meta = $meta >> 4;
		}
		return array($block, $meta);
	}
	
	public function changeBlock($x, $y, $z, $block, $metadata = 0){
		console("[INTERNAL] [Anvil] Changed block X ".$x." Y ".$y." Z ".$z, true, true, 3);
		$index = $this->getIndex($x, $y, $z);
		if(isset($this->block[$index[0]][$index[1]][0]{$index[2]})){
			$this->block[$index[0]][$index[1]][0]{$index[2]} = chr($block);
			if($index[2] % 2 === 0){
				$this->block[$index[0]][$index[1]][1]{$index[2] >> 1} = chr((ord($this->block[$index[0]][$index[1]][1]{$index[2] >> 1}) & 0xF0) | ($meta & 0x0F));
			}else{
				$this->block[$index[0]][$index[1]][1]{$index[2] >> 1} = chr((ord($this->block[$index[0]][$index[1]][1]{$index[2] >> 1}) & 0x0F) | ($meta >> 4));
			}
		}
	}
}