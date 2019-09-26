<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\RouterOs;

class Actions extends Initialization
{
	protected $_shellType="routeros";
	
	public function getCmd($strCmd=null, $regExp=null, $timeout=null)
	{
		if ($this->getChild() === null) {
			$this->initialize();
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
		return $this->getParent()->getPipes();
	}
}