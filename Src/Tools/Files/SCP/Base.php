<?php
//� 2022 Martin Peter Madsen
namespace MTM\Shells\Tools\Files\SCP;

abstract class Base extends \MTM\Shells\Tools\Files\Base
{
	public function passwordCopy($ctrlObj, $srcDir, $dstDir, $ip, $userName, $password, $port=22, $timeout=30000)
	{
		if ($ip instanceof \MTM\Network\Models\Ip\V4Address === false) {
			$ip		= \MTM\Network\Factories::getIp()->getIpFromString($ip);
		}
		if ($srcDir instanceof \MTM\FS\Models\Directory === false) {
			$srcDir		= \MTM\FS\Factories::getDirectories()->getDirectory($srcDir);
		}
		if ($dstDir instanceof \MTM\FS\Models\Directory === false) {
			$dstDir		= \MTM\FS\Factories::getDirectories()->getDirectory($dstDir);
		}

		if (
			$ctrlObj instanceof \MTM\SSH\Models\Shells\Bash\Actions === true
			|| $ctrlObj instanceof \MTM\Shells\Models\Shells\Bash\Actions === true
		) {
			return $this->bashPasswordCopy($ctrlObj, $srcDir, $dstDir, $ip, $userName, $password, $port, $timeout);
		} else {
			throw new \Exception("Not handled for ctrl class");
		}
	}
}