<?php
//� 2023 Martin Peter Madsen
namespace MTM\Shells\Tools\Shells;

class RouterOS extends \MTM\Utilities\Tools\Validations\V1
{
	public function formatUsername($userName)
	{
		//default terminal options for all Mikrotik SSH connections.
		//We need the terminal without colors and a standard width / height
		$this->isStr($userName, true);
		$rosOpts	= "ct1000w1000h";
		if (strpos($userName, "+") !== false) {
			if (preg_match("/(.*?)\+(.*)/", $userName, $raw) == 1) {
				//username has options, but they may be the wrong ones
				$userName	= $raw[1] . "+" . $rosOpts;
			} else {
				throw new \Exception("Not handled for username: '".$userName."'");
			}
		} else {
			$userName	.= "+" . $rosOpts;
		}
		return $userName;
	}
}