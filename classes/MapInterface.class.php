<?php

class MapInterface{
	protected $map;
	
	function __construct($map){
		$this->map = $map;
	}
	
	public function changeBlock($x, $y, $z, $block, $metadata){
		$x = round($x);
		$y = round($y);
		$z = round($z);
		return $this->map->changeBlock($x, $y, $z, $block, $metadata);
	}	
	
	public function getBlock($x, $y, $z){
		$x = round($x);
		$y = round($y);
		$z = round($z);
		return $this->map->getBlock($x, $y, $z);
	}

	public function getColumn($x, $z){
		return $this->getZone($x,0,$z,$x,HEIGHT_LIMIT,$z);
	}

	public function getEllipse($x, $y, $z, $rX=4, $rZ = 4, $rY = 4){
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
		$x1 = round($x1);
		$y1 = round($y1);
		$z1 = round($z1);
		$x2 = round($x2);
		$y2 = round($y2);
		$z2 = round($z2);
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