<?php
//� 2026 Martin Peter Madsen
namespace MTM\Shells\Stencils\RouterOs\MacTelnet;

abstract class RouterOs extends Alpha
{
	protected function rosByPassword($ctrlObj, $macObj, $username, $password, $timeout)
	{
		$this->isStr($username, true);
		$this->isStr($password, true);

		$regExs			= array();
		$regExs[]		= "Login:";
		$regEx			= "(".implode("|", $regExs).")";
		
		$strCmd			= "/tool/mac-telnet ".$macObj->getAsString();
		$rData			= trim($ctrlObj->getCmd($strCmd, $regEx, $timeout)->get());

		$regExs			= array();
		$regExs[]		= "Password:";
		$regEx			= "(".implode("|", $regExs).")";
		
		$strCmd			= \MTM\Shells\Factories::getTools()->getRouterOs()->formatUsername($username);
		$rData			= trim($ctrlObj->getCmd($strCmd, $regEx, $timeout)->get());
		
		
		$regExs	= array(
			"\[".$username."\@(.+?)\] \>"						=> "routeros",
			"Do you want to see the software license\?"			=> "routeros",
			"remove it, you will be disconnected\."				=> "routeros"
		);
		$regEx	= "(" . implode("|", array_keys($regExs)) . ")";
			
		$strCmd			= $password;
		$cmdObj			= $ctrlObj->getCmd($strCmd, $regEx, $timeout)->setFindCommand(false); //no command to find as it is a password
		$cmdObj->get();
		$rData			= trim($cmdObj->getReturnData());

		$rValue	= null;
		$rType	= null;
		foreach ($regExs as $regEx => $type) {
			if (preg_match("/".$regEx."/", $rData) == 1) {
				$rValue	= $regEx;
				$rType	= $type;
				break;
			}
		}
		
		if ($rType == "routeros") {
			if ($rValue == "remove it, you will be disconnected\.") {
				//we are the only ones with the information needed to clear the prompt of the question regarding default config
				//if we dont clear it here the Destination function will have a hell of a time figuring out whats going on
				$strCmd	= "n";
				$regEx	= "(" . implode("|", array_keys($regExs)) . ")";
				$cmdObj		= $ctrlObj->getCmd($strCmd, $regEx, $timeout);
				$cmdObj->get();
				$data		= $cmdObj->getReturnData(); //need return data so the prompt is not stripped out
				
				$rType	= null;
				foreach ($regExs as $regEx => $type) {
					if (preg_match("/".$regEx."/", $data) == 1) {
						$rValue	= $regEx;
						$rType	= $type;
						break;
					}
				}
			}
			if ($rValue == "Do you want to see the software license\?") {
				//we are the only ones with the information needed to clear the prompt
				//if we dont clear it here the Destination function will have a hell of a time figuring out whats going on
				$strCmd	= "n";
				$regEx	= "(" . implode("|", array_keys($regExs)) . ")";
				$cmdObj		= $ctrlObj->getCmd($strCmd, $regEx, $timeout);
				$cmdObj->get();
				$data		= $cmdObj->getReturnData(); //need return data so the prompt is not stripped out
				
				$rType	= null;
				foreach ($regExs as $regEx => $type) {
					if (preg_match("/".$regEx."/", $data) == 1) {
						$rValue	= $regEx;
						$rType	= $type;
						break;
					}
				}
			}
			//there can be both a license and a forced password change one after the other
			if ($rValue == "new password\>") {
				//MT forcing password change, deny the change
				$strCmd		= chr(3);
				$regEx	= "(" . implode("|", array_keys($regExs)) . ")";
				$ctrlObj->getCmd($strCmd, $regEx, $timeout)->get();
			}
			
			$childObj	= \MTM\Shells\Factories::getShells()->getRouterOs();
			$childObj->setParent($ctrlObj);
			$ctrlObj->setChild($childObj);
			//init the shell, we are already logged in
			$childObj->initialize();
			
			return $childObj;
		}
		
		if ($rType == "error") {
			throw new \Exception("Connect error: " . $rValue, 88675);
		} else {
			throw new \Exception("Not Handled: " . $rType, 88676);
		}
	}
}