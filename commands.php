<?php
//*******************************************************
//*****************Default command set *********************

	/* COLOURS*/
	/**********/
	compilehelp('COLOURS', 'Displays all of the colours available for usage on IRC', 'COLOURS');
	function cmd_colours($args) {
		global $COL;
		pm('chan', "\00300 0 \00301 1 \00302 2 \00303 3 \00304 4 \00305 5 \00306 6 \00307 7 \00308 8 \00309 9 \00310 10 \00311 11 \00312 12 \00313 13 \00314 14 \00315 15 \00316 16 "
			. $COL['fail'] . " Fail " . $COL['pass'] . " Pass " . $COL['escall'] . $COL['bold'] . " Bold  " . $COL['escall'] . $COL['uline'] . "Underlined "); //Echo out all colours.
	}
	/* HELP*/
	/******/
	compilehelp('HELP', 'Provides help on all commands', 'HELP', 'HELP HELP||(This message)', 'HELP <CMD>:Provides help specific to <CMD>');
	function cmd_help($cmd) {
		help(strtoupper($cmd),1);	//Just refer to the "help" function, with selected arguments...
	}

	//*****************Quit functions, like Restart, Bybye etc.
	/* RESTART */
	/*********/
	compilehelp('RESTART', 'Restarts the bot', 'RESTART','','',2);
	function cmd_restart($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$exe = 'D:\start.lnk';	//This is the command to re-open your bot. 
				$handle = popen($exe, "r" );
				pclose($handle);
				quit();
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* QUIT*/
	/******/
	compilehelp('BYEBYE', 'Makes the bot quit and shut down', 'BYEBYE','','',2);
	function cmd_byebye($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				quit();
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	//*****************Mod stuff, stuff that requires a mod/admin, i.e Nick.
	/* NICK */
	/******/
	compilehelp('NICK', 'Changes bot\'s nickname to <NICK>', 'NICK <NICK>','NICK Nickname||*>' . $CONFIG['nick'] . ' has changed his name to Nickname','',2);
	function cmd_nick($args) {
		global $_HOSTNAME, $COL, $CONFIG;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$nick = $args;
				cmd_send('NICK '. $nick);
				$CONFIG['nick']=$nick;
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	//*****************Mod management, ADD/DELETE/CHLVL etc.
	/* ADDMOD */
	/*********/
	compilehelp('ADDMOD', 'Allows anyone from <HOSTNAME> to execute commands allowed for Level <LVL> admins', 'ADDMOD <LVL> <HOSTNAME>','ADDMOD 3 127.0.0.1||' . $CONFIG['nick'] . '> 127.0.0.1 has been added to admins. Level 3 rights.','',3);
	function cmd_addmod($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$args = explode(' ',$args);
				$result = query('INSERT INTO admins (level,hostname) VALUES (\''.$args[0].'\',\''.$args[1].'\')');
				pm('chan', $COL['pass'].$args[1].' added to admins. Level '.$args[0].' rights.');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* DELMOD */
	/*********/
	compilehelp('DELMOD', 'Deletes <HOSTNAME> from admin list, and removes all privelages', 'DELMOD <HOSTNAME>','DELMOD 127.0.0.1||' . $CONFIG['nick'] . '> 127.0.0.1 deleted from admins.','',3);
	function cmd_delmod($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$result = query('DELETE FROM admins WHERE hostname=\''.$args.'\'');
				pm('chan', $COL['pass'].$args.' deleted from admins.');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* CHLVLMOD */
	/***********/
	compilehelp('CHLVLMOD','Changes <HOSTNAME>\'s already existing level to <LVL>', 'CHLVLMOD <LVL> <HOSTNAME>','CHLVLMOD 2 127.0.0.1||'.$CONFIG['nick'].'> 127.0.0.1 had their level changed to 2 by User','',3);
	function cmd_chlvlmod($args) {
		global $_HOSTNAME, $COL, $_UNAME;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$args = explode(' ', $args);
				$result = query('UPDATE admins SET level=\''.$args[0].'\' WHERE hostname=\''.$args[1].'\'');
				pm('chan', $COL['pass'].$args[1] . ' had their level changed to '.$args[0].' by '.$_UNAME.'.');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* ADDBLACKLIST */
	/**************/
	compilehelp('ADDBLACKLIST', 'Adds <HOST> to the blacklist', 'BLACKLIST <HOST>');
	function cmd_addblacklist($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$result=query('INSERT INTO blacklist (hostname) VALUES(\''.$args.'\')');
				$BLACKLIST=getblacklist();
				pm('chan', $COL['pass'].'Added to blacklist');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* REMBLACKLIST */
	/**************/
	compilehelp('DELBLACKLIST', 'Deletes <HOST> from the blacklist', 'DELBLACKLIST <HOST>');
	function cmd_delblacklist($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$result=query('DELETE FROM blacklist WHERE hostname=\''.$args.'\'');
				$BLACKLIST=getblacklist();
				pm('chan', $COL['pass'].'Removed from blacklist');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* FLUSHBLACKLIST */
	/****************/
	compilehelp('FLUSHBLACKLIST', 'Deletes everyone from the blacklist', 'FLUSHBLACKLIST');
	function cmd_flushblacklist($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				$result=query('DELETE FROM blacklist');
				$BLACKLIST='';
				pm('chan', $COL['pass'].'Blacklist Flushed');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* BLACKLIST */
	/***********/
	compilehelp('BLACKLIST', 'Refreshes the blacklist', 'BLACKLIST');
	function cmd_blacklist($args) {
		$BLACKLIST=getblacklist();
		pm('chan', 'Blacklist updated.');
	}
	/**TMPHOST**/
	/***********/
	compilehelp('TMPHOST', 'Gives admin (level 3) privelages to the hostname the message comes from, provided they have the correct password', 'TMPHOST <PASSWORD>','TMPHOST <PASSWORD>||'.$CONFIG['nick'].'> You have been added to temp host.','',1);
	function cmd_tmphost($args) {
		global $_HOSTNAME, $COL, $CONFIG;
		$args = $args;
		switch ($args) {
			case $CONFIG['admin_pass']:
				$TEMPHOST=$_HOSTNAME;
				pm('chan', $COL['pass'].'You have been added to temp host.');
			break;
			default:
				pm('uname', $COL['fail'].'Error: Incorrect Password');
			break;
		}
	}
	//***************** Functions that dont fit the above cats.
	/* CLEARLOG */
	/***********/
	compilehelp('CLEARLOG','Clears the IRC logs (for good!)','CLEARLOG','','',3);
	function cmd_clearlog($args) {
		global $_HOSTNAME, $COL;
		switch (isadmin(2,$_HOSTNAME)) {
			case true:
				unlink('irc.log');
				pm('chan', $COL['pass'].'The logs have been cleared');
				pm('chan', '---Logging started on: '.date('j.n.Y', time()));
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
	}
	/* HOST */
	/*******/
	compilehelp('HOST', 'Publicises <USERNAMES> hostname.', 'HOST <USERNAME>');
	function cmd_host($args) {
		global $_HOSTLIST, $CONFIG;
		switch($_HOSTLIST[$args]) {
			case '':
				pm('chan', 'User '.$args.' does not exist.');
			break;
			case $CONFIG['nick']:
				pm('chan', 'Sorry. No, you cant have my hostname.');
			break;
			default:
				pm('chan', $_HOSTLIST[$args]);
			break;
		}
	}
	/* HOWLONG */
	/***********/
	compilehelp('HOWLONG','Tells the channel how many seconds <USERNAME> has been in the channel.','HOWLONG <USERNAME>');
	function cmd_howlong($username) {
		global $TIMEIN;
		switch($TIMEIN[$username]) {
			case '';
				help('HOWLONG');
			break;
			default:
				pm('chan', time()-$TIMEIN[$username].' seconds.');
			break;
		}
	}
	// .printbuffer is a diagnostic command.
	function cmd_printbuffer($args) {
		global $con;
		print_r($con['buffer']);
	}
	// .reload loads up added command modules inside of command/
	function cmd_reload($args) {
		global $_loadedmodules;
		loadcommands();
		loadini();
		print_r($_loadedmodules);
		pm('chan', 'Reloading command modules');
	}