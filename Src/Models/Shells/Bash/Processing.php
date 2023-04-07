<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\Bash;

class Processing extends Termination
{
	public function execute($cmdObj)
	{
		if ($this->getChild() === null) {
			if ($cmdObj->getCmd() !== null) {
				//null means we just want to keep reading
				$this->getPipes()->resetStdOut();
			}
			$this->write($cmdObj);
			return $this;
		} else {
			return $this->getChild()->execute($cmdObj);
		}
	}
	public function write($cmdObj)
	{
		if ($this->getParent() === null) {
			$this->rawWrite($cmdObj);
		} else {
			$this->getParent()->write($cmdObj);
		}
	}
	public function read($cmdObj)
	{
		if ($this->getParent() === null) {
			$this->rawRead($cmdObj);
		} else {
			$this->getParent()->read($cmdObj);
		}
	}
	protected function rawWrite($cmdObj)
	{
		$cmdObj->setRunning();
		if ($cmdObj->getCmd() !== null) {
			try {
				$exeCmd		= $cmdObj->getCmd() . $cmdObj->getCommit();
				$this->getPipes()->write($exeCmd);
			} catch (\Exception $e) {
				switch ($e->getCode()) {
					case 92987:
						//stdIn went away, not sure if the remote side is responsible
						$cmdObj->setError(new \Exception("Shell was terminated", 44733));
						$this->terminate();
						break;
					default:
						$cmdObj->setError($e);
				}
			}
		}
	}
	protected function rawRead($cmdObj)
	{
		try {
			
			$data	= $this->getPipes()->read();
			if ($data != "") {
				$cmdObj->addData($data);
			}

		} catch (\Exception $e) {
			switch ($e->getCode()) {
				case 92980:
					//stdOut went away, not sure if the remote side is responsible
					$cmdObj->setError(new \Exception("Shell was terminated", 44734));
					$this->terminate();
					break;
				default:
					$cmdObj->setError($e);
			}
		}
	}
}