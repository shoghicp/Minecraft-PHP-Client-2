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


class MinecraftClient{
	private $server, $port, $player, $entities, $players, $key;
	protected $spout, $events, $cnt, $responses, $info, $inventory, $timeState, $stop, $connected, $actions, $useMap;
	var $time, $protocol, $map, $auth, $mapParser;
	
	
	function __construct($server, $protocol = CURRENT_PROTOCOL, $port = "25565"){
		$this->server = $server;
		$this->port = $port;
		
		$this->protocol = (int) $protocol;
		console("[INFO] Connecting to Minecraft server protocol ".$this->protocol);
		$this->interface = new MinecraftInterface($server, $protocol, $port);
		$this->cnt = 1;
		$this->events = array("recieved_ff" => array(0 => array('close', true)), "disabled" => array());
		$this->responses = array();
		$this->info = array();
		$this->entities = array();
		$this->inventory = array();
		$this->connected = true;
		$this->actions = array();
		$this->spout = false;
		$this->players = array();
		$this->useMap = true;	
		$this->auth = array();
		register_shutdown_function("logg", "", "console", false, 0, true);
		register_shutdown_function("logg", "", "packets", false, 0, true);
	}
	
	public function disableMap(){
		$this->useMap = false;
		unset($this->mapParser, $this->map);
		console("[DEBUG] Map disabled", true, true, 2);
	}
	
