<?php
//� 2022 Martin Peter Madsen
namespace MTM\Shells\Factories;

class Files extends Base
{
	public function getScpTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Tools\Files\SCP\Zstance();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getSftpTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Tools\Files\SFTP\Zstance();
		}
		return $this->_s[__FUNCTION__];
	}
	public function getRsyncTool()
	{
		if (array_key_exists(__FUNCTION__, $this->_s) === false) {
			$this->_s[__FUNCTION__]		= new \MTM\Shells\Tools\Files\Rsync\Zstance();
		}
		return $this->_s[__FUNCTION__];
	}
}