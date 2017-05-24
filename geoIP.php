<?php
/* This geoIP API library was written by kevinhng86 from Fai Hosting Solution.
 * The database that powered this software is the Maxmind's GEOIP2-LITE Database from Maxmind.com
 * Also credit to http://mrjoy.com/2013/09/04/fast-geoip-queries-in-mysql/ for the primary key formula.
 * The code of this software is licensed with https://creativecommons.org/licenses/by/2.0/.
 * Bug can be reported at http://kevinhng86.iblog.website.
 */
require('config.php');
$dbLib = new dbLib;
if(!$dbLib->dbConnectionCheck()) die ("Database problem.");

$ip = isset($_GET["ip"])? $_GET["ip"] : null ;
$type = isset($_GET["type"])? $_GET["type"] : null;

function getCountryCodeFromIP($ip, $dbLib){
    if (($ip = ip2long($ip)) === false) return false;
    $output = $dbLib->dbIP2CCode($ip);
    return $output;
}

if(isset($ip)){
    header('Content-Type: application/json');
    $ipinfo  = "";
    $dbLib->dbConnect();
    $ccode = getCountryCodeFromIP($ip, $dbLib);
    $ccode = $dbLib->dbResultToArray($ccode);

    if(count($ccode) < 1) die(json_encode([-1, "Can't seem to find any information for ip: $ip"]));

    if($ccode[0][IP_FIELD_CCODE1] > 0){
        $ccode = $ccode[0][IP_FIELD_CCODE1];
    } else if ($ccode[0][IP_FIELD_CCODE2] > 0){
        $ccode = $ccode[0][IP_FIELD_CCODE2];
    } else if ($ccode[0][IP_FIELD_CCODE3] > 0){
        $ccode = $ccode[0][IP_FIELD_CCODE3];
    } else {
        $ccode = 0;
    }

    if($ccode > 0){
        $ipinfo = $dbLib->dbCCodeTo($ccode, "*");
        $ipinfo = $dbLib->dbResultToArray($ipinfo);
        if(count($ipinfo) < 1) die(NO_CODE_RETURN);
        $ipinfo = $ipinfo[0];
    } else {
        die(NO_CODE_RETURN);
    }
    $dbLib->dbExit();

    if ($type === 'name'){
        echo json_encode($ipinfo[COUNTRY_FIELD_NAME]);
    } else {
        echo json_encode($ipinfo[COUNTRY_FIELD_ISO]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode("No IP address input.");
}


?>
