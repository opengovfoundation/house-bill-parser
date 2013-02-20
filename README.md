#Congressional Bill Parser
***

A php parsing script for the US House of Representatives' bulk bill data.  This script parses the XML files into the Madison database.

##Usage:
1.  `php getDocs.php` to retrieve the xml files
2.  Edit config.php to set database credentials
3.  `php parse <file_location>` to parse the files into the Madison database