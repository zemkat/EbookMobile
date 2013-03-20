EbookMobile
=======

EbookMobile is a system for generating and serving feeds of new 
materials (meeting configurable requirements) from a Voyager database.

Files for EbookMobile:

* `gdaily.php` -- run periodically to cache feed entry data
* `gfeed.php` -- install in a web directory to generate feeds
* `crontab.txt` -- sample crontab file to automate daily caching
* `db.php.txt` -- database credentials and functions
* `config.php.txt` -- feed constant data configuration
* `gschema.mysql` -- database schema

PHP tools use oracle (11g) for php (5.4).  Database connection and
feed configuration should be stored in db.php and config.php respectively;
sample files db.php.txt and config.php.txt are provided.

These are copyright Kathryn Lybarger and distributed under CC-BY-SA.
