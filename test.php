<?php

require_once("classes/MinecraftClient.class.php");
define("DEBUG", 1); //0 none, 1 messages, 2 all
define("CURRENT_PROTOCOL", 29);
define("ACTION_MODE", 1); //1 => ticks, other by packets. 

file_put_contents("console.log", "");
file_put_contents("packets.log", "");
$M = new MinecraftClient("127.0.0.1");
//$M->activateSpout();
$M->event("onChat", "testHandler");
$M->event("onDeath", "testHandler");
$M->event("onPluginMessage", "testHandler");

$M->connect("BotAyuda", "");

function testHandler($message, $event, $ob){
	global $record, $play;
	switch($event){
		case "onChat":
			$m = explode(" ",$message);
			$c = array_pop($m);
			if($c == "1234"){
				$ob->say("Adios, mundo cruel!");
				$ob->logout();
			}elseif($c == "4321"){
				include_once("plugin/Record.plugin.php");
				$eid = $ob->getPlayer("shoghicp")->getEID();
				$record = new RecordPath($ob, $eid);
			}elseif($c == "4444"){
				if(isset($record)){
					$record->stop();
					$path = $record->getPath();
					$play = new PlayPath($ob, $path);
				}
			}elseif($c == "5555"){
				if(isset($play)){
					$play->stop();
				}
			}
			break;
		case "onPluginMessage":
			console("[INFO] Plugin Message: Channel => ".$data["channel"].", Data: ".$data["data"]);
			break;
		case "onDeath":
			$messages = array(
				"Nooo!!!",
				"Por que??",
				"Solo hice lo que me pedian!",
				"Noooouuu!",			
			);
			$ob->say($messages[mt_rand(0,count($messages)-1)]);
			break;
	}
}

