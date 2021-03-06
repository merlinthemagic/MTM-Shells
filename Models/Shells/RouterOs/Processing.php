<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Shells\RouterOs;

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
		//cannot be a base shell, ROS does not run PHP :)
		$this->getParent()->write($cmdObj);
	}
	public function read($cmdObj)
	{
		//cannot be a base shell, ROS does not run PHP :)
		$this->getParent()->read($cmdObj);
	}
	public function resetPrompt($timeout=10000)
	{
		//keeps looping until we have a clean prompt
		//for unknown reasons the prompt is sometimes written more than once
		//that means a command will be issued, but the reader will catch an old prompt and return
		//either the previous data or more likely an empty return.
		$tTime	= (\MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($timeout / 1000));
		$i=0;
		while (true) {
			$i++;
			$pattern	= uniqid("cleaner.", true);
			$strCmd		= ":put \"" . $pattern . "\"";
			$data		= $this->getCmd($strCmd)->exec()->get(false); //may timeout
			$lines		= explode("\n", $data);
			foreach ($lines as $line) {
				if (trim($line) == $pattern) {
					//we have a clean prompt
					return;
				}
			}

			if ($tTime < \MTM\Utilities\Factories::getTime()->getMicroEpoch()) {
				throw new \Exception("Failed to recover prompt");
			} else {

				//wait for output to clear, sleep longer and longer or we just clog the pipe on slow connections
				if ($i == 1) {
					usleep(250000);
				} elseif ($i == 2) {
					usleep(500000);
				} elseif ($i == 3) {
					usleep(750000);
				} else {
					sleep(1);
				}
			}
		}
	}
}