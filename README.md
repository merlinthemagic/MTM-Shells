# MTM-Shells

## What is this?

A way to create and work with shells. You can execute any command you want, there is full parity with the underlying shell.

## Install:

```
composer require merlinthemagic/mtm-shells

```

### Allow root shells:

#### Using Sudo:

Edit: /etc/sudoers

```
## Allow root to run any commands anywhere
root    ALL=(ALL)       ALL
## Add the line below, replace apache with whatever name your run your webserver with e.g. www-data
apache ALL=(ALL)NOPASSWD:/usr/bin/python

```

### Get shell:

#### Bash as current user (e.g. apache, www-data, etc):

```
$shellObj		= \MTM\Shells\Factories::getShells()->getBash();
```
	
#### Bash as root:

```
$shellObj		= \MTM\Shells\Factories::getShells()->getBash(true);

```

#### Execute commands
```
$strCmd	= "whoami";
$data		= $shellObj->getCmd($strCmd)->get();
echo $data; //webserver user or if you got a root shell, then root :)

$strCmd1	= "cd /var";
$shellObj->getCmd($strCmd1)->get(); //enter the /var directory

$strCmd2	= "ls -sho --color=none";
$data		= $shellObj->getCmd($strCmd2)->get();
echo $data; //directory and file listing from /var
```

### Files and directories:

#### SFTP Copy directory recursively to remote server

Note: SFTP will not include the source directory only all child files and directories into the specified destination directory
Note: Destination directories that do not exist will be created

```
$shellObj		= \MTM\Shells\Factories::getShells()->getBash(); //can also be a ssh shell obj from MTM-SSH
$toolObj		= \MTM\Shells\Factories::getFiles()->getSftpTool();

$srcDir		= "/path/to/dir/";
$dstDir		= "/";
$ipAddr		= "192.168.1.15";
$username		= "myUserName";
$password		= "verySecret";

$toolObj->passwordCopy($shellObj, $srcDir, $dstDir, $ipAddr, $username, $password);

```

#### SCP Copy directory recursively to remote server

Note: SCP will include the source directory and all child files and directories into the specified destination directory
Note: The base destination directory must exist and will not be created

```
$shellObj		= \MTM\Shells\Factories::getShells()->getBash(); //can also be a ssh shell obj from MTM-SSH
$toolObj		= \MTM\Shells\Factories::getFiles()->getScpTool();

$srcDir		= "/path/to/dir/";
$dstDir		= "/dst/dir/"; //must exist
$ipAddr		= "192.168.1.15";
$username		= "myUserName";
$password		= "verySecret";

$toolObj->passwordCopy($shellObj, $srcDir, $dstDir, $ipAddr, $username, $password);

```

#### Rsync Copy directory recursively to remote server

Note: RSync will include the source directory and all child files and directories into the specified destination directory
Note: The base destination directory must exist and will not be created

```
$shellObj		= \MTM\Shells\Factories::getShells()->getBash(); //can also be a ssh shell obj from MTM-SSH
$toolObj		= \MTM\Shells\Factories::getFiles()->getRsyncTool();

$srcDir		= "/path/to/dir/";
$dstDir		= "/dst/dir/"; //must exist
$ipAddr		= "192.168.1.15";
$username		= "myUserName";
$password		= "verySecret";

$toolObj->passwordCopy($shellObj, $srcDir, $dstDir, $ipAddr, $username, $password);

```
