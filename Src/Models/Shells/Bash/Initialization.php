<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\Bash;

class Initialization extends Processing
{
	protected $_regEx=null;
	protected $_commitChars=null;
	protected $_useSudo=false;
	protected $_spawnPid=null;
	protected $_spawnName=null;
	protected $_phpShPid=null;
	protected $_phpShName=null;
	protected $_basePipes=null;

	public function setSudo($bool)
	{
		$this->_useSudo	= $bool;
	}
	public function getRegEx()
	{
		if ($this->_regEx === null) {
			$this->_regEx	= "[" . uniqid("bash.", true) . "]";
		}
		return $this->_regEx;
	}
	protected function getCommit()
	{
		if ($this->_commitChars === null) {
			$this->_commitChars	= chr(13);
		}
		return $this->_commitChars;
	}
	public function initialize()
	{
		if ($this->_isInit === false) {
			$this->_isInit	= null;
			
			try {
				
				//set the prompt to a known value
				$strCmd		= "PS1=\"".$this->getRegEx()."\"";
				$regEx		= "(".preg_quote($this->getRegEx()).")";
				
				$rTries		= 10;
				while (true) {
					try {
						$this->getCmd($strCmd, $regEx)->get();
						break; //success
					} catch (\Exception $e) {
						switch ($e->getCode()) {
							case 92987:
								if ($rTries > 0) {
									$rTries--;
									//failed to write command to stdIn: Cannot add to a file, error opening for writing: /dev/shm/xxxxxxxxxx/stdIn
									//system is a little busy and the pipe is not yet ready, just wait a little
									usleep(100000);
									break;
								} else {
									//fail
									throw $e;
								}
							default:
								throw $e;
						}
					}
				}
				
				
				
				
				//ssh connections will not inherit the terminal width of the parent.
				$this->setTerminalSize(1000, 1000);
				
				//dont record a history for this session
				$strCmd	= "unset HISTFILE";
				$this->getCmd($strCmd)->get();
	
				if ($this->getParent() === null) {
					//if there is no parent then this is the initial shell
					//get the PIDs back to init, we can use this to kill the shell if everything else fails.
					$strCmd		= "CURID=\$\$; while [[ \$CURID != 1 ]]; do echo $(cat /proc/\$CURID/status | grep \"Name:\" | awk '{ print $2 }'); echo \$CURID; CURID=\$(cat /proc/\$CURID/status | grep \"PPid:\" | awk '{ print $2 }'); done";
					$data		= $this->getCmd($strCmd)->get();
					$procDatas	= explode("\n", $data);
					
					//index 1 is the bash prompt it self
					//index 3 is the python spawn process
					//index 5 depends on if we launched using sudo
					//	if $this->_useSudo === true then 5 is the sudo elevation of python
					//	if $this->_useSudo === false then 5 is the php shell execution
					//index 7 only exists when $this->_useSudo === true, then it is the php shell execution
					if ($this->_useSudo === false) {
						if (count($procDatas) === 6) {
							$this->_spawnName	= trim($procDatas[2]);
							$this->_spawnPid	= trim($procDatas[3]);
							$this->_phpShName	= trim($procDatas[4]);
							$this->_phpShPid	= trim($procDatas[5]);
						} elseif (count($procDatas) === 7) {
							//seen on PHP 8 on raspberry pi
							$this->_spawnName	= trim($procDatas[2]);
							$this->_spawnPid	= trim($procDatas[3]);
							$this->_phpShName	= trim($procDatas[4]);
							$this->_phpShPid	= trim($procDatas[5]);
						} else {
							throw new \Exception("Failed to get process id");
						}
					} else {
						if (count($procDatas) === 8) {
							$this->_spawnName	= trim($procDatas[2]);
							$this->_spawnPid	= trim($procDatas[3]);
							$this->_phpShName	= trim($procDatas[6]);
							$this->_phpShPid	= trim($procDatas[7]);
						} else {
							throw new \Exception("Failed to get process id");
						}
					}

					//TODO: check for inotifywait availabillity and use instead of polling
					
					//if a user is running sudo to get a root shell, we would not be able to kill the PID
					//with the regular user if regular termination failed for some reason
					//because the PID would belong to root
					//we add a fail safe here, open a second process. If exit fails we kill the process

					//since this process may outlive the php session
					//its important that we check that the PID has not been taken over by another process
					$osTool		= \MTM\Utilities\Factories::getSoftware()->getOsTool();
					$psPath		= $osTool->getExecutablePath("ps");
					$killPath	= $osTool->getExecutablePath("kill");
					$loopSleep	= 2;
					$procPid	= getmypid();
					
					
					//start a while loop. When lock is no longer, we clean up if needed
					$strCmd		= "(";
					//must use nohup otherwise we cannot exit the shell until the process finishes
					//nohup is POSIX compliant and will ignore any parent hangup signal
					$strCmd		.= " nohup sh -c '";
					
					//currently the only senario that causes a zombie process is php execution timeout.
					//when that happens register_shutdown_function is not called and the lock file is never removed.
					$phpMax		= ini_get("max_execution_time");
					if ($phpMax > 0) {
						
						//we have a timeout, likely running on a webserver
						//set a max life time, since the PID is from the webserver
						//and when our script ends, the PID likely lives on to serve
						//other requests
						
						$maxCount	= ceil($phpMax / $loopSleep);
						$strCmd		.= " declare -i LOOPCOUNT=0;";
						$strCmd		.= " while";
						$strCmd		.= " [ -f \"" . $this->getPipes()->getLock()->getPathAsString() . "\" ] &&";
						$strCmd		.= " [ -n \"".$procPid."\" -a -e /proc/" . $procPid . " ] &&";
						$strCmd		.= " [ \"\$LOOPCOUNT\" -lt ".$maxCount . " ];";
						$strCmd		.= " do";
						$strCmd		.= " LOOPCOUNT+=1;";
						$strCmd		.= " sleep ".$loopSleep."s;";
						$strCmd		.= " done;";
					} else {
						
						//if we are in CLI mode, the PID will die with us
						//if not stop setting the max_execution_time unlimited :)
						$strCmd		.= " while";
						$strCmd		.= " [ -f \"" . $this->getPipes()->getLock()->getPathAsString() . "\" ] &&";
						$strCmd		.= " [ -n \"".$procPid."\" -a -e /proc/" . $procPid . " ];";
						$strCmd		.= " do";
						$strCmd		.= " sleep ".$loopSleep."s;";
						$strCmd		.= " done;";
					}

					//sleep another cycle, that way processes have a chance to shutdown normally
					//we dont know if the timer is on 0 when the lock file is removed
					//the FS factory seems to destroy the lock before he shell is down
					$strCmd		.= " sleep ".$loopSleep."s ;";

					//check the PHP sub process we spawned is is still alive
					$strCmd		.= " ".$killPath." -0 ".$this->_phpShPid." > /dev/null 2>&1;";
					$strCmd		.= " [ \$(echo \$?) == 0 ] &&";
					$strCmd		.= " PHPNAME=\$( ".$psPath." -p ".$this->_phpShPid." -o comm= ) &&"; //check the PHP sh pid process has the right name
					$strCmd		.= " [ \"\$PHPNAME\" == \"".$this->_phpShName."\" ] &&";
					$strCmd		.= " ".$killPath." -9 " . $this->_phpShPid . ";";//php spawn is still alive, kill it
					
					//check the Python sub process we spawned is is still alive
					$strCmd		.= " ".$killPath." -0 ".$this->_spawnPid." > /dev/null 2>&1;";
					$strCmd		.= " [ \$(echo \$?) == 0 ] &&";
					$strCmd		.= " PYNAME=\$( ".$psPath." -p ".$this->_spawnPid." -o comm= ) &&"; //check the Python spawn pid process has the right name
					$strCmd		.= " [ \"\$PYNAME\" == \"".$this->_spawnName."\" ] &&";
					$strCmd		.= " ".$killPath." -9 " . $this->_spawnPid . ";";//process is alive and confirmed to be ours, -SIGKILL does not work on CentOS7, must be numerical for some reason
					
					//check if the pipe dir is still here
					$strCmd		.= " [ -d \"".$this->getPipes()->getLock()->getDirectory()->getPathAsString()."\" ] &&";
					$strCmd		.= " rm -rf \"".$this->getPipes()->getLock()->getDirectory()->getPathAsString()."\"; ";
					
					//debug log
// 					$strCmd		.= " echo \"Ended bash shell: ".$this->getPipes()->getLock()->getDirectory()->getName()."\" >> ".MTM_FS_TEMP_PATH."mtm-shells.log";

					//we dont want output
					$strCmd		.= " ' & ) > /dev/null 2>&1;";

					$this->getCmd($strCmd)->get();
				}

				//reset the output so we have a clean beginning
				$this->getPipes()->resetStdOut();
				
				//fully initialized
				$this->_isInit	= true;
				
			} catch (\Exception $e) {
				$this->_isInit	= false;
				throw $e;
			}
		}
	}
	protected function getBasePipes()
	{
		if ($this->_basePipes === null) {
			
			if ($this->getParent() === null) {
				
				$osTool		= \MTM\Utilities\Factories::getSoftware()->getOsTool();
				if ($osTool->getType() == "linux") {

					//get exe paths
					$killPath		= $osTool->getExecutablePath("kill");
					$bashPath		= $osTool->getExecutablePath("bash");
					$pythonPath		= $osTool->getExecutablePath("python");
					
					if ($killPath === false) {
						throw new \Exception("Missing Kill application");
					} elseif ($bashPath === false) {
						throw new \Exception("Missing Bash application");
					} elseif ($pythonPath === false) {
						//e.g. Centos8 does not ship with python
						//dnf install python3 -y
						//rm -rf /usr/bin/python; ln -s /usr/bin/python3 /usr/bin/python
						
						$pythonPath		= $osTool->getExecutablePath("python3");
						if ($pythonPath === false) {
							throw new \Exception("Missing Python application");
						}
					}
					
					if ($this->_useSudo === true) {
						$sudoTool		= \MTM\Utilities\Factories::getSoftware()->getSudoTool();
						if ($sudoTool->isEnabled("python") === false) {
							throw new \Exception("Cannot sudo python");
						}
					}

					$fileFact	= \MTM\FS\Factories::getFiles();
					$dirFact	= \MTM\FS\Factories::getDirectories();

					$height		= 1000;
					$width		= 1000;
					//need non temp, since temp are torn down too quickly on destroy
					//files are removed before we have finshed up the termination process
					$dirObj		= $dirFact->getNonTempDirectory();
					$stdIn		= $fileFact->getFile("stdIn", $dirObj);
					$stdOut		= $fileFact->getFile("stdOut", $dirObj);
					$stdErr		= $fileFact->getFile("stdErr", $dirObj);
					$lock		= $fileFact->getFile("procLock", $dirObj);
					
					//will only be triggered on shutdown, so its ok if FS removes
					//the file before we manage to terminate
					$fileFact->setAsTempFile($lock);
					
					//create files
					$stdOut->create();
					$stdErr->create();
					$lock->create();

					//on RHEL 7 the xterm TERM will show a duplicate PS1 command that cannot be removed,
					
					//xterm-mono might work and help us with those pesky colors, need testing
					$term	= "vt100";
					
					//create stdIn pipe
					$strCmd	= "mkfifo ".$stdIn->getPathAsString().";";
					
					//segment off the entire command so we can return from exec() right away
					$strCmd	.= " (";
					
					//stdIn must be bound to a process. We will be writing to it like a file
					//so we use sleep to hold the pipe open
					$strCmd		.= " sleep 1000d > ".$stdIn->getPathAsString()." &";
					
					//segment off the spawned process
					$strCmd		.= " (";
					
					//setup the environment that the spawned python shell will inherit
					$strCmd			.= " export TERM=".$term.";";
					
					//because the sleep process that is holding stdIn open is not bound
					//we need a way to tear it down when we exit
					$strCmd			.= " SLEEP_PID=\$! ;";

					//are we going to spawn a root shell using sudo?
					if ($this->_useSudo === true) {
						$strCmd		.= " sudo";
					}
					
					//setup python to spawn a new bash shell
					$strCmd			.= " " . $pythonPath." -c";
					$strCmd			.= " \"";
					
					//import the python os and pty packages
					$strCmd				.= "import pty, os;";
					
					//set the height of the environment
					$strCmd				.= " os.environ['LINES'] = '".$height."';";
					
					//set the width of the environment
					$strCmd				.= " os.environ['COLUMNS'] = '".$width."';";
										
					//spawn bash as the new process
					$strCmd				.= " pty.spawn(['" . $bashPath . "']);";

					$strCmd			.= "\"";
					
					//instruct the python shell to use our files and stdIn/stdOut/stdErr
					$strCmd			.= " < " . $stdIn->getPathAsString();
					$strCmd			.= " > " . $stdOut->getPathAsString();
					$strCmd			.= " 2> " . $stdErr->getPathAsString() . ";";
					
					//when the python process exits wait a bit before cleaning up, maybe PHP wants one more read of stdOut
					$strCmd			.= " sleep 2s;";
					
					//kill the sleep process holding stdIn open
					$strCmd			.= " \"".$killPath."\" -9 \$SLEEP_PID ;";
					
					//remove the directory we are working in
					$strCmd			.= " rm -rf ".$dirObj->getPathAsString()." &";
					
					//end of segment
					$strCmd		.= " ) &";
					
					//end of segment
					$strCmd	.= " )";
					
					//send all output to null
					$strCmd	.= " > /dev/null 2>&1";

					//give me a shell!
					exec($strCmd, $rData, $status);
					if ($status != 0) {
						throw new \Exception("Failed to excute shell setup: " . $status);
					}
					
					try {
						
						//if the server is busy it could take a bit to setup the shell
						$maxWait	= 30;
						$eTime		= time() + $maxWait;
						$stdInOk	= false;
						while ($eTime > time()) {
							
							$stdErrData	= $stdErr->getContent();
							if ($stdErrData != "") {
								$stdErrData		= trim($stdErrData);
								break;
							}
							
							//simply checking if the file exists is not enough
							//many exceptions are caused by the stdin pipe not being ready to accept data
							$stdInFp		= @fopen($stdIn->getPathAsString(), "an");
							if (is_resource($stdInFp) === true) {
								fclose($stdInFp);
								$stdInOk	= true;
								break;
							} else {
								usleep(10000);
							}
						}
						
						if ($stdErrData != "") {
							
							if (strpos("SyntaxError: Non-ASCII character", $stdErrData) !== false) {
								//need to deal with the environment for python 2
								//cannot figure out how to force utf-8, which is the standard on python3
								throw new \Exception("Failed to create shell. Please consider using python3. Error: '".$stdErrData."'");
							} else {
								throw new \Exception("Failed to create shell. Error: '".$stdErrData."'");
							}
							
						} elseif ($stdInOk !== true) {
							throw new \Exception("stdIn was never created");
						}
						
						$this->_basePipes	= new \MTM\Shells\Models\Shells\ProcessPipe();
						$this->_basePipes->setPipes($stdIn, $stdOut, $stdErr)->setLock($lock);

					} catch (\Exception $e) {
						$dirObj->delete();
						throw $e;
					}

				} else {
					throw new \Exception("Not handled");
				}
				
			} else {
				throw new \Exception("Has parent, cannot be base");
			}
		}
		return $this->_basePipes;
	}
}