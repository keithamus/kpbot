<?php
/*
		OTDB Trivia module, by Keithamus
		 in this we have all the functions
		 for a fully functional trivia system

***REQUIREMENTS: MySQL tables: users, pendingq and questions.***
*/

/* STARTOTDB */
/***********/
compilehelp('TRIVIA', 'Starts the Open Trivia game.', 'TRIVIA', '', 'STARTOT:Provides the same use');
function cmd_startot($args) {
	global $OTDB, $COL;
	switch($OTDB['ison']) {			//If open trivia is already on, dont start it up:
		case 1:
			pm('uname', $COL['fail'] . 'Open Trivia has already started!');
		break;
		default:							//Otherwise, start it up!
			pm('chan',$COL['pass'] . 'Welcome to the Open Trivia Data Base! Question time!');
			OTDBask();					//Ask the first question.
		break;
	}
}
function cmd_trivia($args) {
	cmd_startot($args);
}
/* STOPOTDB */
/***********/
compilehelp('STOPOT','Stops the Open Trivia game','STOPOT','','',1);
function cmd_stopot($args) {
	global $OTDB, $COL, $_HOSTNAME;
	switch($OTDB['ison']) {
		case 0:
			pm('uname', $COL['fail'].'Open Trivia hasnt started!');
		break;
		default:
		switch(isadmin(1,$_HOSTNAME)) {
			case true:
				pm('chan', $COL['pass'].'Open Trivia Database has been stopped! No more questions!');
				$OTDB['ison']=0;
			break;
			default:
				pm('uname', $COL['fail'].'Error: Insufficient Rights');
			break;
		}
		break;
	}
}
/* NOONEKNOWS*/
/**************/
compilehelp('NEXTQ','Gives the answer to the current question, and moves onto the next one.','NEXTQ','','NOONEKNOWS: Provides the same use||NOONECARES: Provides the same use',1);
function cmd_nextq($args) {
	global $OTDB, $COL, $_HOSTNAME;
	switch ($OTDB['ison']) {
		case 0:
			pm('uname', $COL['fail'].'Open Trivia isnt on!');
		break;
		default:
			switch(isadmin(1,$_HOSTNAME)) {
				case true:
					OTDBtimeout();
				break;
				default:
					pm('uname', $COL['fail'].'Error: Insufficient Rights');
				break;
			}
		break;
	}
}
function cmd_nooneknows($args) {
	cmd_nextq($args);
}
function cmd_nooneacares($args) {
	cmd_nextq($args);
}
/* PENDINGLIST */
/*************/
compilehelp('PENDINGLIST','Lists all of the questions currently in the pending questions database','PENDINGLIST','','',1);
function cmd_pendinglist($args) {
	global $OTDB, $COL, $_HOSTNAME;
	switch(isadmin(1,$_HOSTNAME)) {
		case true:
			$result = query("SELECT * FROM pendingq ORDER BY question DESC");
			$num=mysql_numrows($result);
			switch($num) {
				case ($num>0):
					$i=0;
					while($i<11 && $i<$num) {
						$resultset=mysql_fetch_array($result);
						pm('uname','ID:'.$resultset['id'].'  Q:'.$resultset['question'].'? A: '.$resultset['answer'].' Pt: '.$resultset['points']);
						++$i;
					}
					pm('uname','To send a pending q to the main q db, please type ".sendpend ID" where ID is the id of the question.');
				break;
				default:
					pm('uname',$COL['fail'].'There are no questions pending!');
				break;
			}
		break;
		default:
			pm('uname',$COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* SENDPEND*/
/**********/
compilehelp('SENDPEND','Moves Question <ID> from the Pending Questions database to the Questions database','SENDPEND <ID>','','',1);
function cmd_sendpend($id) {
	global $OTDB, $COL, $_HOSTNAME;
	switch(isadmin(1,$_HOSTNAME)) {
		case true:
			$result = query('SELECT * FROM pendingq WHERE id=\''.$id.'\'');
			$num=mysql_numrows($result);
			switch ($num) {
				case 0;
					pm('uname', $COL['fail'].'Sorry, invalid ID');
				break;
				$resultset=mysql_fetch_array($result);
				$result = query('INSERT INTO questions (question, answer, points) VALUES (\''.$resultset['question'].'\',\''.$resultset['answer'].'\',\''.$resultset['points'].'\')');
				$result = query('DELETE FROM pendingq WHERE id=\''.$id.'\'');
				pm('uname',$COL['pass'].$id.'Added, the enty from pendingq has also been deleted.');
			}
		break;
		default:
			pm('uname', $COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* DELPEND*/
/*********/
compilehelp('DELPEND','Deletes question <ID> from the Pending Question database','DELPEND <ID>','','',1);
function cmd_delpend($id) {
	global $OTDB, $COL, $_HOSTNAME;
	switch(isadmin(1,$_HOSTNAME)) {
		case true:
			$result = query('SELECT id FROM pendingq WHERE id=\''.$id.'\'');
			$num=mysql_numrows($result);
			switch($num) {
				case 0:
					pm('uname', $COL['fail'].'Sorry, invalid ID');
				break;
				default:
					$result = query('DELETE FROM pendingq WHERE id=\''.$id.'\'');
					pm('uname',$COL['pass'].$id.'deleted');
				break;
			}
		break;
		default:
			pm('uname', $COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* FLUSHSCORES */
/**************/
compilehelp('FLUSHSCORES','Clears the open trivia scores table','FLUSHSCORES','','',1);
function cmd_flushscores($args) {
	global $OTDB, $COL, $_HOSTNAME;
	switch(isadmin(1,$_HOSTNAME)) {
		case true:
			$result = query('UPDATE users SET points=\'0\'');
			pm('chan', $COL['pass'].'Scores flushed from Open Trivia');
		break;
		default:
			pm('uname', $COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* FIX0POINTERS */
/**************/
compilehelp('FIX0POINTERS','Fixes all of the questions which have 0 point awards','FIX0POINTERS','','',1);
function cmd_fix0pointers($args) {
	global $OTDB, $COL, $_HOSTNAME;
	switch (isadmin(1,$_HOSTNAME)) {
		case true:
			$query=query('SELECT * FROM questions WHERE points=\'0\'');
			$num = mysql_numrows($query);
			$i=0;
			switch($num) {
				case ($num>0):
					while($i<$num) {
						$id = mysql_result($query,$i,'id');
						$rnd = rand(1,5);
						$result = query('UPDATE questions SET points=\''.$rnd.'\' WHERE id=\''.$id.'\'');
						echo "Updated $id with $rnd\n";
						++$i;
					}
					pm('chan', $COL['pass'].'Fixed '.$num.' 0 point errors.');
				break;
				default:
					pm('chan', $COL['fail'].'There are no 0 Pointers in the database. But good job for checking.');
				break;
			}
		break;
		default:
			pm('uname', $COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* BATCHADDQ */
/************/
compilehelp('BATCHADDQ','Adds all of the questions listed in <FILE> (<FILE> must be on host machine)','BATCHADDQ <FILE>','','',3);
function cdm_batchaddq($filename) {
	global $OTDB, $COL, $_HOSTNAME;
	switch(isadmin(3,$_HOSTNAME)) {
		case true:
			$myFile = fopen($filename,'r');
			if(!$myFile){
				pm('uname', $COL['fail'].'File could not be opened.');
			} else {
				$fcontents = file($filename);
				pm('chan', 'Batch adding questions to database.');
				while (list ($line_num, $line) = each ($fcontents)) {
					switch(substr(strtoupper($line),0,9)) {
						case  '.OTDBADDQ':
							$args = substr($line,10);
						break;
						case '!OTDBADDQ':
							$args = substr($line,10);
						break;
						case((substr(strtoupper($line),0, 24) == '/MSG PETMONKEY .OTDBADDQ')||(substr(strtoupper($line),0, 24) == '/MSG PETMONKEY !OTDBADDQ')):
							$args = substr($line,25);
						break;
						default:
							$args=$line;
						break;
					}
					addq($args,1);
				}
				pm('chan', $COL['pass'].'Completed batch adding questions to database.');
			}
		break;
		default:
			pm('uname', $COL['fail'].'Error: Insufficient Rights');
		break;
	}
}
/* TRIVQNO */
/**********/
compilehelp('TRIVQNO','Tells you the amount of questions that reside in the Open Trivia database','TRIVQNO');
function cdm_trivqno($args) {
	global $OTDB, $COL, $_HOSTNAME;
	$result = query('SELECT id FROM questions');
	$num = mysql_numrows($result);
	pm('chan',$COL['pass'].'The Open Trivia Database currently has '.$num.' questions in it!');
}
/* MYINFO */
/********/
compilehelp('MYINFO','Tells you how many points you currently have, and how many you are away from first place in the Open Trivia game','MYINFO','','STATSME: Provides the same use');
function cdm_myinfo($args) {
	global $OTDB, $COL, $_HOSTNAME, $_UNAME;
	$result = query('SELECT * FROM users WHERE (username = \''.$_UNAME.'\')');
	$num=mysql_numrows($result);
	switch($num) {
		case 0:
			pm('chan', $COL['fail'].$_UNAME.', you haven\'t anwered a single question right!! Everyone point and laugh at '.$_UNAME.'!');
		break;
		default:
			$points=mysql_result($result,0,'points');
			switch($points) {
				case 0:
					pm('chan', $COL['fail'].$_UNAME.', you haven\'t anwered a single question right!! Everyone point and laugh at '.$_UNAME.'!');
				break;
				default:
					$result = query('SELECT * FROM users ORDER BY points DESC');
					$toppoints=mysql_result($result,0,'points');
					$pdiff = $toppoints-$points;
					switch($points) {
						case ($points >= $toppoints):
							pm('chan','Who\'s the daddy??? You the daddy! You\'re top of the list with '.$points.'!!!');
						break;
						default:
							pm('chan', 'Well '.$_UNAME.', you have '.$points.'. Thats '.$pdiff.' away from the number 1 slot (of '.$toppoints.'), keep at it!');
						break;
					}
				break;
			}
		break;
	}
}
/* OTDBADDQ */
/***********/
compilehelp('OTDBADDQ','Adds the Question to the Pending Database','OTDBADDQ <QUESTION>|<ANSWER>|','OTDBADDQ Spell Test|Test||'.$CONFIG['nick'].'> Your question has been added to pendingq');
function cmd_otdbaddq($args) {
	addq($args);
}
/* TRIVTOPX */
/***********/
compilehelp('TRIVTOP','Lists the top 10 scorers in the Open Trivia game','TRIVTOP','','TOPTRIV: Provides the same use||TOPTRIV<X>: Lists the top <X> scorers||TRIVTOP<X>: Lists the top <X> scorers');
function cmd_trivtop($args) {
	global $OTDB, $_UNAME;
	$result = query('SELECT * FROM users ORDER BY points DESC');
	$num=mysql_numrows($result);
	$i=0;
	switch($args) {
		case '':
			$topn = $num;
			$chan=$_UNAME;
		break;
		default:
			$topn = $args;
			$chan=$_UNAME;
		break;
	}
	while($i<$topn && $i<$num) {
		$user=mysql_result($result,$i,'user');
		$points=mysql_result($result,$i,'points');
		$place = $i+1;
		pm($chan, $place.'.	(\00310'.$points.'\003)	\00307'.$user.'\003.');
		sleep(1);
		++$i;
	}
}
function cmd_toptriv($args) {
	cmd_trivtop($args);
}
/* HINT*/
/******/
compilehelp('HINT','Gives you a hint about the current Open Trivia question, careful, too many hints and the question will end!', 'HINT');
function cmd_hint($args) {
	global $OTDB, $COL;
	switch($OTDB['ison']) {
		case 1:
			$len = strlen($OTDB['answer']);
			$a=$len-2;
			if($OTDB['hanglen']>1) {
				$OTDB['points']--;
				switch($OTDB['points']) {
					case 1:
						$OTDB['pnts']="\00303 1 \003";
						$s='';
					break;
					case 2:
						$OTDB['pnts']="\00309 2 \003";
						$s='s';
					break;
					case 3:
						$OTDB['pnts']="\00312 3 \003";
						$s='s';
					break;
					case 4:
						$OTDB['pnts']="\00304 4 \003";
						$s='s';
					break;
				}
			}
			if(($OTDB['hanglen']>=$a)||($OTDB['points']<1)) {
				OTDBtimeout();
				$OTD['points']==1;
			} else {
				$OTDB['hanglen']++;
				$letter=substr($OTDB['answer'],0,$OTDB['hanglen']);
				$hangman=$hangman.$letter.' ';
				$i=$OTDB['hanglen']+1;
				while($i<=$len) {
					$hangman=$hangman . '_  ';
					++$i;
				}
				switch($OTDB['hanglen']) {
					case ($OTDB['hanglen']>2):
						pm('chan', substr($hangman,0,-2) . '. (This has now been reduced to' . $OTDB['pnts'] . 'points).');
					break;
					default:
						pm('chan', substr($hangman,0,-2) . '.');
					break;
				}
			}
		break;
		default:
			pm('chan', $COL['fail'].'Open Trivia isnt on!');
		break;
	}
}
//***************The extra Process command:
function proc_trivia($buffl) {
	global $OTDB, $_UNAME;
	$buff=strtoupper($buffl);
	/* OTDB (check it the user has attempted an answer) */
	/******************************************/
	if($OTDB['ison'] == 1) {
		$answer = strpos($buff, strtoupper($OTDB['answer']));
	 	if($answer !== false) {
			OTDBgotright($_UNAME);
		}
	}
}
//*******************************************************
//*****************ASK A Question**************************
function OTDBask() {
	global $con, $CONFIG, $OTDB, $MYSQL, $_UNAME, $otdbb, $otdbc, $otdba;
	$result = query('SELECT * FROM questions LIMIT 10000');
	$num = mysql_numrows($result);
	$num--;
	$i=0;
	//This block of code is to prevent the same question occuring within the past 3 questions:
	while(($i==$otdbb)||($i==$otdbc)||($i==$otdba)) {
		$i = rand(0,$num);
	}
	$otdbc=$otdbb;
	$otdbb = $otdba;
	$otdba=$i;
	//Fill out array with results from query:
	$OTDB['question']=stripslashes(mysql_result($result,$i,'question'));
	$OTDB['answer']=stripslashes(mysql_result($result,$i,'answer'));
	$OTDB['points']=mysql_result($result,$i,'points');
	//Check for duplicates
	$query=query('SELECT * FROM questions WHERE question=\''.addslashes($OTDB['question']).'\'');
	$x = mysql_numrows($query);
	if($x>1) {
		pm('chan', 'Oops, we have a duplicate entry... fixing...');
		$query=query('DELETE FROM questions WHERE question=\''.addslashes($OTDB['question']).'\' AND points!=\''.$OTDB['points'].'\'');
	}
	/* sort out question and ask it*/
	switch($OTDB['points']) {
		case 1:
			$OTDB['pnts']="\00303 1 \003";
			$s='';
		break;
		case 2:
			$OTDB['pnts']="\00309 2 \003";
			$s='s';
		break;
		case 3:
			$OTDB['pnts']="\00312 3 \003";
			$s='s';
		break;
		case 4:
			$OTDB['pnts']="\00304 4 \003";
			$s='s';
		break;
		case 5:
			$OTDB['pnts']="\00307 5 \003";
			$s='s';
		break;

	}
	$len = strlen($OTDB['answer']);
	$i=1;
	$hangman='';
	while($i<$len) {
		$hangman=$hangman . '_  ';
		++$i;
	}
	$countwords=explode(' ',$OTDB['answer']);
	foreach($countwords as $value) {
		$wlens = $wlens . strlen($value) . ',';
	}
	$hangman=$hangman . '_. ' . substr($wlens,0,-1) . '.';
	$OTDB['hanglen']=0;
	pm('chan', "And for" . $OTDB['pnts'] . "point$s, \00310" . $OTDB['question'] . "?\003 (" . $hangman . ")");
	$OTDB['ison']=1;
}
//*******************************************************
//*****************Process if the user got it right***************
function OTDBgotright($_UNAME) {
	global $CONFIG, $OTDB, $MYSQL, $_UNAME;
	$OTDB['ison']=0;
	switch($OTDB['points']) {
		case 1:
			$s='';
		break;
		default:
			$s='s';
		break;
	}
	pm('chan','Congrats '.$_UNAME.', your right, the answer was '.$OTDB['answer'].'. You win '.$OTDB['points'].' point'.$s.'!');
	$result = query('SELECT * FROM users WHERE username=\''.$_UNAME.'\'');
	$num=mysql_numrows($result);
	switch($num) {
		case 0:
			$result = query('INSERT INTO users (username,points) VALUES (\''.$_UNAME.'\',\''.$OTDB['points'].'\')'); //Add the user into the DB
		break;
		default:
			$points = mysql_result($result,0,'points');
			$points = $points + $OTDB['points'];
			$result = query('UPDATE users SET points=\''.$points.'\' WHERE (username = \''.$_UNAME.'\')'); //User is already in DB, so update info instead.
		break;
	}
	sleep(4);
	OTDBask();
}
//*******************************************************
//*****************Timeout!*******************************
function OTDBtimeout() {
	global $CONFIG, $OTDB;
	pm('chan', 'Oooh, too bad! The answer was: '.$OTDB['answer']);
	sleep(4);
	OTDBask();
}
//*******************************************************
//*****************ADD QUESTION TO DB*********************
function addq($argline, $silent=0) {
	global $con, $CONFIG, $OTDB, $MYSQL;
	$_UNAME = $con['buffer']['username'];
	$args = explode('|', $argline);
	if($args[2]!=1 && $args[2]!=2 && $args[2]!=3 && $args[2]!=4 && $args[2]!=5) {
		$args[2]=rand(1,5);
	}
	switch(count($args)) {
		case (count($args) < 3):
			switch($silent) {
				case 0:
					help('OTDBADDQ');
				break;
			}
		break;
		default:
			switch(isadmin(1,$con['buffer']['hostname'])) {
				case true:
					$table = 'questions';
				break;
				default:
					$table = 'pendingq';
				break;
			}
			$result = query('INSERT INTO '.$table.' (question, answer, points) VALUES (\''.addslashes($args[0]).'\',\''.addslashes($args[1]).'\',\''.addslashes($args[2]).'\')');
			switch($silent) {
				case 0:
					pm('uname',$_UNAME.', your question has been added to "'.$table.'".');
				break;
			}
	}
	switch($silent) {
		case 0:
			sleep(1);
		break;
	}
}