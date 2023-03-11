<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells;

class Factories
{
	private static $_s=array();
	
	//USE: $aFact		= \MTM\Shells\Factories::$METHOD_NAME();
	
	public static function getShells()
	{
		if (array_key_exists(__FUNCTION__, self::$_s) === false) {
			self::$_s[__FUNCTION__]	= new \MTM\Shells\Factories\Shells();
		}
		return self::$_s[__FUNCTION__];
	}
	public static function getFiles()
	{
		if (array_key_exists(__FUNCTION__, self::$_s) === false) {
			self::$_s[__FUNCTION__]	= new \MTM\Shells\Factories\Files();
		}
		return self::$_s[__FUNCTION__];
	}
	public static function getTools()
	{
		if (array_key_exists(__FUNCTION__, self::$_s) === false) {
			self::$_s[__FUNCTION__]	= new \MTM\Shells\Factories\Tools();
		}
		return self::$_s[__FUNCTION__];
	}
}