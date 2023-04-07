<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class RouterOs extends Base
{
	private function debugHelper()
	{
		if (strpos($this->getCmd(), "/system/package/update/check-for-updates") !== false) {

			echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
			//var_dump(count($lines));
			echo "\n 2222 \n";
			print_r($lines);
			echo "\n 3333 \n";
			print_r($this->removeCommand(false));
			echo "\n ".time()."</pre></code> \n ";
			die();
		}
	}
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
			$fDelim	= null;
			$lines	= array_reverse($this->removeCommand(false));
			foreach ($lines as $lId => $line) {
				if (strpos($line, "[9999B") === 0) {
					//i think its part of the VT100 ctrl sequence, VT100 because that is the bash base terminal
					//src: https://www.gnu.org/software/screen/manual/html_node/Control-Sequences.html
					$line	= substr($line, 6);
				}
				if (preg_match("/(.*)?(".$this->getDelimitor().")/s", $line, $raw) === 1) {
					if (trim($raw[1]) === "") {
						$lines[$lId]	= "";
					} else {
						$lines[$lId]	= $raw[1];
					}
					$fDelim	= $lId;
					
				} elseif ($fDelim !== null) {
					//we found the delimitor and this next line does not have another delimitor
					//time to stop
					break;
				}
			}
			if ($fDelim !== null) {
				$lines	= array_slice($lines, ($fDelim + 1));
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
					if (array_key_exists(0, $lines) === true && strpos($lines[0], "[K") === 0) {
						//in v6 each new command char results in a new line + break + "[K" + $new char
						//dont trim anything else. If we do a blanket left trim we lose more than the command
						$lines[0]	= substr($lines[0], 2);
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
		$rData		= array();
		$lines		= explode("\x1B", $this->getData());
		foreach ($lines as $lId => $line) {
			$output		= "";
			foreach (str_split($line, 1) as $chr) {
				$ord	= ord($chr);
				if ($ord > 31 && $ord < 127) {
					$output		.= $chr;
				} elseif ($ord === 10 || $ord === 13) {
					$rData[]	= $output;
					$output		= "";
				}
			}
			$rData[]	= $output;
		}
		return $rData;
	}
}