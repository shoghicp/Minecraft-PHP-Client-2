<?php
require_once(dirname(__FILE__)."/Utils.class.php");
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__)."/phpseclib/");
require_once("Crypt/RSA.php");

require_once(dirname(__FILE__)."/Packet.class.php");
require_once(dirname(__FILE__)."/ASNValue.class.php");
require_once(dirname(__FILE__)."/Socket.class.php");

require_once(dirname(__FILE__)."/Entity.class.php");

require_once(dirname(__FILE__)."/../misc/functions.php");


class MinecraftServer{
	private $server, $port, $protocol, $auth, $player, $entities, $players, $key, $rsa, $token;
	protected $spout, $events, $cnt, $responses, $info, $time, $timeState, $stop, $connected, $actions;
	
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = $server;
		$this->port = $port;
		console("[INFO] Generating keypair");
		$this->rsa = new Crypt_RSA();
		$this->key = $this->rsa->createKey();
		$this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$this->rsa->loadKey($this->key["privatekey"], CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
		console("[DEBUG] Server Private key:", true, true, 2);
		foreach(explode(PHP_EOL,$this->key["privatekey"]) as $line){
			console("[DEBUG] ".$line, true, true, 2);
		}
		//Decode root sequence
		$body = new ASNValue;
		$priv = base64_decode(trim(str_replace(array("-----BEGIN RSA PRIVATE KEY-----", "-----END RSA PRIVATE KEY-----", PHP_EOL), "", $this->key["privatekey"])));
		$body->Decode($priv);
		$bodyItems = $body->GetSequence();
		 
		//Read key values:
		$Modulus = $bodyItems[1]->GetIntBuffer();
		$PublicExponent = $bodyItems[2]->GetInt();
		$PrivateExponent = $bodyItems[3]->GetIntBuffer();
		$Prime1 = $bodyItems[4]->GetIntBuffer();
		$Prime2 = $bodyItems[5]->GetIntBuffer();
		$Exponent1 = $bodyItems[6]->GetIntBuffer();
		$Exponent2 = $bodyItems[7]->GetIntBuffer();
		$Coefficient = $bodyItems[8]->GetIntBuffer();
		//Encode key sequence
		$modulus = new ASNValue(ASNValue::TAG_INTEGER);
		$modulus->SetIntBuffer($Modulus);
		$publicExponent = new ASNValue(ASNValue::TAG_INTEGER);
		$publicExponent->SetInt($PublicExponent);
		$keySequenceItems = array($modulus, $publicExponent);
		$keySequence = new ASNValue(ASNValue::TAG_SEQUENCE);
		$keySequence->SetSequence($keySequenceItems);
		//Encode bit string
		$bitStringValue = $keySequence->Encode();
		$bitStringValue = chr(0x00) . $bitStringValue; //Add unused bits byte
		$bitString = new ASNValue(ASNValue::TAG_BITSTRING);
		$bitString->Value = $bitStringValue;
		//Encode body
		$bodyValue = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00" . $bitString->Encode();
		$body = new ASNValue(ASNValue::TAG_SEQUENCE);
		$body->Value = $bodyValue;
		//Get DER encoded public key:
		$PublicDER = $body->Encode();		
		$this->key["publickey"] = "-----BEGIN PUBLIC KEY-----".PHP_EOL.implode(PHP_EOL,str_split(base64_encode($PublicDER),64)).PHP_EOL."-----END PUBLIC KEY-----";
		console("", true, true, 2);
		console("[DEBUG] Server Public key:", true, true, 2);
		foreach(explode(PHP_EOL,$this->key["publickey"]) as $line){
			console("[DEBUG] ".$line, true, true, 2);
		}
		$this->protocol = intval($protocol);
		$this->interface = new MinecraftInterface($server, $protocol, $port);
		$this->cnt = 1;
		$this->events = array();
		$this->responses = array();
		$this->info = array();
		$this->entities = array();
		$this->connected = true;
		$this->actions = array();
		$this->spout = false;
		$this->players = array();
	}
	
