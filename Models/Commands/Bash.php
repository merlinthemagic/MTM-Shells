<?php
//� 2019 Martin Peter Madsen
namespace MTM\Shells\Models\Commands;

class Bash extends Base
{
	protected function parse()
	{
		$data	= $this->removeCommand();
		$lines	= explode("\n", $data);
		$lCount	= count($lines);
		if ($lCount > 0) {
			//Locate the delimitor in the return
			if ($this->getDelimitor() !== null) {
				//its faster to start from the bottom of the return
				$lines	= array_reverse($lines);
				foreach ($lines as $lKey => $line) {
					if (preg_match("/(.+)?(".$this->getDelimitor().")/", $line, $raw) == 1) {
						if (preg_quote($this->getParent()->getRegEx()) != $this->getDelimitor()){
							//user supplied regex, include it both data and regex match
							$lines[$lKey]	= $raw[1] . $raw[2];
						} elseif (strlen(trim($raw[1])) > 0) {
							//there is data before the default regex, include that
							//but discard the regex
							$lines[$lKey]	= $raw[1];
						} else {
							//Only delimitor on the last line
							unset($lines[$lKey]);
						}
						break;
						
					} else {
						//this is data that was picked up after the delimitor was reached
						unset($lines[$lKey]);
					}
				}
				$lines	= array_reverse($lines);
			}			
			$data	= implode("\n", $lines);
		}
		return $data;
	}
	protected function removeCommand()
	{
		//Command string removal from return
		$data	= $this->getData();
		if ($this->getCmd() !== null) {
		
			$lines	= explode("\n", $data);
			$lCount	= count($lines);
			if ($lCount > 0) {

				//there could be junk left over on the terminal before the command was issued
				//so allow a longer string to match before giving up
				$cmdLen		= strlen(trim($this->getCmd()));
				$maxLen		= ($cmdLen * 3);
				$remainCmd	= $this->getCmd();
				$cmdLine	= "";
				foreach ($lines as $lKey => $line) {
					
					if ($this->getParent()->isInit() === true) {
						//locate terminal breaks in very long commands
						$termWidth	= $this->getParent()->getTerminalSize(false)->width;
						if (strlen($line) >= $termWidth) {
							$oIndex		= 0;
							$cIndex		= 0;
							$nLine		= "";
							$cChars		= str_split($remainCmd, 1);
							$oChars		= str_split($line, 1);
							foreach ($cChars as $cChar) {
								if (array_key_exists($oIndex, $oChars) === true) {
									$oChar	= $oChars[$oIndex];
									if ($cChar !== $oChar) {
										$found	= false;
										for ($x=0; $x<4; $x++) {
											$oIndex++;
											$oChar	= $oChars[$oIndex];
											if ($cChar === $oChar) {
												$found	= true;
												break;
											}
										}
										if ($found === false) {
											//we failed to find a terminal break
											$nLine	= $line;
											break;
										}
									}
									
								} else {
									break;
								}
								$nLine	.= $oChar;
								$oIndex++;
								$cIndex++;
							}
							
							$remainCmd	= substr($remainCmd, $cIndex);
							$line		= $nLine;
						}
					}
					$cmdLine	.= trim($line);
					$curLen		= strlen($cmdLine);
					if ($curLen == ($cmdLen + strpos($cmdLine, $this->getCmd()))) {
						//found the command, delete the lines that has the command and anything before it
						$lines		= array_slice($lines, ($lKey + 1));
						break;
					} elseif ($curLen > $maxLen) {
						//no match
						break;
					}
				}

				$data	= implode("\n", $lines);
			}
		}
		return $data;
	}
}