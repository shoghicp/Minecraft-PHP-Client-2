<?php

class MapInterface{
	protected $map;
	
	function __construct($map){
		$this->map = $map;
	}
	
	public function getBlock($x, $y, $z){
		$x = round($x);
		$y = round($y);
		$z = round($z);
		return $this->map->getBlock($x, $y, $z);
	}

	public function getSphere($x,$y,$z,$r=4){
		$r = abs($r);
		return $this->getZone($x-$r,$y-$r,$z-$r,$x+$r,$y+$r,$z+$r);
	}
	
	public function getZone($x1,$z1,$y1, $x2,$z2,$y2){
		if($x1>$x2 or $y1>$y2 or $z1>$z2){
			return false;
		}
		$blocks = array();
		for($x=$x1;$x<=$x2;++$x){
			for($y=$y1;$y<=$y2;++$y){
				for($z=$z1;$z<=$z2;++$z){
					$blocks[$x][$y][$z] = $this->getBlock($x,$y,$z);
				}
			}
		}
		return $blocks;
	}

}