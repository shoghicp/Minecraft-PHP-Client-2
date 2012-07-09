<?php

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__)."/phpseclib/");
require_once("Crypt/RSA.php");

require_once("Utils.class.php");
require_once("classes/Packet.class.php");
require_once("classes/Socket.class.php");
require_once("classes/Entity.class.php");

require_once("misc/functions.php");


class MinecraftClient{
	private $server, $port, $protocol, $auth, $player, $entities, $players, $key;
	protected $spout, $events, $cnt, $responses, $info, $inventory, $timeState, $stop, $connected, $actions;
	var $time;
	
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = $server;
		$this->port = $port;

		$this->protocol = intval($protocol);
		console("[INFO] Connecting to Minecraft server protocol ".$this->protocol);
		$this->interface = new MinecraftInterface($server, $protocol, $port);
		$this->cnt = 1;
		$this->events = array("recieved_ff" => array(0 => array('close', true)));
		$this->responses = array();
		$this->info = array();
		$this->entities = array();
		$this->inventory = array();
		$this->connected = true;
		$this->actions = array();
		$this->spout = false;
		$this->players = array();
	}
	
	public function activateSpout(){
		/*$this->registerPluginChannel("AutoProto:HShake");
		$this->registerPluginChannel("ChkCache:setHash");
		$this->sendPluginMessage("AutoProto:HShake", "VanillaProtocol");
		$this->event("onPluginChannelRegister_WECUI", "spoutHandler", true);*/
		$this->event("onConnect", "spoutHandler", true);
		$this->event("recieved_c3", "spoutHandler", true);
		$this->interface->name["c3"] = "Spout Message";
		$this->interface->pstruct["c3"] = array(
			"short",
			"short",
			"int",
			"byteArray",	
		);
	}
	
	public function getPlayer($name = ""){
		if(isset($this->players[$name]) and $name !== ""){
			return $this->players[$name];
		}elseif($name === ""){
			return $this->player;
		}
	}
	
	public function logout($message = "Quitting"){
		$this->send("ff", array(0 => $message));
		sleep(1);
		$this->close(array(0 => $message));
	}
	
	protected function close($data = ""){
		
		if($data !== ""){
			$this->trigger("onClose", $data[0]);
			console("[ERROR] Kicked from server, ".$data[0], true, true, 0);
		}else{
			$this->trigger("onClose");
		}
		$this->interface->close();
		$this->connected = false;
		$this->stop();
	}
	
	public function getInventory(){
		return $this->inventory;
	}
	
	public function getInventorySlot($id){
		if(!isset($this->inventory[$id])){
			return array(0,0,0);
		}
		return $this->inventory[$id];
	}
	
	public function changeSlot($id){
		$this->send("10", array(0 => $id));	
	}
	
	public function animation($id){
		$this->send("12", array(0 => $this->player->getEID(), 1 => $id));
	}
	
	protected function send($pid, $data = array(), $raw = false){
		if($this->connected === true){
			$this->trigger("sent_".$pid, $data);
			$this->trigger("onSentPacket", $data);
			$this->interface->writePacket($pid, $data, $raw);
		}
	}
	
	public function stop(){
		$this->stop = true;
	}	
	
	public function process($stop = "ff"){
		$pid = "";
		$this->stop = false;
		while($pid != $stop and $this->stop === false and $this->connected === true){
			$packet = $this->interface->readPacket();
			$this->trigger("onRecievedPacket", $packet);
			$pid = $packet["pid"];
			$this->trigger("recieved_".$pid, $packet["data"]);
		}
	}
	
	public function trigger($event, $data = ""){
		console("[INTERNAL] Event ". $event, true, true, 3);
		if(isset($this->events[$event])){
			foreach($this->events[$event] as $eid => $ev){
				if(isset($ev[1]) and ($ev[1] === true or is_object($ev[1]))){
					$this->responses[$eid] = call_user_func(array(($ev[1] === true ? $this:$ev[1]), $ev[0]), $data, $event, $this);
				}else{
					$this->responses[$eid] = call_user_func($ev[0], $data, $event, $this);
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

	public function action($microseconds, $code){
		$this->actions[] = array($microseconds, 0, $code);
	}
	
	public function event($event, $func, $in = false){
		++$this->cnt;
		if(!isset($this->events[$event])){
			$this->events[$event] = array();
		}
		$this->events[$event][$this->cnt] = array($func, $in);
		return $this->cnt;
	}
	
	public function deleteEvent($event, $id = -1){
		if($id === -1){
			unset($this->events[$event]);
		}else{
			unset($this->events[$event][$id]);
			if(isset($this->events[$event]) and count($this->events[$event]) == 0){
				unset($this->events[$event]);
			}
		}
	}
	
	public function ping($data = ""){
		if($data === ""){
			$this->send("fe");
			$this->deleteEvent("recieved_ff");
			$eid = $this->event("recieved_ff", "ping", true);
			$this->process();
			return $this->response($eid);
		}else{
			return explode("\xa7", $data[0]);
			$this->close();
		}
	}
	
	public function say($message, $owner = false){
		if($owner != false){
			foreach(explode("\n", wordwrap($message,100-strlen("/tell $owner "), "\n")) as $mess){
				$this->send("03", array(
					0 => "/tell $owner ".$mess,
				));			
			}
		}else{
			foreach(explode("\n", wordwrap($message,100, "\n")) as $mess){
				$this->send("03", array(
					0 => $mess,
				));	
			}
		}		
		$this->trigger("onChatSent", $message);
	}

	public function jump(){
		$this->player->move(0, 1, 0);
		$this->send("0b",$this->player->packet("0b"));
		$this->trigger("onMove", $this->player);
		$this->trigger("onEntityMove", $this->player);
		$this->trigger("onEntityMove_".$this->player->getEID(), $this->player);
	}
	
	public function move($x, $y, $z, $ground = true){
		$this->player->setCoords($x, $y, $z);
		$this->player->setGround($ground);
		$this->send("0b",$this->player->packet("0b"));
		$this->trigger("onMove", $this->player);
		$this->trigger("onEntityMove", $this->player);
		$this->trigger("onEntityMove_".$this->player->getEID(), $this->player);
	}
	
	public function useEntity($eid, $left = true){
		$this->trigger("onUseEntity", array("eid" => $eid, "left" => $left));
		$this->send("07", array(
			0 => $this->player->getEID(),
			1 => $eid,
			2 => $left,
		));		
	}
	
	public function dropSlot(){
		$this->trigger("onDropSlot");
		$this->send("0e", array(
			0 => 4,
			1 => 0,
			2 => 0,
			3 => 0,
		));		
	}

	public function swingArm(){
		$this->send("12", array(
			0 => $this->player->getEID(),
			1 => 1,
		));		
	}
	
	public function eatSlot(){
		$this->trigger("onEatSlot");
		$this->send("0f", array(
			0 => -1,
			1 => -1,
			2 => -1,
			3 => -1,
			4 => array(-1),
		));		
	}
	
	public function tickerFunction(){
		//actions that repeat every x time will go here
		$time = Utils::microtime();
		$this->trigger("onTick", $time);
		foreach($this->actions as $id => $action){
			if($action[1] <= ($time - ($action[0] / 1000000))){
				$this->actions[$id][1] = $time;
				eval($action[2]);
			}
		}
	}
	
	private function backgroundHandler($data, $event){
		switch($event){
			case "onRecievedPacket":
				$this->tickerFunction();
				break;
			case "onPluginMessage_REGISTER":
				$this->trigger("onPluginChannelRegister", $data);
				$this->trigger("onPluginChannelRegister_".$data);
				break;
			case "onPluginMessage_UNREGISTER":
				$this->trigger("onPluginChannelUnregister", $data);
				$this->trigger("onPluginChannelUnegister_".$data);
				break;
		}	
	}
	
	private function handler($data, $event){
		$pid = str_replace("recieved_", "", $event);
		switch($pid){
			case "c9":
				break;
			case "00":
				$this->send("00", array(0 => $data[0]));
				break;
			case "03":
				console("[DEBUG] Chat: ".$data[0], true, true, 2);
				$this->trigger("onChat", $data[0]);
				break;
			case "04":
				$this->time = $data[0] % 24000;
				console("[DEBUG] Time: ".((intval($this->time/1000+6) % 24)).':'.str_pad(intval(($this->time/1000-floor($this->time/1000))*60),2,"0",STR_PAD_LEFT).', '.(($this->time > 23100 or $this->time < 12900) ? "day":"night"), true, true, 2);
				$this->trigger("onTimeChange", $this->time);
				$timeState = (($this->time > 23100 or $this->time < 12900) ? "day":"night");
				if($this->timeState != $timeState){
					$this->timeState = $timeState;
					$this->trigger("onTimeStateChange", $this->timeState);
					if($this->timeState == "day"){
						$this->trigger("onDay");
					}else{
						$this->trigger("onNight");
					}
				}
				break;
			case "06":
				$this->info["spawn"] = array("x" => $data[0], "y" => $data[1], "z" => $data[2]);
				console("[INFO] Got spawn: (".$data[0].",".$data[1].",".$data[2].")");
				$this->trigger("onSpawnChange", $this->info["spawn"]);
				break;
			case "08":
				$this->player->setHealth($data[0]);
				if(isset($data[1])){ //Food
					$this->player->setFood($data[1]);
					console("[INFO] Health: ".$data[0].", Food: ". $data[1]);
				}else{
					console("[INFO] Health: ".$data[0]);
				}
				$this->trigger("onHealthChange", array("health" => $this->player->getHealth(), "food" => $this->player->getFood()));
				if($data[0] <= 0){ //Respawn
					$this->trigger("onDeath");
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
					$this->trigger("onRespawn", $d);
					console("[INFO] Death and respawn");
				}
				break;
			case "0d":
				if(count($this->player->getPosition()) == 0){
					$this->action(50000, '$this->player->setGround(true); $this->send("0d",$this->player->packet("0d"));');
				}				
				$this->player->setPosition($data[0], $data[2], $data[3], $data[1], $data[4], $data[5], $data[6]);
				$this->send("0d",$this->player->packet("0d"));
				console("[INFO] Got position: (".$data[0].",".$data[2].",".$data[3].")");
				$this->trigger("onMove", $this->player);
				$this->trigger("onEntityMove", $this->player);
				$this->trigger("onEntityMove_".$this->player->getEID(), $this->player);				
				break;
			case "14":
				$this->entities[$data[0]] = new Entity($data[0], 0);
				$this->players[$data[1]] =& $this->entities[$data[0]];
				$this->entities[$data[0]]->setName($data[1]);
				$this->entities[$data[0]]->setCoords($data[2] / 32,$data[3] / 32,$data[4] / 32);
				console("[INFO] Player \"".$data[1]."\" (EID: ".$data[0].") spawned at (".($data[2] / 32).",".($data[3] / 32).",".($data[4] / 32).")");
				$this->trigger("onPlayerSpawn", $this->entities[$data[0]]);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "15":
				console("[DEBUG] Item (EID: ".$data[0].") type ".$data[1]." spawned at (".($data[4] / 32).",".($data[5] / 32).",".($data[6] / 32).")", true, true, 2);
				$this->entities[$data[0]] = new Entity($data[0], $data[1], true);
				$this->entities[$data[0]]->setCoords($data[4] / 32,$data[5] / 32,$data[6] / 32);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);				
				break;
			case "17":
			case "18":
				console("[DEBUG] Entity (EID: ".$data[0].") type ".$data[1]." spawned at (".($data[2] / 32).",".($data[3] / 32).",".($data[4] / 32).")", true, true, 2);
				$this->entities[$data[0]] = new Entity($data[0], $data[1], ($pid === "17" ? true:false));
				$this->entities[$data[0]]->setCoords($data[2] / 32,$data[3] / 32,$data[4] / 32);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "1d":
				console("[DEBUG] EID ".$data[0]." despawned", true, true, 2);
				$this->trigger("onEntityDespawn", $data[0]);
				unset($this->entities[$data[0]]);
				break;
			case "1f":
			case "21":
				if(isset($this->entities[$data[0]])){
					$this->entities[$data[0]]->move($data[1] / 32,$data[2] / 32,$data[3] / 32);
					$this->trigger("onEntityMove", $this->entities[$data[0]]);
					$this->trigger("onEntityMove_".$this->entities[$data[0]]->getEID(), $this->entities[$data[0]]);
				}
				break;
			case "22":
				if(isset($this->entities[$data[0]])){
					$this->entities[$data[0]]->setCoords($data[1] / 32,$data[2] / 32,$data[3] / 32);
					$this->trigger("onEntityMove", $this->entities[$data[0]]);
					$this->trigger("onEntityMove_".$this->entities[$data[0]]->getEID(), $this->entities[$data[0]]);
				}
				break;
			case "46";
				switch($data[0]){
					case 0:
						$m = "Invalid bed";
						break;
					case 1:
						$m = "Started raining";
						$this->trigger("onRainStart", $this->time);
						break;
					case 2:
						$m = "Ended raining";
						$this->trigger("onRainStop", $this->time);
						break;
					case 3:
						$m = "Gamemode changed: ".($data[1]==0 ? "survival":"creative");
						$this->trigger("onGamemodeChange", $data[1]);
						break;
					case 4:
						$m = "Entered credits";
						break;
				}
				console("[INFO] Changed game state: ".$m);
				break;
			case "47":
				console("[INFO] Thunderbolt at (".($data[2] / 32).",".($data[3] / 32).",".($data[4] / 32).")", true, true, 2);
				$this->trigger("onThunderbolt", array("eid" => $data[0], "coords" => array("x" => $data[2] / 32, "y" => $data[3] / 32, "z" => $data[4] / 32)));				
				break;
			case "67":
				if($data[0] == 0){
					if(!isset($data[2][0])){
						$this->inventory[$data[1]] = array(0,0,0);
					}else{
						$this->inventory[$data[1]] = $data[2][0];
					}
					$this->trigger("onInventorySlotChanged", array("slot" => $data[1], "data" => $this->getInventorySlot($data[1])));
					$this->trigger("onInventoryChanged", $this->getInventory());
					console("[DEBUG] Changed inventory slot ".$data[1], true, true, 2);
				}
				break;				
			case "68":
				if($data[0] == 0){
					foreach($data[2] as $i => $slot){
						$this->inventory[$i] = $slot;
						$this->trigger("onInventorySlotChanged", array("slot" => $i, "data" => $slot));
					}
					$this->trigger("onInventoryChanged", $this->getInventory());
					console("[INFO] Recieved complete inventory");
				}
				break;
			case "82":
				$text = $data[3].PHP_EOL.$data[4].PHP_EOL.$data[5].PHP_EOL.$data[6];
				console("[DEBUG] Sign at (".$data[0].",".$data[1].",".$data[2].")".PHP_EOL.implode(PHP_EOL."[DEBUG]\t",explode(PHP_EOL,$text)), true, true, 2);
				$this->trigger("onSignUpdate", array("coords" => array("x" => $data[0], "y" => $data[1], "z" => $data[2]), "text" => $text));
				break;
			case "fa":
				$this->trigger("onPluginMessage", array("channel" => $data[0], "data" => $data[2]));
				$this->trigger("onPluginMessage_".$data[0], $data[2]);
				break;
		}
	}


	public function registerPluginChannel($channel){
		if($this->protocol < 23){
			return false;
		}
		$this->send("fa", array(
			0 => "REGISTER",
			1 => strlen($channel),
			2 => $channel,			
		));
	}

	public function unregisterPluginChannel($channel){
		if($this->protocol < 23){
			return false;
		}
		$this->send("fa", array(
			0 => "UNREGISTER",
			1 => strlen($channel),
			2 => $channel,			
		));
	}
	
	
	public function sendPluginMessage($channel, $data){
		if($this->protocol < 23){
			return false;
		}
		$this->send("fa", array(
			0 => $channel,
			1 => strlen($data),
			2 => $data,			
		));
	}
	
	protected function startHandlers(){
	
		if(ACTION_MODE === 1){
			declare(ticks=15);
			register_tick_function(array($this, "tickerFunction"));		
		}else{
			$this->event("onRecievedPacket", "backgroundHandler", true);
		}
		
		$this->event("recieved_00", "handler", true);
		$this->event("recieved_03", "handler", true);
		$this->event("recieved_04", "handler", true);
		$this->event("recieved_06", "handler", true);
		$this->event("recieved_08", "handler", true);
		$this->event("recieved_0d", "handler", true);
		$this->event("recieved_14", "handler", true);
		$this->event("recieved_15", "handler", true);
		$this->event("recieved_17", "handler", true);
		$this->event("recieved_18", "handler", true);
		$this->event("recieved_1d", "handler", true);
		$this->event("recieved_1f", "handler", true);
		$this->event("recieved_21", "handler", true);
		$this->event("recieved_22", "handler", true);
		$this->event("recieved_46", "handler", true);
		$this->event("recieved_47", "handler", true);
		$this->event("recieved_67", "handler", true);
		$this->event("recieved_68", "handler", true);
		$this->event("recieved_82", "handler", true);
		$this->event("recieved_fa", "handler", true);
		$this->event("recieved_c9", "handler", true);
		$this->event("onPluginMessage_REGISTER", "backgroundHandler", true);
		$this->event("onPluginMessage_UNREGISTER", "backgroundHandler", true);		
		if(isset($this->auth["session_id"])){
			$this->action(300000000, 'Utils::curl_get("https://login.minecraft.net/session?name=".$this->auth["user"]."&session=".$this->auth["session_id"]);');
		}
		
	}
	
	protected function authentication($data, $event){
		$pid = str_replace("recieved_", "", $event);
		switch($pid){
			case "02":
				$hash = $data[0];
				if($hash != "-" and $hash != "+"){
					console("[INFO] Server is Premium (SID: ".$hash.")");
					$this->loginMinecraft($hash);
				}
				$this->send("01", array(
					0 => $this->protocol,
					1 => $this->auth["user"],
				));
				$this->event("recieved_01", 'authentication', true);
				$this->process("01");
				break;
			case "01":
				$this->info["seed"] = $data[2];
				$this->info["level_type"] = $this->protocol >= 23 ? $data[3]:0;
				$this->info["mode"] = $this->protocol <= 23 ? $data[4]:$data[3];
				$this->info["dimension"] = $this->protocol <= 23 ? $data[5]:$data[4];
				$this->info["difficulty"] = $this->protocol <= 23 ? $data[6]:$data[5];
				$this->info["height"] = $this->protocol <= 23 ? $data[7]:$data[6];
				$this->info["max_players"] = $this->protocol <= 23 ? $data[8]:$data[7];
				$this->entities[$data[0]] = new Entity($data[0], 0);
				$this->player =& $this->entities[$data[0]];	
				$this->players[$this->player->getName()] =& $this->player;		
				$this->player->setName($this->auth["user"]);
				console("[INFO] Logged in as ".$this->auth["user"]);
				console("[INFO] Player EID: ".$this->player->getEID());
				$this->startHandlers();
				$this->trigger("onConnect");
				$this->process();
				break;		
		}
	}
	
	public function loginMinecraft($hash){
		if($hash == "" or strpos($hash, "&") !== false){
			console("[WARNING] NAME SPOOF DETECTED", true, true, 0);
		}
		$secure = true;
		if($secure !== false){
			$proto = "https";
			console("[DEBUG] Using secure HTTPS connection", true, true, 2);
		}else{
			$proto = "http";
		}
			
		$response = Utils::curl_get($proto."://login.minecraft.net/?user=".$this->auth["user"]."&password=".$this->auth["password"]."&version=".LAUNCHER_VERSION);
		switch($response){
			case 'Bad login':
			case 'Bad Login':
				console("[ERROR] Bad Login", true, true, 0);
				$this->close();
				break;
			case "Old Version":
				console("[ERROR] Old Version", true, true, 0);
				$this->close();
				break;
			default:
				$content = explode(":",$response);
				if(!is_array($content)){
					console("[ERROR] Unknown Login Error: \"".$response."\"", true, true, 0);
					$this->close();
					break;
				}
				$this->auth["user"] = $content[2];
				$this->auth["session_id"] = $content[3];
				console("[INFO] Logged into minecraft.net as ".$this->auth["user"]);
				console("[DEBUG] minecraft.net Session ID: ".$this->auth["session_id"], true, true, 2);
				$res = Utils::curl_get("http://session.minecraft.net/game/joinserver.jsp?user=".$this->auth["user"]."&sessionId=".$this->auth["session_id"]."&serverId=".$hash); //User check
				if($res != "OK"){
					console("[ERROR] Error in User Check: \"".$res."\"", true, true, 0);
					$this->close();
				}else{
					console("[DEBUG] Sent join server request", true, true, 2);
				}
				break;
		}
	}
	
	protected function generateKey($startEntropy = ""){
		//not much entropy, but works ^^
		$entropy = array(
			lcg_value(),
			implode(mt_rand(0,394),get_defined_constants()),
			get_current_user(),
			print_r(ini_get_all(),true),
			(string) memory_get_usage(),
			php_uname(),
			phpversion(),
			zend_version(),
			function_exists("openssl_random_pseudo_bytes") ? openssl_random_pseudo_bytes(1024):Utils::microtime(),
			uniqid(Utils::microtime(),true),
			file_exists("/dev/random") ? fread(fopen("/dev/random", "r"),128):Utils::microtime(),
		);
		shuffle($entropy);
		$value = Utils::hexToStr(md5((string) $startEntropy));
		unset($startEntropy);
		foreach($entropy as $c){
			for($i = 0; $i < 1024; ++$i){
				$value ^= Utils::hexToStr(md5($c.lcg_value().$value.Utils::microtime().mt_rand(0,mt_getrandmax())));
				$value ^= substr(Utils::hexToStr(sha1($c.lcg_value().$value.Utils::microtime().mt_rand(0,mt_getrandmax()))),0,16);
			}
			
		}
		console("[DEBUG] 128-bit Simmetric Key generated: 0x".strtoupper(Utils::strToHex($value)), true, true, 2);
		$this->key = $value;
	}
	
	protected function newAuthentication($data, $event){
		$pid = str_replace("recieved_", "", $event);
		switch($pid){
			case "fd":
				$publicKey = "-----BEGIN PUBLIC KEY-----".PHP_EOL.implode(PHP_EOL,str_split(base64_encode($data[2]),64)).PHP_EOL."-----END PUBLIC KEY-----";
				console("[DEBUG] [RSA-1024] Server Public key:", true, true, 2);
				foreach(explode(PHP_EOL,$publicKey) as $line){
					console("[DEBUG] ".$line, true, true, 2);
				}
				$rsa = new Crypt_RSA();
				$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
				$rsa->loadKey($publicKey, CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
				console("[DEBUG] Generating simmetric key...", true, true, 2);
				$this->generateKey($data[0].$data[4]);
				console("[DEBUG] [RSA-1024] Encrypting simmetric key...", true, true, 2);
				$encryptedKey = $rsa->encrypt($this->key);
				$encryptedToken = $rsa->encrypt($data[4]);
				$hash = $data[0];
				if($hash != "-" and $hash != "+"){
					console("[INFO] Server is Premium (SID: ".$hash.")");
					$hash = Utils::sha1($hash.$this->key.$data[2]);
					console("[DEBUG] Authentication hash: ".$hash,true,true,2);					
					$this->loginMinecraft($hash);
				}else{
					console("[WARNING] Server is NOT Premium", true, true, 0);
				}
				$this->send("fc", array(
					0 => strlen($encryptedKey),
					1 => $encryptedKey,
					2 => strlen($encryptedToken),
					3 => $encryptedToken,
				));
				console("[DEBUG] [RSA-1024] Sent encrypted shared secret and token", true, true, 2);
				$this->event("recieved_fc", 'newAuthentication', true);
				$this->process("fc");
				break;
			case "fc":
				if($this->protocol >= 34){
					$this->interface->server->startAES($this->key);
					$this->send("cd", array(0 => 0));
				}elseif($this->protocol <= 32){
					$this->interface->server->startRC4($this->key);
					$this->send("01", array());
				}
				$this->event("recieved_01", 'newAuthentication', true);
				$this->process("01");
				break;
			case "01":
				$this->info["seed"] = "";
				$this->info["level_type"] = $data[1];
				$this->info["mode"] = $data[2];
				$this->info["dimension"] = $data[3];
				$this->info["difficulty"] = $data[4];
				$this->info["height"] = $data[5];
				$this->info["max_players"] = $data[6];
				$this->entities[$data[0]] = new Entity($data[0], 0);
				$this->player =& $this->entities[$data[0]];	
				$this->players[$this->player->getName()] =& $this->player;		
				$this->player->setName($this->auth["user"]);
				console("[INFO] Logged in as ".$this->auth["user"]);
				console("[INFO] Player EID: ".$this->player->getEID());
				$this->startHandlers();
				$this->trigger("onConnect");
				$this->process();
				break;		
		}
	}	
	
	public function newConnect(){
		console("[DEBUG] Sending Handshake", true, true, 2);
		$this->send("02", array(
			0 => $this->protocol,
			1 => $this->auth["user"],
			2 => $this->server,
			3 => $this->port,
		));
		$this->event("recieved_fd", 'newAuthentication', true);
		$this->process("fd");
	}
	
	public function connect($user, $password = ""){
		$this->auth = array("user" => $user, "password" => $password);
		if($this->protocol >= 31){
			$this->newConnect();
			return;
		}
		console("[DEBUG] Sending Handshake", true, true, 2);
		$this->send("02", array(
			0 => $user.($this->protocol >= 28 ? ";".$this->server.":".$this->port:""),
		));
		$this->event("recieved_02", 'authentication', true);
		$this->process("02");
	}
	public function sendSpoutMessage($pid, $version, $data){
		if($this->spout == true){
			require("pstruct/spout.php");
			$p = new Packet(false, $pstruct_spout[$pid]);
			$p->data = $data;
			$p->create();
			$this->trigger("onSentSpoutPacket_".$pid, array("version" => $version, "data" => $data));
			$this->trigger("onSentSpoutPacket", array("id" => $pid, "version" => $version, "data" => $data));
			console("[DEBUG] [Spout] Sent packet ".$pid, true, true, 2);
			$this->send("c3", array(
				0 => $pid,
				1 => $version,
				2 => strlen($p->raw),
				3 => $p->raw,
			));	
		}
	}
	
	public function spoutHandler($data, $event){
		switch($event){
			case "onRecievedSpoutPacket_13":
				$offset = 0;
				$BID = Utils::readInt(substr($data["data"], $offset,4));
				$offset += 4;
				$info = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$len = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$name = Utils::readString(substr($data["data"], $offset,$len * 2));
				$offset += $len * 2;
				console("[DEBUG] [Spout] Got block ".$name." (ID ".$BID." DATA ".$info.")", true, true, 2);
				$this->trigger("onSpoutBlock", array("id" => $BID, "data" => $info, "name" => $name));
				$this->trigger("onSpoutBlock_".$BID, array("data" => $info, "name" => $name));
				break;
			case "onRecievedSpoutPacket_30":
				console("[DEBUG] [Spout] Pre-cache Completed", true, true, 2);
				$this->trigger("onSpoutPreCacheCompleted");
				break;
			case "onRecievedSpoutPacket_44":
				$offset = 0;
				$cnt = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$plugins = array();
				console("[DEBUG] [Spout] Recieved server plugins", true, true, 2);
				for($i = 0; $i < $cnt; ++$i){
					$len = Utils::readShort(substr($data["data"], $offset,2));
					$offset += 2;
					$p = Utils::readString(substr($data["data"], $offset,$len * 2));
					$offset += $len * 2;
					$len = Utils::readShort(substr($data["data"], $offset,2));
					$offset += 2;
					$v = Utils::readString(substr($data["data"], $offset,$len * 2));
					$offset += $len * 2;
					$plugins[$p] = $v;
					console("[DEBUG] [Spout] ".$p." => ".$v, true, true, 2);
				}				
				$this->trigger("onSpoutPlugins", $plugins);
				break;
			case "onRecievedSpoutPacket_57":
				$offset = 0;
				$cnt = Utils::readInt(substr($data["data"], $offset,4));
				$offset += 4;
				$permissions = array();
				console("[DEBUG] [Spout] Updated Permissions", true, true, 2);
				for($i = 0; $i < $cnt; ++$i){
					$len = Utils::readShort(substr($data["data"], $offset,2));
					$offset += 2;
					$key = Utils::readString(substr($data["data"], $offset, $len * 2));
					$offset += $len * 2;
					$value = Utils::readByte(substr($data["data"], $offset,1)) == 1 ? true:false;
					$offset += 1;
					$permissions[$key] = $value;
					console("[DEBUG] [Spout] ".$key." => ".$value, true, true, 2);
				}
				$this->trigger("onSpoutPermissions", $permissions);
				break;
			case "onRecievedSpoutPacket_60":
				$offset = 0;
				$x = Utils::readDouble(substr($data["data"], $offset,8));
				$offset += 8;
				$y = Utils::readDouble(substr($data["data"], $offset,8));
				$offset += 8;
				$z = Utils::readDouble(substr($data["data"], $offset,8));
				$offset += 8;				
				$len = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$name = Utils::readString(substr($data["data"], $offset,$len * 2));
				$offset += $len * 2;
				$death = Utils::readByte(substr($data["data"], $offset,1)) == 1 ? true:false;
				$offset += 1;
				console("[DEBUG] [Spout] Got waypoint ".$name." (".$x.",".$y.",".$z.")".($death === true ? " DEATH":""), true, true, 2);
				$this->trigger("onSpoutWaypoint", array("coords" => array("x" => $x, "y" => $y, "z" => $z), "name" => $name, "death" => $death));
				break;
			case "recieved_c3":
				$packetId = $data[0];
				$version = $data[1];
				$packet = $data[3];
				console("[DEBUG] [Spout] Recieved packet ".$packetId, true, true, 2);
				$this->trigger("onRecievedSpoutPacket_".$packetId, array("version" => $version, "data" => $packet));
				$this->trigger("onRecievedSpoutPacket", array("id" => $packetId, "version" => $version, "data" => $packet));
				break;
			case "recieved_12":
				if($data[0] == -42){
					$this->spout = true;
					$this->sendSpoutMessage(33,0,array(0 => SPOUT_VERSION));
					console("[INFO] [Spout] Authenticated as a ".SPOUT_VERSION." Spout client");
					$this->event("onRecievedSpoutPacket_13", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_30", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_44", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_57", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_60", "spoutHandler", true);
				}
				break;
			case "onConnect":
				$this->send("12", array(
					0 => -42,
					1 => 1,				
				));
				$this->event("recieved_12", 'spoutHandler', true);			
				break;		
		}
	
	}	

}

class MinecraftInterface{
	protected $protocol;
	var $pstruct, $name, $server;
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = new Socket($server, $port);
		$this->protocol = intval($protocol);
		require("pstruct/".$this->protocol.".php");
		require("pstruct/packetName.php");
		$this->pstruct = $pstruct;
		$this->name = $packetName;
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
	
	protected function writeDump($pid, $raw, $data, $origin = "client"){
		if(LOG === true and DEBUG >= 2){
			$len = strlen($raw);
			$p = "[".Utils::microtime()."] [".($origin === "client" ? "CLIENT->SERVER":"SERVER->CLIENT")."]: ".$this->name[$pid]." (0x$pid) [lenght $len]".PHP_EOL;
			$p .= hexdump($raw, false, false, true);
			foreach($data as $i => $d){
				$p .= $i ." => ".(!is_array($d) ? $this->pstruct[$pid][$i]."(".(($this->pstruct[$pid][$i] === "byteArray" or $this->pstruct[$pid][$i] === "newChunkArray" or $this->pstruct[$pid][$i] === "chunkArray" or $this->pstruct[$pid][$i] === "chunkInfo" or $this->pstruct[$pid][$i] === "multiblockArray" or $this->pstruct[$pid][$i] === "newMultiblockArray") ? Utils::strToHex($d):$d).")":$this->pstruct[$pid][$i]."(***)").PHP_EOL;
			}
			$p .= PHP_EOL;
			logg($p, "packets", false);	
		}
	
	}
	
	public function readPacket(){
		if($this->server->connected === false){
			return array("pid" => "ff", "data" => array(0 => 'Connection error'));
		}
		$pid = $this->getPID($this->server->read(1));
		$struct = $this->getStruct($pid);
		if($struct === false){
			$this->server->unblock();
			$p = "[".round(Utils::microtime(),4)."] [ERROR]: Bad packet id 0x$pid".PHP_EOL;
			$p .= hexdump(Utils::hexToStr($pid).$this->server->read(1024, true), false, false, true);
			$p .= PHP_EOL . "--------------- (1024 byte max extract) ----------" .PHP_EOL;
			logg($p, "packets");
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Bad packet id '.$pid.''));
			$this->writePacket("ff", array(0 => 'Bad packet id '.$pid.''));
			return array("pid" => "ff", "data" => array(0 => 'Bad packet id '.$pid.''));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->protocol = $this->protocol;
		$packet->parse();
		
		$this->writeDump($pid, $packet->raw, $packet->data, "server");
		
		return array("pid" => $pid, "data" => $packet->data);
	}
	
	public function writePacket($pid, $data = array(), $raw = false){
		$struct = $this->getStruct($pid);
		if($this->protocol >= 32){
			if($pid == "01"){
				$struct = array();
			}
		}
		$packet = new Packet($pid, $struct);
		$packet->protocol = $this->protocol;
		$packet->data = $data;
		$packet->create($raw);
		$write = $this->server->write($packet->raw);
		
		$this->writeDump($pid, $packet->raw, $data, "client");	
		return true;
	}
	
}
?>