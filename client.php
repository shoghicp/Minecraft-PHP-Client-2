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


$versions = array(
	"1.4.3" => 48,
	"1.4.2" => 47,
	"1.4.1" => 47,
	"1.4.0" => 47,
	"1.4" => 47,
	"1.3.2" => 39,
	"1.3.1" => 39,
	"1.3.0" => 39,
	"1.3" => 39,
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

DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
Version 2, December 2004

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

0. You just DO WHAT THE FUCK YOU WANT TO.


INFO;
include("config.php");
require("misc/dependencies.php");
require_once("classes/MinecraftClient.class.php");


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
\tlastlogin => gets username and password from Minecraft lastlogin (default false)
\tlog => log the data from console and packets to file (default false)
\tping => ping (packet 0xFE) a server, and returns info
\tdebug => debug level (none => only errors, info => default, debug => debug info and packets, all => weird data)
\towner => set owner username
\tspout => enables or disables spout (default false)
\tonly-food => only accept food as inventory items (default false)
\taction-mode => Actions in client mode (internal => default, packets => when recieved a packet)
\toptimize => Disables resource-intensive things (default false)

Example:
php {$argv[0]} --server=127.0.0.1 --username=Player --version=1.2.5 --debug=debug

USAGE;
die();
}

$server		= arg("server", "127.0.0.1");
$port		= arg("port", "25565");
$username	= arg("username", "Player");
$password	= arg("password", "");
$version	= arg("version", false);
$protocol	= intval(arg("protocol", CURRENT_PROTOCOL));
$spout		= arg("spout", false);
$owner		= arg("owner", "shoghicp"); // ;)
$only_food	= arg("only-food", false);
define("OPTIMIZE", arg("optimize", false));
define("ACTION_MODE", arg("action-mode", "internal") === "packets" ? 2:1);
define("LOG", arg("log", false) === true ? true:false);
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
if(OPTIMIZE === true){
	$client->disableMap();
}
if(arg("ping", false) != false){
	console("[INFO] Pinging ".$server.":".$port."...");
	$info = $client->ping();
	console("[INFO] Name: ".$info["name"]);
	console("[INFO] Online players: ".$info["online"]);
	console("[INFO] Max players: ".$info["max"]);
	if($client->protocol >= 47){
		console("[INFO] Version: ".$info["version"]);
		console("[INFO] Protocol: ".$info["protocol"]);
	}
	die();
}
require_once("plugin/ChatHandler.plugin.php");
$chatH = new ChatHandler($client, true);
if($spout === true){
	$client->activateSpout();
}
$client->event("onConnect", "clientHandler");
if(arg("lastlogin", false) === true){
	require("plugin/LastLogin.plugin.php");
	$cred = new LastLogin;
	$cred = $cred->get();
	if($cred["username"] != false){
		console("[INFO] Got Credentials from Minecraft lastlogin");
		$client->loginMinecraft($cred["username"], $cred["password"]);
	}

}
$client->connect($username, $password); //NO CODE IS EXECUTED AFTER THIS LINE. BE SURE TO CREATE EVENTS BEFORE THIS LINE


function clientHandler($message, $event, $ob){
	global $food, $only_food, $chat, $lag, $owner, $nav, $follow, $map;
	switch($event){
		case "onChatHandler":
			console("[INFO] [Chat] ".ChatHandler::format($message));
			break;
		case "onLagEnd":
			console("[INFO] [LagOMeter] Lag of ".round($message,2)." seconds ended");
			break;
		case "onConnect":
			if(OPTIMIZE === false){
				require_once("plugin/LagOMeter.plugin.php");
				$lag = new LagOMeter($ob, 10);
				$ob->event("onLagEnd", "clientHandler");
				require_once("plugin/Navigation.plugin.php");
				$nav = new Navigation($ob);
			}
			require_once("plugin/MapPainter.plugin.php");
			$map = new MapPainter($ob);
			require_once("plugin/NoHunger.plugin.php");
			$food = new NoHunger($ob, $only_food);
			require_once("plugin/ChatCommand.plugin.php");
			$chat = new ChatCommand($ob);
			$ob->event("onChatHandler", "clientHandler");
			$chat->addOwner($owner);
			$chat->addOwner("Console");
			$chat->addCommand("die", "clientHandler", true, true);
			$chat->addCommand("say", "clientHandler", true, true);
			$chat->addCommand("follow", "clientHandler", true, true);
			$chat->addCommand("goto", "clientHandler", true, true);
			$chat->addCommand("scan", "clientHandler", true, true);
			$chat->addCommand("map", "clientHandler", true, true);
			$chat->addCommand("coord", "clientHandler");
			$chat->addCommand("dice", "clientHandler");
			break;
		case "onChatCommand_scan":
			$s = min(160, (intval($message["text"]) > 0 ? intval($message["text"]):32));
			$time = time();
			@mkdir("data/map/".$time, 0777, true);
			$ob->say("Starting layer scanning of ".$s."x".$s." blocks", $message["owner"]);
			$map->scan("data/map/".$time."/layer", $s, 4);
			break;
		case "onChatCommand_map":
			$s = min(160, intval($message["text"]) > 0 ? intval($message["text"]):32);
			$ob->say("Starting map surface scanning of ".$s."x".$s." blocks", $message["owner"]);
			@mkdir("data/map/", 0777, true);
			$map->drawMap("data/map/".time().".png", -1, $s, 4);
			break;
		case "onChatCommand_goto":
			$data = explode(" ",$message["text"]);
			$x = (int) array_shift($data);
			$y = (int) array_shift($data);
			$z = (int) array_shift($data);
			$nav->go($x, $y, $z);
			break;
		case "onChatCommand_follow":
			require_once("plugin/Follow.plugin.php");
			if(is_object($follow)){
				$follow->stop();
			}
			if($message["text"] != "stop"){
				$entity = $ob->getPlayer($message["text"]);
				if(is_object($entity)){
					$follow = new FollowPath($ob, $entity->getEID());
					$ob->say("Started following ".$message["text"], $message["owner"]);
				}
			}
			break;
		case "onChatCommand_dice":
			$ob->say("Dice roll: ".mt_rand(1,((intval($message["text"])>0) ? intval($message["text"]):6))."!");
			break;
		case "onChatCommand_coord":
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