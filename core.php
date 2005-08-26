<?php
//****************** Welcome to KPBot.php*******************
//*******************************************************

//*******************************************************
//*****************Declare Functions************************

//*******************************************************
//*************Loads all available modules*********************
function loadcommands() {	//Define as a function so we can use it later.
	global $_loadedmodules;
	$i=0;
	$handler = opendir('commands/'); // create a handler for the directory
	while ($file = readdir($handler)) { // keep going until all files in directory have been read
		// if $file isn't this directory or its parent,
		// add it to the results array
		if ($file != '.' && $file != '..' && in_array($file,$_loadedmodules)===false && substr($file,-4)=='.php') {
			include('commands/'.$file);
			echo "Added " . ucfirst(substr($file,0,-4)) . " functionallity.\n";
			$_loadedmodules[$i] = $file;
			++$i;
		}
	}
	closedir($handler);    // tidy up: close the handler
}
//*******************************************************
//*****************Loads settings ini************************
function loadini() {
	global $CONFIG, $COL, $MYSQL;
	$settings = parse_ini_file("settings.ini", true);
	//Config defines general configuration things, like server, nickname, domain name etc.
	$CONFIG = $settings['config'];
	$CONFIG['cmdprefix']=$settings['cmdprefix'];
	$CONFIG['dontlog']=$settings['dontlog'];
	//print_r($CONFIG); //for debug.
	//Mysql is obviously for Mysql connection
	$MYSQL = $settings['mysql'];
}

//*******************************************************
//*****************MySqlQuery Function**********************
function query($query) {
	global $MYSQL;
	mysql_connect($MYSQL['host'],$MYSQL['username'],$MYSQL['password']);			// Connect to the MySQL server
	@mysql_select_db($MYSQL['database']) or die('Unable to select database');		// Select the database
	$result = mysql_query($query) or die (mysql_error().'<br /> Couldn\'t execute query: '.$result);	//Query the query
	mysql_close();																											// Close connection

	return $result;
}
//*******************************************************
//*****************INITIALISE******************************
function init() {
	global $con, $CONFIG, $BLACKLIST, $_HOSTLIST;
	$firstTime = true;
	$con['socket'] = fsockopen($CONFIG['server'], $CONFIG['port']); //Open the port
	switch ($con['socket']) {
		case '': 																						//If we couldnt open the socket, then tell the user.
			print ('Could not connect to: '. $CONFIG['server'] .' on port '. $CONFIG['port']);
		break;
		default:																						//If we could connect, then start sending commands
			cmd_send('USER '.$CONFIG['nick'].' '.$CONFIG['domain'].'  '.$CONFIG['domain'].' :'.$CONFIG['name']);	// Tell the server who we are, so we dont get failed entry
			cmd_send('NICK '.$CONFIG['nick'].' '.$CONFIG['domain']);																		// Tell the server our nickname.
			while (!feof($con['socket'])) {													//This will loop until we disconnect.
				$con['buffer']['all'] = trim(fgets($con['socket'], 4096));		//Get the raw text from the server, and trim off whitespace.
				if(substr($con['buffer']['all'], 0, 6) == 'PING :') {					//Did we get PINGed? If so PONG them back to confirm that we're alive.
					cmd_send('PONG :'.substr($con['buffer']['all'], 6));
					switch ($firstTime) {														//If this is our first ping, then we should join a channel!
						case true:
							$i=0;
							cmd_send('JOIN '.$CONFIG['channel']);				//Join the channel
							sleep(4);																	// Wait 4 seconds, change this if you like...
							pm('chan', '---Logging started on: '.date('j.n.Y', time()));	//Tell the channel that we're logging their actions!
							$BLACKLIST = getblacklist();									//Activate the blacklist, and get all blacklisted users from the db.
							$firstTime = false;													// Make firsttime false, so we dont join again...
						break;
					}
				} elseif ($con['oldbuffer'] != $con['buffer']['all']) {						//If we werent pinged, then has the buffer changed at all?
					parse_buffer();
					$serve = strpos(strtoupper($con['buffer']['username']), strtoupper($CONFIG['servshort']));	//We want to ignore text sent by the server,
					if ($serve === false && $con['buffer']['text']!='' && substr(strtoupper($con['buffer']['text']),1,8)!='PASSWORD') { // its normally rules and stuff and just clogs up
						$log = date('H:i]')."< ".$con['buffer']['username'] . ":" . $con['buffer']['text'] ."\n";				//Our logs.
						print $log;
					}																																				//Comment out all of this if you really want/need it though.
					$_HOSTLIST[$con['buffer']['username']]=$con['buffer']['hostname'];
					process_commands();
				}
				$con['oldbuffer'] = $con['buffer']['all'];									//Put the new buffer into $con['oldbuffer'] so we can check for changes next time it cycles round.
			}
		break;
	}
}

