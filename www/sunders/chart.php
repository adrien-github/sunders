<?php
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
    echo '<div class="buttongroup bg_year">';
    for ($i = 2007; $i <= idate('Y'); $i++) {
      $isChecked = ($y == $i) ? 'checked' : '';

      echo '<input type="radio" onClick="refreshChart();" id="bg_year_'.$i.'" name="year" value="'.$i.'" '.$isChecked.'>
            <label for="bg_year_'.$i.'">'.$i.'</label>';
    }
      echo '</div>';
  }

  function getButtongroupMonth($y, $m, $i18nStatistics, $i18nStatisticsDefault) {
    echo '<div class="buttongroup bg_month">';
    $isChecked = ($m == 'all') ? 'checked' : '';
    echo '<input type="radio" onClick="refreshChart();" id="bg_month_all" name="month" value="all" '.$isChecked.'>
          <label for="bg_month_all">'.translate($i18nStatistics, $i18nStatisticsDefault, 'all', [], [], []).'</label>';
    for ($i = 1; $i <= 12; $i++) {
      $isChecked = ($m == $i) ? 'checked' : '';
      echo '<input type="radio" onClick="refreshChart();" id="bg_month_'.$i.'" name="month" value="'.$i.'" '.$isChecked.'>
            <label for="bg_month_'.$i.'">'.translate($i18nStatistics, $i18nStatisticsDefault, date('M', strtotime($y.'-'.$i)), [], [], []).'</label>';
    }
      echo '</div>';
  }

  function getUploadsChartDataFromDB($y, $m) {
    /* Connect to database */
    $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
    if ($mysqli->connect_errno) {
      header('Content-type: application/json');
      $result = '{"error":"error while connecting to db : '.$mysqli->error.'"}';
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

  function getUploadsChart($y, $m, $i18nStatistics, $i18nStatisticsDefault) {
    $uploadsPerPeriod = getUploadsChartDataFromDB($y, $m);
    $maxUploads = max($uploadsPerPeriod);

    if ($m == 'all') {
      for ($i = 1; $i <= 12; $i++) {
        $period = date('M', strtotime($y.'-'.$i));
        $columnWidth = '40px';
        getChartColumn($y, $m, $uploadsPerPeriod, $i, $maxUploads, $period, $columnWidth, $i18nStatistics, $i18nStatisticsDefault);
      }
    } else {
      for ($i = 1; $i <= idate('t', strtotime($y.'-'.$m)); $i++) {
        $period = $i;
        $columnWidth = '20px';
        getChartColumn($y, $m, $uploadsPerPeriod, $i, $maxUploads, $period, $columnWidth, $i18nStatistics, $i18nStatisticsDefault);
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

  function getChartColumn($y, $m, $uploadsPerPeriod, $periodKey, $maxUploads, $period, $columnWidth, $i18nStatistics, $i18nStatisticsDefault) {
    $uploads = getOSMUploadsForPeriod($uploadsPerPeriod, $periodKey);
    $ratio = getRatioForOSMUploads($uploads, $maxUploads);

    if ($m != 'all') {
      // $day = substr(date('D', strtotime($y.'-'.$m.'-'.$period)), 0, 2);
      $day = translate($i18nStatistics, $i18nStatisticsDefault, substr(date('D', strtotime($y.'-'.$m.'-'.$period)), 0, 2), [], [], []);

      $dayDiv = '<div>'.$day.'</div>';
    } else {
      $period = translate($i18nStatistics, $i18nStatisticsDefault, $period, [], [], []);
      $dayDiv = '';
    }

    echo '<div class="slice">
            <div>'.$uploads.'</div>
            <div class="column" style="width: '.$columnWidth.'; height: '.(360 * $ratio).'px;"></div>
            <div>'.$period.'</div>
            '.$dayDiv.'
          </div>';
  }

?>
