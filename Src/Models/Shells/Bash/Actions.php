<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\Bash;

class Actions extends Initialization
{
	protected $_shellType="bash";
	protected $_termHeight=null;
	protected $_termWidth=null;
	protected $_maxInput=null;
	protected $_rwDir=null;
	
	public function getCmd($strCmd=null, $regExp=null, $timeout=null)
	{
		if ($this->getChild() === null) {
			$this->initialize();
			$rObj	= new \MTM\Shells\Models\Commands\Bash();
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
	public function setTerminalSize($height, $width)
	{
		$strCmd	= "stty cols ".$width." rows ". $height;
		$this->getCmd($strCmd)->get();
		$rObj	= $this->getTerminalSize(true);
		if ($rObj->height != $height || $rObj->width != $width) {
			throw new \MHT\MException(__METHOD__ . ">> Failed to set Terminal size");
		}
	}
	public function getTerminalSize($refresh=true)
	{
		if (
			$refresh === true
			|| $this->_termHeight === null
			|| $this->_termWidth === null
		) {
			$strCmd	= "stty size";
			$data	= $this->getCmd($strCmd)->get();
			if (preg_match("/([0-9]+)\s([0-9]+)/", $data, $raw) == 1) {
				$this->_termHeight	= intval($raw[1]);
				$this->_termWidth	= intval($raw[2]);
			} else {
				throw new \Exception("Failed to get Terminal size");
			}
		}
		$rObj			= new \stdClass();
		$rObj->height	= $this->_termHeight;
		$rObj->width	= $this->_termWidth;
		return $rObj;
	}
	public function getTempDirectory()
	{
		if ($this->_rwDir === null) {
			$tmpDirs	= array("/tmp/", "/dev/shm/");
			$strCmd		= "echo \$HOME";
			$homeDir	= trim($this->getCmd($strCmd)->get());
			if ($homeDir != "") {
				$tmpDirs[]	= rtrim($homeDir, "/")."/";
			}
			foreach ($tmpDirs as $tmpDir) {
				$strCmd	= "if [ -w \"".$tmpDir."\" ]; then echo \"isWrite\"; else echo \"noWrite\"; fi";
				$data	= trim($this->getCmd($strCmd)->get());
				if ($data == "isWrite") {
					$this->_rwDir	= \MTM\FS\Factories::getDirectories()->getDirectory($tmpDir);
					break;
				}
			}
			if ($this->_rwDir === null) {
				throw new \Exception("Failed to get temp directory");
			}
		}
		return $this->_rwDir;
	}
	public function getMaxInput($refresh=false)
	{
		if (
			$refresh === true
			|| $this->_maxInput === null
		) {
			$strCmd	= "getconf ARG_MAX";
			$data	= $this->getCmd($strCmd)->get();
			if (preg_match("/([0-9]+)/", $data, $raw) == 1) {
				$this->_maxInput	= intval($raw[1]);
			} else {
				throw new \Exception("Failed to get max input length");
			}
		}
		return $this->_maxInput;
	}
	public function getPipes()
	{
		if ($this->getParent() === null) {
			return $this->getBasePipes();
		} else {
			return $this->getParent()->getPipes();
		}
	}
}