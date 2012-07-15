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
		$message = explode(" ", str_replace(array("[Server]", "<Server>", "[Broadcast]", "<Broadcast>"), array("<Console>", "<Console>", "<Console>", "<Console>"), preg_replace("/\xa7[a-z0-9]/", "", $data)));
		$type = "global";
		$group = "";
		$world = "";
		$owner = "";
		if(preg_match("/<([a-zA-Z0-9_]{2,16})>/",$message[0],$username) > 0){ //Default chat format
			$owner = $username[1];
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and preg_match("/\[(.*)\]([a-zA-Z0-9_]{2,16}):/",$message[0].$message[1],$username) > 0){ //Essentials
			$owner = $username[2];
			$group = explode("|", $username[1]);
			if(count($group) > 1){
				$world = array_shift($group);
			}
			$group = implode($group);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and isset($message[2]) and preg_match("/\[(.*)\] .* ([a-zA-Z0-9_]{2,16}):/",$message[0]." ".$message[1]." ".$message[2],$username) > 0){ //Essentials
			$owner = $username[2];
			$group = explode("|", $username[1]);
			if(count($group) > 1){
				$world = array_pop($group);
			}
			$group = implode($group);
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and isset($message[2]) and isset($message[3]) and preg_match("/\[(.*)\] .* ([a-zA-Z0-9_]{2,16}):/",$message[0]." ".$message[1].$message[2]." ".$message[3],$username) > 0){ //Essentials
			$owner = $username[2];
			$group = explode("|", $username[1]);
			if(count($group) > 1){
				$world = array_pop($group);
			}
			$group = implode($group);
			array_shift($message);
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and isset($message[2]) and preg_match("/(.*)\[(.*)\]([a-zA-Z0-9_]{2,16}):/",$message[0].$message[1].$message[2],$username) > 0){ //Essentials
			$world = $username[1];
			$owner = $username[3];
			$group = $username[2];
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and isset($message[2]) and preg_match("/\[(.*)\] (.*) ([a-zA-Z0-9_]{2,16}):/",$message[0]." ".$message[1]." ".$message[2],$username) > 0){ //Essentials
			$world = $username[1];
			$owner = $username[3];
			$group = $username[2];
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(preg_match("/([a-zA-Z0-9_]{2,16}):/",$message[0],$username) > 0){ //Essentials
			$owner = $username[1];
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and preg_match("/([a-zA-Z0-9_]{2,16}):/",$message[1],$username) > 0 and $message[1] != "online:"){ //Essentials, iChat
			$owner = $username[1];
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and preg_match("/([a-zA-Z0-9_]{2,16})\whispers/",$message[0].$message[1],$username) > 0){ //Essentials MP
			$owner = $username[1];
			$type = "private";
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and preg_match("/\[([a-zA-Z0-9_]{2,16})\->/",$message[0].$message[1],$username) > 0){ //Essentials MP
			$owner = $username[1];
			if($owner != "me" and $owner != "yo"){
				$type = "private";
			}
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}elseif(isset($message[1]) and isset($message[2]) and isset($message[3]) and preg_match("/([a-zA-Z0-9_]{2,16}) joined the game/",$message[0]." ".$message[1]." ".$message[2]." ".$message[3],$username) > 0){ //Essentials MP
			$owner = $username[1];
			$type = "join";
			array_shift($message);
			$message = implode(" ", $message);
		}else{
			$message = implode(" ", $message);
		}
		$info = array("owner" => $owner, "group" => $group, "world" => $world, "message" => $message, "type" => $type);
		console("[DEBUG] [ChatHandler] ".ChatHandler::format($info), true, true, 2);
		if(isset($this->blacklist[$owner])){
			return;
		}
		$this->client->trigger("onChatHandler", $info);
	}
	
	public static function format($info){
		return ($info["group"] != "" ? "[".$info["group"]."] ":"").($info["owner"] != "" ? "<".$info["owner"].($info["type"] == "private" ? " -> me":"")."> ":"").$info["message"];
	}


}