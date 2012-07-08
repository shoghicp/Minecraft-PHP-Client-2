<?php

function console($message, $EOL = true, $log = true, $level = 1){
	//global $path;
	if(DEBUG >= $level){
		$message .= $EOL == true ? PHP_EOL:"";
		$message = date("H:i:s"). " ". $message;
		if($log and LOG == true){
			logg($message, "console", false);
		}
	
		echo $message;
	}
}

function logg($message, $name, $EOL = true, $level = 2){
	if(DEBUG >= $level and LOG == true){
		$message .= $EOL == true ? PHP_EOL:"";
		file_put_contents(FILE_PATH."/".$name.".log", $message, FILE_APPEND);
	}
}

function hexdump($data, $htmloutput = true, $uppercase = false, $return = false)
{
    // Init
    $hexi   = '';
    $ascii  = '';
    $dump   = ($htmloutput === true) ? '<pre>' : '';
    $offset = 0;
    $len    = strlen($data);
 
    // Upper or lower case hexadecimal
    $x = ($uppercase === false) ? 'x' : 'X';
 
    // Iterate string
    for ($i = $j = 0; $i < $len; $i++)
    {
        // Convert to hexidecimal
        $hexi .= sprintf("%02$x ", ord($data[$i]));
 
        // Replace non-viewable bytes with '.'
        if (ord($data[$i]) >= 32 and ord($data[$i]) < 0x80) {
            $ascii .= ($htmloutput === true) ?
                            htmlentities($data[$i]) :
                            $data[$i];
        } else {
            $ascii .= '.';
        }
 
        // Add extra column spacing
        if ($j === 7) {
            $hexi  .= ' ';
            $ascii .= ' ';
        }
 
        // Add row
        if (++$j === 16 || $i === $len - 1) {
            // Join the hexi / ascii output
            $dump .= sprintf("%04$x  %-49s  %s", $offset, $hexi, $ascii);
 
            // Reset vars
            $hexi   = $ascii = '';
            $offset += 16;
            $j      = 0;
 
            // Add newline
            if ($i !== $len - 1) {
                $dump .= "\n";
            }
        }
    }
 
    // Finish dump
    $dump .= $htmloutput === true ?
                '</pre>' :
                '';
    $dump .= "\n";
	
	$dump = preg_replace("/[^[:print:]\\r\\n]/", ".", $dump);
 
    // Output method
    if ($return === false) {
        echo $dump;
    } else {
        return $dump;
    }
}