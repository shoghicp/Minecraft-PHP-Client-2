<?php


class Anvil{
	protected $block, $raw;
	
	function __construct(){
		$this->block = array();
	}
	
	protected function splitColumns($data, $bitmask, $X, $Z){
		$offset = 0;
		for ($i=0;$i<16;++$i) {
			if ($bitmask & 1 << $i){
				$cubic_chunk_data = substr($data,$offset,4096);
				$offset += 4096;
				for($j=0; $j<4096; ++$j){
					$x = $X + ($j & 0x0F);
					$y = $i*16 + ($j >> 8);
					$z = $Z + (($j & 0xF0) >> 4);
					$block = ord($cubic_chunk_data{$j});
					if(!isset($this->block[$x])){
						$this->block[$x] = array();
					}
					if(!isset($this->block[$x][$z])){
						$this->block[$x][$z] = array();
					}
					if(!isset($this->block[$x1][$z1][$y1])){
						$this->block[$x][$z][$y] = array($block, 0);
					}else{
						$this->block[$x][$z][$y][0] = $block;
					}
				}
			}
		}
		for ($i=0;$i<16;++$i) {
			if ($bitmask & 1 << $i){
				$cubic_chunk_data = substr($data,$offset,2048);
				$offset += 2048;
				for($j=0; $j<2048; ++$j){
					$block1 = ord($cubic_chunk_data{$j}) & 0x0F;
					$block2 = ord($cubic_chunk_data{$j}) >> 4;
					$k = 2*$j;
					$x1 = $X + ($k & 0x0F);
					$y1 = $i*16 + ($k >> 8);
					$z1 = $Z + (($k & 0xF0) >> 4);
					if(!isset($this->block[$x1])){
						$this->block[$x1] = array();
					}
					if(!isset($this->block[$x1][$z1])){
						$this->block[$x1][$z1] = array();
					}
					if(!isset($this->block[$x1][$z1][$y1])){
						$this->block[$x1][$z1][$y1] = array(0, $block1);
					}else{
						$this->block[$x1][$z1][$y1][1] = $block1;
					}
					
					
					++$k;
					$x2 = $X + ($k & 0x0F);
					$y2 = $i*16 + ($k >> 8);
					$z2 = $Z + (($k & 0xF0) >> 4);
					if(!isset($this->block[$x2])){
						$this->block[$x2] = array();
					}
					if(!isset($this->block[$x2][$z2])){
						$this->block[$x2][$z2] = array();
					}
					if(!isset($this->block[$x2][$z2][$y2])){
						$this->block[$x2][$z2][$y2] = array(0, $block2);
					}else{
						$this->block[$x2][$z2][$y2][1] = $block2;
					}
				}
			}
		}
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
		
		if(!isset($this->block[$X][$Z]) and isset($this->raw[$X][$Z])){
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
		if(isset($this->block[$X])){
			unset($this->block[$X][$Z]);
		}
	}
	
	public function getBlock($x, $y, $z){
		$this->checkChunk($x, $z);
		if(!isset($this->block[$x][$z][$y])){
			return array(0, 0);
		}
		return $this->block[$x][$z][$y];
	}
	
	public function changeBlock($x, $y, $z, $block, $metadata = 0){
		$this->checkChunk($x, $z);
		if(!isset($this->block[$x])){
			$this->block[$x] = array();
		}
		if(!isset($this->block[$x][$z])){
			$this->block[$x][$z] = array();
		}
		$this->block[$x][$z][$y] = array($block, $metadata);
	}
}