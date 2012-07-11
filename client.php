<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
define("FILE_PATH", dirname(__FILE__)."/");
set_include_path(get_include_path() . PATH_SEPARATOR . FILE_PATH);
define("MAX_BUFFER_BYTES", 1024 * 1024 * 4); //4MB max of buffer
define("MIN_BUFFER_BYTES", 64);
ini_set("memory_limit", "512M");
define("HALF_BUFFER_BYTES", MAX_BUFFER_BYTES / 2);

require_once("classes/MinecraftClient.class.php");

$versions = array(
	"1.3" => 38,
	"1.2.5" => 29,
	"1.2.4" => 29,
	"1.2.3" => 28,
	"1.2.2" => 28,
	"1.2.1" => 28,
	"1.2.0" => 28,
	"1.2" => 28,
	"1.1.0" => 23,
	"1.1" => 23,
	"1.0.1" => 22,
	"1.0.0" => 22,
	"1.0" => 22,
);


echo <<<INFO

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
         
         
\tby @shoghicp

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


INFO;
require("misc/dependencies.php");

if(arg("help", false) !== false){
echo <<<USAGE
Usage: php {$argv[0]} [parameters]

Parameters:
\tserver => Server to connect (default 127.0.0.1)
\tport => Port to connect (default 25565)
\tversion => Version of server
\tprotocol => Protocol version of minecraft, supersedes --version
\tusername => username to use in server and minecraft.net if PREMIUM (default Player)
\tpassword => password to use in minecraft.net, if PREMIUM
\tlog => log the data from console and packets to file (default true)
\tping => ping (packet 0xFE) a server, and returns info
\tdebug => debug level (none => only errors, info => default, debug => debug info and packets, all => weird data)
\towner => set owner username
\tspout => enables or disables spout (default false)
\tonly-food => only accept food as inventory items (default false)
\taction-mode => Actions in client mode (internal => default, packets => when recieved a packet)

Example:
php {$argv[0]} --server=127.0.0.1 --username=Player --version=1.2.5 --debug=1

USAGE;
die();
}

define("CURRENT_PROTOCOL", 29);
define("LAUNCHER_VERSION", 13);
define("SPOUT_VERSION", "1000");
$server		= arg("server", "127.0.0.1");
$port		= arg("port", "25565");
$username	= arg("username", "Player");
$password	= arg("password", "");
$version	= arg("version", false);
$protocol	= intval(arg("protocol", CURRENT_PROTOCOL));
$spout		= arg("spout", false);
$owner		= arg("owner", "shoghicp"); // ;)
$only_food	= arg("only-food", false);
define("ACTION_MODE", arg("action-mode", "internal") === "packets" ? 2:1);
define("LOG", arg("log", true) === true ? true:false);
$debug = trim(strtolower(arg("debug", "info")));
if(strlen(str_replace(array("info", "all", "debug","none"), "", $debug)) != 0){
$debug = "info";
}
$debug_level = array(
	"none" => 0,
	"info" => 1,
	"debug" => 2,
	"all" => 3,
);
file_put_contents(FILE_PATH."packets.log", "");
define("DEBUG", $debug_level[$debug]);

if($version !== false){
	if(isset($versions[$version])){
		$protocol = $versions[$version];
	}else{
		console("[ERROR] Got invalid version ".$version,true,true,0);
		die();
	}
}

if(!file_exists("pstruct/".$protocol.".php")){
	console("[ERROR] Got invalid protocol ".$protocol,true,true,0);
	die();
}

$client = new MinecraftClient($server, $protocol, $port);
if(arg("ping", false) != false){
	console("[INFO] Pinging ".$server.":".$port."...");
	$info = $client->ping();
	console("[INFO] Name: ".$info[0]);
	console("[INFO] Online players: ".$info[1]);
	console("[INFO] Max players: ".$info[2]);
	die();
}
require_once("plugin/ChatHandler.plugin.php");
$chatH = new ChatHandler($client, true);
if($spout === true){
	$client->activateSpout();
}
$client->event("onConnect", "clientHandler");
$client->connect($username, $password);


function clientHandler($message, $event, $ob){
	global $food, $only_food, $chat, $lag, $owner, $nav;
	switch($event){
		case "onChatHandler":
			console("[INFO] [Chat] ".ChatHandler::format($message));
			break;
		case "onLagEnd":
			console("[INFO] [LagOMeter] Lag of ".round($message,2)." seconds ended");
			break;
		case "onConnect":
			require_once("plugin/LagOMeter.plugin.php");
			$lag = new LagOMeter($ob, 4);
			$ob->event("onLagEnd", "clientHandler");
			require_once("plugin/NoHunger.plugin.php");
			$food = new NoHunger($ob, $only_food);
			require_once("plugin/ChatCommand.plugin.php");
			$chat = new ChatCommand($ob);
			require_once("plugin/Navigation.plugin.php");
			$nav = new Navigation($ob);
			$ob->event("onChatHandler", "clientHandler");
			$chat->addOwner($owner);
			$chat->addCommand("die", "clientHandler", true, true);
			$chat->addCommand("say", "clientHandler", true, true);
			$chat->addCommand("coord", "clientHandler");
			$chat->addCommand("dice", "clientHandler");
			break;
		case "onChatCommand_dice":
			$ob->say("Dice roll: ".mt_rand(1,((intval($message["text"])>0) ? intval($message["text"]):6))."!");
			break;
		case 'onChatCommand_coord':
			$p = $ob->getPlayer($message["owner"]);
			if(is_object($p)){
				$coords = $p->getPosition();
				$ob->say("Your latest known position: x = ".$coords["x"].", y = ".$coords["y"].", z = ".$coords["z"], $message["owner"]);
			}
			break;
		case "onChatCommand_die":
			$ob->say("Goodbye, cruel world!");
			$ob->logout();
			break;
		case "onChatCommand_say":
			$ob->say($message["text"]);
			break;
			
	}

}