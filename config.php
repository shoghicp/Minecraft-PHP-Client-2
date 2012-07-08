<?php


define("DEBUG", 1); //0 none, 1 messages, 2 all. Anything more that 1 makes the client slow
define("LOG", true);
define("FILE_PATH", dirname(__FILE__));
define("CURRENT_PROTOCOL", 29);
define("LAUNCHER_VERSION", 13);
define("SPOUT_VERSION", "1000");
define("ACTION_MODE", 1); //1 => ticks, other by packets. 

define("MAX_BUFFER_BYTES", 1024 * 1024 * 4); //4MB max of buffer
define("MIN_BUFFER_BYTES", 64);
ini_set("memory_limit", "512M");

define("HALF_BUFFER_BYTES", MAX_BUFFER_BYTES / 2);



?>