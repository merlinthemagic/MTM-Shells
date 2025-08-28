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
			
			if ($this->_isTerm === false && $this->_termActive === false) {
				//expensive, this is a shell call
				$isRun		= \MTM\Utilities\Factories::getSoftware()->getOsTool()->pidRunning($this->_spawnPid);
				if ($isRun === true) {
					return false;
				}
			}
			return true;
		}
	}
	public function terminate()
	{
		if ($this->_isTerm === false && $this->_termActive === false) {
			$this->_termActive	= true;
		
			if ($this->_isInit === true) {
				
				if (is_object($this->getChild()) === true) {
					$this->getChild()->terminate();
				}
				if ($this->getParent() === null) {
					
					//check if the pid is active. e.g MTM-SSH sets up a trap that terminates the shell on exit
					//if the shell is no longer active, the std pipes are gone
					$isRun		= \MTM\Utilities\Factories::getSoftware()->getOsTool()->pidRunning($this->_spawnPid);
					if ($isRun === true) {
						//make sure the last command is dead, give it one sec to exit
						//we really dont care if it exits clean or not we are the base shell
						//and need to shut down. if we wait too long any read error will not be thrown
						$this->issueSigInt(false, 1000);
						
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
						
						//the watcher process is looking for the presense of procLock
						//this is the emergency breake if everything else fails
						//process will be dead within 10 sec
						$this->getPipes()->getLock()->delete();
					}
					$this->_basePipes	= null;
					
				} elseif ($this->isBaseTerm() === false) {
					//make sure the last command is dead, give it the default amount of time
					//we really do need to get the prompt of our parent shell will have trouble
					$this->issueSigInt(false);
					
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
					
					$this->getParent()->setChild(null);
					$this->setParent(null);
					
				}
			}
			$this->_isTerm		= true;
			$this->_termActive	= false;
		}
	}
}