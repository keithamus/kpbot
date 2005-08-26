<?php
/*
			Talky features, by Keithamus
		  	just a load of basic talking
		  	functions for the bot. Aswell
		  	as a fairly complex 8ball trick.

***REQUIREMENTS: none***
*/
//*****************Basic functions, like Time, Say, Me, etc.
/* TIME */
/******/
compilehelp('TIME', 'PMs you the time and date', 'TIME','TIME||'. $CONFIG['nick'] . '>The time is July 12, 2005, 11:25 pm', 'TIME -P: Displays the time inside the channel');
function cmd_time($args) {
	$time = 'The time is ' . date('F j, Y, g:i a', time()); //e.g "The time is August 3, 2005, 3:20 pm"
	switch(strtoupper($args)) {	//If the argument was "-P" then we need to say the message to the channel instead of a user:
		case '-P':
			pm('chan',$time);
		break;
		default:
			pm('uname',$time);
		break;
	}
}
/* SAY*/
/*****/
compilehelp('SAY', 'Says <MSG> to the channel', 'SAY <MSG>','SAY HELLO||' . $CONFIG['nick'] . '> Hello');
function cmd_say($msg) {
	pm('chan', $msg);	//Just return the msg.
}
/* /ME */
/*****/
compilehelp('ME', 'Acts out <MSG> to the channel. (Basically /me <MSG>)', 'ME <MSG>', 'ME WAVES||*>' . $CONFIG['nick'] .'WAVES');
function cmd_me($msg) {
	pm('chan', chr(1)."ACTION ".$msg.chr(1)."\n");	//Return args, but also add ACTION before, to denote a /me command.
}
/* PM */
/****/
compilehelp('PM', 'Sends <MESSAGE> directly to <USER>', 'PM <USER> <MESSAGE>', 'PM JohnDoe Hello||(PM to Johndoe)' . $CONFIG['nick'] . '>Hello');
function cmd_pm($args) {
	$args = explode(" ",$args,2);	//Explode the args, by the first space, so we have <USER> <MESSAGE WITH SPACES>
	if(count($args)==2) {
		pm($args[0],$args[1]);		//PM back, args[0] being name, 1 being message with spaces.
	} else {
		help('PM');							//If the user hasnt given sufficient args, then chuck them the help for PM.
	}
}
/* SHOUT */
/*********/
compilehelp('SHOUT', 'Makes '.$CONFIG['nick'].' shout <MESSAGE>.', 'SHOUT <MESSAGE>', 'SHOUT Hello||' . $CONFIG['nick'] . '>HELLO!!!');
function cmd_shout($msg) {
	pm('chan',strtoupper($msg).'!!!');
}
/* DECIDE */
/********/
compilehelp('DECIDE', 'Gives you a decision based on the inputs you provide', 'DECIDE <option1> OR <option2> OR...');
function cmd_decide($args) {
	$dec = explode('?',$args);
	$dec = explode(' OR ',substr($dec[1],1));	//Explode the var, so we can get each OR statement.
	$a = rand(0,count($dec)-1);						//Set a random number between 0 and the number of OR statements (-1 to exclude surplus).
	while($dec[$a]=='') {									//If the decision is empty, then try a regular decide expression.
		$dec = explode(' OR ',$args);
		$a = rand(0,count($dec));
	}
	pm('chan',"\00308" . $dec[$a]);					//PM the desicion.
}
//******************The proccess command, for extra, non-command proccessing
function proc_talky($buffl) {
	global $CONFIG;
	$buff=strtoupper($buffl);
	/* The eight ball trick (gives an 8Ball answer to people who say "BOTNAME QUESTION?" or "QUESTION BOTNAME?" */
	/*******************************************************************************************/
	if((substr($buff,0,strlen($CONFIG['nick']))==strtoupper($CONFIG['nick']) && substr($buff,-1) == "?")||(substr($buff,-strlen($CONFIG['nick'] . "?")) == strtoupper($CONFIG['nick']) . "?")) {
		$eightball=array("Signs point to yes", "It is certain", "As I see it, yes", "Better not tell you now", "My sources say no", "Very doubtful", "Ask again later", "Don't count on it", "Cannot predict now", "Yes", "Concentrate and ask again", "Most likely", "Reply hazy, ask again", "You may rely on it", "Without a doubt", "My reply is no", "Outlook not so good", "Outlook good", "It is decidedly no", "Yes definatley");
		$a = rand(0,count($eightball)-1);
		pm('chan',"\00303" . $eightball[$a]);
	}
}