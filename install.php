<?php
require('config.php');
$maxmind_ip_file = "GeoLite2-Country-Blocks-IPv4.csv";
$maxmind_country_file = "GeoLite2-Country-Locations-en.csv";
$install = isset($_GET['install'])? $_GET['install'] : null;

function ipMinMax($invalue, $long = false){
    $addresses = array();
    @list($ip, $len) = explode('/', $invalue);
    if( ($min = ip2long($ip)) === false) return false;
    if(!isset($len)) return [$ip, $ip];
    $max = ($min | (1<<(32-$len)) - 1);
    return ($long === false? [long2ip($min), long2ip($max)] : [$min, $max]);
}


function MaxIpCSVToDB($file, $dbLib){
    if (!file_exists($file)) return false;
    $tempfile = $file . ".temp";
    $conn_i = $GLOBALS[DB_CON_G_N];
    $line = 1000;
    $row = 0;
    $FILE = fopen($file, "r");
    $FILEW = fopen($tempfile, "w");
    while (!flock($FILE, LOCK_SH)) { usleep(1); }
    while (!flock($FILEW, LOCK_SH)) { usleep(1); }
    fgetcsv($FILE, $line, ","); // Skip first line.
    while (($data = fgetcsv($FILE, $line, ",")) !== FALSE) {
        $ip = $data[0];
        $iplongminmax = ipMinMax($ip, true);
        $iplongmin = $iplongminmax[0];
        $iplongmax = $iplongminmax[1];
        fwrite($FILEW, "$ip, $iplongmin, $iplongmax, $data[1], $data[2], $data[3], ''\n");
    }
    flock($FILEW, LOCK_UN);
    flock($FILE, LOCK_UN);
    fclose($FILE);
    fclose($FILEW);
    $tempfile = realpath($tempfile);
    $field = "(" . IP_FIELD_NETWORK . ", " . IP_FIELD_START . ", " . IP_FIELD_END . ", " . IP_FIELD_CCODE1 . ", " . IP_FIELD_CCODE2 . ", " . IP_FIELD_CCODE3 . ", " . IP_FIELD_POSTALCODE . ");";
    $query = "LOAD DATA LOCAL INFILE '$tempfile' INTO TABLE " . IP_TABLE . "\n fields terminated by ',' \n" . $field;
    //$grantquery = "GRANT FILE ON *.* TO '" . DB_USERNAME . "'@'" . DB_HOST ."' ;";
    //$revokequery = "REVOKE FILE ON *.* FROM '" . DB_USERNAME . "'@'" . DB_HOST . "' ;";
    //$conn_i->query($grantquery);
    $result = $conn_i->query($query);
    //$conn_i->query($revokequery);
    unlink($tempfile);
    if(!$result) return false;
    return true;
}


function MaxCountryCSVToDB($file, $dbLib){
    if (!file_exists($file)) return false;
    $line = 1000;
    $field = [COUNTRY_FIELD_CODE, COUNTRY_FIELD_CON_CODE, COUNTRY_FIELD_CON_NAME, COUNTRY_FIELD_ISO, COUNTRY_FIELD_NAME, COUNTRY_FIELD_LANGCODE, COUNTRY_FIELD_CURRENCY];
    $FILE = fopen($file, "r");
    while (!flock($FILE, LOCK_SH)) { usleep(1); }
    fgetcsv($FILE, $line, ","); // Skip first line.

    while (($data = fgetcsv($FILE, $line, ",")) !== FALSE) {
        $value = [$data[0], $data[2], $data[3], $data[4], $data[5], "", ""];
        $result = $dbLib->dbTableInsert(COUNTRY_TABLE, $field, $value);
        if(!$result) return $result;
    }
    flock($FILE, LOCK_UN);
    fclose($FILE);
    return true;
}

if ($install === "run"){
    header("Content-Type: text/text");
    $dbLib = new dbLib;
    $conn_test = $dbLib->dbConnectionCheck();
    if($conn_test[0] !== true) die(var_dump($conn_test));
    $C = 'constant';
    $step = isset($_GET['step'])? intval($_GET['step']) : null;

    $ip_table = array(IP_TABLE => [ "{$C('IP_FIELD_NETWORK')} VARCHAR(32) NOT NULL",
                                    "{$C('IP_FIELD_START')} DOUBLE UNSIGNED NULL",
				    "{$C('IP_FIELD_END')} DOUBLE UNSIGNED NULL",
				    "{$C('IP_FIELD_CCODE1')} INT(11) UNSIGNED NULL",
				    "{$C('IP_FIELD_CCODE2')} INT(11) UNSIGNED NULL",
				    "{$C('IP_FIELD_CCODE3')} INT(11) UNSIGNED NULL",
				    "{$C('IP_FIELD_POSTALCODE')} VARCHAR(45) NULL",
				    "PRIMARY KEY({$C('IP_FIELD_START')}, {$C('IP_FIELD_END')})"
				   ]);

    $loc_table = array(COUNTRY_TABLE => [ "{$C('COUNTRY_FIELD_CODE')} INT(11) NOT NULL",
					  "{$C('COUNTRY_FIELD_CON_CODE')} VARCHAR(2) NULL",
				  	  "{$C('COUNTRY_FIELD_CON_NAME')} VARCHAR(80) NULL",
					  "{$C('COUNTRY_FIELD_ISO')} VARCHAR(2) NULL",
					  "{$C('COUNTRY_FIELD_NAME')} VARCHAR(80) NULL",
					  "{$C('COUNTRY_FIELD_LANGCODE')} VARCHAR(8) NULL",
					  "{$C('COUNTRY_FIELD_CURRENCY')} VARCHAR(8) NULL",
					  "PRIMARY KEY ({$C('COUNTRY_FIELD_CODE')})"
					]);

    if ($step === 1){
	$dbLib->dbConnect();
	var_dump($dbLib->dbCreateTable($ip_table)) ."\n\n";
	var_dump($dbLib->dbCreateTable($loc_table)) ."\n\n";
	$dbLib->dbExit();
    } else if ($step === 2){
	$dbLib->dbConnect();
	var_dump(MaxCountryCSVToDB($maxmind_country_file, $dbLib));
	$dbLib->dbExit();
    } else if ($step === 3){
	$dbLib->dbConnect();
	var_dump(MaxIPCSVToDB($maxmind_ip_file, $dbLib));
	$dbLib->dbExit();
    } else if ($step === 4){
	// This step is not working, it is just here for demonstration purposes.
	$dbLib->dbConnect();
	if($dbLib->dbCheckTBExist(IP_TABLE)) die(IP_TABLE . " was not found. Install script may need to be re run.") ;
	if($dbLib->dbCheckTBExist(COUNTRY_TABLE)) ; die(COUNTRY_TABLE . " was not found. Install script may need to be re run.");
	echo "Sucessfully install the database. \n";
	echo "Please removed the install.php file.";
	$dbLib->dbExit();
    }
}
