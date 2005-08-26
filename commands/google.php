<?php
/*
		Google features module, by Keithamus
		  in this we have .g or .google for an
				"Im Feeling Lucky" seach
		We also have .trans for google translate.
		     And .def for google define

***REQUIREMENTS: none***
*/
/* GOOGLE*/
/********/
compilehelp('G', 'Does an "I\'m Feelin\' Lucky" search for <SEARCH>','G <SEARCH>','G CHEESE||'.$CONFIG['nick'].'> http://www.cheese.com/','GOOGLE:Provides same use.');
function cmd_google($query) {
	global $OTDB, $COL;
	switch ($OTDB['ison']) {	//If OTDB doesnt exist, then GOOGLE will work, if it does exist, and its active, then google will not work.
		case 0:
			$errno='';
			$errstr='';
			$fh = fsockopen('google.com', 80, $errno, $errstr, 10);
			switch ($fh){
				case '':
					$msg = 'ERR_TIMEOUT';	//Couldnt connect to google.com?
				break;
				default:
					$earl = 'search?hl=en&ie=UTF-8&oe=UTF-8&q='.urlencode($query).'&btnI';
					$hd .= "GET /".$earl." HTTP/1.0\r\nUser-Agent: Mozilla ".time()."\r\nConnection: Close\r\n\r\n";	//Use Mozillas "Im feelin lucky" keyword search.
					fputs($fh, $hd);
	 				while(!feof($fh)){
						$res[] = fgets($fh, 128);
					}
					fclose($fh);
					$hay = explode('Location: ', $res[1]);
					switch ($hay[1]) {
						case '':
							$msg = $COL['fail'] . 'Sorry, no results';
						break;
						default:
							$msg = trim($COL['pass'] . $hay[1]);
						break;
					}
				break;
			}
			pm('chan', $msg);
		break;
		default:
			pm('chan','Sorry, cant use google when Open Trivia is activated!');
		break;
	}
}
function cmd_g($args) {
	cmd_google($args);
}
/* DEFINE */
/********/
compilehelp('DEF','Gives a definition for <WORD>','DEF <WORD>');
function cmd_def($arg) {
	global $OTDB;
	switch ($OTDB['ison']) {	//If OTDB doesnt exist, then GOOGLE will word, if it does exist, and its active, then google will not work.
		case 0:
			$def = file_get_contents("http://www.google.com/search?q=define:$arg");
			$def = explode('<li>', $def);
			$def = explode('  ', $def[1]);
			$def = explode('<br>', $def[0]);
			$def = $def[0];
			if($def!='' && $def!=$arg) {
				pm('chan',"$arg: \00314$def");
			} else {
				pm('chan','Sorry, there doesnt seem to be a definition for '.$arg);
			}
		break;
		default:
			pm('chan', 'Sorry, cant use google when Open Trivia is activated!');
		break;
	}
}
/* TRANS*/
/*******/
compilehelp("TRANS", "Translates <TEXT> using the <TRANSCODE> codes", "TRANS <TRANSCODE> <TEXT>","TRANS en|de Cheese||" . $CONFIG['nick'] . "> You asked to translate Cheese into en|de, I came up with Käse");
function cmd_trans($qbuff) {
	global $OTDB, $COL;
	switch ($OTDB['ison']) {	//If OTDB doesnt exist, then GOOGLE will word, if it does exist, and its active, then google will not work.
		case 0:
			$lang = substr($qbuff,0,5);
			$text = substr($qbuff,6);
			switch ($lang) {
				case (($lang=="")||($text=="")):
					help('TRANS');
				break;
				case ($lang!="en|de" && $lang!="en|es" && $lang!="en|fr" && $lang!="en|it" && $lang!="en|pt" && $lang!="en|ja" && $lang!="en|ko"  && $lang!="de|en" && $lang!="de|fr" && $lang!="es|en" && $lang!="fr|en" && $lang!="fr|de" && $lang!="it|en" && $lang!="pt|en" && $lang!="ja|en" && $lang!="ko|en"):
					pm('chan',"Please use one of the following langauge codes: en|de , en|es , en|fr , en|it , en|pt , en|ja , en|ko , de|en , de|fr , es|en , fr|en , fr|de , it|en , pt|en , ja|en , ko|en.");
				break;
				default:
					$handle = popen("curl http://translate.google.com/translate_t -d \"langpair=" . $lang . "&text=" . $text . "&hl=en&ie=UTF8\" -A 'Mozilla/3.0' > tmp.txt 2>&1", "r" );
					pclose($handle);
					$a = file_get_contents("tmp.txt");
					$a = explode("textarea",$a);
					$a = substr($a[1],37,-2);
					unlink("tmp.txt");
					switch(strtoupper($text)) {
						case strtoupper($a):
							pm('chan',"\00310$text\003 could not be translated. (\00303$a\003 was returned).");
						break;
						case (substr($a,0,9) == "readonly>"):
							pm('chan',"You asked to translate \00310$text\003 into \00314$lang\003, I came up with \00303" . substr($a,9) . "\003.");
						break;
						default:
							pm('chan',"You asked to translate \00310$text\003 into \00314$lang\003, I came up with \00303$a\003.");
						break;
					}
				break;
			}
		break;
		default:
			pm('chan', 'Sorry, cant use google when Open Trivia is activated!');
		break;
	}
}