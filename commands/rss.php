<?php
/*
		RSS Reade module, by Keithamus
		 in this we have several RSS reading
		 functions:

		 Digg							(www.digg.com)
		 Slashdot						(www.slashdor.org)
		 From the Shadows		(www.fromtheshadows.tv)
		Penny Arcade				(www.pennyarcade.com)
		Chugworth					(www.chugworth.com)
		Bigger Than Cheese	(www.biggercheese.com)
		Anime Suki					(www.rpguru.com)

		Also two seperate commands, one to list all items
		in a defined rss feed, another to list the first one.
*** RSS2ARRAY written by: dan@freelancers.net ***

***REQUIREMENTS: none.***
*/

/* DIGG and SLASHDOT*/
/******************/
compilehelp('DIGG', 'Searches DIGG for latest 10 posts', 'DIGG', '', 'DIGG <X>:Searches DIGG for the latest <X> posts');
function cmd_digg($num) {
	list_rss('http://www.digg.com/rss/index.xml',$num);
}
compilehelp('SLASHDOT', 'Searches SLASHDOT for the latest 10 posts','SLASHDOT','','SLASHDOT <X>:Searches SLASHDOT for the latest <X> posts');
function cmd_slashdot($num) {
	list_rss('http://slashdot.org/rss/index.rss',$num);
}
/* FTS */
/*****/
compilehelp('FTS','Checks the From The Shadows RSS feed, and provides an avi torrent link for the latest episode','FTS');
function cmd_fts($args) {
	$fts= rss2array('http://fromtheshadows.tv/rss.php');
	pm('chan',substr($fts[items][0][title],0,7) . ' is out! ' . $fts[items][0][link]);
}
/* PA */
/*****/
compilehelp('PA','Checks the Penny Arcade RSS feed, and provides a link for the latest comic','PA');
function cmd_pa($args) {
	$pennyarcade = rss2array('http://www.penny-arcade.com/RSS.xml');
	$i=0;
	while ($i<9) {
		$say = 'Sorry, no new comics in the past 10 posts.';
		$patitle = explode(':', $pennyarcade['items'][$i]['title']);
		if ($patitle[0] == 'Comic') {
			$content = substr($pennyarcade['items'][$i]['link'],-10);
			$content = str_ireplace('-','', $content);
			$say = "Latest comic:\00313" . $patitle[1] . " \003( http://img.penny-arcade.com/" . substr($content,0,4) . "/" . $content . "h.jpg )";
			$i=10;
		}
		++$i;
	}
	pm('chan', $say);
}
/* CHUG */
/*******/
compilehelp('CHUG','Checks the Chugworth Academy Comic RSS feed, and provides a link for the latest comic','CHUG');
function cmd_chug($args) {
	$chug = rss2array('http://www.chugworth.com/rss/rssfeed.xml');
	$chug = explode('=',$chug['items'][1]['link']);
	$chug = $chug[1];
	pm('chan',"Latest comic: \00310" . $chug . " \003( http://www.chugworth.com/comic/$chug.jpg )");
}
/* BTC */
/******/
compilehelp('CHEESES','Checks the Bigger Than Cheese Comic RSS feed, and provides a link for the latest comic','Cheeses');
function cmd_cheese($args) {
	$btc = rss2array('http://www.biggercheese.com/feed.xml');
	$title = $btc['items'][0]['title'];
	$btc= explode(':', $title);
	$btc = $btc[0];
	pm('chan',"Latest comic:\00308 " . $title . " \003( http://www.biggercheese.com/comics/0$btc.png )");
}
/* SUKI */
/******/
compilehelp('SUKI','Searches AnimeSuki.com for the latest episodes of <SEARCH>','SUKI <SEARCH>','SUKI BLEACH||'.$CONFIG['nick'].'> Monster 58 (...)','');
function cmd_suki($needle) {
	$suki = rss2array('http://www.rpguru.com/rss.php');
	$i=0;
	$n = count($suki[items]);
	while($i <=$n) {
		$pos = strpos(strtoupper($suki[items][$i][title]), strtoupper($needle));
		if ($pos !== false) {
			pm('uname', "\00310" . $suki[items][$i][title] . " (" . $suki[items][$i][link] . ") ","");
			$found=1;
			sleep(1);
		}
	++$i;
	}
	if($found!=1) {
		pm('uname', "\00310Sorry, no items found","");
	}
}
compilehelp('RSSITEM','Checks <URL> and displays the first item from the feed.','RSSITEM <URL>');
function cmd_rssitem($url) {
	list_rss($url,1,'chan');
}
compilehelp('LISTRSS','Checks <URL> and displays the first <NUM> items from the feed.','LISTRSS <URL> <NUM>');
function cmd_listrss($args) {
	$args = explode(' ',$args);
	list_rss($args[0],$args[1]);
}
function list_rss($url,$num=10,$chan='') {
	switch($chan) {
		case '':
			if($num==10) {
				$chan='uname';
			} else {
				$chan='chan';
			}
		break;
	}
	$rss = rss2array($url);
	$i=0;
	if($rss[items][$i][title]!='') {
		while($i<$num) {
			$b = $i+1;
			pm($chan, "\00312$b - " . $rss[items][$i][title] . " (" . $rss[items][$i][link] . ") ");
			sleep(2);
			++$i;
		}
	}
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////Here on in written by dan@freelancers.net:
global $rss2array_globals;
    function rss2array($url){																	//fetch feed
        global $rss2array_globals;
        $rss2array_globals = array();         											// empty our global array
        $matches='';
        if(preg_match("/^http:\/\/([^\/]+)(.*)$/", $url, $matches)){		// if the URL looks ok
            $host = $matches[1];
            $uri = $matches[2];
            $request = "GET $uri HTTP/1.0\r\n";
            $request .= "Host: $host\r\n";
            $request .= "Connection: close\r\n\r\n";
			$errno='';
			$errstr='';
            if($http = fsockopen($host, 80, $errno, $errstr, 5)){				// open the connection
                fwrite($http, $request);														//make the request
                $timeout = time() + 5;															//read in for max 5 seconds
                while(time() < $timeout && !feof($http)) {
                    $response .= fgets($http, 4096);
                }
                list($header, $xml) = preg_split("/\r?\n\r?\n/", $response, 2);				// split on two newlines
                if(preg_match("/^HTTP\/[0-9\.]+\s+(\d+)\s+/", $header, $matches)){	//get the status
                    $status = $matches[1];
                    if($status == 200){																				//if 200 ok
                        $xml_parser = xml_parser_create();												//create the parser
                        xml_set_element_handler($xml_parser, "startElement", "endElement");
                        xml_set_character_data_handler($xml_parser, "characterData");
                        //parse!
                        xml_parse($xml_parser, trim($xml), true) or $rss2array_globals[errors][] = xml_error_string(xml_get_error_code($xml_parser)) . " at line " . xml_get_current_line_number($xml_parser);
                        xml_parser_free($xml_parser);														//free parser
                    } else {
                        $rss2array_globals[errors][] = "Can't get feed: HTTP status code $status";
                    }
                } else {																									//Can't get status from header
                    $rss2array_globals[errors][] = "Can't get status from header";
                }
            } else {																										//Can't connect to host
                $rss2array_globals[errors][] = "Can't connect to $host";
            }
        } else {																											//Feed url looks wrong
            $rss2array_globals[errors][] = "Invalid url: $url";
        }
        //unset all the working vars
        unset($rss2array_globals[channel_title]);
        unset($rss2array_globals[inside_rdf]);
        unset($rss2array_globals[inside_rss]);
        unset($rss2array_globals[inside_channel]);
        unset($rss2array_globals[inside_item]);
        unset($rss2array_globals[current_tag]);
        unset($rss2array_globals[current_title]);
        unset($rss2array_globals[current_link]);
        unset($rss2array_globals[current_description]);
        return $rss2array_globals;
    }
    function startElement($parser, $name, $attrs){											//this function will be called everytime a tag starts
        global $rss2array_globals;
        $rss2array_globals[current_tag] = $name;
        if($name == "RSS"){
            $rss2array_globals[inside_rss] = true;
        }
        elseif($name == "RDF:RDF"){
            $rss2array_globals[inside_rdf] = true;
        }
        elseif($name == "CHANNEL"){
            $rss2array_globals[inside_channel] = true;
            $rss2array_globals[channel_title] = "";
        }
        elseif(($rss2array_globals[inside_rss] and $rss2array_globals[inside_channel]) or $rss2array_globals[inside_rdf]){
            if($name == "ITEM"){
                $rss2array_globals[inside_item] = true;
            }
            elseif($name == "IMAGE"){
                $rss2array_globals[inside_image] = true;
            }
        }
    }
    function characterData($parser, $data){										//this function will be called everytime there is a string between two tags
        global $rss2array_globals;
        if($rss2array_globals[inside_item]){
            switch($rss2array_globals[current_tag]){
                case "TITLE":
                $rss2array_globals[current_title] .= $data;
                break;
                case "DESCRIPTION":
                $rss2array_globals[current_description] .= $data;
                break;
                case "LINK":
                $rss2array_globals[current_link] .= $data;
                break;
            }
        } elseif($rss2array_globals[inside_image]){
        } elseif($rss2array_globals[inside_channel]){
            switch($rss2array_globals[current_tag]){
                case "TITLE":
                $rss2array_globals[channel_title] .= $data;
                break;
            }
        }
    }
    function endElement($parser, $name){									//this function will be called everytime a tag ends
        global $rss2array_globals;
        if($name == "ITEM"){															//end of item, add complete item to array
            $rss2array_globals[items][] = array(title => trim($rss2array_globals[current_title]), link => trim($rss2array_globals[current_link]), description => trim($rss2array_globals[current_description]));
			//reset these vars for next loop
            $rss2array_globals[current_title] = "";
            $rss2array_globals[current_description] = "";
            $rss2array_globals[current_link] = "";
            $rss2array_globals[inside_item] = false;
        } elseif($name == "RSS"){
            $rss2array_globals[inside_rss] = false;
        } elseif($name == "RDF:RDF"){
            $rss2array_globals[inside_rdf] = false;
        } elseif($name == "CHANNEL"){
            $rss2array_globals[channel][title] = trim($rss2array_globals[channel_title]);
            $rss2array_globals[inside_channel] = false;
        } elseif($name == "IMAGE"){
            $rss2array_globals[inside_image] = false;
        }
    }