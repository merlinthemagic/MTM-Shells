<?php
//� 2024 Martin Peter Madsen
namespace MTM\Shells\Factories\Stencils;

class Linux extends \MTM\Shells\Factories\Base
{
	public function getSu()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Stencils\Linux\Su\Zulu();
		}
		return $this->_s[__FUNCTION__];
	}
}