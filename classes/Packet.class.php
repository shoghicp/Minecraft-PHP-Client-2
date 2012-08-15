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


class Packet{
	private $struct, $sock;
	protected $pid, $packet;
	public $data, $raw, $protocol;
	
	function __construct($pid, $struct, $sock = false){
		$this->pid = $pid;
		$this->raw = "";
		$this->data = array();
		if($pid !== false){
			$this->addRaw(chr(hexdec($pid)));
		}
		$this->struct = $struct;
		$this->sock = $sock;
	}
	
	public function create($raw = false){
		foreach($this->struct as $field => $type){
			if(!isset($this->data[$field])){
				$this->data[$field] = "";
			}
			if($raw === true){
				$this->addRaw($this->data[$field]);
				continue;
			}
			switch($type){
				case "float":
					$this->addRaw(Utils::writeFloat($this->data[$field]));
					break;
				case "int":
					$this->addRaw(Utils::writeInt($this->data[$field]));
					break;
				case "double":
					$this->addRaw(Utils::writeDouble($this->data[$field]));
					break;
				case "long":
					$this->addRaw(Utils::writeLong($this->data[$field]));
					break;
				case "bool":
				case "boolean":
					$this->addRaw(Utils::writeBool($this->data[$field]));
					break;
				case "ubyte":
				case "byte":
					$this->addRaw(Utils::writeByte($this->data[$field]));
					break;
				case "short":
					$this->addRaw(Utils::writeShort($this->data[$field]));
					break;
				case "byteArray":
					$this->addRaw($this->data[$field]);
					break;
				case "string":
					$this->addRaw(Utils::writeShort(strlen($this->data[$field])));
					$this->addRaw(Utils::writeString($this->data[$field]));
					break;
				case "slotData":
					$this->addRaw(Utils::writeShort($this->data[$field][0]));
					if($this->data[$field][0]!=-1){
						$this->addRaw(Utils::writeByte($this->data[$field][1]));
						$this->addRaw(Utils::writeShort($this->data[$field][2]));
					}
					break;
				default:
					$this->addRaw(Utils::writeByte($this->data[$field]));
					break;
			}			
		}
	}
	
	public static function getMin($struct){
		$offset = 0;
		foreach($struct as $type){
			switch($type){
				case "float":
				case "int":
					$offset += 4;
					break;
				case "double":
				case "long":
					$offset += 8;
					break;
				case "bool":
				case "boolean":
				case "ubyte":
				case "byte":
					$offset += 1;
					break;
				case "short":
					$offset += 2;
					break;
				case "string":
					$offset += 2;
					break;
			}
		}
		return $offset;	
	}
	
	private function get($len){
		return $this->addRaw($this->sock->read($len));
	}
	
	protected function addRaw($str){
		$this->raw .= $str;
		return $str;
	}
	
