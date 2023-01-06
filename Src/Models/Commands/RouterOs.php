<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class RouterOs extends Base
{
	protected function parse()
	{
		$data	= $this->removeCommand();
		if ($this->getDelimitor() != "" && $this->getError() === null) {
			$lines	= array_reverse(explode("\n", $data));
			foreach ($lines as $lKey => $line) {
				//is the line part of the command?
				$line		= preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line);
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
			$data	= str_replace("\r", "", implode("\n", array_reverse($lines)));
		}
		return $data;
	}
	protected function removeCommand()
	{
		//Command string removal from return
		//cant be only whitespaces because we trim each line, we would likely
		//find white spaces later in the return the result would be cutting out return data
		$data		= $this->getData();
		$strCmd		= $this->getCmd();
		$lines		= explode("\r\n", $data);
		if ($strCmd !== null && trim($strCmd) != "") {
			foreach ($lines as $lKey => $line) {
				//is the line part of the command?
				$line		= preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $line);
				$cmdPos		= strrpos($line, $strCmd);
				if ($cmdPos !== false) {
					//this line holds all of the command
					$line		= substr($line, ($cmdPos + strlen($strCmd)));
					if (strlen(trim($line)) < 1) {
						//we found all of the command, nothing but whitespace left
						$lines			= array_slice($lines, ($lKey + 1));
					} else {
						$lines			= array_slice($lines, $lKey);
						$lines[$lKey]	= $line;
					}
					break;
				}
			}
		}
		return implode("\n", $lines);
	}
}