//*******************************************************
//*****************File Logging******************************
function log_to_file ($data) {
	global $CONFIG;
	switch ($CONFIG['log']) {
		case true:
			$filename = 'irc.log';															//Yes. IRC.log....
			$data .= "\n\r";																		//Add the new line received, plus a line break.
			switch ($fp = fopen($filename, 'ab')) {								//Open for write/create.
				case true:																		//Did it open? Then write data!
        			switch (fwrite($fp, $data)) {										//Did it write? If it didnt then dont bomb out, just give a l'il error.
	        			case false:
		        			echo 'Could not write to file.';								//Dont die on error, as logging isnt imperitive.
	        			break;
					}
				break;
    			case false:																		//Couldnt open the file :(
	    			echo 'File could not be opened.';
	    		break;
			}
		break;
	}
}

//*******************************************************
//*****************Buffer Parsing******************************
function parse_buffer() {
	global $con, $CONFIG;
	$buffer = $con['buffer']['all'];
	$buffer = explode(' ', $buffer, 4);
	$buffer['username'] = substr($buffer[0], 1, strpos($buffer['0'],'!')-1);
	$posExcl = strpos($buffer[0],'!');
	$posAt = strpos($buffer[0],'@');
	$buffer['identd'] = substr($buffer[0], $posExcl+1, $posAt-$posExcl-1);
	$buffer['hostname'] = substr($buffer[0], strpos($buffer[0],'@')+1);
	$buffer['user_host'] = substr($buffer[0],1);
	switch (strtoupper($buffer[1]))
	{
		case 'JOIN':
		   	$buffer['text'] = '*JOINS: '. $buffer['username'].' ( '.$buffer['user_host'].' )';
			$buffer['command'] = 'JOIN';
			$buffer['channel'] = $CONFIG['channel'];
		   	break;
		case 'QUIT':
		   	$buffer['text'] = '*QUITS: '. $buffer['username'].' ( '.$buffer['user_host'].' )';
			$buffer['command'] = 'QUIT';
			$buffer['channel'] = $CONFIG['channel'];
		   	break;
		case 'NOTICE':
		   	$buffer['text'] = '*NOTICE: '. $buffer['username'];
			$buffer['command'] = 'NOTICE';
			$buffer['channel'] = substr($buffer[2], 1);
		   	break;
		case 'PART':
		  	$buffer['text'] = '*PARTS: '. $buffer['username']." ( ".$buffer['user_host']." )";
			$buffer['command'] = 'PART';
			$buffer['channel'] = $CONFIG['channel'];
		  	break;
		case 'MODE':
		  	$buffer['text'] = $buffer['username'].' sets mode: '.$buffer[3];
			$buffer['command'] = 'MODE';
			$buffer['channel'] = $buffer[2];
		break;
		case 'NICK':
			$buffer['text'] = '*NICK: '.$buffer['username'].' => '.substr($buffer[2], 1)." ( ".$buffer['user_host']." )";
			$buffer['command'] = 'NICK';
			$buffer['channel'] = $CONFIG['channel'];
		break;
		default:
			$buffer['command'] = $buffer[1];
			$buffer['channel'] = $buffer[2];
			$buffer['text'] = substr($buffer[3], 1);
		break;
	}
	$con['buffer'] = $buffer;
}

