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


class MapPainter{
	protected $map, $player;
	
	function __construct($client){
		$this->map = $client->map;
		$this->player = $client->getPlayer();
	}
	
	public function getMap($floor = -1, $radius = 16, $blockSize = 1){
		$map = array();
		$pos = $this->player->getPosition(true);
		$startX = $pos["x"] - $radius * $blockSize;
		$startZ = $pos["z"] - $radius * $blockSize;
		$i = 0;
		$j = 0;
		for($x = $pos["x"] - $radius * $blockSize; $x < ($pos["x"] + $radius * $blockSize); $x += $blockSize){
			$map[$i] = array();
			$j = 0;
			for($z = $pos["z"] - $radius * $blockSize; $z < ($pos["z"] + $radius * $blockSize); $z += $blockSize){
				if($floor === -1){
					$map[$i][$j] = $this->map->getFloor($x, $z);
				}else{
					$b = $this->map->getBlock($x, $floor, $z);
					$map[$i][$j] = array(0, $b[0], $b[1]);
				}
				++$j;
			}
			++$i;
		}
		return $map;
	}
	
	public function scan($dest, $radius = 16, $width = 8){
		$dest = str_replace(".png", "", $dest);
		for($y = 0; $y < HEIGHT_LIMIT; ++$y){
			$this->drawMap($dest."_".$y.".png", $y, $radius, $width);
		}
	}
	
	public function drawMap($dest, $floor = -1, $radius = 16, $width = 1, $blockSize = 1){
		$s = $radius * 2 * $width;
		$map = $this->getMap($floor, $radius, $blockSize);
		$img = imagecreatetruecolor($s, $s);
		include("misc/materials.php");
		foreach($map as $x => $d){
			foreach($d as $z => $block){
				$y = $block[0];
				$color = isset($material["color"][$block[1]]) ? (is_array($material["color"][$block[1]][0]) ? imagecolorallocatealpha($img, max(0, $material["color"][$block[1]][$block[2]][0] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][$block[2]][1] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][$block[2]][2] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][$block[2]][3] + (($y % 2) * 13) - ((int) ($y / 8))) ):imagecolorallocatealpha($img, max(0, $material["color"][$block[1]][0] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][1] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][2] + (($y % 2) * 13) - ((int) ($y / 8))) , max(0, $material["color"][$block[1]][3] + (($y % 2) * 13) - ((int) ($y / 8))) )):imagecolorallocatealpha($img, 214, 127, 255, 0);
				
				for($i = 0; $i < $width; ++$i){
					for($j = 0; $j < $width; ++$j){
						imagesetpixel($img, $x * $width + $i, $z * $width + $j, $color);
					}
				}
			}
		}
		imagepng($img, $dest, 9);
		imagedestroy($img);
	}
}