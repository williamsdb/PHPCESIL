<?php

	// load the functions
	require('functions.php');

	// set variables
	$cli = (php_sapi_name() == "cli") ? TRUE : FALSE;
	$debug = FALSE;
	$cmds = array('LOAD', 'STORE', 'IN', 'ADD', 'SUBTRACT', 'MULTIPLY', 'DIVIDE', 'JUMP', 'JIZERO', 'JINEG', 'PRINT', 'OUT', 'LINE', 'HALT');
	$cmdsp = array('LOAD', 'STORE', 'ADD', 'SUBTRACT', 'MULTIPLY', 'DIVIDE', 'JUMP', 'JIZERO', 'JINEG', 'PRINT');
	$data = '';
	
	// is there a file to run?
	if ($argc == 2 && $cli){
		$data = load($argv[1], FALSE);
		if (!empty($data)){
			run($data);
			die;
		}else{
			echo 'File '.$argv[1].' not found or not a valid CESIL file.'.PHP_EOL;
			die;
		}
	}

	// interactive mode
	if ($cli){
		echo 'C.E.S.I.L for PHP'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '(c)2019 Neil Thompson '.PHP_EOL;			

		while(TRUE){
			
			echo 'ok>';
			// get and process the command
			$line = ($cli) ? trim(fgets(STDIN)) : trim($_REQUEST['cmd']);
			$cmd = (strpos($line,' ')===FALSE) ? strtolower($line) : strtolower(substr($line,0,strpos($line,' ')));
			list($data,$output) = process_command($line, $cmd, $data);

			// write out any output
			echo $output;
		}
		
	}else{
		
		// start the session
		session_start();
		
		$data = unserialize($_SESSION['data']);
		
		// get and process the command
		$line = ($cli) ? trim(fgets(STDIN)) : trim($_REQUEST['cmd']);
		$cmd = (strpos($line,' ')===FALSE) ? strtolower($line) : strtolower(substr($line,0,strpos($line,' ')));
		list($data,$output) = process_command($line, $cmd, $data);

		$_SESSION['data'] = serialize($data);

		// write out any output
		echo nl2br($output);
	}
?>
