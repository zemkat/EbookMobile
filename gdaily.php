<?php
#
#	gdaily.php - this script should be run daily by a cron job
#
#	Usage:
#	gdaily.php <bdb_id> <gfeed_id1> ... <gfeed_idn>
#	gfeed = feed # or "all"
#
#	(c) 2013 Kathryn Lybarger. CC-BY-SA
#

require_once("db.php");

$bdb_id = (isset($argv[1])) ? intval($argv[1]) : 1;
$query = "SELECT host,port,ro_login,ro_pw FROM BDBs WHERE bdb_id='$bdb_id'";
$result = do_query( $query );
if ( $result and NULL !== ($row = $result->fetch_row() ) ) {
	$bdb_host = $row[0];
	$bdb_port = $row[1];
	$bdb_login = $row[2];
	$bdb_pw = $row[3];
} else {
	print "ERROR: Could not login to database [$bdb_id]\n";
	exit;
}

$feeds_to_cache = array();

if (!isset($argv[2]) or ($argv[2] == "all")) {
	$query = "SELECT gfeed_id FROM gfeeds";
	$result = do_query ( $query );
	while ( $result and NULL !== ($row = $result->fetch_row() ) ) {
		array_push($feeds_to_cache, $row[0]);
	}
} else {
	array_shift($argv);
	while ( $feed_id = array_shift($argv) ) {
		array_push($feeds_to_cache, $feed_id);
	}
}

$db = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = $bdb_host)(PORT = $bdb_port)))(CONNECT_DATA=(SID=VGER)))";
$conn = oci_connect($bdb_login, $bdb_pw, $db );

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$today = mktime(0,0,0,date("m"),date("d"),date("Y"));
$today_date = date("Y-m-d",$today);

foreach ($feeds_to_cache as $gfeed_id) {

	$query = "SELECT query_id,embargo_days,lifetime_days FROM gfeed_query where gfeed_id = '$gfeed_id'";
	$result = do_query( $query );

	while ( $result and NULL !== ($row = $result->fetch_row() ) ) {
		$query_id = $row[0];
		$embargo = $row[1];
		$lifetime = $row[2];
	
		$first = mktime(0,0,0,date("m"),date("d")-$embargo-$lifetime, date("Y"));
		$first_date = date("Y-m-d",$first);
		$last = mktime(0,0,0,date("m"),date("d")-$embargo, date("Y"));
		$last_date = date("Y-m-d",$last);
	
		$query = "SELECT query_text FROM queries WHERE query_id='$query_id'";
		$res1 = do_query( $query );
		if ( $res1 and NULL !== ($row = $res1->fetch_row() ) ) {
			$query_text = $row[0];
		} else {
			print "ERROR: No query [$query_id]\n";
			exit;
		}
	
		$query_text = str_replace( "EBM_FIRST_DATE", "'$first_date'", $query_text );
		$query_text = str_replace( "EBM_LAST_DATE", "'$last_date'", $query_text );
	
		print "QUERY: $query_text\n";
	
		$stid = oci_parse( $conn, $query_text );
		
		if (false === oci_execute( $stid )) {
			print "ERROR: Failed to execute query\n";
			exit;
		}
	
		while (false !== ($row = oci_fetch_array( $stid, OCI_ASSOC+OCI_RETURN_NULLS ) ) ) {
			$isbn = preg_replace( "/ .*/","",$row['ISBN'] );
			$ins_query = "INSERT INTO gbooks SET 
				query_id='$query_id',
				bib_id='" . $row['BIB_ID'] . "',
				title_brief=" . sqlescape( $row['TITLE_BRIEF'] ) . ",
				title=" . sqlescape( $row['TITLE'] ) . ",
				isbn='$isbn',
				modify_date='" . $row['LASTMODIFIED'] . "'
			ON DUPLICATE KEY UPDATE modify_date='" . $row['LASTMODIFIED'] . "'";
			do_query( $ins_query );
		}
	}
}
