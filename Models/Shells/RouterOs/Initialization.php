<?php
//© 2019 Martin Peter Madsen
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
	protected function getCommit()
	{
		if ($this->_commitChars === null) {
			$this->_commitChars	= chr(13);
		}
		return $this->_commitChars;
	}
	protected function initialize()
	{
		if ($this->_isInit === false) {
			$this->_isInit	= null;
			
			try {

				$strCmd		= " ";
				$regEx		= "\]\s+\>(\s*)?$";
				$this->getCmd($strCmd, $regEx)->get();
				
				$strCmd		= ":local MHIT \"\";";
				$regChars	= "[a-zA-Z0-9\+\_\-\.\:\#\,]+";
				$regEx		= "\[((".$regChars.")@(".$regChars."))\]\s+\>";
				$data		= $this->getCmd($strCmd, $regEx)->get();

				//prompt may carry some junk special characters back even with colors disabled, not sure why, might be a MT issue
				$lines			= array_filter(explode("\n", $data));
				foreach ($lines as $line) {
					$line	= trim($line);
					if (preg_match("/(\[((".$regChars.")@(".$regChars."))]\s+\>)/", $line, $raw) == 1) {
						$this->_regEx	= $raw[1];
						break;
					}
				}
				if ($this->getRegEx() === null) {
					throw new \Exception("Failed to get shell prompt");
				}

				//reset the output so we have a clean beginning
				$this->resetPrompt();
				$this->getPipes()->resetStdOut();
				
				//fully initialized
				$this->_isInit	= true;
				
			} catch (\Exception $e) {
				$this->_isInit	= false;
				throw $e;
			}
		}
	}
}