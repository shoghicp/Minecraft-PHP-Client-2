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


class Anvil{
	private $sections = array(
		"blockData" => 4096,
		"metaData" => 2048,
		"lightData" => 2048,
		"skyLightData" => 2048,
		"addData" => 2048,	
	);
	protected $column, $material, $air;
	var $sectionSize;
	
	function __construct(){
		include("misc/materials.php");
		$this->material = $material;
		$this->column = array();
		$this->height = (int) ((string) log(HEIGHT_LIMIT, 2));
		$this->air = str_repeat("\x00", 2048);
		$this->sectionSize = 0;
		foreach($this->sections as $type => $size){
			if($type === "addData"){
				continue;
			}
			$this->sectionSize += $size;
		}
	}
	
	protected function splitColumns($data, $bitmask, $addBitmask, $X, $Z){
		$offset = 0;
		$len = HEIGHT_LIMIT >> 4;
		$lastBlock = 0;
		foreach($this->sections as $type => $size){
			$$type = b"";
			for($i = 0; $i < $len; ++$i){
				if (($type !== "addData" and $bitmask & (1 << $i)) or ($type === "addData" and $addBitmask & (1 << $i))){
					$$type .= substr($data, $offset, $size);
					$offset += $size;
					$lastBlock = $i << 4;
				}elseif($type === "blockData" and isset($this->column[$X][$Z])){
					$$type .= substr($this->column[$X][$Z][0], $i << 12, $size);
					$lastBlock = $i << 4;
				}elseif($type === "metaData" and isset($this->column[$X][$Z])){
					$$type .= substr($this->column[$X][$Z][1], $i << 11, $size);
				}else{
					$$type .= $size === 4096 ? $this->air . $this->air:$this->air;
				}
			}
		}
		$biomeData = substr($data, $offset, 256);

		if(!isset($this->column[$X])){
			$this->column[$X] = array();
		}
		$this->column[$X][$Z] = array(0 => $blockData, 1 => $metaData, 2 => $lastBlock, 3 => $biomeData);
		console("[DEBUG] [Anvil] Parsed X ".$X.", Z ".$Z, true, true, 2);
	}
	
	public function addChunk($X, $Z, $data, $bitmask, $compressed = true, $addBitmask = 0){
		$X = $X << 4;
		$Z = $Z << 4;
		$this->splitColumns(($compressed === true ? gzinflate(substr($data,2)):$data), $bitmask, $addBitmask, $X, $Z);
		console("[INTERNAL] [Anvil] Loaded X ".$X.", Z ".$Z, true, true, 3);
	}
	
	//O(1)
	public function unloadChunk($X, $Z){
		$X = $X << 4;
		$Z = $Z << 4;
		unset($this->column[$X][$Z]);
		console("[DEBUG] [Anvil] Unloaded X ".$X." Z ".$Z, true, true, 2);
	}
	
	
	//O(1)
	public function getIndex($x, $y, $z){
		$X = ($x >> 4) << 4;
		$Z = ($z >> 4) << 4;
		$index = ($y << $this->height) + (($z - $Z) << 4) + ($x - $X);
		return array($X, $Z, $index);
	}
	
	public function getColumn($x, $z, $meta = true){
		$index = $this->getIndex($x, 0, $z);
		if(!isset($this->column[$index[0]][$index[1]])){
			return array_fill(0, HEIGHT_LIMIT, array(0, 0));
		}
		$block = preg_replace("/(.).{".(HEIGHT_LIMIT - 1)."}/s", '$1', substr($this->column[$index[0]][$index[1]][0], $index[2], HEIGHT_LIMIT << $this->height));
		if($meta === true){
			$meta = preg_replace("/(.).{".(HEIGHT_LIMIT >> 1 - 1)."}/s", '$1', substr($this->column[$index[0]][$index[1]][0], $index[2], HEIGHT_LIMIT << ($this->height - 1)));
		}
		$data = array();
		$m = 0;
		for($i = 0; $i < HEIGHT_LIMIT; ++$i){
			$b = ord($block{$i});
			if($meta === true){
				$m = ord($this->column[$index[0]][$index[1]][1]{$index[2] >> 1});
				if($y % 2 === 0){
					$m = $m & 0x0F;
				}else{
					$m = $m >> 4;
				}
			}
			$data[$i] = array($b, $m);
		}
		return $data;
	}
	
	public function getFloor($x, $z, $startY = -1){ //Fast method
		$index = $this->getIndex($x, HEIGHT_LIMIT, $z);
		if(!isset($this->column[$index[0]][$index[1]])){
			return array(0, 0, 0);
		}
		if(((int) $startY) > -1){
			$i = ((int) $startY >> 4) << 4;
		}else{
			$i = $this->column[$index[0]][$index[1]][2] + 16;
		}
		$index[2] -= (HEIGHT_LIMIT - $i) << $this->height;
		$b =& $this->column[$index[0]][$index[1]][0];
		
		for($y = $i; $y > 0; --$y){
			if(!isset($this->material["nosolid"][ord($b{$index[2]})])){
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
		if(isset($this->column[$index[0]][$index[1]])){
			$block = ord($this->column[$index[0]][$index[1]][0]{$index[2]});
			$meta = ord($this->column[$index[0]][$index[1]][1]{$index[2] >> 1});
			if($index[2] % 2 === 0){
				$meta = $meta & 0x0F;
			}else{
				$meta = $meta >> 4;
			}
			return array($block, $meta);
		}
		return array(0, 0);
	}
	
	public function getBiome($x, $z){
		$index = $this->getIndex($x, 0, $z);
		return ord($this->column[$index[0]][$index[1]][3]{(($z - $index[1]) << 4) + ($x - $index[0])});
	}
	
	public function changeBlock($x, $y, $z, $block, $meta = 0){
		console("[INTERNAL] [Anvil] Changed block X ".$x." Y ".$y." Z ".$z, true, true, 3);
		$index = $this->getIndex($x, $y, $z);
		if(isset($this->column[$index[0]][$index[1]][0]{$index[2]})){
			$this->column[$index[0]][$index[1]][0]{$index[2]} = chr($block);
			if($index[2] % 2 === 0){
				$this->column[$index[0]][$index[1]][1]{$index[2] >> 1} = chr((ord($this->column[$index[0]][$index[1]][1]{$index[2] >> 1}) & 0xF0) | ($meta & 0x0F));
			}else{
				$this->column[$index[0]][$index[1]][1]{$index[2] >> 1} = chr((ord($this->column[$index[0]][$index[1]][1]{$index[2] >> 1}) & 0x0F) | ($meta >> 4));
			}
		}
	}
}