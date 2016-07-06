<?php
try {
	// Prepare query
	$q = $db->prepare("SELECT COUNT(t2.Salt) AS OrderCount, t1.CountryName AS CountryName, t1.Alpha3 AS CountryCode FROM countrycode AS t1 LEFT JOIN orders_unique AS t2 ON t1.Alpha3 = t2.Country GROUP BY t1.Alpha3");

	// Execute query with array of values
	$q->execute();

	// Get results
	if($q->rowCount() > 0) {
		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$countryData = array('countryCode' => $row['CountryCode'], 'countryName' => $row['CountryName'], 'orderCount' => intval($row['OrderCount']));
			$ordersByCountry[] = $countryData;
		}
		$dataReturn->set_data($ordersByCountry);
		$dataReturn->execute();
	}
} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	echo json_encode(
		array(
			'error' => true,
			'status' => 100,
			'message' => 'MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage()
		)
	);
	exit();
}
?>