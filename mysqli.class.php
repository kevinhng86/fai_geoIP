<?php
class dbLib
{
    private $host = DB_HOST;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $db = DB_DATABASE;
    private $prefix = DB_PREFIX;
    private $port = DB_PORT;
    private $conGlobal = DB_CON_G_N;

    public function dbCheckTBExist($tname){
        $conn_i = $GLOBALS[$this->conGlobal];
        if($conn_i->query("select 1 from `${tname}` LIMIT 1")) return true;
        return false;
    }

    public function dbConnectionCheck(){
        $conn_i = @mysqli_connect($this->host, $this->username, $this->password);
        if(!$conn_i) return [-1,'Failed to connect to the database server.'];
        if(!@mysqli_select_db($conn_i, $this->db)) return [-2,'Failed to connect to the database.'];
        mysqli_close($conn_i);
        return [true];
    }

    public function dbConnect(){
        $GLOBALS[$this->conGlobal] = @mysqli_connect($this->host, $this->username, $this->password, $this->db);
        if($GLOBALS[$this->conGlobal]->connect_error) return false;
        return true;
    }

    public function dbExit(){
        $GLOBALS[$this->conGlobal]->close();
    }

  /* Usage: array $content[ array[ name , [array field to create] ] ]
   * Examples:
   *          $content = array( "table1name" => ["id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY", "firstname VARCHAR(30) NOT NULL"],
   *                            "table2name" => "singlefield VARCHAR(30) NOT NULL"
   *                          )
   *
   * Whether closing the connection through the global variable or a local variable, the connection will be closed.
   */

    public function dbCreateTable($content, $startopt = "", $endopt = ""){
        $conn_i = $GLOBALS[$this->conGlobal];
        if(!is_array($content)) return [-3,"Incorrect input format"];
        $failquery = array();
        $sucessquery = array();

        foreach ($content as $name => $value){
            $tname = $conn_i->real_escape_string($name);
            if($conn_i->query("select 1 from `${tname}` LIMIT 1")){
                array_push($failquery,[-5,"Table ${tname} can't be created because the table is exist."]);
                continue;
            }

            $query = "CREATE TABLE ${tname} ${startopt} (";

            if(is_string($value)){
                $query .= $value;
            } else if(is_array($value)){
                $end = count($value) - 1;
                for ($i = 0; $i < count($value); $i++){
                    $query .= $value[$i];
                    if($i < $end) $query .= ", " ;
                }
            } else {
                array_push($failquery, [-3,"Fail to create ${tname} table because incorrect input value(only array or string)"]);
                continue;
            }

            $query .= " ) ";
            $query .= $endopt . ";";
            if ( $conn_i->query($query) === true ){
                array_push($sucessquery, [true, "Table ${tname} was created."]);
            } else {
                array_push($failquery, [-4,"Failed to create the table ${tname} with the query: $query. Errors: $conn_i->error()"]);
            }
        }
        return [$sucessquery, $failquery];
    }

    public function dbTableInsert($tname, $field, $content ){
        $conn_i = $GLOBALS[$this->conGlobal];
        $s = " ";
        $c = ",";
        $b = "'";
        $query = "INSERT INTO ${tname}" . $s;

        if( is_string($field) && is_string($content) ){
            $query .= "(" . $conn_i->real_escape_string($field) . ")"  . $s; // Can remove real_escape_string if doesn't need, escaping field name.
            $query .= "VALUES" . $s ;
            $query .= "(" . $b . $conn_i->real_escape_string($content) . $b . ");";
        } else if( is_array($field) && is_array($content) ){
            $flen = count($field);
            $end = $flen - 1;
            $query .=  "(";
            $queryV = "VALUES (";
            for($i = 0; $i < $flen; $i++){
                if(!isset($content[$i])) $content[$i] = "";
                $query .= $conn_i->real_escape_string($field[$i]); // Can remove real_escape_string if doesn't need, escaping field name.
                $queryV .= $b . $conn_i->real_escape_string($content[$i]) . $b;
                if($i < $end){
                    $query .= $c . $s;
                    $queryV .= $c . $s;
                }
            }
            $query .= ")" . $s . $queryV . ");";
        } else {
            return [-3, "Incorrect input value, array or string only."];
        }

        if($conn_i->query($query)){
            return [1, "Query was successfully executed: $query"];
        } else {
            return [-4, "Query executed with errors: $query :: $conn_i->error"];
        }
    }

