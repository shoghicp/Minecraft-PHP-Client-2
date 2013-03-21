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
	TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

	0. You just DO WHAT THE FUCK YOU WANT TO.


*/

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
ini_set('default_charset', 'utf-8');
define("FILE_PATH", dirname(__FILE__)."/");
set_include_path(get_include_path() . PATH_SEPARATOR . FILE_PATH . PATH_SEPARATOR . FILE_PATH . "/classes/" . PATH_SEPARATOR . FILE_PATH . "/classes/phpseclib/");
ini_set("memory_limit", "512M");
define("CURRENT_PROTOCOL", 60);
define("LAUNCHER_VERSION", 13);
define("SPOUT_VERSION", "1000");