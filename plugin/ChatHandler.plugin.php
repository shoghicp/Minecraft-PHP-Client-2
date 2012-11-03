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


class ChatHandler{
	protected $client, $event, $callback;
	var $blacklist;
	function __construct($client, $only = true, $blacklist = array()){
		$this->client = $client;
		if($only == true){
			$this->client->deleteEvent("onChat");
		}
		$this->blacklist = $blacklist;
		$this->event = $this->client->event("onChat", "handler", $this);
		console("[INFO] [ChatHandler] Loaded");
	}
	
	public function stop(){
		$this->client->deleteEvent("onChat", $this->event);
	}
	
	public function handler($data, $event, $ob){
		$mess = str_replace(array("[Server]", "<Server>", "[Broadcast]", "<Broadcast>"), array("<Console>", "<Console>", "<Console>", "<Console>"), preg_replace("/\xa7[a-z0-9]/", "", $data));
		$message = "";
		$type = "global";
		$group = "";
		$world = "";
		$owner = "";
		$receptor = "";
		if(preg_match("/([a-zA-Z0-9_~]{2,16})\ whispers ([a-zA-Z0-9_]{2,16})/",$mess,$username) > 0){ //Default MP
			$owner = $username[1];
			$type = "private";
			$receptor = $username[2];
			$message = ltrim(substr($mess, strpos($mess, $username[0]) + strlen($username[0])));
		}elseif(preg_match("/\[([a-zA-Z0-9_~]{2,16}) \-> ([a-zA-Z0-9_]{2,16})\]/",$mess,$username) > 0){ //Essentials MP
			$owner = $username[1];
			if($owner != "me" and $owner != "yo"){
				$type = "private";
			}
			$receptor = $username[2];
			$message = ltrim(substr($mess, strpos($mess, $username[0]) + strlen($username[0])));
		}elseif(preg_match("#([\(<\{][ ]{0,1}|)([a-zA-Z0-9_]{2,16})(:|[ ]{0,1}[\)>\}])#", $mess, $username) > 0){ //Catch them all!!
			if(preg_match("#[\[]([a-zA-Z0-9\-_ |]*)[\]]#", $mess, $zone) > 0){
				$zone = explode("|", $zone[1]);
				if(count($zone) > 1){
					$world = $zone[0];
					$group = $zone[1];
				}else{
					$group = $zone[0];
				}
			}
			$owner = trim($username[2]);
			$message = ltrim(substr($mess, strpos($mess, $username[0]) + strlen($username[0])));
		}elseif(preg_match("/([a-zA-Z0-9_]{2,16}) ([a-z]*) the game/",$mess,$username) > 0){
			$owner = $username[1];
			$type = $username[2] == "joined" ? "join":"left";
		}elseif(preg_match("/([a-zA-Z0-9_]{2,16}) (drowned|hit the ground too hard|was (slain|shot|killed|fireballed|pummeled) by |fell out of the world|tried to swim in lava|went up in flames|burned to death|suffocated in a wall|was pricked to death|starved to death|died|withered away)([a-zA-Z0-9_]{0,16})/",$mess,$username) > 0){
			$owner = $username[1];
			$type = "kill";
			$receptor = $username[4];
			$message = "was killed".($receptor != "" ? " by ".$receptor:"");
		}else{
			$message = trim($mess);
			if($mess == ""){
				return array("owner" => "", "receptor" => "", "group" => "", "world" => "", "message" => "", "type" => "");
			}
		}
		$info = array("owner" => $owner, "receptor" => $receptor, "group" => $group, "world" => $world, "message" => $message, "type" => $type, "raw" => $mess);
		if($event != "internal"){
			console("[INTERNAL] [ChatHandler] ".ChatHandler::format($info), true, true, 3);
			if(isset($this->blacklist[$owner])){
				$this->client->trigger("onChatHandler_blacklisted", $info);
			}else{
				$this->client->trigger("onChatHandler", $info);
			}
		}else{
			return $info;
		}
	}
	
	public static function format($info){
		if(!is_array($info)){
			$info = @ChatHandler::handler($info, "internal", null);
		}
		if($info["type"] == "private" or $info["receptor"] != ""){
			return "[".$info["owner"]." -> ".$info["receptor"]."] ".$info["message"];
		}elseif($info["type"] == "join"){
			return "<".$info["owner"]."> joined the game";
		}elseif($info["type"] == "left"){
			return "<".$info["owner"]."> left the game";
		}else{		
			return ($info["world"] != "" ? "[".$info["world"]."|".$info["group"]."] ":($info["group"] != "" ? "[".$info["group"]."] ":"")).($info["owner"] != "" ? "<".$info["owner"].($info["type"] == "private" ? " -> me":"")."> ":"").$info["message"];
		}
	}


}