	public function parse(){
		$continue = true;
		foreach($this->struct as $field => $type){
			switch($type){
				case "int":
					$this->data[] = Utils::readInt($this->get(4));
					if($field === 5 and $this->pid === "17" and $this->data[$field] === 0){
						$continue = false;
					}
					break;
				case "string":
					$this->data[] = Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
					break;
				case "long":
					$this->data[] = Utils::readLong($this->get(8));
					break;
				case "byte":
					$this->data[] = Utils::readByte($this->get(1));
					break;				
				case "ubyte":
					$this->data[] = Utils::readByte($this->get(1), false);
					break;
				case "float":
					$this->data[] = Utils::readFloat($this->get(4));
					break;
				case "double":
					$this->data[] = Utils::readDouble($this->get(8));
					break;
				case "ushort":
				case "short":
					$this->data[] = Utils::readShort($this->get(2));
					break;
				case "bool":
				case "boolean":
					$this->data[] = Utils::readBool($this->get(1));
					break;
				case "explosionRecord":
					$count = $this->data[$field - 1];
					$r = array();
					for($i = 0; $i < $count; ++$i){
						$r[] = array(Utils::readByte($this->get(1)),Utils::readByte($this->get(1)),Utils::readByte($this->get(1)));
					}
					$this->data[] = $r;
					break;
				case "byteArray":
					$len = $this->data[$field - 1];
					if($len <= 0){
						$this->data[] = "";
						break;
					}
					$this->data[] = $this->get($len);
					break;
				case "intArray":
					$len = $this->data[$field - 1];
					if($len <= 0){
						$this->data[] = array();
						break;
					}
					$this->data[] = array_map("Utils::readInt",str_split($this->get($len << 2),4));
					break;
				case "chunkInfo":
					$n = $this->data[0];
					$this->data[] = $this->get($n * 12);
					break;
				case "chunkArray":
					$this->data[] = $this->get(max(0,$this->data[6]));
					break;
				case "newChunkArray":
					$this->data[] = $this->get(max(0,$this->data[5]));
					break;
				case "multiblockArray":
					$count = $this->data[$field - 1];
					$d = array();
					for($i=0;$i<$count;++$i){
						$this->get(2);
					}
					for($i=0;$i<$count;++$i){
						$this->get(1);
					}
					for($i=0;$i<$count;++$i){
						$this->get(1);
					}
					$this->data[] = "";
					break;

				case "newMultiblockArray":
					$len = $this->data[3];
					$this->data[] = $this->get($len);
					break;
				case "slotArray":
				case "slotData":
					$scount = $type === "slotData" ? 1:$this->data[$field-1];
					$d = array();
					for($i=0;$i<$scount;++$i){
						$id = Utils::readShort($this->get(2));
						if($id != -1){
							$count = Utils::readByte($this->get(1));						
							$meta = Utils::readShort($this->get(2));
							$d[$i] = array($id,$count,$meta);
							$enchantable_items = array(
								 0x103, #Flint and steel
								 0x105, #Bow
								 0x15A, #Fishing rod
								 0x167, #Shears
								 
								 #TOOLS
								 #sword, shovel, pickaxe, axe, hoe
								 0x10C, 0x10D, 0x10E, 0x10F, 0x122, #WOOD
								 0x110, 0x111, 0x112, 0x113, 0x123, #STONE
								 0x10B, 0x100, 0x101, 0x102, 0x124, #IRON
								 0x114, 0x115, 0x116, 0x117, 0x125, #DIAMOND
								 0x11B, 0x11C, 0x11D, 0x11E, 0x126, #GOLD
								 
								 #ARMOUR
								 #helmet, chestplate, leggings, boots
								 0x12A, 0x12B, 0x12C, 0x12D, #LEATHER
								 0x12E, 0x12F, 0x130, 0x131, #CHAIN
								 0x132, 0x133, 0x134, 0x135, #IRON
								 0x136, 0x137, 0x138, 0x139, #DIAMOND
								 0x13A, 0x13B, 0x13C, 0x13D, #GOLD
							);
							if(($this->protocol >= 36 and $id != 0xff) or in_array($id, $enchantable_items)){
								$len = Utils::readShort($this->get(2));
								if($len > -1){
									$arr = $this->get($len);
								}
							}
						}
					}
					$this->data[] = $d;
					break;
				case "entityMetadata":
					$m = array();
					$b = Utils::readByte($this->get(1), false);
					while($b != 127){
						$bottom = $b & 0x1F;
						$type = $b >> 5;
						switch($type){
							case 0:
								$r = Utils::readByte($this->get(1));
								break;
							case 1:
								$r = Utils::readShort($this->get(2));
								break;
							case 2:
								$r = Utils::readInt($this->get(4));
								break;
							case 3:
								$r = Utils::readFloat($this->get(4));
								break;
							case 4:
								$r = Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
								break;
							case 5:
								$r = array("id" => Utils::readShort($this->get(2)), "count" => Utils::readByte($this->get(1)), "damage" => Utils::readShort($this->get(2)));
								break;
							case 6:
								$r = array();
								for($i=0;$i<3;++$i){
									$r[] = Utils::readInt($this->get(4));
								}
								break;
								
						}
						$m[$bottom] = $r;
						$b = Utils::readByte($this->get(1), false);
					}
					$this->data[] = $m;
					break;
					
					
			}
			if($continue === false){
				break;
			}
		}
	}
	
	


}