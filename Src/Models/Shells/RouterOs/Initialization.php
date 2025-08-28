<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\RouterOs;

class Initialization extends Processing
{
	protected $_isInit=false;
	protected $_regEx=null;
	protected $_commitChars=null;
	
	public function getRegEx()
	{
		return $this->_regEx;
	}
	public function resetDefaultRegEx()
	{
		$this->_regEx	= null;
		$strCmd			= " ";
		$regEx			= "\]\s+\>(\s*)?$";
		$this->getCmd($strCmd, $regEx)->get();
		
		$strCmd			= ":local MHIT \"\";";
		$regChars		= "[a-zA-Z0-9\+\_\-\.\:\#\,]+";
		$regEx			= "\[((".$regChars.")@(".$regChars."))\]\s+\>";
		$cmdObj			= $this->getCmd($strCmd, $regEx);
		$cmdObj->get();
		$data			= $cmdObj->getReturnData();
		//prompt may carry some junk special characters back even with colors disabled, not sure why, might be a MT issue
		$lines			= array_filter(explode("\n", $data));
		foreach ($lines as $line) {
			$tline	= trim($line);
			if (preg_match("/(\[((".$regChars.")@(".$regChars."))]\s+\>)/", $tline, $raw) == 1) {
				$this->_regEx	= $raw[1];
				break;
			}
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
		if ($this->_isInit === false && $this->_initActive === false) {
			$this->_initActive	= true;
			
			try {
				
				//need the abillity to reset the shell delimitor regEx
				//if we change the identity of the device, the prompt changes too

				if ($this->resetDefaultRegEx() === null) {
					throw new \Exception("Failed to get shell prompt", 1111);
				}

				//reset the output so we have a clean beginning
				$this->resetPrompt();
				$this->getPipes()->resetStdOut();
				
				//fully initialized
				$this->_isInit		= true;
				$this->_initActive	= false;
				
			} catch (\Exception $e) {
				$this->_initActive	= false;
				throw $e;
			}
		}
	}
}