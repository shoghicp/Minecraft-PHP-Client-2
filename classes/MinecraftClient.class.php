<?php
require_once(dirname(__FILE__)."/Utils.class.php");
require_once(dirname(__FILE__)."/Packet.class.php");
require_once(dirname(__FILE__)."/Socket.class.php");

require_once(dirname(__FILE__)."/Entity.class.php");

require_once(dirname(__FILE__)."/../functions.php");


define("CURRENT_PROTOCOL", 29);



class MinecraftClient{
	private $server, $port, $protocol, $auth, $player, $entities;
	protected $events, $cnt, $responses, $info, $inventory, $time;
	
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = $server;
		$this->port = $port;
		$this->protocol = intval($protocol);
		$this->interface = new MinecraftInterface($server, $protocol, $port);
		$this->cnt = 1;
		$this->events = array("ff" => array(0 => array('close', true)));
		$this->responses = array();
		$this->info = array();
		$this->entities = array();
		$this->inventory = array();
	}
	
	public function logout($message = ""){
		$this->send("ff", array(0 => $message));
		$this->close(array("data" => array(0 => $message)));
	}
	
	public function close($data = ""){
		if($data !== ""){
			console("[-] Kicked from server, ".$data["data"][0]);
		}
		$this->interface->close();
		die();
	}
	
	public function getInventory(){
		return $this->inventory;
	}
	
	public function getInventorySlot($id){
		return $this->inventory[$id];
	}
	
	public function changeSlot($id){
		$this->send("10", array(0 => $id));	
	}
	
	protected function send($pid, $data = array()){	
		$this->interface->writePacket($pid, $data);
	}
	
	public function process($stop = "ff"){
		$pid = "";
		while($pid != $stop){
			$packet = $this->interface->readPacket();
			$pid = $packet["pid"];
			if(isset($this->events[$pid]) and count($this->events[$pid]) > 0){
				foreach($this->events[$pid] as $eid => $ev){
					if(isset($ev[1]) and $ev[1] == true){
						$this->responses[$eid] = call_user_func(array($this, $ev[0]), $packet, $eid, $this);
					}else{
						$this->responses[$eid] = call_user_func($ev[0], $packet, $eid, $this);
					}
				}
			}		
		}
	}
	
	public function response($eid){
		if(isset($this->responses[$eid])){
			$res = $this->responses[$eid];
			unset($this->responses[$eid]);
			return $res;
		}
		return false;
	}
	
	public function event($pid, $func, $in = false){
		++$this->cnt;
		if(!isset($this->events[$pid])){
			$this->events[$pid] = array();
		}
		$this->events[$pid][$this->cnt] = array($func, $in);
		return $this->cnt;
	}
	
	public function deleteEvent($pid, $id = -1){
		if($id === -1){
			unset($this->events[$pid]);
		}else{
			unset($this->events[$pid][$id]);
		}
		
	}
	
	public function ping($data = ""){
		if($data === ""){
			$this->send("fe");
			$eid = $this->event("ff", 'ping', true);
			$this->process();
			return $eid;
		}else{
			return explode("\xa7", $data["data"][0]);
		}
	}
	
	public function say($message){
		$this->send("03", array(
			0 => $message,
		));
	}
	
	private function handler($data, $eid){
		switch($data["pid"]){
			case "00":
				$this->send("00", array(0 => $data["data"][0]));
				break;
			case "03":
				console("[*] Chat: ".$data["data"][0]);
				break;
			case "04":
				$this->time = $data["data"][0] % 24000;
				console("[*] Time: ".((intval($this->time/1000+6) % 24)).':'.str_pad(intval(($this->time/1000-floor($this->time/1000))*60),2,"0",STR_PAD_LEFT).', '.(($this->time > 23100 or $this->time < 12900) ? "day":"night")."   \r", false, false);
				break;
			case "08":
				$this->entities[$this->player]->setHealth($data["data"][0]);
				if(isset($data["data"][1])){ //Food
					$this->entities[$this->player]->setFood($data["data"][1]);
					console("[*] Health: ".$data["data"][0].", Food: ". $data["data"][1]);
				}else{
					console("[*] Health: ".$data["data"][0]);
				}
				if($data["data"][0] <= 0){ //Respawn
					$d = array(					
						0 => $this->info["dimension"],
						1 => 1,
						2 => $this->info["mode"],
						3 => $this->info["height"],
						4 => $this->info["seed"],
					);
					if($this->protocol >= 23){
						$d[5] = $this->info["level_type"];
					}
					$this->send("09", $d);
					console("[-] Death and respawn");
				}
				break;
			case "0d":
				$this->entities[$this->player]->setPosition($data["data"][0], $data["data"][2], $data["data"][3], $data["data"][1], $data["data"][4], $data["data"][5], $data["data"][6]);
				$this->send("0d",$this->entities[$this->player]->packet("0d"));
				console("[+] Got position: (".$data["data"][0].",".$data["data"][2].",".$data["data"][3].")");
				$this->entities[$this->player]->setGround(true);
				$this->send("0a",$this->entities[$this->player]->packet("0a"));
				break;
			case "14":				
				$this->entities[$data["data"][0]] = new Entity($data["data"][0], 0);
				$this->entities[$data["data"][0]]->setName($data["data"][1]);
				$this->entities[$data["data"][0]]->setCoords($data["data"][2] / 32,$data["data"][3] / 32,$data["data"][4] / 32);
				console("[+] Player \"".$data["data"][1]."\" (EID: ".$data["data"][0].") spawned at (".($data["data"][2] / 32).",".($data["data"][3] / 32).",".($data["data"][4] / 32).")");
				break;
			case "17":
			case "18":
				console("[+] EID: ".$data["data"][0]." type ".$data["data"][1]." spawned at (".($data["data"][2] / 32).",".($data["data"][3] / 32).",".($data["data"][4] / 32).")");
				$this->entities[$data["data"][0]] = new Entity($data["data"][0], $data["data"][1], ($data["pid"] === "17" ? true:false));
				$this->entities[$data["data"][0]]->setCoords($data["data"][2] / 32,$data["data"][3] / 32,$data["data"][4] / 32);
				break;
			case "1d":
				console("[*] EID ".$data["data"][0]." despawned");
				unset($this->entities[$data["data"][0]]);
				break;
			case "1f":
			case "21":
				if(isset($this->entities[$data["data"][0]])){
					$this->entities[$data["data"][0]]->move($data["data"][1] / 32,$data["data"][2] / 32,$data["data"][3] / 32);
				}
				break;
			case "22":
				if(isset($this->entities[$data["data"][0]])){
					$this->entities[$data["data"][0]]->setCoords($data["data"][1] / 32,$data["data"][2] / 32,$data["data"][3] / 32);
				}
				break;
			case "46";
				switch($data["data"][0]){
					case 0:
						$m = "Invalid bed";
						break;
					case 1:
						$m = "Started raining";
						break;
					case 2:
						$m = "Ended raining";
						break;
					case 3:
						$m = "Gamemode changed: ".($$data["data"][1]==0 ? "survival":"creative");
						break;
					case 4:
						$m = "Entered credits";
						break;
				}
				console("[*] ".$m);
				break;
			case "67":
				if($data["data"][0] == 0){
					if(!isset($data["data"][2][0])){
						$this->inventory[$data["data"][1]] = array(0,0,0);
					}else{
						$this->inventory[$data["data"][1]] = $data["data"][2][0];
					}
					console("[*] Changed inventory slot ".$data["data"][1]);
				}
				break;				
			case "68":
				if($data["data"][0] == 0){
					foreach($data["data"][2] as $i => $slot){
						$this->inventory[$i] = $slot;
					}
					console("[+] Recieved inventory");
				}
				break;
		}
	}
	
	protected function startHandlers(){
		$this->event("00", "handler", true);
		$this->event("03", "handler", true);
		$this->event("04", "handler", true);
		$this->event("08", "handler", true);
		$this->event("0d", "handler", true);
		$this->event("14", "handler", true);
		$this->event("17", "handler", true);
		$this->event("18", "handler", true);
		$this->event("1d", "handler", true);
		$this->event("1f", "handler", true);
		$this->event("21", "handler", true);
		$this->event("22", "handler", true);
		$this->event("46", "handler", true);
		$this->event("67", "handler", true);
		$this->event("68", "handler", true);
	}
	
	protected function authentication($data){
		switch($data["pid"]){
			case "02":
				$hash = $data["data"][0];
				if($hash != "-" and $hash != "+"){
					console("[*] Server is Premium (SID: ".$hash.")");
					if($hash == "" or strpos($hash, "&") !== false){
						console("[!] NAME SPOOF DETECTED");
					}
					$secure = true;
					if($secure !== false){
						$proto = "https";
						console("[+] Using secure HTTPS connection");
					}else{
						$proto = "http";
					}
						
					$response = Utils::curl_get($proto."://login.minecraft.net/?user=".$this->auth["user"]."&password=".$this->auth["password"]."&version=12");
					switch($response){
						case 'Bad login':
						case 'Bad Login':
							console("[-] Bad login");
							$this->close();
							break;
						case "Old Version":
							console("[-] Old Version");
							$this->close();
							break;
						default:
							$content = explode(":",$response);
							if(!is_array($content)){
								console("[-] Unknown Login Error: \"".$response."\"");
								$this->close();
							}
							console("[+] Logged into minecraft.net");
							break;
					}
					$res = Utils::curl_get("http://session.minecraft.net/game/joinserver.jsp?user=".$this->auth["user"]."&sessionId=".$content[3]."&serverId=".$hash); //User check
					if($res != "OK"){
						console("[-] Error in User Check: \"".$res."\"");
						$this->close();
					}
				}
				$this->send("01", array(
					0 => $this->protocol,
					1 => $this->auth["user"],
				));
				$this->event("01", 'authentication', true);
				break;
			case "01":
				$this->info["seed"] = $data["data"][2];
				$this->info["level_type"] = $this->protocol >= 23 ? $data["data"][3]:0;
				$this->info["mode"] = $this->protocol <= 23 ? $data["data"][4]:$data["data"][3];
				$this->info["dimension"] = $this->protocol <= 23 ? $data["data"][5]:$data["data"][4];
				$this->info["difficulty"] = $this->protocol <= 23 ? $data["data"][6]:$data["data"][5];
				$this->info["height"] = $this->protocol <= 23 ? $data["data"][7]:$data["data"][6];
				$this->info["max_players"] = $this->protocol <= 23 ? $data["data"][8]:$data["data"][7];
				$this->player = $data["data"][0];
				$this->entities[$this->player] = new Entity($data["data"][0], 0);
				$this->entities[$this->player]->setName($this->auth["user"]);
				console("[+] EID: ".$this->player);
				$this->startHandlers();
				break;		
		}
	}
	
	public function connect($user, $password = ""){
		$this->auth = array("user" => $user, "password" => $password);
		$this->send("02", array(
			0 => $user.($this->protocol >= 28 ? ";".$this->server.":".$this->port:""),
		));
		$this->event("02", 'authentication', true);
		$this->process("0d");
	}
	
	

}




