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


class Packet{
	private $struct, $sock;
	protected $pid, $packet;
	public $data, $raw, $protocol;
	
	function __construct($pid, $struct, $sock = false){
		$this->pid = $pid;
		$this->raw = "";
		$this->data = array();
		if($pid !== false){
			$this->addRaw(chr($pid));
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
					if((($this->protocol >= 50 and $field === 7) or ($this->protocol < 50 and $field === 5)) and $this->pid === 0x17 and $this->data[$field] === 0){
						$continue = false;
					}
					break;
				case "teamData":
					$d = ord($this->get(1));
					if($d === 0 or $d === 2){
						Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
						Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
						Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
						Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
					}
					if($d === 0 or $d === 3 or $d === 4){
						$c = Utils::readShort($this->get(2));
						for($i = 0; $i < $c; ++$i){
							Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
						}
					}
					break;
				case "scoreboardUpdate":
					$d = ord($this->get(1));
					if($d === 0){
						Utils::readString($this->get(Utils::readShort($this->get(2)) << 1));
						$this->get(4);
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
					$this->data[] = ord($this->get(1));
					break;
				case "float":
					$this->data[] = Utils::readFloat($this->get(4));
					break;
				case "double":
					$this->data[] = Utils::readDouble($this->get(8));
					break;
				case "ushort":
					$this->data[] = Utils::readShort($this->get(2), false);
					break;
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
				case "dropArray":
					$item = Utils::readShort($this->get(2));
					$this->data[] = $item;
					if($item === -1){
						$this->data[] = 0;
						$this->data[] = 0;
					}else{
						$this->data[] = ord($this->get(1));
						$this->data[] = Utils::readShort($this->get(2));
						$f = Utils::readShort($this->get(2));
						if($f !== -1){
							$d = $this->get(Utils::readShort($this->get(2)));
						}
					}
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
				case "newNewChunkArray":
					$this->data[] = $this->get(max(0,$this->data[1]));
					break;
				case "multiblockArray":
					$count = $this->data[$field - 1]; 
					$d = array(0 => array(), 1 => array(), 2 => array());
					for($i = 0; $i < $count; ++$i){
						$d[0][] = $this->get(2);
					}
					for($i = 0; $i < $count; ++$i){
						$d[1][] = $this->get(1);
					}
					for($i = 0; $i < $count; ++$i){
						$d[2][] = $this->get(1);
					}
					$this->data[] = $d;
					break;

				case "newMultiblockArray":
					$len = $this->data[3];
					$this->data[] = $this->get($len);
					break;
				case "slotArray":
				case "slotData":
					$scount = $type === "slotData" ? 1:$this->data[$field-1];
					$d = array();
					for($i = 0; $i < $scount; ++$i){
						$id = Utils::readShort($this->get(2), false);
						if($id !== 0xffff){
							$count = Utils::readByte($this->get(1));						
							$meta = Utils::readShort($this->get(2));
							$d[$i] = array($id, $count, $meta);
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
							if($this->protocol >= 36 or in_array($id, $enchantable_items)){
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
					$b = ord($this->get(1));
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
								$r = array();
								$r[] = Utils::readShort($this->get(2));
								if($this->protocol < 41 or $r[0] != -1){
									$r[] = Utils::readByte($this->get(1));
									$r[] = Utils::readShort($this->get(2));
									if($this->protocol >= 48){
										$r[] = Utils::readShort($this->get(2));
										if($r[3] != -1){
											$r[] = $this->get($r[3]);
										}
									
									}
								}
								break;
							case 6:
								$r = array();
								for($i=0;$i<3;++$i){
									$r[] = Utils::readInt($this->get(4));
								}
								break;
								
						}
						$m[$bottom] = $r;
						$b = ord($this->get(1));
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