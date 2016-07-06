<?php

// Database connection
require_once('../config.php');

// Declare namespace
use SameerShelavale\PhpCountriesArray\CountriesArray;

// Error flag
$error_flag = false;

if(isset($_POST['actiontype'])) {
	$actiontype = $_POST['actiontype'];
} else {
	$error_flag = true;
	$error_msg = 'Action type unspecified.';
}
if(isset($_POST['itemscope'])) {
	$itemscope = $_POST['itemscope'];
} else {
	$error_flag = true;
	$error_msg = 'Item scope unspecified.';
}
if(isset($_POST['ordersalts'])) {
	$ordersalts = json_decode($_POST['ordersalts']);
} else {
	$error_flag = true;
	$error_msg = 'Order salts undefined.';
}
if(isset($_POST['origin'])) {
	$return_url = urldecode($_POST['origin']);
} else {
	$return_url = 'orders.php';
}


// Change order salts
if($itemscope == "some" && isset($_POST['selectedsalts'])) {
	$ordersalts = $_POST['selectedsalts'];
}

// Error handling
if($error_flag) {
	$_SESSION['ord_error'] = $error_msg;
	session_write_close();
	header("location: ".$return_url);
	exit();
}

try {

	// Establish database connection
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	// Construct placeholder
	$os_placeholder = str_repeat('?,', count($ordersalts)-1).'?';

	if($actiontype == '1') {
		// Download postage information
		$dldq = "SELECT
			ord.FirstName,
			ord.LastName,
			ord.Institution,
			ord.Address,
			ord.City,
			ord.State,
			ord.PostalCode,
			ord.Country
		FROM orders_unique AS ord
		LEFT JOIN orders_lines AS lin ON
			ord.Salt = lin.Salt
		WHERE ord.Salt IN ($os_placeholder)
		GROUP BY ord.Salt
		ORDER BY
			OrderID DESC";

		$datatype = "postage";

	} elseif($actiontype == '2') {
		// Download seed bag information
		$dldq = "SELECT
			lin.PlantID AS PlantID,
			lin.Salt AS Salt,
			ord.FirstName AS FirstName,
			ord.LastName AS LastName
		FROM orders_lines AS lin
		LEFT JOIN orders_unique AS ord
			ON lin.Salt = ord.Salt
		WHERE ord.Salt IN ($os_placeholder)
		ORDER BY
			lin.OrderLineID DESC";

		$datatype = "seedbag";

	} elseif($actiontype == '3') {
		// Download all relevant order information
		$dldq = "SELECT
			ord.Salt as Salt,
			ord.Timestamp as Timestamp,
			ord.FirstName as FirstName,
			ord.LastName as LastName,
			ord.Email as Email,
			ord.Institution as Institution,
			ord.Address as Address,
			ord.City as City,
			ord.State as State,
			ord.PostalCode as PostalCode,
			ord.Country as Country,
			ord.Verified as Verified,
			ord.Comments as Comments,
			lin.PlantID AS PlantID,
			lin.SeedQuantity as SeedQuantity,
			lin.ProcessDate as ProcessDate,
			lin.AdminComments as AdminComments,
			ad.FirstName as AdminFirstName,
			ad.LastName as AdminLastName
		FROM orders_lines AS lin
		LEFT JOIN orders_unique AS ord
			ON lin.Salt = ord.Salt
		LEFT JOIN auth AS ad
			ON lin.AdminID = ad.UserID
		WHERE ord.Salt In ($os_placeholder)
		ORDER BY
			lin.OrderLineID DESC";

		$datatype = "all";
	}

	$query = $db->prepare($dldq);
	$query->execute($ordersalts);

	// Initiate download if there are rows
	if($query->rowCount() > 0) {
		$error_flag = false;

		// Append header
		if($actiontype == '1') {
			$out = "Name\tInstitution\tAddress\tCity, State, Postal Code\tCountry\n";
		} elseif($actiontype == '2') {
			$out = "Name\tPlantID\n";
		} elseif($actiontype == '3') {
			$out = "Order ID\tTimestamp\tName\tEmail\tInstitution\tAddress\tCity\tState\tPostal Code\tCountry\tPlant ID\tRecipient Comments\tNo. of Seeds Sent\tProcessed Date\tAdmin In-charge\tAdmin Comments\tVerified\n";
		} else {
			$error_flag = true;
			$error_msg = 'Download action is invalid.';
		}

		// Allow country name conversion
		$alpha3 = CountriesArray::get('alpha3', 'name');
		
		// Print rows
		if($actiontype == '1') {
			while($results = $query->fetch(PDO::FETCH_ASSOC)) {
				// Write output
				$out .= $results['FirstName']." ".$results['LastName']."\t".$results['Institution']."\t".$results['Address']."\t";
				if($results['State'] == '') {
					$out .= $results['City'].", ".$results['PostalCode']."\t";
				} else {
					$out .= $results['City'].", ".$results['State'].", ".$results['PostalCode']."\t";
				}
				$out .= $alpha3[$results['Country']]."\t"."\n";
			}
		} elseif($actiontype == '2') {
			while($results = $query->fetch(PDO::FETCH_ASSOC)) {
				// Write output
				$out .= $results['FirstName'].' '.$results['LastName']."\t".$results['PlantID']."\n";
			}
		} elseif($actiontype == '3') {
			while($results = $query->fetch(PDO::FETCH_ASSOC)) {
				// Pre-process
				$results['SeedQuantity']	= (($results['SeedQuantity'] == null) ? '-' : $results['SeedQuantity']);
				$results['ProcessDate']		= (($results['ProcessDate'] == null) ? 'Unprocessed' : $results['ProcessDate']);
				$results['AdminComments']	= (($results['AdminComments'] == null) ? '-' : $results['AdminComments']);
				if(!$results['AdminFirstName'] || !$results['AdminLastName']) {
					$admin = "-";
				} else {
					$admin = $results['AdminFirstName']." ".$results['AdminLastName'];
				}

				// Replace line breaks in comments
				$comments = preg_replace("/[\r\n]+/", " ", $results['Comments']);
				$admincomments = preg_replace("/[\r\n]+/", " ", $results['AdminComments']);

				// Write output
				$out .= $results['Salt']."\t".$results['Timestamp']."\t".$results['FirstName']." ".$results['LastName']."\t".$results['Email']."\t".$results['Institution']."\t".$results['Addresss']."\t".$results['City']."\t".$results['State']."\t".$results['PostalCode']."\t".$alpha3[$results['Country']]."\t".$results['PlantID']."\t".$comments."\t".$results['SeedQuantity']."\t".$results['ProcessDate']."\t".$admin."\t".$admincomments."\t".$results['Verified']."\n";
			}		
		} else {
			$error_flag = true;
			$error_msg = 'Download action is invalid';
		}

		// Error flag
		if($error_flag) {
			$_SESSION['ord_error'] = $error_msg;
			session_write_close();
			header("location: ".$return_url);
			exit();
		}

		// Force download
		$file = "lore1orders_" . $datatype . "_" . date("Y-m-d_H-i-s") . ".txt";
		header("Content-disposition: attachment; filename=\"".$file."\"");
		header("Content-type: text/plain; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

		// Transliterate output
		print translit($out);
		exit();

	} else {
		$_SESSION['ord_error'] = "Error in producing a download file.";
		session_write_close();
		header("location: ".$return_url);
		exit();
	}
} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$_SESSION['ord_error'] = 'MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage();
	session_write_close();
	header("location: ".$return_url);
	exit();
}

?>