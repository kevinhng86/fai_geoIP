# fai_geoIP
A geoIP API that based on http://maxmind.com database and hopefully will expand to incorporate ip from multiple vendors. 

Currently this API is only capable of getting country information for an ip address if there is available information available in the database. 

Therer is nothing special about the install script at this time. The installation have to be done almost manually. You have to manually go to install.php?install=run&step=1 to install.php?install=run&step=3 to be able to install the maxmind database at this moment. 

This program requires the maxmind GEOIP2-LITE-COUNTRY database from http://maxmind.com. The two files the program requires is the ip blocks and the location files. This two files need to be in the same directory with the install.php file for installation. The install script need to be removed after the database is successfully install. There is no built in safe mechanic to prevent the installation script from not executing.

This is still in a very beta state. The program will work and provide very basic function. It is more of a demonstatration of how to use the maxmind database currently.

Maxmind database can be download from maxmind.com. This script uses the csv version of the database.

The souce code of this program was written by kevinhng86, you can view other programming example I wrote at http://kevinhng86.iblog.website.

By using this program or the source code of this program, you are agreeing to https://opensource.org/licenses/CDDL-1.0 licensing terms.

In the nosql folder is a version that purely run in php and does not requires any database, nevertheless, the version that does not require a database consume 200mb of memory during execution which lasted about 9ms per execution.