//*******************************************************
//*****************CMD Send******************************
function cmd_send($command) {
	global $con, $CONFIG;
	fputs($con['socket'], $command."\n\r");		//Put the command in the socket, so it gets sent to IRC.
	$command = explode(':',$command);		//Explode it so the short-term log looks less spammy.
	if($command[2]!='') {								//And if the message isnt empty then actually log it in the short-term log.
		echo "\n" . date('H:i]') ."> ". $CONFIG['nick'] . ":" .  $command[2]. "\n";
	}
}

//*******************************************************
//***********PM($chan for channel $uname for PM username)******
function pm($who, $msg) { // $who should either be a valid nickname, or valid channel name, $msg is the message to send,
	global $CONFIG, $_UNAME, $con;	//and $msgtype should be left blank, unless you want a /me, in which case use "ACTION "
	switch ($who) {
		case 'chan':
			$who=$CONFIG['channel'];
		break;
		case 'uname':
			$who=$_UNAME;
		break;
		default:
			$who=$CONFIG['channel'];
		break;
	}
	if(!empty($msg)){		//If the message isnt empty then go for it.
		cmd_send("PRIVMSG ".$who." :".str_replace('&nbsp;',' ',trim($msg))."\n");
		log_to_file(date('[H:i]',time()) . ' <' . $CONFIG['nick'] . '> '.$msg.'('.$who.')');
	}
}
//*******************************************************
//***********************QUIT****************************
function quit() {
	updatestats();
	pm('chan','---Logging stopped on: '. date('j.n.Y', time()));
	cmd_send('QUIT I know when I\'m not wanted!');
	exit;
}

//*******************************************************
//*****************Check if admin***************************
function isadmin($lvl,$hostname) {
	global $TEMPHOST;
	if($lvl==0) {
		return true;
	}
	if($TEMPHOST==$hostname) {				//Does the hostname match $TEMPHOST?
		return true; // In which case the user is a a temporary super admin, with all privs.
	}
	$result = query('SELECT level FROM admins WHERE hostname=\''.$hostname.'\'');	//Query the DB for the hostname
	$num=mysql_numrows($result);
	if($num==0) {
		return false; //We got no matches in the DB? Then the person isnt a mod.
	} else {
		$ulvl = mysql_result($result,0,'level');
		if($ulvl<$lvl) {
			return false; //Is the users level less than what is required for this command? The user can't use the command then.
		} else {
			return true;	//The user checks up, and has a sufficient level. Return true then.
		}
	}
}

//*******************************************************
//******Compile help makes arrays of all the functions.************
function compilehelp($cmd, $desc,$syntax,$exu = '',$cmdetc = '',$lvl=0) {
	global $HELPS, $HELPSd;
	if($HELPSd!=1) {							//Just fill out an array with all the func inf, so it can be called to later.
		$HELPS[$cmd]['exist'] = 1;
		$HELPS[$cmd]['syntax'] = $syntax;
		$HELPS[$cmd]['desc'] = $desc;
		$HELPS[$cmd]['cmdetc'] = explode('||',$cmdetc);
		$HELPS[$cmd]['exu'] = explode('||',$exu);
		$HELPS[$cmd]['lvl'] = $lvl;
	}
}

