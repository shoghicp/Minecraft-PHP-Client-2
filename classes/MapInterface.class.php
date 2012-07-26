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

class MapInterface{
	protected $map;
	
	function __construct($map){
		$this->map = $map;
	}
	
	public function getFloor($x, $z){
		include("misc/materials.php");
		
		$x = (int) $x;
		$z = (int) $z;
		
		if(method_exists($this->map, "getFloor")){
			return $this->map->getFloor($x, $z);
		}else{
			for($y = HEIGHT_LIMIT - 1; $y > 0; --$y){
				$block = $this->getBlock($x, $y, $z);
				if(!isset($material["nosolid"][$block[0]])){
					break;
				}
			}
			return array($y, $block[0], $block[1]);
		}
	}
	
	public function changeBlock($x, $y, $z, $block, $metadata){
		$x = (int) $x;
		$y = (int) $y;
		$z = (int) $z;
		return $this->map->changeBlock($x, $y, $z, $block, $metadata);
	}	
	
	public function getBlock($x, $y, $z){
		$x = (int) $x;
		$y = (int) $y;
		$z = (int) $z;
		return $this->map->getBlock($x, $y, $z);
	}

	public function getColumn($x, $z){
		return $this->getZone($x,0,$z,$x,HEIGHT_LIMIT,$z);
	}

	public function getEllipse($x, $y, $z, $rX = 4, $rZ = 4, $rY = 4){
		$rY = abs($rX);
		$rY = abs($rZ);
		$rY = abs($rY);
		return $this->getZone($x-$rX,max(0,$y-$rY),$z-$rZ,$x+$rX,$y+$rY,$z+$rZ);
	}
	
	public function getSphere($x, $y, $z, $r=4){
		$r = abs($r);
		return $this->getZone($x-$r,max(0,$y-$r),$z-$r,$x+$r,$y+$r,$z+$r);
	}
	
	public function getZone($x1, $y1, $z1, $x2, $y2, $z2){
		$x1 = (int) $x1;
		$y1 = (int) $y1;
		$z1 = (int) $z1;
		$x2 = (int) $x2;
		$y2 = (int) $y2;
		$z2 = (int) $z2;
		if($x1>$x2 or $y1>$y2 or $z1>$z2){
			return array();
		}
		$blocks = array();
		for($x=$x1;$x<=$x2;++$x){
			$blocks[$x] = array();
			for($z=$z1;$z<=$z2;++$z){
				$blocks[$x][$z] = array();
				for($y=$y1;$y<=$y2;++$y){
					$blocks[$x][$z][$y] = $this->getBlock($x,$y,$z);
				}
			}
		}
		return $blocks;
	}

}