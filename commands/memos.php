<?php
/*
		Memo module, by Keithamus
		this allows people to write
		memos to other channel users
		and also PMs users as they
		sign on with memos people have
		sent them. Basically a way of
		queuing up messages for offline
		users.

***REQUIREMENTS: read, write and create file access to the root folder that the bot sits in.
*/
compilehelp('MEMO', 'When <USER> logs on, it will send him/her <MESSAGE>. <USER> should either be the users nickname or the users hostname.', 'MEMO <USER> <MESSAGE>','JaneDoe>!MEMO JohnDoe Hello||(When JohnDow gets online)'. $CONFIG['nick'] . '>Memo from: JaneDoe: Hello');
function cmd_memo($args) {
	global $_UNAME, $CONFIG;
	$args = explode(' ',$args,2);
	$name = $args[0];
	$filename=md5(strtolower($name)) . '.memo';
	if($name!='' && $name!=$CONIFG['nick']) {
		$fp = fopen($filename, 'a');
		fputs($fp, 'Memo from: '.$_UNAME.': '.$args[1]."\n");
		fclose($fp);
		pm($_UNAME, 'Your memo has been added, ' . $name .' will get it when they next log on.');
	}
}
function proc_memos($buff) {
	global $CONFIG, $_UNAME, $_HOSTNAME;
	$file=md5(strtolower($_UNAME)).'.memo';
	if(strtoupper(substr($buff,0,8))=='*JOINS: ' && $_UNAME!=$CONFIG['nick'] && file_exists($file)) {
		$lines = file($file);
		$n = count($lines);
		$i = 0;
		while($i<$n) {
			pm($_UNAME, $lines[$i]);
			sleep(2);
			++$i;
		}
		unlink($file);
		$file=md5(strtolower($_HOSTNAME)).'.memo';
		$lines = file($file);
		$n = count($lines);
		$i = 0;
		while($i<$n) {
			pm($_UNAME, $lines[$i]);
			sleep(2);
			++$i;
		}
		unlink($file);
	}
}