	public function activateSpout(){
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
	
	public function close($data = ""){
		
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
	
	public function send($pid, $data = array(), $raw = false){
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
		while($pid !== $stop and $this->stop === false and $this->connected === true){
			$packet = $this->interface->readPacket();
			$this->trigger("onRecievedPacket", $packet);
			$pid = $packet["pid"];
			$this->trigger("recieved_".$pid, $packet["data"]);
		}
	}
	
	public function trigger($event, $data = ""){
		console("[INTERNAL] Event ". $event, true, true, 3);
		if(isset($this->events[$event]) and !isset($this->events["disabled"][$event])){
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
		$this->actions[] = array($microseconds / 1000000, microtime(true), $code);
		console("[INTERNAL] Attached to action ".$microseconds, true, true, 3);
	}	
	
	public function toggleEvent($event){
		if(isset($this->events["disabled"][$event])){
			unset($this->events["disabled"][$event]);
			console("[INTERNAL] Enabled event ".$event, true, true, 3);
		}else{
			$this->events["disabled"][$event] = false;
			console("[INTERNAL] Disabled event ".$event, true, true, 3);
		}	
	}
	
	public function event($event, $func, $in = false){
		++$this->cnt;
		if(!isset($this->events[$event])){
			$this->events[$event] = array();
		}
		$this->events[$event][$this->cnt] = array($func, $in);
		console("[INTERNAL] Attached to event ".$event, true, true, 3);
		return $this->cnt;
	}
	
	public function deleteEvent($event, $id = -1){
		if($id === -1){
			unset($this->events[$event]);
		}else{
			unset($this->events[$event][$id]);
			if(isset($this->events[$event]) and count($this->events[$event]) === 0){
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
		if($this->player->dead === false){
			$this->send("0b",$this->player->packet("0b"));
		}
		$this->trigger("onMove", $this->player);
		$this->trigger("onEntityMove", $this->player);
		$this->trigger("onEntityMove_".$this->player->eid, $this->player);
	}

	public function moveFromHere($x, $y, $z, $yaw = 0, $pitch = 0){
		$this->player->move($x, $y, $z, $yaw, $pitch);
		if($this->player->dead === false){
			$this->send("0d",$this->player->packet("0d"));
		}
		$this->trigger("onMove", $this->player);
		$this->trigger("onEntityMove", $this->player);
		$this->trigger("onEntityMove_".$this->player->eid, $this->player);
	}
	
	public function move($x, $y, $z, $ground = true){
		$this->player->setCoords($x, $y, $z);
		$this->player->setGround($ground);
		if($this->player->dead === false){
			$this->send("0b",$this->player->packet("0b"));
		}
		$this->trigger("onMove", $this->player);
		$this->trigger("onEntityMove", $this->player);
		$this->trigger("onEntityMove_".$this->player->eid, $this->player);
	}
	
	public function useEntity($eid, $left = true){
		$this->trigger("onUseEntity", array("eid" => $eid, "left" => $left));
		$this->send("07", array(
			0 => $this->player->eid,
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
		$this->animation(1);
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
		$time = microtime(true);
		foreach($this->actions as $id => $action){
			if($action[1] <= ($time - $action[0])){
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
	
	private function mapHandler($data, $event){
		switch($event){
			case "start":
				if($this->protocol >= 28){
					//Anvil format
					define("HEIGHT_LIMIT", ($this->info["height"] > 0 ? $this->info["height"]:256) );
					require_once("classes/Anvil.class.php");
					$this->mapParser = new Anvil;
					console("[DEBUG] [Anvil] Map parser started", true, true, 2);
				}else{
					//McRegion format, not tested
					define("HEIGHT_LIMIT", 128);
					require_once("classes/McRegion.class.php");
					$this->mapParser = new McRegion;
					console("[DEBUG] [McRegion] Map parser started", true, true, 2);				
				}
				$this->map = new MapInterface($this);
				break;
			case "recieved_38":
				if($this->useMap === true){
					$offset = 0;
					$data[2] = gzinflate(substr($data[2],2));
					$offsetData = 0;
					for($x = 0; $x < $data[0]; ++$x){
						$X = Utils::readInt(substr($data[3],$offset,4));
						$offset += 4;
						$Z = Utils::readInt(substr($data[3],$offset,4));
						$offset += 4;
						$bitmask = Utils::readShort(substr($data[3],$offset,2));
						$offset += 2;
						$add_bitmask = Utils::readShort(substr($data[3],$offset,2));
						$offset += 2;
						$d = "";
						for($i = 0; $i < (HEIGHT_LIMIT >> 4); ++$i){
							if($bitmask & (1 << $i)){
								$d .= substr($data[2], $offsetData, $this->mapParser->sectionSize);
								$offsetData += $this->mapParser->sectionSize;
							}
							if($add_bitmask & (1 << $i)){
								$offsetData += 2048;
							}							
						}
						$offsetData += 256;
						$this->mapParser->addChunk($X, $Z, $d, $bitmask, false, $add_bitmask);
					}
				}
				break;
			case "recieved_35":
				if($this->useMap === true){
					$this->map->changeBlock($data[0], $data[1], $data[2], $data[3], $data[4]);
				}
				break;
			case "recieved_34":
				if($this->useMap === true){
				
				}
				break;
			case "recieved_33":
				if($this->useMap === true){
					if($this->protocol > 29){
						if($data[2] === true and $data[3] === 0){
							$this->mapParser->unloadChunk($data[0], $data[1]);
						}else{
							$this->mapParser->addChunk($data[0], $data[1], $data[6], $data[3], true, $data[4]);
						}
					}elseif($this->protocol >= 28){
						$this->mapParser->addChunk($data[0], $data[1], $data[7], $data[3], true, $data[4]);
					}else{
						if($data[4] >= 127){
							$this->mapParser->addChunk($data[0], $data[2], $data[7]);
						}
					}
				}
				break;
			case "recieved_32":
				if($this->useMap === true){
					if($data[2] === false){
						$this->mapParser->unloadChunk($data[0], $data[1]);
					}
				}
				break;
		}
	}
	
	private function handler($data, $event){
		switch($event){
			case "recieved_c9":
				console("[INTERNAL] ".$data[0]." ping: ".$data[2], true, true, 3);
				if($data[1] === false){
					$this->trigger("onPlayerPingRemove", $data[0]);
				}else{
					$this->trigger("onPlayerPing", array("name" => $data[0], "ping" => $data[2]));
				}
				break;
			case "recieved_00":
				$this->send("00", array(0 => $data[0]));
				break;
			case "recieved_03":
				console("[DEBUG] Chat: ".$data[0], true, true, 2);
				$this->trigger("onChat", $data[0]);
				break;
			case "recieved_04":
				$this->time = ($this->protocol > 39 ? $data[1]:$data[0]) % 24000;
				console("[DEBUG] Time: ".((intval($this->time/1000+6) % 24)).':'.str_pad(intval(($this->time/1000-floor($this->time/1000))*60),2,"0",STR_PAD_LEFT).', '.(($this->time > 23100 or $this->time < 12900) ? "day":"night"), true, true, 2);
				$this->trigger("onTimeChange", $this->time);
				$timeState = (($this->time > 23100 or $this->time < 12900) ? "day":"night");
				if($this->timeState != $timeState){
					$this->timeState = $timeState;
					$this->trigger("onTimeStateChange", $this->timeState);
					if($this->timeState === "day"){
						$this->trigger("onDay");
					}else{
						$this->trigger("onNight");
					}
				}
				break;
			case "recieved_06":
				$this->info["spawn"] = array("x" => $data[0], "y" => $data[1], "z" => $data[2]);
				console("[INFO] Got spawn: (".$data[0].",".$data[1].",".$data[2].")");
				$this->trigger("onSpawnChange", $this->info["spawn"]);
				break;
			case "recieved_08":
				$this->player->setHealth($data[0]);
				if($data[0] <= 0){ //Respawn
					$this->player->dead = true;
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
					if($this->protocol >= 36){						
						$this->send("cd", array(1));
					}else{
						$this->send("09", $d);
					}
					$this->trigger("onRespawn", $d);
					console("[DEBUG] Death", true, true, 2);
				}
				if(isset($data[1])){ //Food
					$this->player->setFood($data[1]);
					console("[INFO] Health: ".$data[0].", Food: ". $data[1]);
				}else{
					console("[INFO] Health: ".$data[0]);
				}
				$this->trigger("onHealthChange", array("health" => $this->player->getHealth(), "food" => $this->player->getFood()));
				break;
			case "recieved_09":
				$this->player->dead = false;
				console("[INFO] Respawned");
				break;
			case "recieved_0d":
				$this->player->setPosition($data[0], $data[2], $data[3], $data[1], $data[4], $data[5], $data[6]);
				console("[DEBUG] Got position: (".$data[0].",".$data[2].",".$data[3].")", true, true, 2);
				$this->send("0d",$this->player->packet("0d"));
				$this->trigger("onMove", $this->player);
				$this->trigger("onEntityMove", $this->player);
				$this->trigger("onEntityMove_".$this->player->eid, $this->player);
			case "onTick":
				if($this->player->dead === false){
					$this->send("0d", $this->player->packet("0d"));
				}
				break;
			case "recieved_13":
				console("[DEBUG] Entity ".$data[0]." did action ".$data[1], true, true, 2);
				$this->trigger("onEntityAction_".$data[1], $this->entities[$data[0]]);
				break;
			case "recieved_14":
				$this->entities[$data[0]] = new Entity($data[0], ENTITY_PLAYER);
				$this->players[$data[1]] =& $this->entities[$data[0]];
				$this->entities[$data[0]]->setName($data[1]);
				$this->entities[$data[0]]->setCoords($data[2] >> 5,$data[3] >> 5,$data[4] >> 5);
				if($this->protocol > 29){
					$this->entities[$data[0]]->setMetadata($data[8]);
				}
				console("[INFO] Player \"".$data[1]."\" (EID: ".$data[0].") spawned at (".($data[2] >> 5).",".($data[3] >> 5).",".($data[4] >> 5).")");
				$this->trigger("onPlayerSpawn", $this->entities[$data[0]]);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "recieved_15":
				console("[DEBUG] Item (EID: ".$data[0].") type ".$data[1]." spawned at (".($data[4] >> 5).",".($data[5] >> 5).",".($data[6] >> 5).")", true, true, 2);
				$this->entities[$data[0]] = new Entity($data[0], ENTITY_ITEM, $data[1]);
				$this->entities[$data[0]]->setCoords($data[4] >> 5,$data[5] >> 5,$data[6] >> 5);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "recieved_17":
			case "recieved_18":
				$this->entities[$data[0]] = new Entity($data[0], ($event === "recieved_17" ? ENTITY_OBJECT:ENTITY_MOB), $data[1]);
				$this->entities[$data[0]]->setCoords($data[2] >> 5,$data[3] >> 5,$data[4] >> 5);
				if($event === "recieved_18"){
					$this->entities[$data[0]]->setMetadata(($this->protocol > 29 ? $data[11]:$data[8]));
				}
				console("[DEBUG] Entity (EID: ".$data[0].") type ".$this->entities[$data[0]]->getName()." spawned at (".($data[2] >> 5).",".($data[3] >> 5).",".($data[4] >> 5).")", true, true, 2);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "recieved_19":
				$this->entities[$data[0]] = new Entity($data[0], ENTITY_PAINTING);
				$this->entities[$data[0]]->setName($data[1]);
				$this->entities[$data[0]]->setCoords($data[2],$data[3],$data[4]);
				console("[DEBUG] Painting (EID: ".$data[0].") type ".$this->entities[$data[0]]->getName()." spawned at (".$data[2].",".$data[3].",".$data[4].")", true, true, 2);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "recieved_1a":
				$this->entities[$data[0]] = new Entity($data[0], ENTITY_EXPERIENCE);
				$this->entities[$data[0]]->setCoords($data[1],$data[2],$data[3]);
				console("[DEBUG] Experience Orb (EID: ".$data[0].") spawned at (".$data[1].",".$data[2].",".$data[3].")", true, true, 2);
				$this->trigger("onEntitySpawn", $this->entities[$data[0]]);
				break;
			case "recieved_1d":
				if($this->protocol <= 29){
					console("[DEBUG] EID ".$data[0]." despawned", true, true, 2);
					$this->trigger("onEntityDespawn", $data[0]);
					unset($this->entities[$data[0]]);
				}else{
					foreach($data[1] as $eid){
						console("[DEBUG] EID ".$eid." despawned", true, true, 2);
						$this->trigger("onEntityDespawn", $eid);					
					}
				}
				
				break;
			case "recieved_1f":
			case "recieved_21":
				if(isset($this->entities[$data[0]])){
					$this->entities[$data[0]]->move($data[1] >> 5,$data[2] >> 5,$data[3] >> 5);
					$this->trigger("onEntityMove", $this->entities[$data[0]]);
					$this->trigger("onEntityMove_".$this->entities[$data[0]]->eid, $this->entities[$data[0]]);
				}
				break;
			case "recieved_22":
				if(isset($this->entities[$data[0]])){
					$this->entities[$data[0]]->setCoords($data[1] >> 5,$data[2] >> 5,$data[3] >> 5);
					$this->trigger("onEntityMove", $this->entities[$data[0]]);
					$this->trigger("onEntityMove_".$this->entities[$data[0]]->eid, $this->entities[$data[0]]);
				}
				break;
			case "recieved_28":
				if(isset($this->entities[$data[0]])){
					$this->entities[$data[0]]->setMetadata($data[1]);
					$this->trigger("onEntityMetadataChange", $this->entities[$data[0]]);
					$this->trigger("onEntityMetadataChange_".$this->entities[$data[0]]->eid, $this->entities[$data[0]]);
					console("[INTERNAL] EID ".$data[0]." metadata changed", true, true, 3);
				}
				break;
			case "recieved_46";
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
			case "recieved_47":
				console("[DEBUG] Thunderbolt at (".($data[2] >> 5).",".($data[3] >> 5).",".($data[4] >> 5).")", true, true, 2);
				$this->trigger("onThunderbolt", array("eid" => $data[0], "coords" => array("x" => $data[2] >> 5, "y" => $data[3] >> 5, "z" => $data[4] >> 5)));
				break;
			case "recieved_67":
				if($data[0] === 0){
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
			case "recieved_68":
				if($data[0] === 0){
					foreach($data[2] as $i => $slot){
						$this->inventory[$i] = $slot;
						$this->trigger("onInventorySlotChanged", array("slot" => $i, "data" => $slot));
					}
					$this->trigger("onInventoryChanged", $this->getInventory());
					console("[INFO] Recieved complete inventory");
				}
				break;
			case "recieved_82":
				$text = $data[3].PHP_EOL.$data[4].PHP_EOL.$data[5].PHP_EOL.$data[6];
				console("[INTERNAL] Sign at (".$data[0].",".$data[1].",".$data[2].")".PHP_EOL.implode(PHP_EOL."[INTERNAL]\t",explode(PHP_EOL,$text)), true, true, 3);
				$this->trigger("onSignUpdate", array("coords" => array("x" => $data[0], "y" => $data[1], "z" => $data[2]), "text" => $text));
				break;
			case "recieved_fa":
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
			$this->event("onSentPacket", "backgroundHandler", true);
		}
		$this->mapHandler("","start");
		$this->event("recieved_00", "handler", true);
		$this->event("recieved_03", "handler", true);
		$this->event("recieved_04", "handler", true);
		$this->event("recieved_06", "handler", true);
		$this->event("recieved_08", "handler", true);
		$this->event("recieved_09", "handler", true);
		$this->event("recieved_0d", "handler", true);
		$this->event("recieved_13", "handler", true);
		$this->event("recieved_14", "handler", true);
		$this->event("recieved_15", "handler", true);
		$this->event("recieved_17", "handler", true);
		$this->event("recieved_18", "handler", true);
		$this->event("recieved_19", "handler", true);
		$this->event("recieved_1a", "handler", true);
		$this->event("recieved_1d", "handler", true);
		$this->event("recieved_1f", "handler", true);
		$this->event("recieved_21", "handler", true);
		$this->event("recieved_22", "handler", true);
		$this->event("recieved_28", "handler", true);
		$this->event("recieved_32", "mapHandler", true);
		$this->event("recieved_33", "mapHandler", true);
		$this->event("recieved_34", "mapHandler", true);
		$this->event("recieved_35", "mapHandler", true);
		$this->event("recieved_38", "mapHandler", true);
		$this->event("recieved_46", "handler", true);
		$this->event("recieved_47", "handler", true);
		$this->event("recieved_67", "handler", true);
		$this->event("recieved_68", "handler", true);
		$this->event("recieved_82", "handler", true);
		$this->event("recieved_fa", "handler", true);
		$this->event("recieved_c9", "handler", true);
		$this->event("onPluginMessage_REGISTER", "backgroundHandler", true);
		$this->event("onPluginMessage_UNREGISTER", "backgroundHandler", true);
		register_shutdown_function(array($this, "logout"));
		$this->action(50000, '$this->trigger("onTick", $time);');
		$this->action(10000000, 'console("[DEBUG] Memory Usage: ".round((memory_get_usage(true) / 1024) / 1024, 2)." MB", true, true, 2);');
		$this->event("onTick", "handler", true);
		if(isset($this->auth["session_id"])){
			$this->action(300000000, 'Utils::curl_get("https://login.minecraft.net/session?name=".$this->auth["user"]."&session=".$this->auth["session_id"]);');
		}
		
	}
	
	protected function authentication($data, $event){
		switch($event){
			case "recieved_02":
				$hash = $data[0];
				if($hash != "-" and $hash != "+"){
					console("[INFO] Server is Premium (SID: ".$hash.")");
					$this->loginServer($hash);
				}else{
					console("[WARNING] Server is NOT Premium", true, true, 0);
				}
				$this->send("01", array(
					0 => $this->protocol,
					1 => $this->auth["user"],
				));
				$this->event("recieved_01", 'authentication', true);
				$this->process("01");
				break;
			case "recieved_01":
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
				console("[DEBUG] Player EID: ".$this->player->eid, true, true, 2);
				$this->startHandlers();
				$this->trigger("onConnect");
				$this->process();
				break;
		}
	}
	
	public function loginMinecraft($username, $password){
		$secure = true;
		if($secure !== false){
			$proto = "https";
			console("[DEBUG] Using secure HTTPS connection", true, true, 2);
		}else{
			$proto = "http";
		}
			
		$response = Utils::curl_get($proto."://login.minecraft.net/?user=".$username."&password=".$password."&version=".LAUNCHER_VERSION);
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
				if(!is_array($content) or count($content) === 1){
					console("[ERROR] Unknown Login Error: \"".$response."\"", true, true, 0);
					$this->close();
					break;
				}
				$this->auth["user"] = $content[2];
				$this->auth["password"] = $password;
				$this->auth["session_id"] = $content[3];
				console("[INFO] Logged into minecraft.net as ".$this->auth["user"]);
				console("[DEBUG] minecraft.net Session ID: ".$this->auth["session_id"], true, true, 2);
				break;
		}
	}
	
	public function loginServer($hash){
		if($hash == "" or strpos($hash, "&") !== false){
			console("[WARNING] NAME SPOOF DETECTED", true, true, 0);
		}
		if(!isset($this->auth["session_id"]) or $this->auth["session_id"] == ""){
			$this->loginMinecraft($this->auth["user"], $this->auth["password"]);
		}
		$res = Utils::curl_get("http://session.minecraft.net/game/joinserver.jsp?user=".$this->auth["user"]."&sessionId=".$this->auth["session_id"]."&serverId=".$hash); //User check
		if($res != "OK"){
			console("[ERROR] Error in User Check: \"".$res."\"", true, true, 0);
			$this->close();
		}else{
			console("[DEBUG] Sent join server request", true, true, 2);
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
			function_exists("openssl_random_pseudo_bytes") ? openssl_random_pseudo_bytes(16):microtime(true),
			function_exists("mcrypt_create_iv") ? mcrypt_create_iv(16):microtime(true),
			uniqid(microtime(true),true),
			file_exists("/dev/random") ? fread(fopen("/dev/random", "r"),16):microtime(true),
		);
		shuffle($entropy);
		$value = Utils::hexToStr(md5((string) $startEntropy));
		unset($startEntropy);
		foreach($entropy as $c){
			for($i = 0; $i < 64; ++$i){
				$value ^= md5($c.lcg_value().$value.microtime(true).mt_rand(0,mt_getrandmax()), true);
				$value ^= substr(sha1($c.lcg_value().$value.microtime(true).mt_rand(0,mt_getrandmax()), true),0,16);
			}
			
		}
		console("[INTERNAL] 128-bit Simmetric Key generated: 0x".strtoupper(Utils::strToHex($value)), true, true, 3);
		$this->key = $value;
	}
	
	protected function newAuthentication($data, $event){
		switch($event){
			case "recieved_fd":
				require_once("phpseclib/Crypt/RSA.php");
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
					$this->loginServer($hash);
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
			case "recieved_fc":
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
			case "recieved_01":
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
	
	public function connect($user = "", $password = ""){
		if($user != "" and (!isset($this->auth["user"]) or $this->auth["user"] == "")){
			$this->auth = array("user" => $user, "password" => $password);
			if($password != ""){
				$this->loginMinecraft($user, $password);
			}
		}
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
		if($this->spout === true){
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
			case "onRecievedSpoutPacket_9":
				$offset = 0;
				$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
				$offset += 2;
				$text = Utils::readString(substr($data["data"], $offset,$len));
				$offset += $len;
				console("[DEBUG] [Spout] Clipboard: ".$text, true, true, 2);
				$this->trigger("onSpoutClipboard", $text);
				break;
			case "onRecievedSpoutPacket_13":
				$offset = 0;
				$BID = Utils::readInt(substr($data["data"], $offset,4));
				$offset += 4;
				$info = Utils::readShort(substr($data["data"], $offset,2));
				$offset += 2;
				$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
				$offset += 2;
				$name = Utils::readString(substr($data["data"], $offset,$len));
				$offset += $len;
				console("[INTERNAL] [Spout] Got block ".$name." (ID ".$BID." DATA ".$info.")", true, true, 3);
				$this->trigger("onSpoutBlock", array("id" => $BID, "data" => $info, "name" => $name));
				$this->trigger("onSpoutBlock_".$BID, array("data" => $info, "name" => $name));
				break;
			case "onRecievedSpoutPacket_27":
				$offset = 0;
				$cached = Utils::readBool($data["data"]{$offset});
				$offset += 1;
				$url = Utils::readBool($data["data"]{$offset});
				$offset += 1;
				$CRC = Utils::readLong(substr($data["data"], $offset, 8));
				$offset += 8;
				
				$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
				$offset += 2;
				$file = Utils::readString(substr($data["data"], $offset,$len));
				$offset += $len;
				$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
				$offset += 2;
				$plugin = Utils::readString(substr($data["data"], $offset,$len));
				$offset += $len;
				console("[INTERNAL] [Spout] ".$plugin." Cache file: ".$file, true, true, 3);
				$this->trigger("onSpoutCache", array("file" => $file, "plugin" => $plugin));
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
					$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
					$offset += 2;
					$p = Utils::readString(substr($data["data"], $offset,$len));
					$offset += $len;
					$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
					$offset += 2;
					$v = Utils::readString(substr($data["data"], $offset,$len));
					$offset += $len;
					$plugins[$p] = $v;
					console("[DEBUG] [Spout] ".$p." => ".$v, true, true, 2);
					if($p === "Spout"){
						console("[INFO] [Spout] Server authenticated as a v".$v." Spout");
					}
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
					$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
					$offset += 2;
					$key = Utils::readString(substr($data["data"], $offset, $len));
					$offset += $len;
					$value = Utils::readBool($data["data"]{$offset});
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
				$len = Utils::readShort(substr($data["data"], $offset,2)) << 1;
				$offset += 2;
				$name = Utils::readString(substr($data["data"], $offset,$len));
				$offset += $len;
				$death = Utils::readBool($data["data"]{$offset});
				$offset += 1;
				console("[DEBUG] [Spout] Got waypoint ".$name." (".$x.",".$y.",".$z.")".($death === true ? " DEATH":""), true, true, 2);
				$this->trigger("onSpoutWaypoint", array("coords" => array("x" => $x, "y" => $y, "z" => $z), "name" => $name, "death" => $death));
				break;
			case "recieved_c3":
				$packetId = $data[0];
				$version = $data[1];
				$packet = $data[3];
				console("[INTERNAL] [Spout] Recieved packet ".$packetId, true, true, 3);
				$this->trigger("onRecievedSpoutPacket_".$packetId, array("version" => $version, "data" => $packet));
				$this->trigger("onRecievedSpoutPacket", array("id" => $packetId, "version" => $version, "data" => $packet));
				break;
			case "recieved_12":
				if($data[0] === -42){
					$this->spout = true;
					$this->sendSpoutMessage(33,0,array(0 => SPOUT_VERSION));
					console("[INFO] [Spout] Authenticated as a v".SPOUT_VERSION." Spout client");
					$this->event("onRecievedSpoutPacket_9", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_13", "spoutHandler", true);
					$this->event("onRecievedSpoutPacket_27", "spoutHandler", true);
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
				console("[DEBUG] [Spout] Sent Spout verification packet", true, true, 2);
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
		$this->protocol = (int) $protocol;
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
			$p = "[".microtime(true)."] [".($origin === "client" ? "CLIENT->SERVER":"SERVER->CLIENT")."]: ".$this->name[$pid]." (0x$pid) [lenght ".strlen($raw)."]".PHP_EOL;
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
			$p = "[".microtime(true)."] [SERVER->CLIENT]: Error, bad packet id 0x$pid".PHP_EOL;
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
			if($pid === "01"){
				$struct = array();
			}elseif($pid === "09"){
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