//*******************************************************
//****Parses through all the help crap and provides the answer!*****
function help($cmd) {
	global $HELPS, $con;
	$uname = $con['buffer']['username'];
	switch (strtoupper($cmd)) {
	/**/case '-VERBOSE':																		//If the user asked for -VERBOSE feedback, return help on every command in the list...
			foreach(array_keys($HELPS) as $key => $value) {
				echohelp($key);
				sleep(4);
			}
		break;/*You may want to comment this one out....*/
		case '-COMMANDLIST':														//If the user asked for -COMMANDLIST, then feedback all the available commands with help strings attached.
			$cmdlist = 'All available commands: ';
			$i=0;
			foreach($HELPS as $key => $value) {
				$cmdlist = $cmdlist . "\00304".$key."\003, ";
				if($i==29) {
					pm('uname', substr($cmdlist, 0, -2) . ".");
					$cmdlist='';
				}
				$i++;
			}
			pm('uname', substr($cmdlist, 0, -2) . ".");
		break;
		case '':																				// Did the user just type ".help"? Then just help her/him.
			pm('uname', 'Please use ".help <commandname>", for command list type ".help -commandlist"');
		break;
		default:																			// If all of the above isnt true, then the user is attempting to get help on a sepcific command, so give it.
			echohelp($cmd,1);
		break;
	}
}

//*******************************************************
//****Parses through all the help crap and provides the answer!*****
function echohelp($cmd,$vb=0) {
	global $con, $HELPS, $CONFIG;
	if($cmd!='' && $HELPS[$cmd]['exist'] == 1) {		//The command actually exists right?
		if(isadmin($HELPS[$cmd]['lvl'],$con['buffer']['hostname'])) {		//The user does have sufficient rights to use the command right?
			$usage = ' : Usage: ';
			$i = 0;
			$n = count($CONFIG['cmdprefix']);
			while($i<$n) {
				$usage = $usage."\00304".$CONFIG['cmdprefix'][$i].$HELPS[$cmd]['syntax']."\003 or ";			//Show example usages using available command prefixes.
				++$i;
			}
			$usage = substr($usage,0,-4) . '.';		//Cap of the last 4 letters, which would be " or "...
			if($HELPS[$cmd]['exu'][0]!='') {			//Does an example usage text exist? Use it then.
				$exusage = 'Example usage: "'.$CONFIG['cmdprefix'][0].$HELPS[$cmd]['exu'][0].'" results in "'.$HELPS[$cmd]['exu'][1].'".';
			}
			if($HELPS[$cmd]['cmdetc'][0]!='') {	//Does a "command aliases" text exist? use it then.
				$cmdetc = "Sub commands or other aliases: ";
				$i = 0;
				$n = count($HELPS[$cmd]['cmdetc']);
				while($i<$n) {
					$cmdetc = $cmdetc . $$HELPS[$cmd]['cmdetc'][$i] . ",";		//Loop through and add each alternate command.
					++$i;
				}
				$cmdetc = substr($cmdetc,0,-1) . ".";			//Scrap the last character (which will be a ,).
			} else {
				$cmdetc = ".";			//If no command aliases exist, then just add a full stop to the mix...
			}
			pm('uname', "\00304" . $cmd . "\003" . $usage . " \00312" . $HELPS[$cmd]['desc'] . ".\003 " . $exusage . $cmdetc);
			// CMD : Usage; .CMD or !CMD. Description. Example usage: ... . Sub commands or other aliases: ... .
		} elseif($vb=1) {		//If the user doesnt have sufficient rights to access the command, and silent mode is off, then give appropriate feedback.
			pm('uname',"Cannot receive help on $cmd: Insufficient Rights");
		}
	} elseif($vb=1) {	// If the command doesnt exist, and if silent mode is off, then give appropriate feedback:
		pm('uname',"Cannot receive help on $cmd: Command, or help for the command doesnt exist.");
	}
}

//*****************
//***Blacklist Check***
function getblacklist() {
	$result=query('SELECT hostname FROM blacklist');	//Fetch all the blacklisted hostnames in the table "blacklist"
	$num=mysql_numrows($result);
	$i=0;
	while($i<$num) {
		$BLACKLIST[$i]=mysql_result($result,$i,'hostname');
		$i++;
	}
	$BLACKLIST[$i] = ''; //this is to stop the "not a valid array type" msgs if the array only has one (or no) values in it.

	return $BLACKLIST;
}

