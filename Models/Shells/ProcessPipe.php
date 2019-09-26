<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells;

class ProcessPipe
{
	private $_procLock=null;
	private $_stdIn=null;
	private $_stdOut=null;
	private $_stdErr=null;
	
	private $_stdInPos=0;
	private $_stdOutPos=0;
	private $_stdErrPos=0;
	
	public function setLock($lock)
	{
		$this->_procLock	= $lock;
		return $this;
	}
	public function getLock()
	{
		return $this->_procLock;
	}
	public function setPipes($stdIn, $stdOut, $stdErr=null)
	{
		$this->_stdIn	= $stdIn;
		$this->_stdOut	= $stdOut;
		$this->_stdErr	= $stdErr;
		return $this;
	}
	public function getStdIn()
	{
		return $this->_stdIn;
	}
	public function getStdOut()
	{
		return $this->_stdOut;
	}
	public function getStdErr()
	{
		return $this->_stdErr;
	}
	public function getStdInPos()
	{
		return $this->_stdInPos;
	}
	public function getStdOutPos()
	{
		return $this->_stdOutPos;
	}
	public function getStdOutMax()
	{
		return $this->getStdOut()->getByteCount();
	}
	public function getStdErrPos()
	{
		return $this->_stdErrPos;
	}
	public function getStdErrMax()
	{
		return $this->getStdErr()->getByteCount();
	}
	public function resetStdOut()
	{
		$this->_stdOutPos	= $this->getStdOutMax();
	}
	public function write($data)
	{
		$this->getStdIn()->addContent($data, "append");
		return $this;
	}
	public function read()
	{
		$cur	= $this->getStdOutPos();
		$max	= $this->getStdOutMax();
		$diff	= $max - $cur;
		if ($diff > 0) {
			//new content avaliable, + 1 so we dont pickup the last byte again. (also getBytes() min sByte == 1)
			$this->_stdOutPos	= $max;
			return $this->getStdOut()->getBytes($diff, ($cur + 1));
		} else {
			return "";
		}
	}
	public function readError()
	{
		if ($this->getStdErr() !== null) {
			$cur	= $this->getStdErrPos();
			$max	= $this->getStdErrMax();
			$diff	= $max - $cur;
			if ($diff > 0) {
				$this->_stdErrPos	= $max;
				return $this->getStdErr()->getBytes($diff, ($cur + 1));
			} else {
				return "";
			}
		} else {
			return "";
		}
	}
}