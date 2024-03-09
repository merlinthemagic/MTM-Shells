<?php
//� 2024 Martin Peter Madsen
namespace MTM\Shells\Factories;

class Stencils extends Base
{
	public function getLinux()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Factories\Stencils\Linux();
		}
		return $this->_s[__FUNCTION__];
	}
}