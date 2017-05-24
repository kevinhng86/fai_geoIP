<?php
define("DB_USERNAME", "");
define("DB_PASSWORD", "");
define("DB_DATABASE", "");
define("DB_PREFIX", "geoip_");
define("DB_HOST", "localhost");
define("DB_PORT", "3306");
define("DB_LIB", "mysqli"); // For now, only mysqli
define("DB_SERVER", "MYSQL"); // For now, only MYSQL
define("DB_CON_G_N", "CONN_I"); // Globals variable name for connection.

/* IP database configuration */

/* Can customize if need more information */
define("IP_TABLE", DB_PREFIX . "dbip");
define("COUNTRY_TABLE", DB_PREFIX . "dbcountry");
define("IP_FIELD_START", "ip_start");
define("IP_FIELD_END", "ip_end");
define("IP_FIELD_CCODE1", "geoname_id");
define("IP_FIELD_CCODE2", "registered_country_geoname_id");
define("IP_FIELD_CCODE3", "represented_country_geoname_id");
define("IP_FIELD_NETWORK", "network");
define("IP_FIELD_POSTALCODE", "postal_code"); // Future usages.
define("COUNTRY_FIELD_CODE", "geoname_id");
define("COUNTRY_FIELD_NAME","country_name");
define("COUNTRY_FIELD_ISO", "country_iso_code");
define("COUNTRY_FIELD_CON_NAME", "continent_name");
define("COUNTRY_FIELD_CON_CODE", "continent_code");
define("COUNTRY_FIELD_LANGCODE", "lang");
define("COUNTRY_FIELD_CURRENCY", "msymbol");


// No information return.
define("NO_CODE_RETURN", "Database does not contained information for the ip.");


require(DB_LIB . ".class.php");
