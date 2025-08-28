<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells;

abstract class Base
{
	protected $_guid=null;
	protected $_parentObj=null; //shell that this instance is built on
	protected $_childObj=null; //shell built on top of this instance
	protected $_cmdObj=null; //currently executing
	protected $_dTimeout=25000;
	protected $_isInit=false; //shell has been fully setup
	protected $_isTerm=false; //shell is terminated
	protected $_initActive=false; //init is active
	protected $_termActive=false; //termination is active
	
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
	public function isInit()
	{
		return $this->_isInit;
	}
	public function isTerm()
	{
		return $this->_isTerm;
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
}