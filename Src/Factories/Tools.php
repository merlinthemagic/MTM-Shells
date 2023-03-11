<?php
//� 2023 Martin Peter Madsen
namespace MTM\Shells\Factories;

class Tools extends Base
{
	public function getRouterOs()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Tools\Shells\RouterOS();
		}
		return $this->_s[__FUNCTION__];
	}
}