### What is this?

A way to create and work with shells
You can execute any command you want. There is full parity with the underlying shells


#### Get a Bash shell as the webserver user
```
$ctrlObj	= \MTM\Shells\Factories::getShells()->getBash();
```
	
#### Get a Bash shell as root, using sudo
##### Edit: /etc/sudoers
```
## Allow root to run any commands anywhere
root    ALL=(ALL)       ALL
## Add the line below, replace apache with whatever name your run your webserver with e.g. www-data
apache ALL=(ALL)NOPASSWD:/usr/bin/python

```

##### Get the Bash root shell
```
$ctrlObj	= \MTM\Shells\Factories::getShells()->getBash(true);
```


#### Start running commands
```
$data		= $ctrlObj->getCmd("whoami")->get();
echo $data; //webserver user or if you got a root shell, then root :)

$ctrlObj->getCmd("cd /var")->get();
$data		= $ctrlObj->getCmd("ls -sho --color=none")->get();
echo $data; //directory and file listing from /var
```