<?php

	function process_command($line, $cmd, $data, $debug=FALSE){

		$output = '';
		$cmds = array('LOAD', 'STORE', 'IN', 'ADD', 'SUBTRACT', 'MULTIPLY', 'DIVIDE', 'JUMP', 'JIZERO', 'JINEG', 'PRINT', 'OUT', 'LINE', 'HALT');
		$cmdsp = array('LOAD', 'STORE', 'ADD', 'SUBTRACT', 'MULTIPLY', 'DIVIDE', 'JUMP', 'JIZERO', 'JINEG', 'PRINT');

		switch ($cmd){
			case 'exit':
				die;
				break;
			case 'load':
				if (strpos($line,' ')===false){
					return array($data, 'You must enter a filename'.PHP_EOL, $debug);
				}else{
					$filename = trim(substr($line, strpos($line,' ')+1));
					return array(load($filename),'', $debug);
				}
				break;
			case 'save':
				if (strpos($line,' ')===false){
					return array($data, 'You must enter a filename'.PHP_EOL, $debug);
				}else{
					$filename = trim(substr($line, strpos($line,' ')+1));
					save($filename, serialize($data));
				}
				break;
			case 'new':
				$i = 0;
				unset($data);
				unset($value);

				while(true){
					echo '>';
					$line = fgets(STDIN);
					
					// have we done?
					if (trim($line)=='exit') break;

					// parse the entered line
					if (strpos($line,'"')!==FALSE){
						$text = trim(substr($line,strpos($line,'"')+1,(strlen($line)-strpos($line,'"'))-3));
						$line = trim(substr($line,0,strpos($line,'"')));
					}else{
						$text = '';
						$line = trim($line);
					}
					$split = split_line($line);

					if ($debug) echo 'Line *'.$line.'*'.PHP_EOL;
					if ($debug) echo 'Text *'.$text.'*'.PHP_EOL;

					// store the line depending on how many tokens have been entered
					switch (count($split)){
						case 0:
							echo 'You don\'t seem to have entered a valid CESIL command.'.PHP_EOL;
							break;
						case 1:
							// check that the command given is valid
							if (in_array($split[0], $cmds)){
								// does the command need a parameter?
								if (in_array($split[0], $cmdsp) && (empty($text))){
									echo $split[0].' requires a parameter.'.PHP_EOL;
									break;
								}else{
									$data[$i]['label'] = '';
									$data[$i]['cmd'] = $split[0];

									if (empty($text)){
										$data[$i]['goto'] = '';
									}else{
										$data[$i]['goto'] = $text;
									}
									$i++;
								}
							}else{
								echo $split[0].' is not a valid CESIL command.'.PHP_EOL;
							}
							break;
						case 2:
							// check that the command given is valid
							if (in_array($split[0], $cmds)){
								$data[$i]['label'] = '';
								$data[$i]['cmd'] = $split[0];
								if (empty($text)){
									$data[$i]['goto'] = $split[1];
								}else{
									$data[$i]['goto'] = $text;
								}
								$i++;						
							}elseif (in_array($split[1], $cmds)){
								// does the command need a parameter?
								if (in_array($split[1], $cmdsp) && (empty($text))){
									echo $split[1].' requires a parameter.'.PHP_EOL;
									break;
								}else{
									$data[$i]['label'] = $split[0];
									$data[$i]['cmd'] = $split[1];

									if (empty($text)){
										$data[$i]['goto'] = $split[2];
									}else{
										$data[$i]['goto'] = $text;
									}
									$i++;
								}
							}else{
								echo $split[1].' is not a valid CESIL command.'.PHP_EOL;
							}
							break;
						case 3:						
							// check that the command given is valid
							if (in_array($split[1], $cmds)){
								// does the command need a parameter?
								if (in_array($split[1], $cmdsp) && (empty($text) && empty($split[2]))){
									echo $split[1].' requires a parameter.'.PHP_EOL;
									break;
								}else{
									$data[$i]['label'] = $split[0];
									$data[$i]['cmd'] = $split[1];

									if (empty($text)){
										$data[$i]['goto'] = $split[2];
									}else{
										$data[$i]['goto'] = $text;
									}
									$i++;
								}
							}else{
								echo trim($split[1]).' is not a valid CESIL command.'.PHP_EOL;
							}
							break;
					}
				}
				break;
			case 'list':
				// reject if no script entered
				if (!isset($data)){
					$output .= 'No CESIL script loaded.'.PHP_EOL;
					break;
				}

				// list the current script
				for ($i = 0; $i<count($data); $i++){
					$output .= substr('00'.$i,(strlen('00'.$i)-3),3)."\t".$data[$i]['label']."\t".$data[$i]['cmd']."\t".$data[$i]['goto'].PHP_EOL;
				}
				break;
			case 'run':
				// reject if no script entered
				if (!isset($data)){
					$output .= 'No CESIL script loaded.'.PHP_EOL;
					break;
				}

				// run the script
				run($data, $debug);
				break;
			case 'dir':
				$dir = opendir('files');
				while(($file = readdir($dir))){
					if ($file != '.' && $file != '..'){
						$output .= $file.PHP_EOL;
					}
				}
				closedir($dir);
				break;
			case 'debug':
				$debug = !$debug;
				break;
			case 'help':
				$output .= 'debug - toggle debugging on or off'.PHP_EOL;
				$output .=  'exit - leave the CESIL interpreter'.PHP_EOL;
				$output .=  'help - displays this list'.PHP_EOL;
				$output .=  'load - load a previously saved file'.PHP_EOL;
				$output .=  'new - create a new program'.PHP_EOL;
				$output .=  'run - execute a loaded CESIL script'.PHP_EOL;
				$output .=  'dir - list all the script available'.PHP_EOL;
				$output .=  'save - save a CESIL file'.PHP_EOL;
				$output .=  'Valid CESIL commands are: ';
				for ($i = 0; $i<count($cmds); $i++){
					if ($i == count($cmds)-1){
						$output .=  $cmds[$i].PHP_EOL;
					}else{
						$output .=  $cmds[$i].', ';
					}
				}
				break;
			default:
				echo 'Command not recognised'.PHP_EOL;
		}
		
		return array($data, $output, $debug);
		
	}

	function run($data, $debug=FALSE){
		// set the accumulator
		$acc = 0;
		$value = [];
		$i = 0;

		// run the script
		while($i < count($data)){

			if ($debug) echo 'Executing line '.$i.' '.$data[$i]['label']."\t".$data[$i]['cmd']."\t".$data[$i]['goto'].PHP_EOL;

			switch (strtoupper($data[$i]['cmd'])){
				case 'LOAD':
					if (is_numeric($data[$i]['goto'])){
						$acc = $data[$i]['goto'];
					}else{
						$acc = $value[$data[$i]['goto']];
					}
					break;
				case 'STORE':
					$value[$data[$i]['goto']] = $acc;
					break;
				case 'IN':
					$in = '';
					while (empty($in) || !is_numeric($in)){
						echo '>';
						$in = trim(fgets(STDIN));
					}
					$acc = $in;
					break;
				case 'ADD':
					if (is_numeric($data[$i]['goto'])){
						$acc = $acc + $data[$i]['goto'];
					}else{
						if (!isset($value[$data[$i]['goto']])){
							echo $data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;
						}else{
							$acc = $acc + $value[$data[$i]['goto']];
						}
					}
					break;
				case 'SUBTRACT':
					if (is_numeric($data[$i]['goto'])){
						$acc = $acc - $data[$i]['goto'];
					}else{
						if (!isset($value[$data[$i]['goto']])){
							echo $data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;
						}else{
							$acc = $acc - $value[$data[$i]['goto']];
						}
					}
					break;
				case 'MULTIPLY':
					if (is_numeric($data[$i]['goto'])){
						$acc = $acc * $data[$i]['goto'];
					}{
						if (!isset($value[$data[$i]['goto']])){
							echo $data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;
						}else{
							$acc = $acc * $value[$data[$i]['goto']];
						}
					}
					break;
				case 'DIVIDE':
					if (is_numeric($data[$i]['goto'])){
						if ($data[$i]['goto']==0){
							echo 'Division by zero.'.PHP_EOL;
							break 2;
						}
						$acc = $acc / $data[$i]['goto'];
					}else{
						if (!isset($value[$data[$i]['goto']])){
							echo $data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;
						}else{
							if ($value[$data[$i]['goto']]==0){
								echo 'Division by zero.'.PHP_EOL;
								break 2;
							}
							$acc = $acc / $value[$data[$i]['goto']];
						}
					}
					break;
				case 'JUMP':
					// check the jump point exists
					$newline = check_jump($data[$i]['goto'],$data);

					if ($newline == 0){
							echo 'Label '.$data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;								
					}else{
						if ($debug) echo 'Jumping to line '.$newline.PHP_EOL;
						$i = $newline-1;
					}
					break;
				case 'JIZERO':
					if ($acc != 0) break;

					// check the jump point exists
					$newline = check_jump($data[$i]['goto'],$data);

					if ($newline == 0){
							echo 'Label '.$data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;								
					}else{
						if ($debug) echo 'Jumping to line '.$newline.PHP_EOL;
						$i = $newline-1;
					}

					break;
				case 'JINEG':
					if ($acc >= 0) break;

					// check the jump point exists
					$newline = check_jump($data[$i]['goto'],$data);

					if ($newline == 0){
							echo 'Label '.$data[$i]['goto'].' hasn\'t been set.'.PHP_EOL;
							break 2;								
					}else{
						if ($debug) echo 'Jumping to line '.$newline.PHP_EOL;
						$i = $newline-1;
					}

					break;
				case 'PRINT':
					// is it a variable?
					if (isset($value[$data[$i]['goto']])){
						echo $value[$data[$i]['goto']].PHP_EOL;
					}else{
						if (substr($data[$i]['goto'],0,1) == '"'){
							echo substr(trim($data[$i]['goto']),1,strlen(trim($data[$i]['goto']))-2);
						}else{
							echo trim($data[$i]['goto']);
						}
					}
					break;
				case 'OUT':
					echo $acc;
					break;
				case 'LINE':
					echo PHP_EOL;
					break;
				case 'HALT':
					break 2;
			}
			$i++;
		}
		
	}

	function load($filename, $interactive=TRUE){

		$data = '';

		if (file_exists('/Users/neilthompson/Development/PHPCESIL/files/'.$filename.'.csl')){
			$data = file_get_contents('files/'.$filename.'.csl', $data);
			$data = unserialize($data);
			
			// check that this is actually a CESIL file
			if (isset($data[0]['label']) && isset($data[0]['cmd']) && isset($data[0]['goto'])){
				if ($interactive) echo 'File '.$filename.' loaded successfully'.PHP_EOL;
				return $data;
			}else{
				if ($interactive) echo 'File '.$filename.' not a valid CESIL file.'.PHP_EOL;
				return '';
			}
		}else{
			echo 'File not loaded'.PHP_EOL;
		}
	}
	
	function save($filename, $data){
		if (file_put_contents('files/'.$filename.'.csl', $data)){
			echo 'File '.$filename.' saved successfully'.PHP_EOL;
		}else{
			echo 'File not saved'.PHP_EOL;
		}
	}

	function check_jump($label,$data){

		for ($i = 0; $i<count($data); $i++){
			if (strtoupper($data[$i]['label'])==strtoupper(trim($label))){
				return $i;
			} 
		}				

		return 0;
	}
	
	function check_goto($split, $text){
		
		// has a parameter been passed?
		if (empty($text) && empty($split)) return '';

		// select which to return
		return (empty($text)) ? $split : $text;
	}

	function split_line($line){
		$arr =  explode(' ', strtoupper($line));
		if (isset($arr[0])) $arr[0] = trim($arr[0]);
		if (isset($arr[1])) $arr[1] = trim($arr[1]);
		if (isset($arr[2])) $arr[2] = trim($arr[2]);
		return $arr;
	}
?>
