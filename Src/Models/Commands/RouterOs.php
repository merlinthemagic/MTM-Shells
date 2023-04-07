<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class RouterOs extends Base
{
	protected function checkData()
	{
		//we handle newlines as well with modifier = /s
		//src: https://php.net/manual/en/reference.pcre.pattern.modifiers.php
		if (
			$this->getDelimitor() != ""
			&& preg_match("/(.*)?(".$this->getDelimitor().")/s", $this->getData(), $raw) === 1 //too costly to check return data on every read, just do raw for starters
			&& $this->getCmdFound() === true
			&& preg_match("/".$this->getDelimitor()."/s", $this->getReturnData()) === 1
		) {
			$this->setDone();
		}
		
		if ($this->getIsDone() === false && $this->getRunTime() > $this->getTimeout()) {
			if ($this->getDelimitor() == "") {
				//we wanted to read until time ran out
				$this->setDone();
			} else {
				$this->setError(new \Exception("Read timeout"));
			}
		}
	}
	
	protected function parse()
	{
		if ($this->getDelimitor() != "" && $this->getError() === null) {
			$lines	= array_reverse($this->removeCommand(false));
			foreach ($lines as $lKey => $line) {
				if (substr($line, 0, 6) == "[9999B") {
					//i think its part of the VT100 ctrl sequence, VT100 because that is the bash base terminal
					//src: https://www.gnu.org/software/screen/manual/html_node/Control-Sequences.html
					$line		= substr($line, 6);
				}
				if (preg_match("/(.*)?(".$this->getDelimitor().")/s", $line, $raw) === 1) {
					$raw			= array_values(array_filter($raw));
					$delimPos		= strpos($line, $raw[1]);
					if ($delimPos !== false) {
						//this line holds all of the command
						$regEx	= $this->getParent()->getRegEx();
						if ($regEx !== null && preg_quote($regEx) === $this->getDelimitor()) {
							//exclude system delimitors
							$line		= substr($line, 0, $delimPos);
						} else {
							//include custom delimitors
							$line		= substr($line, 0, (1+ $delimPos + strlen($raw[1])));
						}
						
						if (strlen(trim($line)) < 1) {
							//we found all of the command, nothing but whitespace left
							$lines			= array_slice($lines, ($lKey + 1));
						} else {
							$lines			= array_slice($lines, $lKey);
							$lines[$lKey]	= $line;
						}
						break;
					}
					
				} else {
					unset($lines[$lKey]);
				}
			}
			return implode("\n", array_reverse($lines));
			
		} else {
			return $this->removeCommand(true);
		}
	}
	protected function removeCommand($asStr=true)
	{
		$strCmd		= $this->getCmd();
		$lines		= $this->getLines();
		if ($strCmd !== null && trim($strCmd) != "") {
			foreach ($lines as $lKey => $line) {
				//is the line part of the command?
				$cmdPos		= strrpos($line, $strCmd);
				if ($cmdPos !== false) {
					//this line holds all of the command
					$lines		= array_slice($lines, ($lKey + 1));
					$line		= substr($line, ($cmdPos + strlen($strCmd)));
					if (strlen(trim($line)) > 0) {
						array_unshift($lines, $line);
					}
					if (array_key_exists(0, $lines) === true && strpos($lines[0], "[K\n") === 0) {
						//in v6 each new command char results in a new line + break + "[K" + $new char
						//dont trim anything else. If we do a blanket left trim we lose more than the command
						$lines[0]	= substr($lines[0], 3);
					}
					break;
				}
			}
		}
		if ($asStr === true) {
			return implode("\n", $lines);
		} else {
			return $lines;
		}
	}
	protected function getCmdFound()
	{
		$strCmd		= "";
		if ($this->getCmd() !== null) {
			foreach (str_split($this->getCmd(), 1) as $chr) {
				$ord	= ord($chr);
				if (($ord > 31 && $ord < 127)) {
					$strCmd		.= $chr;
				}
			}
		}
		if (trim($strCmd) != "") {
			$lines		= $this->getLines();
			foreach ($lines as $line) {
				$cmdPos		= strrpos($line, $strCmd);
				if ($cmdPos !== false) {
					return true;
				}
			}
		} else {
			return true;
		}
		return false;
	}
	protected function getLines()
	{
		$lines		= explode("\x1B", $this->getData());
		foreach ($lines as $lId => $line) {
			$output		= "";
			foreach (str_split($line, 1) as $chr) {
				$ord	= ord($chr);
				if (($ord > 31 && $ord < 127) || $ord === 10) {
					$output		.= $chr;
				} elseif ($ord === 13) {
					//relace \r with newline
					$output		.= "\n";
				}
			}
			$lines[$lId]	= $output;
		}
		return $lines;
	}
}