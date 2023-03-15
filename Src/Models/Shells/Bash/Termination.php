<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\Bash;

class Termination extends \MTM\Shells\Models\Shells\Base
{
	protected function issueSigInt($throw=true, $timeout=null)
	{
		//http://ascii-table.com/control-chars.php
		//check out: https://unix.stackexchange.com/questions/105295/what-numeric-key-codes-do-i-need-to-send-for-the-magic-sysrq-functionality-in-a
		//we are sending chars to a pipe, ping responds to the sigInt, but a php script will not exit
		//SIGINT current process and get prompt, stty -a to view
		$strCmd		= chr(3);
		$cmdObj		= $this->getCmd($strCmd);
		if ($timeout !== null) {
			$cmdObj->setTimeout($timeout);
		}
		$cmdObj->get($throw);
	}
	protected function issueSigQuit($throw=true, $timeout=null)
	{
		//SIGQUIT current process and get prompt, stty -a to view
		$strCmd		= chr(28);
		$cmdObj		= $this->getCmd($strCmd);
		if ($timeout !== null) {
			$cmdObj->setTimeout($timeout);
		}
		$cmdObj->get($throw);
	}
	public function isBaseTerm()
	{
		//figure out if the base pipes are still there
		if ($this->getParent() !== null) {
			return $this->getParent()->isBaseTerm();
		} else {
			$exists	= $this->getBasePipes()->getStdOut()->getExists();
			if ($exists === true) {
				$exists	= $this->getBasePipes()->getStdIn()->getExists();
				if ($exists === true) {
					return false;
				}
			}
			return true;
		}
	}
	public function terminate()
	{
		if ($this->_isInit === true) {
			$this->_isInit	= null;
			if (is_object($this->getChild()) === true) {
				$this->getChild()->terminate();
			}
			if ($this->isBaseTerm() === false) {
				
				if ($this->getParent() !== null) {
					//make sure the last command is dead, give it the default amount of time
					//we really do need to get the prompt of our parent shell will have trouble
					$this->issueSigInt(false);
				} else {
					//make sure the last command is dead, give it one sec to exit
					//we really dont care if it exits clean or not we are the base shell
					//and need to shut down. if we wait too long any read error will not be thrown
					$this->issueSigInt(false, 1000);
				}
	
				//exit the shell
				$cmdObj		= $this->getCmd();
				$strCmd		= "exit";
				$regEx		= false;
				$timeout	= 0;
				if ($this->getParent() !== null) {
					$regEx		= "(".preg_quote($this->getParent()->getRegEx()).")";
					$timeout	= $cmdObj->getTimeout();
				}
				$cmdObj->setCmd($strCmd)->setDelimitor($regEx)->setTimeout($timeout);
				$cmdObj->get(false);
	
				if ($this->getParent() === null) {
					//the watcher process is looking for the presense of procLock
					//this is the emergency breake if everything else fails
					//process will be dead within 10 sec
					$this->getPipes()->getLock()->delete();
					$this->_basePipes	= null;
				} else {
					$this->getParent()->setChild(null);
					$this->setParent(null);
				}
			}
			
			$this->_isInit	= false;
		}
	}
}