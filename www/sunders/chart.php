<?php

  include './config.php';

  // current year, month, and period
  $statYear   = date('Y');  // YYYY
  $statMonth  = date('n');  // 1-12
  $statPeriod = '1';        // 1-28/29/30/31

  // get year from URL if valid
  if (array_key_exists('year', $_GET)) {
    $valueStr = $_GET['year'];
    $valueInt = (int) $valueStr;
    if (is_numeric($valueStr) && $valueInt >= 2007 && $valueInt <= $statYear) { // the first OSM surveillance entry is from 2007
      $statYear = $valueStr;
    }
  }

  // get month from URL if valid
  if (array_key_exists('month', $_GET)) {
    $valueStr = $_GET['month'];
    $valueInt = (int) $valueStr;
    if ($valueStr == 'all' || (is_numeric($valueStr) && $valueInt >= 1 && $valueInt <= 12)) {
      $statMonth = $valueStr;
    }
  }

  // get period from URL if valid
  if (array_key_exists('period', $_GET)) {
    $valueStr = $_GET['period'];
    $valueInt = (int) $valueStr;
    if (is_numeric($valueStr) && $valueInt >= 1) {
      if (($statMonth == 'all' && $valueInt <= 12) || ($statMonth != 'all' && $valueInt <= idate('t', strtotime($statYear.'-'.$statMonth)))) {
        $statPeriod = $valueStr;
      }
    }
  }

  function getButtongroupYear($y) {
    echo "<div class='buttongroup bg_year'>\n";
    for ($i = 2007; $i <= idate('Y'); $i++) {
      $isChecked = ($y == $i) ? "checked" : "";

      echo "<input type='radio' onClick='refreshChart();' id='bg_year_".$i."' name='year' value='".$i."' ".$isChecked.">\n
            <label for='bg_year_".$i."'>".$i."</label>\n";
    }
      echo "</div>\n";
  }

  function getButtongroupMonth($y, $m) {
    echo "<div class='buttongroup bg_month'>\n";
    $isChecked = ($m == 'all') ? "checked" : "";
    echo "<input type='radio' onClick='refreshChart();' id='bg_month_all' name='month' value='all' ".$isChecked.">\n
          <label for='bg_month_all'>all</label>\n";
    for ($i = 1; $i <= 12; $i++) {
      $isChecked = ($m == $i) ? "checked" : "";
      echo "<input type='radio' onClick='refreshChart();' id='bg_month_".$i."' name='month' value='".$i."' ".$isChecked.">\n
            <label for='bg_month_".$i."'>".date('M', strtotime($y.'-'.$i))."</label>\n";
    }
      echo "</div>\n";
  }

  function getUploadsChartDataFromDB($y, $m) {
    /* Connect to database */
    $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
    if ($mysqli->connect_errno) {
      header('Content-type: application/json');
      $result = '{"error":"error while connecting to db : ' . $mysqli->error . '"}';
      echo $result;
      exit;
    }

    if ($m == 'all') {
      // e.g. SELECT month period, COUNT(*) uploads FROM statistics WHERE year = 2015 GROUP BY month
      $periodField = 'month';
      $whereClause = 'year = ?';
    } else {
      // e.g. SELECT day period, COUNT(*) uploads FROM statistics WHERE year=2015 AND month=12 GROUP BY day
      $periodField = 'day';
      $whereClause = 'year = ? AND month = ?';
    }

    $sql = 'SELECT '.$periodField.' period, COUNT(*) uploads
      FROM statistics
      WHERE '.$whereClause.'
      GROUP BY '.$periodField;

    $stmt = $mysqli->prepare($sql);

    if ($m == 'all') {
      $stmt->bind_param('i', $y);
    } else {
      $stmt->bind_param('ii', $y, $m);
    }

    $stmt->bind_result($period, $uploads);
    $stmt->execute();

    $result = array();

    while ($stmt->fetch()) {
      $result[$period] = $uploads;
    }

    $stmt->close();
    $mysqli->close();

    return $result;
  }

  function getUploadsChart($y, $m) {
    $uploadsPerPeriod = getUploadsChartDataFromDB($y, $m);
    $maxUploads = max($uploadsPerPeriod);

    if ($m == 'all') {
      for ($i = 1; $i <= 12; $i++) {
        $period = date('M', strtotime($y.'-'.$i));
        $columnWidth = '40px';
        getChartColumn($y, $m, $uploadsPerPeriod, $i, $maxUploads, $period, $columnWidth);
      }
    } else {
      for ($i = 1; $i <= idate('t', strtotime($y.'-'.$m)); $i++) {
        $period = $i;
        $columnWidth = '20px';
        getChartColumn($y, $m, $uploadsPerPeriod, $i, $maxUploads, $period, $columnWidth);
      }
    }
  }

  function getOSMUploadsForPeriod($uploadsPerPeriod, $period) {
    if (array_key_exists ($period, $uploadsPerPeriod)) {
      return $uploadsPerPeriod[$period];
    } else {
      return 0;
    }
  }

  function getRatioForOSMUploads($uploads, $maxUploads) {
    if ($uploads > 0 && $uploads < $maxUploads) {
      return $uploads / $maxUploads;
    } elseif ($uploads != 0 && $uploads == $maxUploads) {
      return 1;
    }
    return 0;
  }

  function getChartColumn($y, $m, $uploadsPerPeriod, $periodKey, $maxUploads, $period, $columnWidth) {
    $uploads = getOSMUploadsForPeriod($uploadsPerPeriod, $periodKey);
    $ratio = getRatioForOSMUploads($uploads, $maxUploads);

    if ($m != 'all') {
      $day = substr(date('D', strtotime($y.'-'.$m.'-'.$period)), 0, 2);
      $dayDiv = "<div>".$day."</div>\n";
    } else {
      $dayDiv = '';
    }

    echo "<div class='slice'>\n
            <div>".$uploads."</div>\n
            <div class='column' style='width: ".$columnWidth."; height: ".(360 * $ratio)."px;'></div>\n
            <div>".$period."</div>\n
            ".$dayDiv."
          </div>\n";
  }

?>
