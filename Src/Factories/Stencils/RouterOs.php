<?php
//� 2026 Martin Peter Madsen
namespace MTM\Shells\Factories\Stencils;

class RouterOs extends \MTM\Shells\Factories\Base
{
	public function getMacTelnet()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Stencils\RouterOs\MacTelnet\Zulu();
		}
		return $this->_s[__FUNCTION__];
	}
}