<?php
//� 2026 Martin Peter Madsen
namespace MTM\Shells\Stencils\RouterOs\MacTelnet;

abstract class Alpha extends \MTM\Shells\Stencils\Base
{
	public function byPassword($ctrlObj, $macAddr, $username, $password, $timeout=30000)
	{
		if ($this->isStr($macAddr, false) === true) {
			$macAddr	= \MTM\Network\Factories::getMac()->getEui48($macAddr);
		}

		if ($ctrlObj instanceof \MTM\Shells\Models\Shells\RouterOs\Actions === true) {
			return $this->rosByPassword($ctrlObj, $macAddr, $username, $password, $timeout);
		} else {
			throw new \Exception("Not handled for ctrl class: ".get_class($ctrlObj), 1111);
		}
	}
}