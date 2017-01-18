<?php

include "config.php";

if (USE_STATISTICS) {

  $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);

  if ($mysqli->connect_errno) {
    echo "Error while connecting to DB : ".$mysqli->error." \n" ;
    exit(1);
  }
  $mysqli->autocommit(FALSE);

  if (!($selectStatsCountryStmt = $mysqli->prepare("SELECT s.id, p.latitude, p.longitude FROM statistics AS s INNER JOIN position AS p ON p.id = s.id WHERE s.country IS NULL LIMIT ?"))) {
    echo "Error while preparing select statistics country statement : ".$mysqli->error."\n";
    exit(1);
  }

  if (!($updateStatsCountryStmt = $mysqli->prepare("UPDATE statistics SET country = ? WHERE id = ?"))) {
    echo "Error while preparing update statistics country statement : " . $mysqli->error ;
    exit(1);
  }

  if (!($updateStatsDateValuesStmt = $mysqli->prepare("UPDATE statistics SET year = YEAR(ts), month = MONTH(ts), day = DAYOFMONTH(ts), t = TIME(STR_TO_DATE(ts, '%Y-%m-%dT%H:%i:%sZ')), week = WEEKOFYEAR(ts) WHERE id = ?"))) {
    echo "Error while preparing update statistics date values statement : " . $mysqli->error ;
    exit(1);
  }

  function updateStatistics() {
    global $selectStatsCountryStmt, $updateStatsCountryStmt;

    $statisticsWithoutCountry = getStatisticsWithoutCountry();

    foreach($statisticsWithoutCountry as $stats) {
      $id = $stats['id'];
      $latitude  = bcdiv($stats['latitude'], 10000000, 7);
      $longitude  = bcdiv($stats['longitude'], 10000000, 7);

      $countryISO = getCountryISOFromWebservice($id, $latitude, $longitude);

      insertCountryToStatistics($countryISO, $id);
      insertDateValuesToStatistics($id);
    }

    $selectStatsCountryStmt->close();
    $updateStatsCountryStmt->close();
  }

  function getStatisticsWithoutCountry() {
    global $selectStatsCountryStmt;

    $selectStatsCountryStmt->bind_param('i', $limit);
    $selectStatsCountryStmt->bind_result($id, $latitude, $longitude);

    $limit = MAX_WEBREQUESTS_PER_HOUR;

    if (! $selectStatsCountryStmt->execute()) {
      echo "Error while selecting max ".$limit." statistics where country is empty : ".$selectStatsCountryStmt->error."\n";
    }

    $result = array();

    while ($selectStatsCountryStmt->fetch()) {
      $result[] = array(
        'id' => $id,
        'latitude' => $latitude,
        'longitude' => $longitude
      );
    }

    return $result;
  }

  function getCountryISOFromWebservice($id, $latitude, $longitude) {
    echo "Requesting country ISO code for node ".$id." at latitude ".$latitude." and longitude ".$longitude."\n";

    $countryURL = WEBSERVICE_COUNTRY_URL."?lat=".$latitude."&lng=".$longitude."&username=".WEBSERVICE_USER;
    $webserviceResult = file_get_contents($countryURL);

    if (substr($webserviceResult, 0, 3) == "ERR") {
      echo "--- Error from web service : ".$webserviceResult."\n";
      $countryISO = "--";
      echo "--- Using ".$countryISO." as country code for node ".$id."\n";
    } else {
      $countryISO = substr($webserviceResult, 0, 2);
      echo "--- Result from web service for node ".$id.": ".$countryISO."\n";
    }

    return $countryISO;
  }


  function insertCountryToStatistics($countryISO, $id) {
    global $updateStatsCountryStmt;

    $updateStatsCountryStmt->bind_param('sd', $countryISO, $id);

    echo "--- Updating country ".$countryISO." of node ".$id."\n";

    if (! $updateStatsCountryStmt->execute()) {
      echo "--- Error while updating country ".$countryISO." of node ".$id." : ".$updateStatsCountryStmt->error."\n";
    }
  }

  function insertDateValuesToStatistics($id) {
    global $updateStatsDateValuesStmt;

    $updateStatsDateValuesStmt->bind_param('d', $id);

    echo "--- Updating date values of node ".$id."\n";

    if (! $updateStatsDateValuesStmt->execute()) {
      echo "--- Error while updating date values of node ".$id." : ".$updateStatsDateValuesStmt->error."\n";
    }
  }

  updateStatistics();

  $mysqli->commit();
  $mysqli->close();
}

?>
