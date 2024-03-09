<?php
//� 2024 Martin Peter Madsen
namespace MTM\Shells\Stencils\Linux\Su;

abstract class Alpha extends \MTM\Shells\Stencils\Base
{
	public function byPassword($ctrlObj, $username, $password)
	{
		if (
			$ctrlObj instanceof \MTM\SSH\Models\Shells\Bash\Actions === true
			|| $ctrlObj instanceof \MTM\Shells\Models\Shells\Bash\Actions === true
		) {
			return $this->bashByPassword($ctrlObj, $username, $password);
		} else {
			throw new \Exception("Not handled for ctrl class: ".get_class($ctrlObj), 1111);
		}
	}
}