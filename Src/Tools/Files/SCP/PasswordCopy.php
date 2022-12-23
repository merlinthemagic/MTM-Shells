<?php
//� 2022 Martin Peter Madsen
namespace MTM\Shells\Tools\Files\SCP;

abstract class PasswordCopy extends Base
{
	protected function bashPasswordCopy($ctrlObj, $srcDir, $dstDir, $ipObj, $userName, $password, $port, $timeout)
	{
		//NOTES:
		//the -T option is needed to correct an issue when connecting to Windows OPENSSH
		//but we cannot set it or connections to linux/routeros etc have issues
		//https://github.com/PowerShell/Win32-OpenSSH/issues/712
		
		//Base destination directory must exist

		$strCmd	= "scp";
		$strCmd	.= " -o NumberOfPasswordPrompts=1";
		$strCmd	.= " -o ConnectTimeout=" .ceil($timeout / 1000). "";
		$strCmd	.= " -o UserKnownHostsFile='/dev/null'";
		$strCmd	.= " -o StrictHostKeyChecking=no";
		$strCmd	.= " -o GSSAPIAuthentication=no";
		$strCmd	.= " -P " . $port;
		$strCmd	.= " -r '".$srcDir->getPathAsString()."'";
		$strCmd	.= " ".$userName."@".$ipObj->getAsString("std", false);
		$strCmd	.= ":'" . $dstDir->getPathAsString()."'";
		
		$regExs	= array(
				"No route to host"									=> "error", //not tested
				"Could not resolve hostname"						=> "error", //not tested
				"Connection reset by peer"							=> "error", //not tested
				"Connection timed out"								=> "error", //ssh: connect to host xx.xx.xx.xx port 22: Connection timed out\nlost connection
				"Permission denied"									=> "error", //tested: Permission denied (password).
				"Connection closed by remote host"					=> "error", //not tested
				"Connection refused"								=> "error", //not tested
				$ipObj->getAsString("std", false) . "'s password:"	=> "pwAuth",
		);
		$regEx	= "(" . implode("|", array_keys($regExs)) . ")";
		try {
			
			$pad	= 5000; //we want the connect error
			$cmdObj	= $ctrlObj->getCmd($strCmd, $regEx, ($timeout + $pad));
			$data	= $cmdObj->get();
		
		} catch (\Exception $e) {
			//can always peak in $cmdObj->getData() to see what was returned
			//this connect is special because bash forms the base for most connects
			throw $e;
		}

		$rValue	= null;
		$rType	= null;
		foreach ($regExs as $regEx => $type) {
			if (preg_match("/".$regEx."/", $data) == 1) {
				$rValue	= $regEx;
				$rType	= $type;
				break;
			}
		}
		if ($rType == "pwAuth") {
			//login, add more regex options
			//cannot include these above as it will match before the pwAuth
			//most specific fail senario must come before success else we match on the exit
			$regExs2	= array(
					"No such file or directory"				=> "error",
					"scp\: failed to upload directory"		=> "error",
					preg_quote($ctrlObj->getRegEx())		=> "completed"
			);
			
			$regExs			= array_merge($regExs, $regExs2);
			$strCmd			= $password;
			$regEx			= "(" . implode("|", array_keys($regExs)) . ")";
			$cmdObj			= $ctrlObj->getCmd($strCmd, $regEx, $timeout)->setTimeout(2592000000); //30 day timeout enough?

			$rValue			= null;
			$rType			= null;
			$lastHash		= "";
			$staticCount	= 0; //how many times in the loop have we received no change in the return
			$maxStatic		= 25; //how many time can the output hash to the same thing before we consider the transfer stalled? EU to US, 10 is too little @ .25 sec loop
			$mSleep			= 250000; //how many micro secs to sleep each time we are "not done" this + maxStatic makes this more or less responsive (and more or less CPU intensive)
			
			$cmdObj->exec();
			while(true) {
				$ctrlObj->read($cmdObj);
				if ($cmdObj->getIsDone() === false) {
					
					usleep($mSleep); //dont redline the CPU
					$curHash		= hash("sha256", $cmdObj->getData()); //file name, transfer rate lots can change
					if ($curHash != $lastHash) {
						$lastHash		= $curHash;
						$staticCount	= 0;
					} elseif ($maxStatic > $staticCount) {
						$staticCount++;
					} else {
						$rValue	= "Transfer stalled, no change in output. Count: ".$staticCount;
						$rType	= "error";
						break;
					}
				} else {
					//completed
					$data	= $cmdObj->getData();
					foreach ($regExs as $regEx => $type) {
						if (preg_match("/".$regEx."/", $data) === 1) {
							$rValue	= $regEx;
							$rType	= $type;
							break;
						}
					}
					break;
				}
			}
		}
		if ($rType == "completed") {
			return true;
		} elseif ($rType == "error") {
			throw new \Exception("Error: '".$rValue."'");
		} else {
			throw new \Exception("Not Handled: '".$rType."'");
		}
	}
}