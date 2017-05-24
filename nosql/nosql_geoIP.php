<?php
/* This geoIP API library was written by kevinhng86 from Fai Hosting Solution.
 * A special thanks to Chirag Mehta - http://chir.ag/projects/geoiploc for the half division formula.
 * The database this software uses is the compress version of the Maxmind GEOIP2LITE database by www.maxmind.com.
 * This software is licensed with https://creativecommons.org/licenses/by/2.0/. Please give appropriate credit to Maxmind and Chirag Mehta when use.
 * Bug can be reported at http://kevinhng86.iblog.website.
 */


require('country.class.php');
require('iprange.php');

$ip = isset($_GET["ip"])? $_GET["ip"] : null ;
$type = isset($_GET["type"])? $_GET["type"] : null;

function getCountryCodeFromIP($ip){
	if ( ($ip = ip2long($ip)) === false ) return -1;
	global $iprange;
	global $dbsize;
	$upto = $dbsize;
	$output = "Unknown";
	$from = 0;
	$idn = 0;
	while( $from < $upto ){
		$idn = $from + intval(($upto - $from) / 2);
		if( $ip >= $iprange[$idn][0] && $ip <= $iprange[$idn][1] ){
			$output = $iprange[$idn][2];
			break;
		}
		if ($ip < $iprange[$idn][0]){
			if($upto == $idn) break;
			$upto = $idn;
		}
		if ($ip > $iprange[$idn][1]){
			if($from == $idn) break;
			$from = $idn;
		}
	}
	return $output;
}

if( isset($ip) ){
	if ($type === 'name'){
		header('Content-Type: application/json');
		$text = getCountryCodeFromIP($ip);
		$text = strlen($text) > 0? getCountryCodeOrName($text)[0] : -1;
	} else {
		$text = getCountryCodeFromIP($ip);
	}
	echo json_encode($text);
} else {
	header('Content-Type: application/json');
	echo json_encode("No IP address input");
}

/* Unset this two arrays when the script need to continue without them. Iprange array cost close to 200MB while in memory.
* $iprange = null;
* $dbsize = null;
*/

?>
