<?php

require("config.php");
require_once("classes/MinecraftServer.class.php");
include_once("plugin/Record.plugin.php");
include_once("plugin/Follow.plugin.php");
include_once("plugin/ChatCommand.plugin.php");
include_once("plugin/NoHunger.plugin.php");
include_once("plugin/LagOMeter.plugin.php");


file_put_contents("console.log", "");
file_put_contents("packets.log", "");
$M = new MinecraftServer("127.0.0.1", 38);
$M->start("");


