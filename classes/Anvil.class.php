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
		$len = HEIGHT_LIMIT / 16;
		for($i=0; $i < $len; ++$i){
			if ($bitmask & 1 << $i){
				$blockData .= substr($data, $offset, 4096);
				$offset += 4096;
			}elseif(isset($this->block[$X][$Z][0]{$i*4096})){
				$blockData .= substr($this->block[$X][$Z][0], $i*4096, 4096);
			}else{
				$blockData .= str_repeat("\x00", 4096);
			}
		}
		for($i=0; $i < $len; ++$i){
			if ($bitmask & 1 << $i){
				$metaData .= substr($data, $offset, 2048);
				$offset += 2048;
			}elseif(isset($this->block[$X][$Z][1]{$i*2048})){
				$metaData .= substr($this->block[$X][$Z][1], $i*2048, 2048);
			}else{
				$metaData .= str_repeat("\x00", 2048);
			}
		}
		if(!isset($this->block[$X])){
			$this->block[$X] = array();
		}
		$this->block[$X][$Z] = array(0 => $blockData, 1 => $metaData);
		console("[DEBUG] [Anvil] Parsed X ".$X.", Z ".$Z, true, true, 2);
	}
	
	public function addChunk($X, $Z, $data, $bitmask, $compressed = true){
		$X *= 16;
		$Z *= 16;

		if(!isset($this->block[$X][$Z])){
			if(!isset($this->raw[$X])){
				$this->raw[$X] = array();
			}
			if(!isset($this->raw[$X][$Z])){
				$this->raw[$X][$Z] = array();
			}
			$this->raw[$X][$Z][] = array(($compressed === true ? gzinflate(substr($data,2)):$data), $bitmask, $X, $Z);
		}else{
			$this->splitColumns(($compressed === true ? gzinflate(substr($data,2)):$data), $bitmask, $X, $Z);
		}	
		console("[INTERNAL] [Anvil] Loaded X ".$X.", Z ".$Z, true, true, 3);
	}
	
	protected function checkChunk($X, $Z){		
		$X = floor($X / 16) * 16;
		$Z = floor($Z / 16) * 16;
		
		if(isset($this->raw[$X][$Z])){
			foreach($this->raw[$X][$Z] as $d){
				$this->splitColumns($d[0], $d[1], $d[2], $d[3]);
			}
			unset($this->raw[$X][$Z]);
			return true;
		}elseif(isset($this->block[$X][$Z])){
			return true;
		}
		return false;
	}
	
	public function unloadChunk($X, $Z){
		$X = floor($X / 16) * 16;
		$Z = floor($Z / 16) * 16;
		for($x = $X; $x < ($X + 16); ++$x){
			for($z = $Z; $z < ($Z + 16); ++$z){
				unset($this->block[$x][$z]);
				unset($this->raw[$x][$z]);
			}
		}
		console("[DEBUG] [Anvil] Unloaded X ".$X." Z ".$Z, true, true, 2);
	}
	
	public function getIndex($x, $y, $z){
		$X = floor($x / 16) * 16;
		$Z = floor($z / 16) * 16;
		$aX = $x - $X;
		$aZ = $z - $Z;
		$index = $y * HEIGHT_LIMIT + $aZ * 16 + $aX;
		return array($X, $Z, $index);
	}
	
	public function getBlock($x, $y, $z){
		$this->checkChunk($x, $z);
		$index = $this->getIndex($x, $y, $z);
		if(!isset($this->block[$index[0]][$index[1]])){
			return array(0, 0);
		}
		$block = $this->block[$index[0]][$index[1]][0][$index[2]];
		$meta = $this->block[$index[0]][$index[1]][1][floor($index[2] / 2)];
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
		if(isset($this->block[$index[0]][$index[1]])){
			$this->block[$index[0]][$index[1]][0][$index[2]] = $block;
			if($index[2] % 2 === 0){
				$this->block[$index[0]][$index[1]][1][floor($index[2] / 2)] = ($this->block[$index[0]][$index[1]][1][floor($index[2] / 2)] & 0xF0) | ($meta & 0x0F);
			}else{
				$this->block[$index[0]][$index[1]][1][floor($index[2] / 2)] = ($this->block[$index[0]][$index[1]][1][floor($index[2] / 2)] & 0x0F) | ($meta >> 4);
			}
		}
	}
}