<?php
//© 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class RouterOs extends Base
{
	protected function parse()
	{
		$data	= $this->getData();
		$lines	= explode("\n\r", $data);
		$lCount	= count($lines);
		if ($lCount > 0) {
			
			//Command string removal from return
			//cant be only whitespaces because we trim each line, we would likely
			//find white spaces later in the return the result would be cutting out return data
			if ($this->getCmd() !== null && trim($this->getCmd()) != "") {
				
				$strCmd		= $this->getCmd();
				$found		= false;
				foreach ($lines as $lKey => $line) {
					
					$line	= trim($line);
					if ($line != "") {
						
						//after each 1000 chars the commands encur a terminal break
						$line	= str_replace("\r\n", "", $line);
						//is the line part of the command?
						$cmdPos		= strpos($strCmd, $line);
						//is the command only part of the line
						//e.g. there is more data than just the command
						$linePos    = strpos($line, $strCmd);

						if ($cmdPos !== false || $linePos !== false) {
							//this line holds part or all of the command
							$found		= true;
							if ($cmdPos !== false) {
								$strCmd		= substr($strCmd, ($cmdPos + strlen($line)));
								if (strlen(trim($strCmd)) < 1) {
									//we found all of the command, nothing but whitespace left
									$lines		= array_slice($lines, ($lKey + 1));
									break;
								}
								
							} elseif ($linePos !== false) {
								
								//this line holds what remains of the command, the rest is data
								//the rest of the line is data, if it was only whitespace, it would have been caught above
								$lines[$lKey]	= substr($line, ($linePos + strlen($strCmd)));
								$lines			= array_slice($lines, $lKey);
								break;
							}

						} elseif ($found === true) {
							//we had part of the command but lost it before a match could be made
							break;
						}
					}		
				}
			}
			//Locate the delimitor in the return
			if ($this->getDelimitor() !== null) {
				//RouterOS has a nasty habit of including some non-printable chars in the return
				$junkBreak     = chr(13) . chr(13) . chr(13) . chr(27);
				//its faster to start from the bottom of the return
				$lines	= array_reverse($lines);
				foreach ($lines as $lKey => $line) {
					//check across line breaks with /s
					if (preg_match("/(.+)?(".$this->getDelimitor().")/s", $line, $raw) == 1) {
						if (preg_quote($this->getParent()->getRegEx()) == $this->getDelimitor()){
							if (strlen(trim($raw[1])) > 0 && substr($line, 0, 4) != $junkBreak) {
								//replace the line with only the good data
								$lines[$lKey]	= $raw[1];
							} else {
								//Only delimitor on the last line
								unset($lines[$lKey]);
							}

						} else {
							//user supplied regex, include it both data and regex match
							$lines[$lKey]	= $raw[1] . $raw[2];
						}
						break;
						
					} else {
						//this is data that was picked up after the delimitor was reached
						unset($lines[$lKey]);
					}
				}
				//remove junk from the last lines
				foreach ($lines as $lKey => $line) {
					$line		= implode("\r", array_filter(explode("\r", ltrim($line))));
					if (substr($line, 0, 7) == chr(27) . "[9999B") {
						//the last few lines seem to be made up of an ESC followed by almost exclusively carriage returns
						//i think its part of the VT100 ctrl sequence, VT100 because that is the bash base terminal
						//src: https://www.gnu.org/software/screen/manual/html_node/Control-Sequences.html
						$line		= substr($line, 7);
						if (trim($line) == "") {
							unset($lines[$lKey]);
						} else {
							$lines[$lKey]	= $line;
						}
					} elseif (trim($line) == "") {
						unset($lines[$lKey]);
					} else {
						break;
					}
				}
				$lines		= array_reverse($lines);
			}
			$data		= implode("\n", $lines);
		}
		return $data;
	}
}