	public function activateSpout(){
		return;
		/*$this->registerPluginChannel("AutoProto:HShake");
		$this->registerPluginChannel("ChkCache:setHash");
		$this->sendPluginMessage("AutoProto:HShake", "VanillaProtocol");
		$this->event("onPluginChannelRegister_WECUI", "spoutHandler", true);*/
		$this->event("onConnect", "spoutHandler", true);
		$this->event("recieved_c3", "spoutHandler", true);
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
	
	protected function close($data = ""){
		if($data !== ""){
			$this->trigger("onClose", $data[0]);
			console("[ERROR] Kicked from server, ".$data[0]);
		}else{
			$this->trigger("onClose");
		}
		$this->interface->close();
		$this->connected = false;
		$this->stop();
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
		console("[INFO] Event ". $event, true, true, 2);
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
		$this->event("onPluginMessage_REGISTER", "backgroundHandler", true);
		$this->event("onPluginMessage_UNREGISTER", "backgroundHandler", true);		
		//$this->action(50000, '$this->player->setGround(true); $this->send("0d",$this->player->packet("0d"));');
		//if(isset($this->auth["session_id"])){
		//	$this->action(300000000, 'Utils::curl_get("https://login.minecraft.net/session?name=".$this->auth["user"]."&session=".$this->auth["session_id"]);');
		//}
		
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
				console("[INFO] EID: ".$this->player->getEID());
				$this->startHandlers();
				$this->trigger("onConnect");
				$this->process();
				break;		
		}
	}
	
	public function loginMinecraft($hash){
		if($hash == "" or strpos($hash, "&") !== false){
			console("[WARNING] NAME SPOOF DETECTED");
		}
		$secure = true;
		if($secure !== false){
			$proto = "https";
			console("[INFO] Using secure HTTPS connection");
		}else{
			$proto = "http";
		}
			
		$response = Utils::curl_get($proto."://login.minecraft.net/?user=".$this->auth["user"]."&password=".$this->auth["password"]."&version=".LAUNCHER_VERSION);
		switch($response){
			case 'Bad login':
			case 'Bad Login':
				console("[ERROR] Bad Login");
				$this->close();
				break;
			case "Old Version":
				console("[ERROR] Old Version");
				$this->close();
				break;
			default:
				$content = explode(":",$response);
				if(!is_array($content)){
					console("[ERROR] Unknown Login Error: \"".$response."\"");
					$this->close();
					break;
				}
				console("[INFO] Logged into minecraft.net");
				$this->auth["user"] = $content[2];
				$this->auth["session_id"] = $content[3];
				$res = Utils::curl_get("http://session.minecraft.net/game/joinserver.jsp?user=".$this->auth["user"]."&sessionId=".$this->auth["session_id"]."&serverId=".$hash); //User check
				if($res != "OK"){
					console("[ERROR] Error in User Check: \"".$res."\"");
					$this->close();
				}else{
					console("[INFO] Sent join server request");
				}
				break;
		}
	}
	
	protected function newAuthentication($data, $event){
		$pid = str_replace("recieved_", "", $event);
		switch($pid){
			case "cd":			
				print_r($data);
				die();
			case "02":	
				//console("[DEBUG] 128-bit Simmetric Key generated: 0x".strtoupper(Utils::strToHex($value)), true, true, 2);
				//$publicKey = "-----BEGIN PUBLIC KEY-----".PHP_EOL.implode(PHP_EOL,str_split(base64_encode($data[2]),64)).PHP_EOL."-----END PUBLIC KEY-----";

				$this->token = Utils::writeInt(mt_rand(-2147483647,2147483647));
				$publicKey = base64_decode(trim(str_replace(array("-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----",PHP_EOL), "", $this->key["publickey"])));
				//var_dump($publicKey);
				$this->send("fd", array(
					0 => "-",
					1 => strlen($publicKey),
					2 => $publicKey,
					3 => strlen($this->token),
					4 => $this->token,
				));
				console("[INFO] Public key and token sent");
				$this->event("recieved_fc", 'newAuthentication', true);
				$this->process("fc");
				break;
			case "fc":
				if($this->protocol >= 34){
					$this->key = $this->rsa->decrypt($data[1]);
					console("[DEBUG] 128-bit Simmetric Key : 0x".strtoupper(Utils::strToHex($this->key)), true, true, 2);
					if($this->rsa->decrypt($data[3]) != $this->token){
						die();
					}
					$this->send("fc");
					$this->interface->server->startAES($this->key);
					$this->event("recieved_fc", 'newAuthentication', true);
					$this->process("cd");
				}elseif($this->protocol <= 32){
					$this->interface->server->startRC4($this->key);
					$this->send("01", array());
				}
				$this->event("recieved_01", 'newAuthentication', true);
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
				console("[INFO] EID: ".$this->player->getEID());
				$this->startHandlers();
				$this->trigger("onConnect");
				$this->process();
				break;		
		}
	}	
	
