<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells;

class Factories
{
	private static $_cStore=array();
	
	//USE: $aFact		= \MTM\Shells\Factories::$METHOD_NAME();
	
	public static function getShells()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\Shells\Factories\Shells();
		}
		return self::$_cStore[__FUNCTION__];
	}
}