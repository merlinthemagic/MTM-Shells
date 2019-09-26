<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells;

abstract class Base
{
	protected $_guid=null;
	protected $_asyncObj=null;
	protected $_parentObj=null; //shell that this instance is built on
	protected $_childObj=null; //shell built on top of this instance
	protected $_cmdObj=null; //currently executing
	protected $_dTimeout=25000;
	protected $_cmdQueue=array(); //pending commands
	
	public function __construct()
	{
		//on uncaught exception __destruct is not called, this might leave us 
		//as a zombie running on the system we cant have that.
		register_shutdown_function(array($this, "__destruct"));
	}
	public function __destruct()
	{
		$this->terminate();
	}
	public function getGuid()
	{
		if ($this->_guid === null) {
			$this->_guid	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		}
		return $this->_guid;
	}
	public function getType()
	{
		return $this->_shellType;
	}
	public function setChild($obj)
	{
		$this->_childObj	= $obj;
		return $this;
	}
	public function getChild()
	{
		return $this->_childObj;
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
	public function setDefaultTimeout($ms)
	{
		$this->_dTimeout	= $ms;
		return $this;
	}
	public function getDefaultTimeout()
	{
		return $this->_dTimeout;
	}
	public function execute($cmdObj)
	{
		if ($this->getChild() === null) {
			$this->initialize();
			$this->submit($cmdObj);
			return $this;
		} else {
			return $this->getChild()->execute($cmdObj);
		}
	}
	protected function submit($cmdObj)
	{
		$this->_cmdQueue[]	= $cmdObj;
		$this->getAsync(true);
		return $this;
	}
	protected function getCurrent()
	{
		if (is_object($this->_cmdObj) === true && $this->_cmdObj->getIsDone() === false) {
			//do nothing, current command still active
		} elseif (count($this->_cmdQueue) > 0) {
			$this->_cmdObj	= array_shift($this->_cmdQueue);
		} else {
			$this->_cmdObj	= null;
			$this->clearSubscription();
		}
		return $this->_cmdObj;
	}
	protected function getAsync($create=false)
	{
		if ($this->_asyncObj === null && $create === true) {
			$this->_asyncObj	= \MTM\Async\Factories::getServices()->getLoop()->getSubscription();
			$this->_asyncObj->setCallback($this, "run");
		}
		return $this->_asyncObj;
	}
	protected function clearSubscription()
	{
		if (is_object($this->getAsync()) === true) {
			$this->getAsync()->unsubscribe();
			$this->_asyncObj	= null;
		}
		return $this;
	}
}