	public function newConnect(){
		$this->event("recieved_02", 'newAuthentication', true);
		$this->process("02");
	}
	
	public function start($user, $password = ""){
		if($this->protocol >= 31){
			$this->newConnect();
			return;
		}
		$this->event("recieved_02", 'authentication', true);
		$this->process("02");
	}
	public function sendSpoutMessage($pid, $version, $data){
		if($this->spout == true){
			include(dirname(__FILE__)."/../pstruct/spout.php");
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
				console("[INFO] [Spout] Pre-cache Completed");
				$this->trigger("onSpoutPreCacheCompleted");
				break;
			case "onRecievedSpoutPacket_44":
				$offset = 0;
				$cnt = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$plugins = array();
				console("[INFO] [Spout] Recieved server plugins");
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
					console("[INFO] [Spout] ".$p." => ".$v);
				}				
				$this->trigger("onSpoutPlugins", $plugins);
				break;
			case "onRecievedSpoutPacket_57":
				$offset = 0;
				$cnt = Utils::readInt(substr($data["data"], $offset,4));
				$offset += 4;
				$permissions = array();
				console("[INFO] [Spout] Updated Permissions");
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
				console("[INFO] [Spout] Got waypoint ".$name." (".$x.",".$y.",".$z.")".($death === true ? " DEATH":""));
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
	var $pstruct, $server;
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = new Socket($server, $port, true);
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
		if($this->server->connected === false){
			return array("pid" => "ff", "data" => array(0 => 'Connection error'));
		}
		$pid = $this->getPID($this->server->read(1));
		$struct = $this->getStruct($pid);
		if($struct === false){
			$this->server->unblock();
			$p = "[".round(Utils::microtime(),4)."] [ERROR]: Bad packet id 0x$pid".PHP_EOL;
			$p .= hexdump(Utils::hexToStr($pid).$this->server->read(512), false, false, true);
			$p .= PHP_EOL . "--------------- (512 byte max extract) ----------" .PHP_EOL;
			logg($p, "packets");
			
			$this->buffer = "";
			$this->server->recieve("\xff".Utils::writeString('Bad packet id '.$pid.''));
			$this->writePacket("ff", array(0 => Utils::writeString('Bad packet id '.$pid.'')));
			return array("pid" => "ff", "data" => array(0 => 'Bad packet id '.$pid.''));
		}
		
		$packet = new Packet($pid, $struct, $this->server);
		$packet->parse();
		
		$len = strlen($packet->raw);
		$p = "[".round(Utils::microtime(),4)."] [SERVER->CLIENT]: 0x$pid (lenght $len)".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL;
		logg($p, "packets", false);
		
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
		$packet->data = $data;
		$packet->create($raw);
		$write = $this->server->write($packet->raw);
		
		$len = strlen($packet->raw);
		$p = "[".round(Utils::microtime(),4)."] [CLIENT->SERVER]: 0x$pid (lenght $len)".PHP_EOL;
		$p .= hexdump($packet->raw, false, false, true);
		$p .= PHP_EOL;
		logg($p, "packets", false);		
		return true;
	}
	
}
?>