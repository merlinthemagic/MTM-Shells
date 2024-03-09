<?php
//� 2024 Martin Peter Madsen
namespace MTM\Shells\Stencils\Linux\Su;

abstract class Bash extends Alpha
{
	protected function bashByPassword($ctrlObj, $username, $password)
	{
		$this->isStr($username, true);
		$this->isStr($password, true);
		
		$strCmd			= "su ".$username;
		
		$regExs			= array();
		$regExs[]		= "Password:";
		$regExs[]		= "user ".$username." does not exist";
		$regExs[]		= "This account is currently not available";
		$regExs[]		= $username."@";
		
		$regEx			= "(".implode("|", $regExs).")";
		$rData			= trim($ctrlObj->getCmd($strCmd, $regEx)->get());
		
		if ($rData === "Password:") {
			
			$strCmd			= $password;
			
			$regExs			= array();
			$regExs[]		= $username."@";
			$regExs[]		= "Authentication failure";

			$regEx			= "(".implode("|", $regExs).")";
			$rData			= trim($ctrlObj->getCmd($strCmd, $regEx)->get());
			
			if (strpos($rData, $username."@") !== false) {
				return $this->getBashChildShell($ctrlObj);
			} elseif (strpos($rData, "Authentication failure") !== false) {
				throw new \Exception("Failed to switch user. Invalid password", 1111);
			} else {
				throw new \Exception("Failed to switch user. Return data: '".$rData."'", 1111);
			}
			
		} elseif (strpos($rData, $username."@") !== false) {
			//no password required
			return $this->getBashChildShell($ctrlObj);
		}
		throw new \Exception("Failed to switch user. Return data: '".$rData."'", 1111);				
	}
	protected function getBashChildShell($ctrlObj)
	{
		$childObj	= \MTM\Shells\Factories::getShells()->getBash(false);
		$childObj->setParent($ctrlObj);
		$ctrlObj->setChild($childObj);
		$childObj->initialize();
		return $childObj;
	}
}