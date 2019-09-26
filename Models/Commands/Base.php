<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

abstract class Base
{
	protected $_guid=null;
	protected $_isExec=false;
	protected $_isRunning=false;
	protected $_isDone=false;
	protected $_parentObj=null;
	protected $_strCmd=null;
	protected $_regExp=null;
	protected $_commit=null;
	protected $_execTime=null;
	protected $_initTime=null;
	protected $_doneTime=null;
	protected $_timeout=25000;
	protected $_data="";
	protected $_error=null;
	
	public function getGuid()
	{
		if ($this->_guid === null) {
			$this->_guid	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		}
		return $this->_guid;
	}
	public function setParent($obj)
	{
		$this->_parentObj	= $obj;
		return $this;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
	public function setCmd($strCmd)
	{
		$this->_strCmd	= $strCmd;
		return $this;
	}
	public function getCmd()
	{
		return $this->_strCmd;
	}
	public function setDelimitor($regExp)
	{
		$this->_regExp	= $regExp;
		return $this;
	}
	public function getDelimitor()
	{
		return $this->_regExp;
	}
	public function setCommit($chars)
	{
		$this->_commit	= $chars;
		return $this;
	}
	public function getCommit()
	{
		return $this->_commit;
	}
	public function setTimeout($ms)
	{
		$this->_timeout	= $ms;
		return $this;
	}
	public function getTimeout()
	{
		return $this->_timeout;
	}
	public function getIsExec()
	{
		return $this->_isExec;
	}
	public function getIsRunning()
	{
		return $this->_isRunning;
	}
	public function getRunTime()
	{
		//returns in miliseconds
		if ($this->_initTime !== null) {
			if ($this->getIsDone() === false) {
				return (\MTM\Utilities\Factories::getTime()->getMicroEpoch() - $this->_initTime) * 1000;
			} else {
				return ($this->_doneTime - $this->_initTime) * 1000;
			}
		} else {
			return 0;
		}
	}
	public function getIsDone()
	{
		return $this->_isDone;
	}
	public function setRunning()
	{
		//triggered by parent 
		if ($this->_isRunning === false) {
			$this->_isRunning	= true;
			$this->_initTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		}
		return $this;
	}
	public function setDone()
	{
		if ($this->_isDone === false) {
			$this->_isDone		= true;
			$this->_doneTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		}
		return $this;
	}
	public function exec()
	{
		if ($this->_isExec === false) {
			$this->getParent()->execute($this);
			$this->_isExec		= true;
			$this->_execTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		}
		return $this;
	}
	public function addData($data)
	{
		$this->_data	.= $data;
		return $this;
	}
	public function getData()
	{
		return $this->_data;
	}
	public function setError($e)
	{
		$this->_error	= $e;
		$this->setDone();
		return $this;
	}
	public function get($throw=true)
	{
		$this->exec();
		while(true) {
			if ($this->getIsDone() === false) {
				\MTM\Async\Factories::getServices()->getLoop()->runOnce();
				usleep(10000); //this structure has to go
			} elseif (is_object($this->_error) === false) {
				return $this->parse();
			} elseif ($throw === true) {
				throw $this->_error;
			} else {
				return $this->_data;
			}
		}
	}
}