  /* Currently does not throughly check input value.
   * Examples: $query = array(["geoip_test", "id", "8"],
   *                         ["geoip_test2", ["firstname","lastname"], ["nguyen"]]
   *                          );
   */

    public function dbInsertMass($content, $checkinput = false){
        if (!is_array($content)) return [-3, "Incorrect input value, must be string or array"];
        $output = array();
        $this->dbConnect();
        for($i = 0; $i < count($content); $i++){
            $output[$i] = $this->dbTableInsert($content[$i][0], $content[$i][1], $content[$i][2]);
        }
        $this->dbExit();
        return $output;
    }

  /* Type MYSQLI_NUM | MYSQLI_ASSOC | MYSQLI_BOTH
   * Example:
   * $query = array("firstname" => ["=", "kevin", "OR"],
   *               "lastname" => ["LIKE", "%nguyen%"]);
   */
    public function dbSelectFrom($tname, $content, $getstring = "*", $opstring = "", $type = MYSQLI_ASSOC){
        $conn_i = $GLOBALS[$this->conGlobal];
        $s = " ";
        $c = ",";
        $b = "'";
        $query = "SELECT " . $conn_i->real_escape_string($getstring) . " FROM ${tname}" . $s;
        if (strlen($opstring) > 0) $query .= $conn_i->real_escape_string($opstring) . $s;
        $query .= "WHERE" . $s;
        if (!is_array($content)) return [-3, "Incorrect input type, correct type is array type."];

        foreach ($content as $key => $value){
            $vlen = count($value);
            if ($vlen < 2 || $vlen > 3) return [-7, "Not enough or have more than the correct amount of values."];
            $query .= $key . $s;
            for($i = 0; $i < $vlen; $i++){
                if($i !== 1) $query .= $value[$i] . $s;
                if($i === 1) $query .= $b . $conn_i->real_escape_string($value[$i]) . $b . $s;
            }
        }

        $query .= ";";
        echo $query;
        $output = $conn_i->query($query);
        var_dump($output);
        if(!$output) return false;
        return $output;
    }

    public function dbResultToArray($content, $type = MYSQLI_ASSOC){
        $output = array();
        while($row = mysqli_fetch_array($content, $type)){
            array_push($output, $row);
        }
        mysqli_free_result($content);
        return $output;
    }
    // Below Are functions that specifically designed for this fai_geoIP.

    public function dbIP2CCode($iplong){
        $conn_i = $GLOBALS[$this->conGlobal];
        $C = 'constant';
        $iplong =  $conn_i->real_escape_string($iplong);
        $query = "SELECT {$C('IP_FIELD_CCODE1')},{$C('IP_FIELD_CCODE2')},{$C('IP_FIELD_CCODE3')} \n";
        $query .= "FROM {$C('IP_TABLE')}\n";
        $query .= "WHERE $iplong BETWEEN {$C('IP_FIELD_START')} ";
        $query .= "AND {$C('IP_FIELD_END')} \n";
        $query .= "LIMIT 1;";
        $output = $conn_i->query($query);
        if (!$output) return false;
        return $output;
    }

    public function dbCCodeTo($ccode, $type = "iso"){
        $conn_i = $GLOBALS[$this->conGlobal];
        $C = 'constant';
        $field = COUNTRY_FIELD_ISO;
        if($type === "iso") $field = COUNTRY_FIELD_ISO;
        if($type === "name") $field = COUNTRY_FIELD_CON_NAME;
        if($type === "code") $field = COUNTRY_FIELD_CON_CODE;
        if($type === "*") $field = "*";
        $query = "SELECT $field FROM {$C('COUNTRY_TABLE')} WHERE {$C('COUNTRY_FIELD_CODE')} = '" . $conn_i->real_escape_string($ccode) . "' LIMIT 1;";
        if( ($output = $conn_i->query($query)) === false ) return false;
        return $output;
    }
}
