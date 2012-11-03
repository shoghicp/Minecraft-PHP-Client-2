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
				Version 2, December 2004

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

			DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


*/


class PathFind{
	protected $client, $open, $closed, $start, $end, $nosolid;
	var $path;
	function __construct($client, $start, $end, $max = 64){
		$this->client = $client;
		include("misc/materials.php");
		$this->nosolid = $material["nosolid"];
		$this->nosolid[10] = true;
		$this->nosolid[11] = true;
		$this->start = $start;
		$this->end = $end;
		$this->path = new Path;
		$this->open = new Path;
		$this->closed = new Path;
		$this->look($this->start, (int) $max);
	}
	
	private function look($pos, $max){
		$rep = 0;
		while(($pos["x"] != $this->end["x"] or $pos["y"] != $this->end["y"] or $pos["z"] != $this->end["z"]) and $rep < $max){
			++$rep;
			$this->closed->add($pos);
			$this->open->remove($pos);
			for($i = -1; $i <= 1; ++$i) {
                for($j = -1; $j <= 1; ++$j) {
                    for($k = -1; $k <= 1; ++$k) {
						$adjacentBlock = array("x" => $pos["x"] + $i, "y" => $pos["y"] + $j, "z" => $pos["z"] + $k);						
						if($adjacentBlock["x"] != $this->end["x"] or $adjacentBlock["y"] != $this->end["y"] or $adjacentBlock["z"] != $this->end["z"]){
							$next = $this->client->map->getBlock($pos["x"] + $i, $pos["y"] + $j - 1, $pos["z"] + $k);
							if(!($j === 1 and $next[0] === 85)){
								$this->scoreBlock($adjacentBlock, $pos);
							}
						}
					}
				}
			}
			$n = $this->open->toArray();
			usort($n, array($this, "nodeComp"));
			if (count($n) === 0) {
                break;
            }
			$pos = $n[0];
			if($pos["x"] == $this->end["x"] and $pos["y"] == $this->end["y"] and $pos["z"] == $this->end["z"]){
				$adjacentBlock = $pos;
				while($adjacentBlock != null and ($adjacentBlock["x"] != $this->start["x"] or $adjacentBlock["y"] != $this->start["y"] or $adjacentBlock["z"] != $this->start["z"])) {
                    $this->path->add($adjacentBlock);
                    $adjacentBlock = $adjacentBlock["parent"];					
                }
				$this->path->reverse();
			}
		}
		if ($this->path->size() === 0) {
            $this->path->add($this->end);
        }
		
		
		
	}
	
	public function getNextBlock(){
		if($this->path->size() > 0){
			$r = $this->path->get(0);
            $this->path->remove($r);
            return $r;
        }
		return null;
	}
	
	public function nodeComp($o1, $o2){
		$o1 = isset($o1["f"]) ? $o1["f"]:0;
		$o2 = isset($o2["f"]) ? $o2["f"]:0;
		if($o1 > $o2){
			return 1;
		}elseif($o1 < $o2){
			return -1;
		}
		return 0;
	}
	
	private function scoreBlock($pos, $parent){
		$xZDiagonal = ($pos["x"] != $parent["x"] and $pos["z"] != $parent["z"]);
		$xYDiagonal = ($pos["x"] != $parent["x"] and $pos["y"] != $parent["y"]);
		$yZDiagonal = ($pos["y"] != $parent["y"] and $pos["z"] != $parent["z"]);
		$b = $this->client->map->getBlock($pos["x"], $pos["y"], $pos["z"]);
		$floor = $this->client->map->getBlock($pos["x"], $pos["y"] - 1, $pos["z"]);
		$ceil = $this->client->map->getBlock($pos["x"], $pos["y"] + 1, $pos["z"]);
		if((isset($this->nosolid[$b[0]]) and !isset($this->nosolid[$floor[0]]) and isset($this->nosolid[$ceil[0]])) or ($pos["x"] == $this->end["x"] and $pos["y"] == $this->end["y"] and $pos["z"] == $this->end["z"])){
			 if(!$this->open->contains($pos) and !$this->closed->contains($pos)){
				$pos["parent"] = $parent;
				$pos["g"] = (isset($parent["g"]) ? $parent["g"]:0) + (($xZDiagonal or $xYDiagonal or $yZDiagonal) ? 14 : 10);
				$difX = abs($this->end["x"] - $pos["x"]);
				$difY = abs($this->end["y"] - $pos["y"]);
				$difZ = abs($this->end["z"] - $pos["z"]);
				$pos["h"] = ($difX + $difY + $difZ) * 10;
				$pos["f"] = $pos["g"] + $pos["h"];
				$this->open->add($pos);
			}elseif(!$this->closed->contains($pos)){
                $g = (isset($parent["g"]) ? $parent["g"]:0) + (($xZDiagonal or $xYDiagonal or $yZDiagonal) ? 14 : 10);
                if($g < (isset($pos["g"]) ? $pos["g"]:0)){
                    $pos["g"] = $g;
                    $pos["parent"] = $parent;
                }
            }
		}
	}

}


