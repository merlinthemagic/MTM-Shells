<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\Bash;

class Processing extends Termination
{
	public function execute($cmdObj)
	{
		if ($this->getChild() === null) {
			$this->getPipes()->resetStdOut();
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
				$cmdObj->setError($e);
			}
		}
	}
	protected function rawRead($cmdObj)
	{
		try {
			
			$data	= $this->getPipes()->read();
			if ($data != "") {

				$cmdObj->addData($data);
				//we handle newlines as well with modifier = /s
				//src: https://php.net/manual/en/reference.pcre.pattern.modifiers.php
				if (
					$cmdObj->getDelimitor() != ""
					&& preg_match("/(.*)?(".$cmdObj->getDelimitor().")/s", $cmdObj->getData(), $raw) === 1 //too costly to check return data on every read, just do raw for starters
					&& (strlen(trim($raw[1])) > 0 || $cmdObj->getCmd() == "") === true //some shells (routeros) prints the prompt again and again. This triggers a false hit by the delimiter before we have a return. anything but an empty command should yeild some return. Maybe all read logic needs to move to the command class  
					&& preg_match("/".$cmdObj->getDelimitor()."/s", $cmdObj->getReturnData()) === 1
				) {
					$cmdObj->setDone();
				}
			}
			if ($cmdObj->getIsDone() === false && $cmdObj->getRunTime() > $cmdObj->getTimeout()) {
				if ($cmdObj->getDelimitor() == "") {
					//we wanted to read until time ran out
					$cmdObj->setDone();
				} else {
					throw new \Exception("Read timeout");
				}
			}
			
		} catch (\Exception $e) {
			$cmdObj->setError($e);
		}
	}
}