class MinecraftInterface{
	private $server;
	protected $pstruct, $protocol;
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = new Socket($server, $port);
		$this->protocol = intval($protocol);
		include(dirname(__FILE__)."/../pstruct/".$this->protocol.".php");
		$this->pstruct = $pstruct;
	}
	
	public function close(){
		return $this->server->close();
	}
	
	protected function getPID($chr){
		return Utils::strToHex($chr{0});
	}
	
	protected function getStruct($pid){
		if(isset($this->pstruct[$pid])){
			return $this->pstruct[$pid];
		}
		return false;
	}
	
	public function readPacket(){		
		$pid = $this->getPID($this->server->read(1));
		$struct = $this->getStruct($pid);
		if($struct === false){
			$this->server->unblock();
			$p = "==".time()."==> ERROR Bad packet id $pid :".PHP_EOL;
			$p .= hexdump($this->server->read(512), false, false, true);
			$p .= PHP_EOL . "--------------- (512 byte extract) ----------" .PHP_EOL .PHP_EOL;
			logg($p, "packets");
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Kicked from server, "Bad packet id '.$pid.'"'));
			$this->writePacket("ff", array(0 => Utils::writeString('Kicked from server, "Bad packet id '.$pid.'"')));
			return array("pid" => "ff", "data" => array(0 => 'Kicked from server, "Bad packet id '.$pid.'"'));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->parse();
		
		$len = strlen($packet->raw);
		$p = "==".time()."==> RECIEVED Packet $pid, lenght $len:".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL .PHP_EOL;
		logg($p, "packets", false);
		
		return array("pid" => $pid, "data" => $packet->data);
	}
	
	public function writePacket($pid, $data = array()){
		$struct = $this->getStruct($pid);
		$packet = new Packet($pid, $struct);
		$packet->data = $data;
		$packet->create();
		$this->server->write($packet->raw);
		
		$len = strlen($packet->raw);
		$p = "==".time()."==> SENT Packet $pid, lenght $len:".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL .PHP_EOL;
		logg($p, "packets", false);		
		
		return true;
	}
	
}
?>