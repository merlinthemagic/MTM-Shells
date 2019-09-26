<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Docs\Examples;

class Tests
{
	protected $_normCtrlObj=null;
	protected $_rootCtrlObj=null;
	
	public function whoAmI($asRoot=false)
	{
		$ctrlObj	= $this->getShell($asRoot);
		return $ctrlObj->getCmd("whoami")->get();
	}
	public function listFiles($path, $asRoot=false)
	{
		$ctrlObj	= $this->getShell($asRoot);
		return $ctrlObj->getCmd("ls -sho --color=none \"" . $path . "\"")->get();
	}
	public function getShell($asRoot=false)
	{
		if ($asRoot === false) {
			return $this->getNormalShell();
		} else {
			return $this->getRootShell();
		}
	}
	public function getNormalShell()
	{
		if ($this->_normCtrlObj === null) {
			$this->_normCtrlObj	= \MTM\Shells\Factories::getShells()->getBash();
		}
		return $this->_normCtrlObj;
	}
	public function getRootShell()
	{
		if ($this->_rootCtrlObj === null) {
			$this->_rootCtrlObj	= \MTM\Shells\Factories::getShells()->getBash(true);
		}
		return $this->_rootCtrlObj;
	}
}