<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class RouterOs extends Base
{
	protected function parse()
	{
		$data	= $this->removeCommand();
		if ($this->getDelimitor() !== null && $this->getError() === null) {
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
	
	
// 		$lCount	= count($lines);
// 		if ($lCount > 0) {

// 			//Locate the delimitor in the return
// 			if ($this->getDelimitor() !== null) {
// 				//RouterOS has a nasty habit of including some non-printable chars in the return
// 				$junkBreak     = chr(13) . chr(13) . chr(13) . chr(27);
// 				//its faster to start from the bottom of the return
// 				$lines	= array_reverse($lines);
// 				foreach ($lines as $lKey => $line) {
// 					//check across line breaks with /s
// 					if (preg_match("/(.+)?(".$this->getDelimitor().")/s", $line, $raw) == 1) {
// 						if (preg_quote($this->getParent()->getRegEx()) == $this->getDelimitor()){
// 							if (strlen(trim($raw[1])) > 0 && substr($line, 0, 4) != $junkBreak) {
// 								//replace the line with only the good data
// 								$lines[$lKey]	= $raw[1];
// 							} else {
// 								//Only delimitor on the last line
// 								unset($lines[$lKey]);
// 							}

// 						} else {
// 							//user supplied regex, include it both data and regex match
// 							$lines[$lKey]	= $raw[1] . $raw[2];
// 						}
// 						break;
						
// 					} else {
// 						//this is data that was picked up after the delimitor was reached
// 						unset($lines[$lKey]);
// 					}
// 				}
// 				//remove junk from the last lines
// 				foreach ($lines as $lKey => $line) {
// 					$line		= implode("\r", array_filter(explode("\r", ltrim($line))));
// 					if (substr($line, 0, 7) == chr(27) . "[9999B") {
// 						//the last few lines seem to be made up of an ESC followed by almost exclusively carriage returns
// 						//i think its part of the VT100 ctrl sequence, VT100 because that is the bash base terminal
// 						//src: https://www.gnu.org/software/screen/manual/html_node/Control-Sequences.html
// 						$line		= substr($line, 7);
// 						if (trim($line) == "") {
// 							unset($lines[$lKey]);
// 						} else {
// 							$lines[$lKey]	= $line;
// 						}
// 					} elseif (trim($line) == "") {
// 						unset($lines[$lKey]);
// 					} else {
// 						break;
// 					}
// 				}
// 				$lines		= array_reverse($lines);
// 			}
// 		}
// 		return implode("\n", $lines);
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