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


class MapPainter{
	protected $map, $player;
	
	function __construct($client){
		$this->map = $client->map;
		$this->player = $client->getPlayer();
		include("misc/materials.php");
		$this->material = $material;
		console("[INFO] [MapPainter] loaded");
	}
	
	public function getMap($floor = -1, $radius = 16, $blockSize = 1, $startY = -1){
		$map = array();
		$pos = $this->player->getPosition(true);
		$radius = $radius * $blockSize;
		$startX = $pos["x"] - $radius;
		$startZ = $pos["z"] - $radius;
		$endX = $pos["x"] + $radius;
		$endZ = $pos["z"] + $radius;
		$i = 0;
		$j = 0;
		for($x = $startX; $x < $endX; $x += $blockSize){
			$map[$i] = array();
			$j = 0;
			for($z = $startZ; $z < $endZ; $z += $blockSize){
				if($floor === -1){
					$map[$i][$j] = $this->map->getFloor($x, $z, $startY);
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
	
	public function drawMap($dest, $floor = -1, $radius = 16, $width = 1, $blockSize = 1, $startY = -1){
		$s = ($radius << 1) * $width;
		$map = $this->getMap($floor, $radius, $blockSize, $startY);
		$img = imagecreatetruecolor($s, $s);
		$c =& $this->material["color"];
		foreach($map as $x => $d){
			foreach($d as $z => $block){
				$y = $block[0];
				$b = $block[1];
				$m = $block[2];
				$color = isset($c[$b]) ? 
				(isset($c[$b][$m][0]) ? 
					imagecolorallocatealpha($img,
						max(0, $c[$b][$m][0] - (($y % 3) << 4)),
						max(0, $c[$b][$m][1] - (($y % 3) << 4)),
						max(0, $c[$b][$m][2] - (($y % 3) << 4)),
						max(0, $c[$b][$m][3] - (($y % 3) << 4))
					)
					:
					imagecolorallocatealpha($img,
						max(0, $c[$b][0] - (($y % 3) << 4)),
						max(0, $c[$b][1] - (($y % 3) << 4)),
						max(0, $c[$b][2] - (($y % 3) << 4)),
						max(0, $c[$b][3] - (($y % 3) << 4))
					)
				)
				:
				imagecolorallocatealpha($img, 214, 127, 255, 0);

				for($i = 0; $i < $width; ++$i){
					for($j = 0; $j < $width; ++$j){
						imagesetpixel($img, $x * $width + $i, $z * $width + $j, $color);
					}
				}
			}
		}
		imagepng($img, $dest, 9);
		imagedestroy($img);
		console("[DEBUG] [MapPainter] Drawed map radius ".$radius, true, true, 2);
	}
}