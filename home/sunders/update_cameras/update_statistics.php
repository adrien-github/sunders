<?php

include "config.php";

if (USE_STATISTICS) {

  $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);

  if ($mysqli->connect_errno) {
    echo "Error while connecting to DB : ".$mysqli->error." \n" ;
    exit(1);
  }
  $mysqli->autocommit(FALSE);

  if (!($selectStatsCountryStmt = $mysqli->prepare("SELECT s.id, p.latitude, p.longitude
      FROM statistics AS s
      INNER JOIN position AS p
      ON p.id = s.id
      WHERE s.country IS NULL LIMIT ?"))) {
    echo "Error while preparing select statistics country statement : ".$mysqli->error."\n";
    exit(1);
  }

  if (!($updateStatsCountryStmt = $mysqli->prepare("UPDATE statistics
      SET country = ?
      WHERE id = ?"))) {
    echo "Error while preparing update statistics country statement : " . $mysqli->error ;
    exit(1);
  }

  if (!($updateStatsDateValuesStmt = $mysqli->prepare("UPDATE statistics
      SET year = YEAR(ts),
        month = MONTH(ts),
        day = DAYOFMONTH(ts),
        t = TIME(STR_TO_DATE(ts, '%Y-%m-%dT%H:%i:%sZ')),
        week = WEEKOFYEAR(ts)
      WHERE id = ?"))) {
    echo "Error while preparing update statistics date values statement : " . $mysqli->error ;
    exit(1);
  }

  if (!($updateStatsZeroAreaTypeStmt = $mysqli->prepare("UPDATE statistics
      SET area = 0,
        type = 0
      WHERE id = ?"))) {
    echo "Error while preparing update statistics area and type to zero statement : " . $mysqli->error ;
    exit(1);
  }

  if (!($updateStatsAreaStmt = $mysqli->prepare("UPDATE statistics AS s
      INNER JOIN tag AS t
      ON t.id = s.id
      SET s.area = CASE
          WHEN t.k = 'surveillance' AND t.v = 'public' THEN 1
          WHEN t.k = 'surveillance' AND t.v = 'outdoor' THEN 2
          WHEN t.k = 'surveillance' AND t.v = 'indoor' THEN 3
          ELSE 0
        END
      WHERE s.id = ? AND (
        t.k = 'surveillance' AND (
        t.v = 'public' OR t.v = 'outdoor' OR t.v = 'indoor'))"))) {
    echo "Error while preparing update statistics area statement : " . $mysqli->error ;
    exit(1);
  }

  if (!($updateStatsTypeStmt = $mysqli->prepare("UPDATE statistics AS s
      INNER JOIN tag AS t
      ON t.id = s.id
      SET s.type = CASE
          WHEN t.k = 'camera:type' AND t.v = 'fixed' THEN 1
          WHEN t.k = 'camera:type' AND t.v = 'panning' THEN 2
          WHEN t.k = 'camera:type' AND t.v = 'dome' THEN 3
          WHEN t.k = 'surveillance:type' AND t.v = 'guard' THEN 4
          WHEN (t.k = 'surveillance:type' AND t.v = 'ALPR')
            OR (t.k = 'surveillance' AND (
              t.v = 'level_crossing' OR
              t.v = 'red_light' OR
              t.v = 'speed_camera')) THEN 5
          ELSE 0
        END
      WHERE s.id = ? AND (
        (t.k = 'camera:type' AND (t.v = 'fixed' OR t.v = 'panning' OR t.v = 'dome')) OR
        (t.k = 'surveillance:type' AND (t.v = 'guard' OR t.v = 'ALPR')) OR
        (t.k = 'surveillance' AND (
          t.v = 'level_crossing' OR
          t.v = 'red_light' OR
          t.v = 'speed_camera')))"))) {
    echo "Error while preparing update statistics type statement : " . $mysqli->error ;
    exit(1);
  }

  function updateStatistics() {
    global $selectStatsCountryStmt, $updateStatsCountryStmt, $updateStatsDateValuesStmt, $updateStatsZeroAreaTypeStmt, $updateStatsAreaStmt, $updateStatsTypeStmt;

    $statisticsWithoutCountry = getStatisticsWithoutCountry();

    foreach($statisticsWithoutCountry as $stats) {
      $id = $stats['id'];
      $latitude  = bcdiv($stats['latitude'], 10000000, 7);
      $longitude  = bcdiv($stats['longitude'], 10000000, 7);

      $countryISO = getCountryISOFromWebservice($id, $latitude, $longitude);

      updateStatsCountry($countryISO, $id);
      updateStatsDateValues($id);
      updateStatsZeroAreaType($id);
      updateStatsArea($id);
      updateStatsType($id);
    }

    $selectStatsCountryStmt->close();
    $updateStatsCountryStmt->close();
    $updateStatsDateValuesStmt->close();
    $updateStatsZeroAreaTypeStmt->close();
    $updateStatsAreaStmt->close();
    $updateStatsTypeStmt->close();
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


  function updateStatsCountry($countryISO, $id) {
    global $updateStatsCountryStmt;

    $updateStatsCountryStmt->bind_param('sd', $countryISO, $id);

    echo "--- Updating country ".$countryISO." of node ".$id."\n";

    if (! $updateStatsCountryStmt->execute()) {
      echo "--- Error while updating country ".$countryISO." of node ".$id." : ".$updateStatsCountryStmt->error."\n";
    }
  }

  function updateStatsDateValues($id) {
    global $updateStatsDateValuesStmt;

    $updateStatsDateValuesStmt->bind_param('d', $id);

    echo "--- Updating date values of node ".$id."\n";

    if (! $updateStatsDateValuesStmt->execute()) {
      echo "--- Error while updating date values of node ".$id." : ".$updateStatsDateValuesStmt->error."\n";
    }
  }

  function updateStatsZeroAreaType($id) {
    global $updateStatsZeroAreaTypeStmt;

    $updateStatsZeroAreaTypeStmt->bind_param('d', $id);

    echo "--- Updating area and type to zero of node ".$id."\n";

    if (! $updateStatsZeroAreaTypeStmt->execute()) {
      echo "--- Error while updating area and type to zero of node ".$id." : ".$updateStatsZeroAreaTypeStmt->error."\n";
    }
  }

  function updateStatsArea($id) {
    global $updateStatsAreaStmt;

    $updateStatsAreaStmt->bind_param('d', $id);

    echo "--- Updating area of node ".$id."\n";

    if (! $updateStatsAreaStmt->execute()) {
      echo "--- Error while updating area of node ".$id." : ".$updateStatsAreaStmt->error."\n";
    }
  }

  function updateStatsType($id) {
    global $updateStatsTypeStmt;

    $updateStatsTypeStmt->bind_param('d', $id);

    echo "--- Updating type of node ".$id."\n";

    if (! $updateStatsTypeStmt->execute()) {
      echo "--- Error while updating type of node ".$id." : ".$updateStatsTypeStmt->error."\n";
    }
  }

  updateStatistics();

  $mysqli->commit();
  $mysqli->close();
}

?>
