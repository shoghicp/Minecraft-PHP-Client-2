<?php


class ChatHandler{
	protected $client, $event, $callback;
	function __construct($client, $only = true){
		$this->client = $client;
		if($only == true){
			$this->client->deleteEvent("onChat");
		}
		$this->event = $this->client->event("onChat", "handler", $this);
		console("[INFO] [ChatHandler] Started");
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
				$world = array_shift($group);
			}
			$group = implode($group);
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
			$type = "private";
			array_shift($message);
			array_shift($message);
			array_shift($message);
			$message = implode(" ", $message);
		}else{
			$message = implode(" ", $message);
		}
		$info = array("owner" => $owner, "group" => $group, "world" => $world, "message" => $message, "type" => $type);
		console("[INFO] [ChatHandler] ".ChatHandler::format($info));
		$this->client->trigger("onChatHandler", $info);
	}
	
	public static function format($info){
		return ($info["group"] != "" ? "[".$info["group"]."] ":"").($info["owner"] != "" ? "<".$info["owner"].($info["type"] == "private" ? " -> me":"")."> ":"").$info["message"];
	}


}