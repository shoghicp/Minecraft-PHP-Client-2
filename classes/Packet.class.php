<?php
require_once(dirname(__FILE__)."/Utils.class.php");


class Packet{
	private $struct, $sock;
	protected $pid, $packet;
	public $data, $raw;
	
	function __construct($pid, $struct, $sock = false){
		$this->pid = $pid;
		$this->raw = "";
		$this->data = array();
		if($pid !== false){
			$this->addRaw(Utils::hexToStr($pid));
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
					$this->addRaw(Utils::writeByte($this->data[$field] == true ? 1:0));
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
					$len = strlen($this->data[$field]);
					$this->addRaw(Utils::writeShort($len));
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
					if($field == 5 and $this->pid == "17" and $this->data[$field] == 0){
						$continue = false;
					}
					break;
				case "string":
					$len = Utils::readShort($this->get(2));
					$this->data[] = Utils::readString($this->get($len * 2));
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
					$this->data[] = Utils::readByte($this->get(1), false) === 0 ? false:true;
					break;
				case "explosionRecord":
					$r = array();
					for($i=$this->data[4]; $i>0; --$i){
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
						$this->data[] = "";
						break;
					}
					$this->data[] = array_map("Utils::readInt",str_split($this->get($len * 4),4));
					break;
				case "chunkInfo":
					$n = $this->data[0];
					$this->data[] = $this->get($n*12);
					break;
				case "chunkArray":
					$len = max(0,$this->data[6]);
					$this->data[] = $this->get($len);
					break;
				case "newChunkArray":
					$len = max(0,$this->data[5]);
					$this->data[] = $this->get($len);
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
					$scount = $type == "slotData" ? 1:$this->data[$field-1];
					$d = array();
					for($i=0;$i<$scount;++$i){
						$id = Utils::readShort($this->get(2));
						if($id != -1){
							$count = Utils::readByte($this->get(1));						
							$meta = Utils::readShort($this->get(2));
							$d[$i] = array($id,$count,$meta);
							if($id != 0xff){
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
								$len = Utils::readShort($this->get(2));
								$r = Utils::readString($this->get($len * 2));
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
						$m[] = $r;
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