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
$shellObj	= \MTM\Shells\Factories::getShells()->getBash();
```
	
#### Bash as root:

```
$shellObj	= \MTM\Shells\Factories::getShells()->getBash(true);

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






