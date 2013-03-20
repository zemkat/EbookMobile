<?php
#
#	gfeed.php - display atom feed
#
#	(c) 2013 Kathryn Lybarger. CC-BY-SA
#

require_once "db.php";
require_once "config.php";

header('Content-type: application/xml');

$id_sql = (isset($_REQUEST['id']))?intval($_REQUEST['id']):2;

$query = "SELECT title, lifetime_days FROM gfeeds WHERE gfeed_id='$id_sql'";
$result = do_query( $query );

if ( $result and NULL !== ($row = $result->fetch_row() ) ) {
	$feed_title = $row[0];
	$lifetime = $row[1];
} else {
	print "ERROR: No such feed\n";
	exit;
}

$date_atom = date(DATE_ATOM);

#	print top
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\">
    <title>$feed_title</title>
    <subtitle>delivered by eBookMobile</subtitle>
    <link href=\"$path_to_EBM/gfeed.php?id=$id_sql\" rel=\"self\" />
    <id>$path_to_EBM/gfeed.php?id=$id_sql</id>
    <updated>$date_atom</updated>
    <author>
         <name>EbookMobile</name>
         <email>$feed_email</email>
    </author>
    <generator>EbookMobile</generator>
";

$today_date = date("Y-m-d");
$first = mktime(0,0,0,date("m"),date("d")-$lifetime,date("Y"));
$first_date = date("Y-m-d",$first);

$query = "SELECT DISTINCT gbooks.bib_id FROM gfeed_query INNER JOIN gbooks
ON gfeed_query.query_id = gbooks.query_id
WHERE gfeed_query.gfeed_id = '$id_sql' and modify_date between '$first_date' and '$today_date' ";

$result = do_query( $query );

while ( $result and NULL !== ($row = $result->fetch_row() ) ) {
	print_entry($row[0]);
}

#	build bottom
print "
</feed>
";


function print_entry ($bib_id) {
	global $purl_pattern;

	$query = "SELECT title, title_brief, isbn, modify_date FROM gbooks WHERE bib_id='$bib_id'";
	$result = do_query( $query );

	if ( $result and NULL !== ($row = $result->fetch_row() ) ) {
		$title = $row[0];
		$title_brief = $row[1];
		$isbn = $row[2];
		$modify_date = $row[3];
	} else {
		return;
	}

	$link = str_replace("EBM_BIB_ID", $bib_id, $purl_pattern);

	if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$modify_date,$m)) {
		$entry_date = date(DATE_ATOM, mktime($m[4],$m[5],$m[6],$m[2],$m[3],$m[1]));
	} else {
		$entry_date = date(DATE_ATOM); # Better default date?
	}

	$date = date(DATE_ATOM);
	$isbn = preg_replace("/ .*/","",$isbn);
	$title_brief = rtrim(preg_replace("/\[electronic resource\].*/","",
		$title_brief)," /");

	print "
<entry>
	<title>" . htmlspecialchars($title_brief) . "</title>
	<link href=\"$link\" />
	<id>$link</id>
	<updated>$entry_date</updated>
	<content type=\"xhtml\">
	<div xmlns=\"http://www.w3.org/1999/xhtml\">
	<p style=\"display: block\">" . htmlspecialchars($title) . "</p>
	</div>
	</content>
</entry>
";
}

#	Coming soon: cover service!
#	<img src=\"http://covers.openlibrary.org/b/isbn/$isbn-M.jpg\" />
#	<img width=\"100px\" style=\"display: block; float: left\" src=\"covers/$isbn.jpg\" />

