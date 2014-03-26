<?php
global $argv; 

class lucid_test
{
	public function __construct($config=array())
	{
		$this->config = $config;
		$this->config['hr'] = (isset($_SERVER['argc']))?"----------------------------\n":'<hr />';
	}
	
	public function process()
	{
		$this->parse_parameters();
		$this->html_header();
		$files  = $this->find_files();
		list($total_files, $errors) = $this->run_tests($files);
		$this->report_results($total_files,$errors);
		$this->html_footer();
	}
	
	private function html_header()
	{
		if(!isset($_SERVER['argc']))
		{
			echo('<html><head><style>body{font-family:Ubuntu Mono, Monaco, Consolas, Monospace;background-color: black;color: white;}</style></head><body>');
			echo('<pre>');
		}
		echo("Beginning test run:\n");
		echo($this->config['hr']);
	}
	
	private function html_footer()
	{
		if(!isset($_SERVER['argc']))
		{
			echo('</pre>');
			echo('</body></html>');
		}
		exit();
	}

	private function parse_parameters()
	{
		global $argv; 
		foreach($_REQUEST as $name=>$value)
		{
			$this->config[$name] = $value;
		}
		for($i=1;$i<count($argv);$i++)
		{
			list($name,$value) = explode('=',$argv[$i]);
			$this->config[$name] = $value;
			$_REQUEST[$name] = $value;
		}
	}
	
	private function find_files()
	{
		# first, get a list of all of the files
		# we need the files as an array so that we can sort them
		$files = array();
		if ($handle = opendir($this->config['test_path']))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if($entry !== '.' and $entry != '..')
				{
					$pathinfo = pathinfo($this->config['test_path'].$entry);
					if(isset($pathinfo['extension']) and $pathinfo['extension'] == 'php')
					{
						$entry_parts = explode('__',$entry);
						if(count($entry_parts) >= 3)
						{
							$code = $entry_parts[0];
							$type = $entry_parts[1];
							$descriptor = (is_array($entry_parts[2]))?implode('__',$entry_parts[2]):$entry_parts[2];
							

							if (isset($this->config['include-only-types']))
							{
								if(in_array($type,$this->config['include-only-types']))
								{
									$files[] = $entry;
								}
							}
							else if(isset($this->config['include-only-names']))
							{
								$include = false;
								foreach($this->config['include-only-names'] as $include_name)
								{
									if(strstr($descriptor,$include_name) !== false)
									{
										$include = true;
									}
								}
								if($include)
								{
									$files[] = $entry;
								}
							}
							else if(isset($this->config['exclude-types']))
							{
								if(!in_array($type,$this->config['exclude-types']))
								{
									$files[] = $entry;
								}
							}
							else if(isset($this->config['exclude-names']))
							{
								$include = true;
								foreach($this->config['exclude-names'] as $exclude_name)
								{
									if(strstr($descriptor,$exclude_name) !== false)
									{
										$include = false;
									}
								}
								if($include)
								{
									$files[] = $entry;
								}
							}
							else
							{
								$files[] = $entry;
							}
						}
					}
				}
			}
		}
		sort($files);
		return $files;
	}
	
	private function run_tests($files)
	{
		$errors = array();
		$total_files = 0;
		foreach($files as $file)
		{
			$pathinfo = pathinfo($this->config['test_path'].'/'.$file);

			$total_files++;
			$func = 'test_'.$pathinfo['filename'];
			include($this->config['test_path'].'/'.$file);
			if(function_exists($func))
			{
				$results = $func();
				
				$passes = false; 
				
				$passes = array_shift($results);
				
				if(!($passes === true) and count($results) > 0)
				{
					$errors[] =  array(
						'filename'=>$pathinfo['filename'],
						'msg'=>array_shift($results),
					);
				}
				
				printf('[ %30s ]: '.(($passes)?'PASS':'FAIL')."\n",$pathinfo['filename']);
				
			}
			else
			{
				printf("[ %30s ]: FAIL\n",$pathinfo['filename']);
					
				$errors[] = array(
					'filename'=>$pathinfo['filename'],
					'msg'=>'Could not find test function test_'.$pathinfo['filename'],
				);
			}
		}
		
		if($total_files == 0)
		{
			echo("WARNING: no tests found\n");
		}
		return array($total_files,$errors);
	}
	
	private function report_results($total_files,$errors)
	{
		echo($this->config['hr']);
		echo("Run complete, ".($total_files - count($errors))." PASS, ".count($errors)." FAIL\n");
		echo("Result: ".((count($errors) == 0)?'SUCCESS':'FAIL')."\n");
		echo("\n");
		foreach($errors as $error)
		{
			printf("[ %30s ]: %s\n",$error['filename'],$error['msg']);
		}
	}
}

# this file is itself runable
if(isset($argv) and isset($argv[0]))
{
	$pathinfo = pathinfo($argv[0]);
	if($pathinfo['filename'] == 'lucid_test')
	{
		$tests = new lucid_test();
		$tests->process();
	}
}

?>