<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Factories;

class Shells extends Base
{
	public function getBash($useSudo=false)
	{
		$rObj	= new \MTM\Shells\Models\Shells\Bash\Actions();
		$rObj->setSudo($useSudo);
		return $rObj;
	}
	public function getRouterOs()
	{
		$rObj	= new \MTM\Shells\Models\Shells\RouterOs\Actions();
		return $rObj;
	}
}