//*******************************************************
//********Proccess The Commands so the bot does something!
function process_commands() {
	global $con, $CONFIG, $BLACKLIST, $_UNAME, $_HOSTNAME, $OTDB, $_loadedmodules, $TIMEIN;
	$_UNAME = $con['buffer']['username'];
	$_HOSTNAME = $con['buffer']['hostname'];
	$buff=strtoupper($con['buffer']['text']);
	$buffl=$con['buffer']['text'];
	if(!in_array($con['buffer']['hostname'],$BLACKLIST)) {				//Is the hostname of the sender in the blacklist? Dont let him use any commands then!
		$command = explode(' ', $con['buffer']['text'], 2);					// Take the first word out of the buffer, this will be the users command.
		$command = $command[0];
		if(in_array(substr($command,0,1),$CONFIG['cmdprefix'])) {	//Is the first character one of our CMD_Prefixes?
			$command = substr($command, 1);
			$a = strlen($command)+2;													//Get the command character length, and add 1 for the CMD syntax, and another for the space. Now you have the length to remove for the args.
			$function = 'cmd_'.strtolower($command);							//The name of the function, if it is a command, will be cmd_commandname. So lets get that
			$dir='.';
			if (function_exists($function)) {											// If the function exists, run it.
				$function(substr($con['buffer']['text'],$a));						//Activate the function and send it the arguments, username, hostname, channel.
			}
		}
		/* Welcomes a user when he joins */
		/***************************/
		$rnd=rand(0,1);																		//Put this here just to make things a little more natural... its annoying when he says "Hey Bob" all the time.
		if($con['buffer']['command']=='JOIN' && $_UNAME!=$CONFIG['nick']) {
			if($rnd==1) { pm('chan', 'Hey '.$_UNAME); }
			$TIMEIN[$_UNAME]=time();												//Get the time for .howlong
		}
		$n = count($_loadedmodules);
		$i=0;
		while($i<$n) {																			//Loop through all of the loaded modules, and load the functions that declare extra processing.
			$func = 'proc_'.substr($_loadedmodules[$i],0,-4);
			if (function_exists($func)) {
				$func($buffl);
			}
			++$i;
		}
		if(function_exists('proc_commands')) {
			proc_commands($buffl);
		}
	}
	//***************** Here we put the leftover stuff, mainly things to parse every message, like the log.
	$serve = strpos(strtoupper($con['buffer']['username']), strtoupper($CONFIG['servshort']));
	if ((in_array($con['buffer']['username'],$CONFIG['dontlog']))||($serve !== false)||($con['buffer']['username']=="")||($con['buffer']['username']==$CONIFG['channel'])) {
		// Don't log.
	} else {
		log_to_file(date('[H:i]',time()) . ' <' . $con['buffer']['username'] . '> ' . $buffl);
	}
}

//*******************Includes and globalvars*******************

include('commands.php');	//Includes the default command set.
//Define arrays:
$OTDB = array();
$BLACKLIST=array();
$con = array();
$_loadedmodules = array();
$_HOSTLIST=array();
$COL = array(
	'escall'=>"\017",		//Escape all formatting
	'esc'=>"\003",			//Esc is used to escape all colours, and SHOULD BE LEFT AS \003.
	'fail'=>"\00304",		//A colour for a failed attempt at something.
	'pass'=>"\00303",	//A colour for a successful attempt at something.
	'bold'=>"\002",		//Bold formatting.
	'uline'=>"\037"		//Underlined text.
);
//This is where the actual processing starts!
//Load commands and ini file.
loadcommands();
loadini();
set_time_limit(0);
echo "Initialising\n\n";
// Now we have loaded everything, lets connect!
init();
?>