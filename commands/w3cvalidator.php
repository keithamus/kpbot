<?php
/*
		  W3C Validator Module, uses the
		 W3C Validator to check if a website
		 		has valid HTML or not.

***REQUIREMENTS: None***
*/

/* THE W3C VALIDATOR, .VALID*/
/*************************/
compilehelp('VALID','Checks with W3C to see if <URL> is valid HTML/XHTML','VALID <URL>','VALID http://www.opposingsimplicity.com/||'.$CONFIG['nick'].'>This page is valid!');
function cmd_valid($url) {
	$file = file_get_contents('http://validator.w3.org/check?verbose=1&uri='.$url);		//Get the HTML from the page.
	$handler = explode('<td colspan="2" class="',$file);										//Keep exploding until all we are left with
	$handler = explode('</td>', $handler[1]);																	//Is a numer; pertaining to the
	$break = array('invalid">', 'valid">');
	$errno = $handler[0];
	$valid = str_replace($break,'',$errno);
	$errno = substr($valid,26,-17);
	switch ($valid) {																											//If the error number is empty, there is a possibility that we have either a valid page
		case '':																														//Or an incorrect URL error.
			pm('chan','You entered an invalid URL, please check the URL you entered');	//Incorrect URL
		break;
		case ($errno>1):
		switch ($errno) {
			case($errno>0&&$errno<11):
				pm('chan',"\00304Aahhh, its no so bad, $url only has \00308$errno\00304 errors.");		//Page has between 1 and 10 errors.
			break;
			case($errno>10&&$errno<101):
				pm('chan',"\00304Phew, someone ought to fix this; $url has \00308$errno\00304 errors.");	//Page has between 50 and 100 errors.
			break;
			case($errno>99&&$errno<201):
				pm('chan',"\00304Ok \00308$errno\00304 is alot of errors. Someone /slap $url's author!");	//Page has between 100 and 200 errors.
			break;
			case($errno>201&&$errno<801):
				pm('chan',"\00304OUCH! \00308$errno\00304 errors at $url, someone used MS Word to code their website!!");	//Page has between 200 and 800 errors.
			break;
			case($errno>800):
				pm('chan',"\00304BLEEDING HELL!!! \00308$errno\00304 ERRORS!!! SOMEONE SET US UP THE BOMB!!!");	//Page has more than 800 errors (pretty damn rare).
			break;
		}
		break;
		default:
			pm('chan', "\00303This site is valid!");	//Valid site
		break;
	}
}