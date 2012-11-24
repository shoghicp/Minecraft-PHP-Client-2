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

class McRegion{
	protected $block, $raw;
	
	function __construct(){
		$this->block = array();
	}
	
	protected function splitColumns($data, $X, $Z){
	
		$chunkBlocks = 16 * 16 * 128; //32768
		for($offset=0;$offset<$chunkBlocks;++$offset){
			$x = $X + ($offset >> 11);
			$y = $offset & 0x7F;
			$z = $Z + (($offset & 0x780) >> 7 );
			$block = ord($data{$totalOffset+$offset});
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
		$totalOffset += $offset;
		for($offset=0;$offset<$chunkBlocks;$offset += 2){
			$byte = ord($data{$totalOffset+($offset/2)});
			$x = $X + (($offset) >> 11);
			$y = ($offset) & 0x7F;
			$z = $Z + ((($offset) & 0x780) >> 7 );
			$block = $byte & 0x0F;
			if(!isset($this->block[$x])){
				$this->block[$x] = array();
			}
			if(!isset($this->block[$x][$z])){
				$this->block[$x][$z] = array();
			}
			if(!isset($this->block[$x][$z][$y])){
				$this->block[$x][$z][$y] = array(0, $block);
			}else{
				$this->block[$x][$z][$y][1] = $block;
			}

			$x = $X + (($offset+1) >> 11);
			$y = ($offset+1) & 0x7F;
			$z = $Z + ((($offset+1) & 0x780) >> 7 );
			$block = ($byte >> 4) & 0x0F;
			if(!isset($this->block[$x])){
				$this->block[$x] = array();
			}
			if(!isset($this->block[$x][$z])){
				$this->block[$x][$z] = array();
			}
			if(!isset($this->block[$x][$z][$y])){
				$this->block[$x][$z][$y] = array(0, $block);
			}else{
				$this->block[$x][$z][$y][1] = $block;
			}			
			
		}
		console("[DEBUG] [McRegion] Parsed X ".$X.", Z ".$Z, true, true, 2);
	}
	
	public function addChunk($X, $Z, $data, $compressed = true){
		$X *= 16;
		$Z *= 16;

		if(!isset($this->block[$X][$Z])){
			if(!isset($this->raw[$X])){
				$this->raw[$X] = array();
			}
			if(!isset($this->raw[$X][$Z])){
				$this->raw[$X][$Z] = array();
			}
			$this->raw[$X][$Z][] = array(($compressed === true ? gzinflate(substr($data,2)):$data), $X, $Z);
		}else{
			$this->splitColumns(($compressed === true ? gzinflate(substr($data,2)):$data), $X, $Z);
		}
		console("[INTERNAL] [McRegion] Loaded X ".$X.", Z ".$Z, true, true, 3);
	}
	
	protected function checkChunk($X, $Z){		
		$X = floor($X / 16) * 16;
		$Z = floor($Z / 16) * 16;
		
		if(isset($this->raw[$X][$Z])){
			foreach($this->raw[$X][$Z] as $d){
				$this->splitColumns($d[0], $d[2], $d[3]);
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
		console("[DEBUG] [McRegion] Unloaded X ".$X." Z ".$Z, true, true, 2);
	}
	
	public function getBlock($x, $y, $z){
		$this->checkChunk($x, $z);
		if(!isset($this->block[$x][$z][$y])){
			return array(0, 0);
		}
		return $this->block[$x][$z][$y];
	}
	
	public function changeBlock($x, $y, $z, $block, $metadata = 0){
		console("[INTERNAL] [McRegion] Changed block X ".$x." Y ".$y." Z ".$z, true, true, 3);
		if(!isset($this->block[$x])){
			$this->block[$x] = array();
		}
		if(!isset($this->block[$x][$z])){
			$this->block[$x][$z] = array();
		}
		$this->block[$x][$z][$y] = array($block, $metadata);
	}
}