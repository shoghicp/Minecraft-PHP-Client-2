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
	
	public function getMap($radius = 16){
		$map = array();
		$pos = $this->player->getPosition(true);
		$startX = $pos["x"] - $radius;
		$startZ = $pos["z"] - $radius;
		for($x = $pos["x"] - $radius; $x < ($pos["x"] + $radius); ++$x){
			$map[$x - $startX] = array();
			for($z = $pos["z"] - $radius; $z < ($pos["z"] + $radius); ++$z){
				$map[$x - $startX][$z - $startZ] = $this->map->getFloor($x, $z);
			}		
		}
		return $map;
	}
	
	public function drawPNG($dest, $radius = 16){
		$s = $radius * 2;
		$map = $this->getMap($radius);
		$img = imagecreatetruecolor($s, $s);
		include("misc/materials.php");
		foreach($map as $x => $d){
			foreach($d as $z => $block){
				$y = $block[0];
				$color = isset($material["color"][$block[1]]) ? (is_array($material["color"][$block[1]][0]) ? imagecolorallocatealpha($img, max(0, $material["color"][$block[1]][$block[2]][0] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][$block[2]][1] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][$block[2]][2] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][$block[2]][3] - ($y % 2) * 13) ):imagecolorallocatealpha($img, max(0, $material["color"][$block[1]][0] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][1] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][2] - ($y % 2) * 13) , max(0, $material["color"][$block[1]][3] - ($y % 2) * 13) )):imagecolorallocatealpha($img, 214, 127, 255, 0);
				imagesetpixel($img, $x, $z, $color);
			}
		}
		imagepng($img, $dest, 9);
		imagedestroy($img);
	}
}