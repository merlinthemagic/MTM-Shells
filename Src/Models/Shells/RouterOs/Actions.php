<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\RouterOs;

class Actions extends Initialization
{
	protected $_shellType="routeros";
	
	public function getCmd($strCmd=null, $regExp=null, $timeout=null)
	{
		if ($this->getChild() === null) {
			if ($this->isInit() === false) {
				$this->initialize();
			}
			if ($this->isTerm() === true) {
				throw new \Exception("Cannot create command, shell is in terminated state", 1111);
			}
			$rObj	= new \MTM\Shells\Models\Commands\RouterOs();
			$rObj->setParent($this)->setCmd($strCmd)->setCommit($this->getCommit());
			if ($regExp === null) {
				$rObj->setDelimitor(preg_quote($this->getRegEx()));
			} else {
				$rObj->setDelimitor($regExp);
			}
			if ($timeout === null) {
				$rObj->setTimeout($this->getDefaultTimeout());
			} else {
				$rObj->setTimeout($timeout);
			}
			return $rObj;
		} else {
			return $this->getChild()->getCmd($strCmd, $regExp, $timeout);
		}
	}
	public function getPipes()
	{
		$pObj	= $this->getParent();
		if ($pObj !== null) {
			return $pObj->getPipes();
		} else {
			//happens if the shell was terminated and someone holds a command obj and executes after
			throw new \Exception("Cannot get pipes shell has no parent", 1111);
		}
	}
}