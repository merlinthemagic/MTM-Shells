<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\RouterOs;

class Termination extends \MTM\Shells\Models\Shells\Base
{
	protected function issueSigInt($throw=true)
	{
		//SIGINT current process and get prompt
		$strCmd		= chr(3);
		$this->getCmd($strCmd)->get($throw);
	}
	public function isBaseTerm()
	{
		//figure out if the base pipes are still there
		return $this->getParent()->isBaseTerm();
	}
	public function terminate()
	{
		if ($this->_isInit === true) {
			$this->_isInit	= null;
			if (is_object($this->getChild()) === true) {
				$this->getChild()->terminate();
			}

			if ($this->isBaseTerm() === false) {
				
				//make sure the last command is dead
				$this->issueSigInt(false);
	
				$cmdObj		= $this->getCmd();
				$strCmd		= "/quit";
				$regEx		= false;
				$timeout	= 0;
				if ($this->getParent() !== null) {
					$regEx		= "(".preg_quote($this->getParent()->getRegEx()).")";
					$timeout	= $cmdObj->getTimeout();
				}
				$cmdObj->setCmd($strCmd)->setDelimitor($regEx)->setTimeout($timeout);
				$cmdObj->get(false);
				
				if ($this->getParent() !== null) {
					$this->getParent()->setChild(null);
					$this->setParent(null);
				}
			}
			$this->_isInit	= false;
		}
	}
}