<?php
  $pieRadius = 50;
  $validPies = array('country', 'area', 'type');
  $validTimeLines = array('single', 'sum');
  $surveillanceNodesTotal;
  $whereCriteria = '';
  $paramTypes = '';
  $year = 'all';
  $month = 'all';
  $titleStringFilter = '';
  $titleStringTime = '';

  if (!isset($pathToWebFolder)) {
    $pathToWebFolder = '../';
  }

  include $pathToWebFolder.'config.php';

  if (!isset($initialLanguage)) {
    $initialLanguage = DEFAULT_LANGUAGE;
  }

  include $pathToWebFolder.'decode-json.php';
  include $pathToWebFolder.'i18n.php';

  $initialPie = DEFAULT_PIE;
  $initialTime = DEFAULT_TIME;

  // get pie from URL if valid
  if (array_key_exists('pie', $_GET)) {
    $initialPie = $_GET['pie'];
    if (!in_array($initialPie, $validPies)) {
      $initialPie = DEFAULT_PIE;
    }
  }

  // get time chart type from URL if valid
  if (array_key_exists('time', $_GET)) {
    $initialTime = $_GET['time'];
    if (!in_array($initialTime, $validTimeLines)) {
      $initialTime = DEFAULT_TIME;
    }
  }

  // get columns from URL if valid
  if (array_key_exists('cols', $_GET)) {
    $cols = $_GET['cols'];
    $colsArray = explode('|', $cols);

    if (count($colsArray) > 2 || in_array($initialPie, $colsArray)) {
      $colsArray = array();
    }

    foreach ($colsArray as $col) {
      if (!in_array($col, $validPies)) {
        // ignore 'cols'
        $colsArray = array();
        break;
      }
    }
  }

  // get values from URL if valid
  if (!empty($colsArray) && array_key_exists('vals', $_GET)) {
    $vals = $_GET['vals'];
    $vals = strtoupper($vals);
    $valsArray = explode('|', $vals);

    if (count($colsArray) == count($valsArray)) {
      for ($i = 0; $i < count($colsArray); $i++) {
        if ($colsArray[$i] == 'area') {
          $i18n = $i18nStats;
          $i18nDefault = $i18nStatsDefault;
          $key = 'area'.$valsArray[$i].'-2';
        } elseif ($colsArray[$i] == 'type') {
          $i18n = $i18nStats;
          $i18nDefault = $i18nStatsDefault;
          $key = 'type'.$valsArray[$i].'-2';
        } else {
          $i18n = $i18nCountries;
          $i18nDefault = $i18nCountriesDefault;
          // Check if $key consists of 2 capital letters.
          $key = preg_match('/\b[A-Z]{2}\b/', $valsArray[$i]) === 1 || '--' ? $valsArray[$i] : 'dontfindme';
        }
        if (!isValidValue($i18n, $i18nDefault, $key)) {
          // ignore 'vals' and 'cols'
          $valsArray = array();
          $colsArray = array();
          break;
        }
      }
    } else {
      // ignore 'vals' and 'cols'
      $valsArray = array();
      $colsArray = array();
    }
  }

  // get year from URL if valid
  if (array_key_exists('year', $_GET)) {
    $valueStr = $_GET['year'];
    $valueInt = (int) $valueStr;
    if (is_numeric($valueStr) && $valueInt >= 2007 && $valueInt <= date('Y')) { // the first OSM surveillance entry is from 2007
      $year = $valueStr;
    }
  }

  // get month from URL if valid
  if (array_key_exists('month', $_GET)) {
    $valueStr = $_GET['month'];
    $valueInt = (int) $valueStr;
    if (is_numeric($valueStr) && $valueInt >= 1 && $valueInt <= 12) {
      $month = $valueStr;
    }
  }

  $pieLevel = 'level'.count($colsArray);

  $decodedStatsQueryJSON = getDecodedJSON($pathToWebFolder.'json/stats-query.json');
  $levelObject = $decodedStatsQueryJSON->{$pieLevel};
  $statsKey = count($colsArray) > 0 ? $initialPie.'_'.$cols : $initialPie;
  $statsQueryObject = $levelObject->{$statsKey};

  /* Connect to database */
  $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
  if($mysqli->connect_errno) {
    echo 'Error while connecting to DB : $mysqli->error \n' ;
    exit(1);
  }
  $mysqli->autocommit(FALSE);

  function isValidValue($i18n, $i18nDefault, $key) {
    $translated = translate($i18n, $i18nDefault, $key, [], [], []);
    return strlen($translated) != 0;
  }

  function getCharts($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject) {

    $surveillanceStatsJSON = getSurveillanceStatsJSON($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject);

    return getSurveillanceStatsTags($surveillanceStatsJSON);
  }

  function getSurveillanceStatsJSON($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject) {
    $objectStrings[] = getTotalStatsJSON($i18nStats, $i18nStatsDefault, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject);
    $objectStrings[] = getNaviStatsJSON($i18nStats, $i18nStatsDefault, $statsQueryObject, $pieType, $lineType, $colsArray, $valsArray, $year, $month);
    $objectStrings[] = getPieStatsJSON($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $statsQueryObject);

    $pieStatsObject = json_decode('{'.$objectStrings[2].'}');
    $pieArray = $pieStatsObject->{'pie-chart'}->{'pie'};
    $pieLegendArray = $pieStatsObject->{'pie-chart'}->{'legend'};

    $objectStrings[] = getTimeStatsJSON($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $pieArray, $pieLegendArray);

    return '{'.(implode(',', $objectStrings)).'}';
  }

  function getTotalStatsJSON($i18nStats, $i18nStatsDefault, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject) {
    global $surveillanceNodesTotal, $titleStringFilter, $titleStringTime;

    $surveillanceNodesTotal = getTotalDataFromDB($colsArray, $valsArray, $year, $month);

    $totalArray = $statsQueryObject->{'total'};

    if ($pieLevel == 'level0') {
      foreach($totalArray as $totalKey) {
        $filterArray[] = translate($i18nStats, $i18nStatsDefault, $totalKey, [], [], []);
      }
    } else {
      foreach($totalArray as $totalKey) {
        $totalValue = $valsArray[array_search($totalKey, $colsArray)];

        if ($totalKey == 'country') {
          $filterArray[] = translate($i18nStats, $i18nStatsDefault, $totalKey, [], [], []).': '.$totalValue;
        } elseif (substr($totalKey, -4) == '-all') {
          $filterArray[] = translate($i18nStats, $i18nStatsDefault, $totalKey, [], [], []);
        } else {
          $filterArray[] = translate($i18nStats, $i18nStatsDefault, $totalKey, [], [], []).': '.
            translate($i18nStats, $i18nStatsDefault, $totalKey.$totalValue.'-2', [], [], []);
        }
      }
    }

    $titleStringFilter = implode('&ensp;&bull;&ensp;', $filterArray);

    $timeArray[] = $year == 'all' ?
      translate($i18nStats, $i18nStatsDefault, 'year-all', [], [], []) :
      translate($i18nStats, $i18nStatsDefault, 'year', [], [], []).': '.$year;

    $timeArray[] = $month == 'all' ?
      translate($i18nStats, $i18nStatsDefault, 'month-all', [], [], []) :
      translate($i18nStats, $i18nStatsDefault, 'month', [], [], []).': '.translate($i18nStats, $i18nStatsDefault, date('M', strtotime('1970-'.$month)).'_', [], [], []);

    $titleStringTime = implode('&ensp;&bull;&ensp;', $timeArray);
    $labelKey = 'surv-node'.($surveillanceNodesTotal != 1 ? 's' : '');

    return '"total": {
        "no-of-nodes": '.$surveillanceNodesTotal.',
        "label": "'.translate($i18nStats, $i18nStatsDefault, $labelKey, [], [], []).'",
        "filter": "'.$titleStringFilter.'",
        "time": "'.$titleStringTime.'"
      }';
  }

  function getTotalDataFromDB($colsArray, $valsArray, $year, $month) {
    global $mysqli, $whereCriteria, $whereValues, $paramTypes;

    $whereValues = $valsArray;

    if (!empty($colsArray)) {
      foreach ($colsArray as $col) {
        $whereCriteriaArray[] = $col.'=?';
        $paramTypes = $col == 'country' ? $paramTypes.'s' : $paramTypes.'i';
      }
    }

    if (!empty($year) && $year != 'all') {
      $whereCriteriaArray[] = 'year=?';
      $paramTypes = $paramTypes.'i';
      $whereValues[] = $year;
    }

    if (!empty($month) && $month != 'all') {
      $whereCriteriaArray[] = 'month=?';
      $paramTypes = $paramTypes.'i';
      $whereValues[] = $month;
    }

    if (!empty($whereCriteriaArray)) {
      $whereCriteria = ' WHERE '.implode(' AND ', $whereCriteriaArray);
    }

    if (!($selectTotalStmt = $mysqli->prepare('SELECT COUNT(*) FROM statistics'.$whereCriteria))) {
      echo 'Error while preparing select total statement : ' . $mysqli->error ;
      exit(1);
    }

    if (!empty($whereValues)) {
      if (count($whereValues) == 1) {
        $selectTotalStmt->bind_param($paramTypes, $whereValues[0]);
      } elseif (count($whereValues) == 2) {
        $selectTotalStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1]);
      } elseif (count($whereValues) == 3) {
        $selectTotalStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2]);
      } elseif (count($whereValues) == 4) {
        $selectTotalStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2], $whereValues[3]);
      } else {
        echo 'Error while binding where parameters to select total statement.';
      }
    }

    $selectTotalStmt->bind_result($total);

    $selectTotalStmt->execute();
    while ($selectTotalStmt->fetch()) {
      $result = $total;
    }
    $selectTotalStmt->close();

    return $result;
  }

  function getNaviStatsJSON($i18nStats, $i18nStatsDefault, $statsQueryObject, $pieType, $lineType, $colsArray, $valsArray, $year, $month) {
    $naviCatsArrayString = getNaviCatsArrayString($i18nStats, $i18nStatsDefault, $statsQueryObject, $pieType, $lineType, $colsArray, $valsArray, $year, $month);
    $naviYearsArrayString = getNaviYearsArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month);
    $naviMonthsArrayString = getNaviMonthsArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month);

    return '"navi": {
        "label": "'.translate($i18nStats, $i18nStatsDefault, 'filter', [], [], []).'",
        '.$naviCatsArrayString.',
        '.$naviYearsArrayString.',
        '.$naviMonthsArrayString.'
      }';
  }

  function getPieStatsJSON($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $statsQueryObject) {
    $surveillanceNodesPerX = getPieDataFromDB($pieType);

    $pieLegendArrayStrings = getPieLegendArrayStrings($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $valsArray, $year, $month, $statsQueryObject, $surveillanceNodesPerX);

    return '"pie-chart": {
      '.$pieLegendArrayStrings['pie'].',
      '.$pieLegendArrayStrings['legend'].'
    }';
  }

  function getPieDataFromDB($pieType) {
    global $mysqli, $whereCriteria, $whereValues, $paramTypes;

    if (!($selectPieStmt = $mysqli->prepare('SELECT COUNT(*) total, '.$pieType.' FROM statistics '.$whereCriteria.' GROUP BY '.$pieType.' ORDER BY total DESC'))) {
      echo 'Error while preparing select country statement : ' . $mysqli->error ;
      exit(1);
    }

    if (!empty($whereValues)) {
      if (count($whereValues) == 1) {
        $selectPieStmt->bind_param($paramTypes, $whereValues[0]);
      } elseif (count($whereValues) == 2) {
        $selectPieStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1]);
      } elseif (count($whereValues) == 3) {
        $selectPieStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2]);
      } elseif (count($whereValues) == 4) {
        $selectPieStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2], $whereValues[3]);
      } else {
        echo 'Error while binding where parameters to select country statement.';
      }
    }

    $selectPieStmt->bind_result($noOfNodes, $sliceID);

    $selectPieStmt->execute();
    while ($selectPieStmt->fetch()) {
      $result[$sliceID] = $noOfNodes;
    }
    $selectPieStmt->close();

    return $result;
  }

  function getNaviCatsArrayString($i18nStats, $i18nStatsDefault, $statsQueryObject, $pieType, $lineType, $colsArray, $valsArray, $year, $month) {
    foreach($statsQueryObject->{'navi'} as $naviObject) {
      $naviObjectElements = array();
      $queryStrings = array();

      $colsQuery = '';
      $valsQuery = '';
      $colsQueryArray = array();
      $valsQueryArray = array();

      $selectedClass = $naviObject->{'pie'} == $pieType ? 'selected' : 'unselected';
      $naviObjectElements[] = '"selected-class": "'.$selectedClass.'"';

      $queryStrings[] = '?pie='.$naviObject->{'pie'};

      for ($i = 0; $i < count($colsArray); $i++) {
        if ($colsArray[$i] != $naviObject->{'pie'}) {
          $colsQueryArray[] = $colsArray[$i];
          $valsQueryArray[] = $valsArray[$i];
        }
      }

      if (!empty($colsQueryArray) && !empty($valsQueryArray)) {
       $queryStrings[] = 'cols='.implode('|', $colsQueryArray);
       $queryStrings[] = 'vals='.implode('|', $valsQueryArray);
      }
      $queryStrings[] = 'year='.$year;
      $queryStrings[] = 'month='.$month;
      $queryStrings[] = 'time='.$lineType;

      $naviObjectElements[] = '"url-query": "'.implode('&', $queryStrings).'"';
      $naviObjectElements[] = '"label": "'.translate($i18nStats, $i18nStatsDefault, $naviObject->{'label'}, [], [], []).'"';
      $naviObjectStrings[] = '{'.implode(',', $naviObjectElements)."}";
    }

    $naviArrayString = '"cats": ['.(implode(',', $naviObjectStrings)).']';

    return $naviArrayString;
  }

  function getPieLegendArrayStrings($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType, $lineType, $valsArray, $year, $month, $statsQueryObject, $surveillanceNodesPerX) {
    global $surveillanceNodesTotal, $pieRadius;

    $rotateDegrees = 0;
    $sumOfNodesLT1 = 0;
    $sumOfPercentageLT1 = 0;
    $fillColor = 1;

    foreach ($surveillanceNodesPerX as $sliceID => $noOfNodes) {
      $legendTitleArray = array();
      $queryValues = array();
      $queryStrings = array();
      $urlQuery = '';

      // The CSS file only knows 20 slice colors. Start again for every 20 slices.
      if ($fillColor > 20) {
        $fillColor = 1;
      }

      if (strlen($sliceID) == 0) {
        $sliceID = 'xxx';
      }

      $percentageValue = getPercentage($surveillanceNodesTotal, $noOfNodes);
      $degrees = getDegrees($percentageValue);

      $percentageUnit = '%';
      $percentageString = $percentageValue.$percentageUnit;
      $nodesUnit = 'node'.($noOfNodes != 1 ? 's' : '');
      $nodesString = $noOfNodes.' '.translate($i18nStats, $i18nStatsDefault, $nodesUnit, [], [], []);

      // Strings for the legend title
      if ($pieType == 'country') {
        // Countries: country ISO - translated country name
        $legendTitleArray[] = $sliceID == 'xxx' ? '??' : $sliceID;
        $legendTitleArray[] = translate($i18nCountries, $i18nCountriesDefault, $sliceID, [], [], []);
      } else {
        // Other: translated title - translated subtitle
        $legendTitleArray[] = translate($i18nStats, $i18nStatsDefault, $pieType.$sliceID.'-0', [], [], []);
        $legendSubtitle = translate($i18nStats, $i18nStatsDefault, $pieType.$sliceID.'-1', [], [], []);
      }

      $legendTitle = implode('&ensp;&bull;&ensp;', $legendTitleArray);

      // url query for links of pie and legend
      $pieQuery = $statsQueryObject->{'query'}->{'pie'};
      if (!(empty($pieQuery) || $sliceID === 'xxx' || in_array('xxx', $valsArray))) {
        $queryStrings[] = '?pie='.$pieQuery;
        $queryStrings[] = 'cols='.implode ('|', $statsQueryObject->{'query'}->{'cols'});
        foreach ($valsArray as $val) {
          $queryValues[] = $val;
        }
        $queryValues[] = $sliceID;
        $queryStrings[] = 'vals='.implode ('|', $queryValues);
        $queryStrings[] = 'year='.$year;
        $queryStrings[] = 'month='.$month;
        $queryStrings[] = 'time='.$lineType;

        $urlQuery = implode('&', $queryStrings);
      }

      if ($pieType == 'country' && $percentageValue < 1) {
        // Countries < 1% will be sumarized to one gray slice but displayed separately in the legend.
        $sumOfNodesLT1 = $sumOfNodesLT1 + $noOfNodes;
        $sumOfPercentageLT1 = $sumOfPercentageLT1 + $percentageValue;
        $fillClass = 'color0';
        $bgClass = $fillClass;
      } else {
        // Area slices are using color classes starting with 'icon'. Others are starting with 'color'.
        $fillClass = $pieType == 'area' ? 'icon'.$sliceID : 'color'.$fillColor;
        $bgClass = $fillClass;

        $pieObjectStrings[] = getPieObjectString($i18nStats, $i18nStatsDefault, $pieType, $degrees, $pieRadius, $rotateDegrees, $fillClass, $sliceID, $nodesString, $percentageString, $urlQuery);

        $rotateDegrees = $rotateDegrees + $degrees;
        $fillColor++;
      }

      $pieLegendObjectStrings[] = getPieLegendObjectString($legendTitle, $legendSubtitle, $percentageValue, $percentageUnit, $percentageString, $noOfNodes, $nodesUnit, $nodesString, $bgClass, $sliceID, $urlQuery);
    }

    // Values of the slice that represents the sum of all < 1% slices.
    $percentageString = $sumOfPercentageLT1.'%';
    $nodesString = $sumOfNodesLT1.' node'.($sumOfNodesLT1 != 1 ? 's' : '');

    if ($pieType == 'country' && $sumOfNodesLT1 > 0) {
      $pieObjectStrings[] = getPieObjectString($i18nStats, $i18nStatsDefault, $pieType, (360 - $rotateDegrees), $pieRadius, $rotateDegrees, $fillClass, 'lt1', $nodesString, $percentageString, null);
    }

    $pieLegendArrayStrings['pie'] = '"pie": ['.(implode(',', $pieObjectStrings)).']';
    $pieLegendArrayStrings['legend'] = '"legend": ['.(implode(',', $pieLegendObjectStrings)).']';

    return $pieLegendArrayStrings;
  }

  function getPieObjectString($i18nStats, $i18nStatsDefault, $pieType, $degrees, $pieRadius, $rotateDegrees, $fillClass, $sliceID, $nodesString, $percentageString, $urlQuery) {
    $sliceClass = (empty($urlQuery) || (strlen($sliceID) == 3) && ($sliceID == 'xxx')) ? '' : 'pie-slice-link';
    $pathTextID = $sliceID;

    if ($pieType == 'country') {
      if ($sliceID == 'lt1') {
        $idText = '< 1%';
        $sliceClass = '';
      } elseif ($sliceID == 'xxx') {
        $idText = '??';
      } elseif ($pieType == 'country') {
        $idText = $sliceID;
      }

      // mouseover/mouseout in set tag is not working with '--'
      if ($pathTextID == '--') {
        $pathTextID = 'zzz';
      }
    } else {
      $idText = translate($i18nStats, $i18nStatsDefault, ($pieType.$sliceID.'-2'), [], [], []);
    }

    $largeArcFlag = $degrees > 180 ? '1' : '0';
    $xyCoordinates = getXYCoordinates($degrees, $pieRadius);

    $pathClassesArray = array($sliceClass, $fillClass.'-fill');

    $pieObjectElements[] = '"id": "'.$sliceID.'"';
    $pieObjectElements[] = '"path-id": "slice'.$pathTextID.'"';
    $pieObjectElements[] = '"path-classes": "'.implode(' ', $pathClassesArray).'"';
    $pieObjectElements[] = '"path-d": "M'.$pieRadius.','.$pieRadius.' L'.$pieRadius.',0 A'.$pieRadius.','.$pieRadius.' 1 '.$largeArcFlag.',1 '.$xyCoordinates['X'].', '.$xyCoordinates['Y'].' z"';
    $pieObjectElements[] = '"has-link": '.(empty($sliceClass) ? 'false' : 'true');
    $pieObjectElements[] = '"url-query": "'.$urlQuery.'"';
    $pieObjectElements[] = '"anim-from": "0, '.$pieRadius.', '.$pieRadius.'"';
    $pieObjectElements[] = '"anim-to": "'.$rotateDegrees.', '.$pieRadius.', '.$pieRadius.'"';
    $pieObjectElements[] = '"anim-values": "0, '.$pieRadius.', '.$pieRadius.'; '.($rotateDegrees / 10).', '.$pieRadius.', '.$pieRadius.'; '.($rotateDegrees * 9 / 10).', '.$pieRadius.', '.$pieRadius.'; '.$rotateDegrees.', '.$pieRadius.', '.$pieRadius.'"';
    $pieObjectElements[] = '"text-id": "text'.$pathTextID.'"';
    $pieObjectElements[] = '"text-x": '.$pieRadius;
    $pieObjectElements[] = '"text-y": '.($pieRadius - 8);
    $pieObjectElements[] = '"text-label": "'.$idText.'"';
    $pieObjectElements[] = '"text-nodes-y": '.($pieRadius + 2);
    $pieObjectElements[] = '"text-nodes": "'.$nodesString.'"';
    $pieObjectElements[] = '"text-percentage-y": '.($pieRadius + 12);
    $pieObjectElements[] = '"text-percentage": "'.$percentageString.'"';

    return '{'.implode(',', $pieObjectElements)."}";
  }

  function getPieLegendObjectString($legendTitle, $legendSubtitle, $percentageValue, $percentageUnit, $percentageString, $nodesValue, $nodesUnit, $nodesString, $bgClass, $sliceID, $urlQuery) {
    $pieLegendObjectElements[] = '"id": "'.(strlen($sliceID) == 0 ? '??' : $sliceID).'"';
    $pieLegendObjectElements[] = '"bg-class": "'.$bgClass.'-bg"';
    $pieLegendObjectElements[] = '"title": "'.$legendTitle.'"';
    $pieLegendObjectElements[] = '"has-link": '.(empty($urlQuery) ? 'false' : 'true');
    $pieLegendObjectElements[] = '"url-query": "'.$urlQuery.'"';
    $pieLegendObjectElements[] = '"has-subtitle": '.(empty($legendSubtitle) ? 'false' : 'true');
    $pieLegendObjectElements[] = '"subtitle": "'.$legendSubtitle.'"';
    $pieLegendObjectElements[] = '"is-lt1": '.($percentageValue < 1 ? 'true' : 'false');
    $pieLegendObjectElements[] = '"percentage": { "value": '.$percentageValue.', "unit": "'.$percentageUnit.'", "string": "'.$percentageString.'"}';
    $pieLegendObjectElements[] = '"nodes": { "value": '.$nodesValue.', "unit": "'.$nodesUnit.'", "string": "'.$nodesString.'"}';

    return '{'.implode(',', $pieLegendObjectElements)."}";
  }

  function getTimeStatsJSON($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $pieArray, $pieLegendArray) {
    if ($year == 'all') {
      // e.g. SELECT year period, COUNT(*) uploads FROM statistics WHERE ... GROUP BY year
      $period = 'year';
    } elseif ($month == 'all') {
      // e.g. SELECT month period, COUNT(*) uploads FROM statistics WHERE ... GROUP BY month
      $period = 'month';
    } else {
      // e.g. SELECT day period, COUNT(*) uploads FROM statistics WHERE ... GROUP BY day
      $period = 'day';
    }

    $surveillanceNodesPerX = getTimeDataFromDB($pieType, $period);

    $timeNaviLinesArrayString = getNaviLinesArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month);
    $timeLegendArrayStrings = getTimeLegendArrayStrings($i18nStats, $i18nStatsDefault, $pieType, $lineType, $surveillanceNodesPerX, $year, $month, $pieArray, $pieLegendArray, $period);

    return '"time-chart": {
      '.$timeNaviLinesArrayString.',
      '.$timeLegendArrayStrings['time'].',
      '.$timeLegendArrayStrings['legend'].'
    }';
  }

  function getTimeDataFromDB($pieType, $period) {
    global $mysqli, $whereCriteria, $whereValues, $paramTypes;

    if (!($selectTimeStmt = $mysqli->prepare('SELECT '.$pieType.', '.$period.', COUNT(*) FROM statistics '.$whereCriteria.' GROUP BY '.$pieType.', '.$period))) {
      echo 'Error while preparing select country statement : ' . $mysqli->error ;
      exit(1);
    }

    if (!empty($whereValues)) {
      if (count($whereValues) == 1) {
        $selectTimeStmt->bind_param($paramTypes, $whereValues[0]);
      } elseif (count($whereValues) == 2) {
        $selectTimeStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1]);
      } elseif (count($whereValues) == 3) {
        $selectTimeStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2]);
      } elseif (count($whereValues) == 4) {
        $selectTimeStmt->bind_param($paramTypes, $whereValues[0], $whereValues[1], $whereValues[2], $whereValues[3]);
      } else {
        echo 'Error while binding where parameters to select country statement.';
      }
    }

    $selectTimeStmt->bind_result($lineID, $period, $noOfUploads);
    $selectTimeStmt->execute();

    $maxUploads = 0;
    $maxSumUploads = 0;

    while ($selectTimeStmt->fetch()) {
      $id = strlen($lineID) == 0 ? '??' : $lineID;

      if (empty($result[$id])) {
        $result[$id] = array($period => $noOfUploads);
      } else {
        $result[$id][$period] = $noOfUploads;
      }

      if (empty($sumResult[$period])) {
        $sumResult[$period] = $noOfUploads;
      } else {
        $sumResult[$period] = $sumResult[$period] + $noOfUploads;
      }

      $maxSumUploads = $sumResult[$period] > $maxSumUploads ? $sumResult[$period] : $maxSumUploads;
      $maxUploads = $noOfUploads > $maxUploads ? $noOfUploads : $maxUploads;
    }

    $selectTimeStmt->close();

    $result['max'] = $maxUploads;
    $result['max-sum'] = $maxSumUploads;

    return $result;
  }

  function getNaviYearsArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month) {
    return getNaviArrayString('years', $i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, 2007, idate('Y'));
  }

  function getNaviMonthsArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month) {
    return getNaviArrayString('months', $i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, 1, 12);
  }

  function getNaviLinesArrayString($i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month) {
    return getNaviArrayString('lines', $i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, null, null);
  }

  function getNaviArrayString($mode, $i18nStats, $i18nStatsDefault, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $loopStart, $loopEnd) {
    if ($mode == 'lines') {
      $naviObjectStrings[] = getNaviObjectString($mode, 'single', $pieType, $lineType, $colsArray, $valsArray, $year, $month, translate($i18nStats, $i18nStatsDefault, 'single', [], [], []));
      $naviObjectStrings[] = getNaviObjectString($mode, 'sum', $pieType, $lineType, $colsArray, $valsArray, $year, $month, translate($i18nStats, $i18nStatsDefault, 'total', [], [], []));
    } else {
      $allIndex = 'all';
      $allLabel = translate($i18nStats, $i18nStatsDefault, $allIndex, [], [], []);
      $naviObjectStrings[] = getNaviObjectString($mode, $allIndex, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $allLabel);

      for ($i = $loopStart; $i <= $loopEnd; $i++) {
        $label = $mode == 'months' ? translate($i18nStats, $i18nStatsDefault, date('M', strtotime('1970-'.$i)), [], [], []) : $i;
        $naviObjectStrings[] = getNaviObjectString($mode, $i, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $label);
      }
    }

    return '"'.$mode.'": ['.(implode(',', $naviObjectStrings)).']';
  }

  function getNaviObjectString($mode, $index, $pieType, $lineType, $colsArray, $valsArray, $year, $month, $label) {
    if ($mode == 'years') {
      $selectedTime = $year;
      $queryYear = $index;
      $queryMonth = $month;
      $queryType = $lineType;
    } elseif ($mode == 'months') {
      $selectedTime = $month;
      $queryYear = $year;
      $queryMonth = $index;
      $queryType = $lineType;
    } else {
      $selectedTime = $lineType;
      $queryYear = $year;
      $queryMonth = $month;
      $queryType = $index;
    }

    $selectedClass = $index == $selectedTime ? 'selected' : 'unselected';
    $naviObjectElements[] = '"selected-class": "'.$selectedClass.'"';

    $queryStrings[] = '?pie='.$pieType;
    if (!empty($colsArray) && !empty($valsArray)) {
     $queryStrings[] = 'cols='.implode('|', $colsArray);
     $queryStrings[] = 'vals='.implode('|', $valsArray);
    }
    $queryStrings[] = 'year='.$queryYear;
    $queryStrings[] = 'month='.$queryMonth;
    $queryStrings[] = 'time='.$queryType;
    $naviObjectElements[] = '"url-query": "'.implode('&', $queryStrings).'"';

    $naviObjectElements[] = '"label": "'.$label.'"';

    return '{'.implode(',', $naviObjectElements)."}";
  }

  function getTimeLegendArrayStrings($i18nStats, $i18nStatsDefault, $pieType, $lineType, $surveillanceNodesPerX, $year, $month, $pieArray, $pieLegendArray, $period) {
    global $titleStringFilter, $titleStringTime;

    $viewboxMultiplier = 80;

    if ($period == 'year') {
      $periodStart = 2007;
      $periodEnd = idate('Y');
    } elseif ($period == 'month') {
      $periodStart = 1;
      $periodEnd = 12;
    } elseif ($period == 'day') {
      $periodStart = 1;
      $periodEnd = idate('t', strtotime($year.'-'.$month));
    }

    if ($periodStart && $periodEnd) {
      for ($i = $periodStart; $i <= $periodEnd; $i++) {
        $periodArray[] = $i;
      }
    }

    foreach ($pieArray as $pieElement) {
      $pieIdArray[$pieElement->{'id'}] = $pieElement;
    }

    /*       1    2    3    4    5
          +----+----+----+----+----+
        1 |------------------------|
          +                        +
        2 |                        |
          +       chart: 5*3       +
        3 |                        |
          +                        +
        4 |------------------------|
          +                        +
          +----+----+----+----+----+
                viewbox: 5*4.5       */

    $vbWidth = count($periodArray) * $viewboxMultiplier;
    $vbHeight = $vbWidth * 4 / 5;
    $chartHeight = $vbWidth * 3 / 5;
    $chartPaddingTop = ($vbHeight - $chartHeight) * 1 / 5;
    $chartPaddingBottom = ($vbHeight - $chartHeight) * 4 / 5;
    $horizontalSegments = 4;

    $maxUploads = $lineType == 'sum' ? $surveillanceNodesPerX['max-sum'] : $surveillanceNodesPerX['max'];

    // paths of 5 horizontal grid lines to devide the chart in 4 horizontal segments
    for ($i=0; $i <= $horizontalSegments; $i++) {
      $gridY = $chartPaddingTop + ($i * $chartHeight / $horizontalSegments);
      $horizontalGridObjectStrings[] = '{ "path-d": "M0,'.$gridY.' L'.$vbWidth.','.$gridY.'" }';
      $horizontalTspanObjectStrings[] = '{
        "tspan-x": 0,
        "tspan-y": '.($gridY - ($vbHeight / 100)).',
        "label": "'.($maxUploads - ($maxUploads * $i / $horizontalSegments)).'" }';
    }

    foreach ($pieLegendArray as $i => $pieLegendObject) {
      $periodsObjectElements = array();
      $pathDElements = array();
      $colorClass = substr($pieLegendObject->{'bg-class'}, 0, -3);

      foreach ($periodArray as $j => $periodValue) {
        $periodStringArray = array();
        // path of a vertical grid line for the current period
        $gridX = ($viewboxMultiplier / 2) + ($j * $viewboxMultiplier);

        // do this only once
        if ($i == 0) {
          $verticalGridObjectStrings[] = '{ "path-d": "M'.$gridX.','.$chartPaddingTop.' L'.$gridX.','.($vbHeight - $chartPaddingBottom).'" }';

          if ($period == 'month') {
            $periodStringArray[] = translate($i18nStats, $i18nStatsDefault, date('M', strtotime('1970-'.$periodValue)), [], [], []);
          } elseif ($period == 'day') {
            $periodStringArray[] = $periodValue;
            $periodStringArray[] = translate($i18nStats, $i18nStatsDefault, substr(date('D', strtotime($year.'-'.$month.'-'.$periodValue)), 0, 2), [], [], []);
          } else {
            $periodStringArray[] = $periodValue;
          }

          foreach ($periodStringArray as $k => $periodString) {
            $verticalTspanObjectStrings[] = '{
              "tspan-x": '.$gridX.',
              "tspan-y": '.($chartPaddingTop + $chartHeight + ($vbHeight / 25 * ($k + 1))).',
              "label": "'.$periodString.'" }';
          }
        }

        // coordinate command of the first line point starts with a M (move to), the others with a L (line to)
        $dataCommand = $j == 0 ? 'M' : 'L';
        $commandX = $dataCommand.$gridX;

        // initialize the uploads sum of the current period
        if (empty($sumUploadsArray[$periodValue])) {
          $sumUploadsArray[$periodValue] = 0;
        }

        // get uploads of the current period
        $uploads = $surveillanceNodesPerX[$pieLegendObject->{'id'} == 'xxx' ? '??' : $pieLegendObject->{'id'}][$periodValue];
        $uploads = empty($uploads) ? 0 : $uploads;

        // sum the uploads of the current period
        $sumUploadsArray[$periodValue] = $sumUploadsArray[$periodValue] + $uploads;

        // uploads for the line point of the current period (sum or single)
        $uploadsForLine = $lineType == 'sum' ? $sumUploadsArray[$periodValue] : $uploads;

        if ($pieType == 'country' && $pieLegendObject->{'is-lt1'}) {
          // Countries < 1% will be sumarized to one gray line but displayed separately in the legend.
          if (empty($sumUploadsLT1Array[$commandX])) {
            $sumUploadsLT1Array[$commandX] = $uploads;
          } else {
            $sumUploadsLT1Array[$commandX] = $sumUploadsLT1Array[$commandX] + $uploads;
          }
        } else {
          $pathDElements[] = getPathDElement($chartPaddingTop, $chartHeight, $uploadsForLine, $maxUploads, $commandX);
        }

        // collect uploads per period for the legend JSON
        $periodsObjectElements[] = '{ "period": "'.(implode('<br>', $periodStringArray)).'", "uploads": '.$uploads.' }';
      }

      // collect line object for time JSON
      if (count($pathDElements) > 0) {
        $pathDString = implode(' ', $pathDElements);
        $lineObjectStrings[] = getLineObjectString($colorClass, $pathDString, $pieIdArray[$pieLegendObject->{'id'}], $vbWidth, $vbHeight);
      }

      // collect legend object for legend JSON
      $periodsObjectArrayString = '[ '.implode(',', $periodsObjectElements).' ]';
      $timeLegendObjectStrings[] = getTimeLegendObjectString($colorClass, $pieLegendObject, $periodsObjectArrayString);
    }

    // Values of the line that represents the sum of all < 1% lines.
    if ($pieType == 'country' && count($sumUploadsLT1Array) > 0) {
      $i = $periodStart;

      foreach ($sumUploadsLT1Array as $commandX => $sumUploadsLT1) {
        $uploadsForLine = $lineType == 'sum' ? $sumUploadsArray[$i++] : $sumUploadsLT1;
        $pathDElements[] = getPathDElement($chartPaddingTop, $chartHeight, $uploadsForLine, $maxUploads, $commandX);
      }

      // collect line object of < 1% line for time JSON
      $pathDString = implode(' ', $pathDElements);
      $lineObjectStrings[] = getLineObjectString('color0', $pathDString, $pieIdArray['lt1'], $vbWidth, $vbHeight);
    }

    if ($lineType == 'sum') {
      $lineObjectStrings = array_reverse($lineObjectStrings);
    }

    // collect sum legend object for legend JSON
    foreach ($sumUploadsArray as $periodValue => $sumUploads) {
      $periodsSumObjectElements[] = '{ "period": "'.$periodValue.'", "uploads": '.$sumUploads.' }';
    }
    $periodsSumObjectArrayString = '[ '.implode(',', $periodsSumObjectElements).' ]';
    $timeLegendObjectStrings[] = getTimeLegendObjectString('iconxxx', null, $periodsSumObjectArrayString);

    // common label for the vertical time chart labels
    if ($period == 'year' && $month != 'all') {
      $verticalTspanObjectStrings[] = '{
        "tspan-x": '.($vbWidth / 2).',
        "tspan-y": '.($chartPaddingTop + $chartHeight + ($vbHeight / 25 * 2)).',
        "label": "'.translate($i18nStats, $i18nStatsDefault, date('M', strtotime('1970-'.$month)).'_', [], [], []).'" }';
    } elseif ($period == 'month') {
      $verticalTspanObjectStrings[] = '{
        "tspan-x": '.($vbWidth / 2).',
        "tspan-y": '.($chartPaddingTop + $chartHeight + ($vbHeight / 25 * 2)).',
        "label": "'.$year.'" }';
    } elseif ($period == 'day') {
      $verticalTspanObjectStrings[] = '{
        "tspan-x": '.($vbWidth / 2).',
        "tspan-y": '.($chartPaddingTop + $chartHeight + ($vbHeight / 25 * 3)).',
        "label": "'.translate($i18nStats, $i18nStatsDefault, date('M', strtotime('1970-'.$month)).'_', [], [], []).' '.$year.'" }';
    }

    $timeObjectStrings[] = '"period": "'.$period.'"';
    $timeObjectStrings[] = '"vb-width": '.$vbWidth;
    $timeObjectStrings[] = '"vb-height": '.$vbHeight;
    $timeObjectStrings[] = '"h-grids": ['.(implode(',', $horizontalGridObjectStrings)).']';
    $timeObjectStrings[] = '"v-grids": ['.(implode(',', $verticalGridObjectStrings)).']';
    $timeObjectStrings[] = '"h-labels": {
      "text-x": 0,
      "text-y": 0,
      "font-size": '.($vbWidth / 50).',
      "tspan": ['.(implode(',', $horizontalTspanObjectStrings)).']
    }';
    $timeObjectStrings[] = '"v-labels": {
      "text-x": 0,
      "text-y": '.($chartPaddingTop + $chartHeight + (2 * $vbHeight / 100)).',
      "font-size": '.($vbWidth / 50).',
      "tspan": ['.(implode(',', $verticalTspanObjectStrings)).']
    }';
    $timeObjectStrings[] = '"lines": ['.(implode(',', $lineObjectStrings)).']';

    $timeLegendArrayStrings['time'] = '"time": {'.(implode(',', $timeObjectStrings)).'}';
    $timeLegendArrayStrings['legend'] = '"legend": {
      "title": "'.$titleStringFilter.'<br>'.$titleStringTime.'",
      "table": ['.(implode(',', $timeLegendObjectStrings)).']
    }';

    return $timeLegendArrayStrings;
  }

  function getPathDElement($chartPaddingTop, $chartHeight, $uploadsForLine, $maxUploads, $commandX) {
    $lineY = $chartPaddingTop + ($chartHeight - ($uploadsForLine / $maxUploads * $chartHeight));
    return $commandX.','.$lineY;
  }

  function getLineObjectString($colorClass, $pathDString, $pieObject, $vbWidth, $vbHeight) {
    $pathClassesArray = array($colorClass.'-stroke');

    $lineObjectElements[] = '"path-id": "line'.$pieObject->{'path-id'}.'"';
    $lineObjectElements[] = '"path-classes": "'.implode(' ', $pathClassesArray).'"';
    $lineObjectElements[] = '"path-d": "'.$pathDString.'"';
    $lineObjectElements[] = '"has-link": '.($pieObject->{'has-link'} ? 'true' : 'false');
    $lineObjectElements[] = '"url-query": "'.$pieObject->{'url-query'}.'"';
    $lineObjectElements[] = '"text-id": "'.$pieObject->{'text-id'}.'"';
    $lineObjectElements[] = '"text-x": '.($vbWidth / 2);
    $lineObjectElements[] = '"text-y": '.($vbHeight * 39 / 40);
    $lineObjectElements[] = '"text-label": "'.$pieObject->{'text-label'}.'&emsp;&bull;&emsp;'.$pieObject->{'text-percentage'}.'&emsp;&bull;&emsp;'.$pieObject->{'text-nodes'}.'"';
    $lineObjectElements[] = '"font-size": '.($vbWidth / 30);

    return '{'.implode(',', $lineObjectElements)."}";
  }

  function getTimeLegendObjectString($colorClass, $pieLegendObject, $periodsObjectArrayString) {
    global $surveillanceNodesTotal;

    if (!empty($pieLegendObject)) {
      $title = $pieLegendObject->{'title'};
      $hasLink = $pieLegendObject->{'has-link'} ? 'true' : 'false';
      $urlQuery = $pieLegendObject->{'url-query'};
      $percentage = $pieLegendObject->{'percentage'}->{'string'};
      $nodes = $pieLegendObject->{'nodes'}->{'string'};
    } else {
      $title = 'Total';
      $hasLink = 'false';
      $urlQuery = '';
      $percentage = '100%';
      $nodes = $surveillanceNodesTotal.' node'.($surveillanceNodesTotal != 1 ? 's' : '');
    }

    $timeLegendObjectElements[] = '"color-class": "'.$colorClass.'"';
    $timeLegendObjectElements[] = '"title": "'.$title.'"';
    $timeLegendObjectElements[] = '"has-link": '.$hasLink;
    $timeLegendObjectElements[] = '"url-query": "'.$urlQuery.'"';
    $timeLegendObjectElements[] = '"percentage": "'.$percentage.'"';
    $timeLegendObjectElements[] = '"nodes": "'.$nodes.'"';
    $timeLegendObjectElements[] = '"periods": '.$periodsObjectArrayString;

    return '{'.implode(',', $timeLegendObjectElements)."}";
  }

  function getSurveillanceStatsTags($surveillanceStatsJSON) {
    global $pieRadius;

    $surveillanceStatsObjects = json_decode($surveillanceStatsJSON);

    $totalObject = $surveillanceStatsObjects->{'total'};
    $naviObject = $surveillanceStatsObjects->{'navi'};
    $pieChartObject = $surveillanceStatsObjects->{'pie-chart'};
    $timeChartObject = $surveillanceStatsObjects->{'time-chart'};

    // ######## ######## ########     TOTAL CHART     ######## ######## ########

    $totalTags = '
      <svg class="vb-total" viewbox="0 0 100 35">
        <g class="g-total">
          <text class="number bold" x="50" y="15">
            '.$totalObject->{'no-of-nodes'}.'
          </text>
          <text class="text" x="50" y="22">
            <tspan>'.$totalObject->{'label'}.'</tspan>
            <tspan class="light" x="50" y="29">'.$totalObject->{'filter'}.'</tspan>
            <tspan class="light" x="50" y="33">'.$totalObject->{'time'}.'</tspan>
          </text>
        </g>
        Your browser is not able to display SVG graphics.
      </svg>';

    // ######## ######## ########        NAVI         ######## ######## ########

      $naviCatsTags = getNaviTags('cats', $naviObject, null);
      $naviYearsTags = getNaviTags('years', $naviObject, null);
      $naviMonthsTags = getNaviTags('months', $naviObject, null);

      $naviTags = '
        <input class="navi-section-check" id="navi" type="checkbox">
        <label class="navi-section-toggle" for="navi">
          <svg viewBox="0 0 100 5">
            <text class="navi-text" x="50" y="4">'.$naviObject->{'label'}.'</text>
          </svg>
        </label>
        <div class="navi-section">
          '.$naviCatsTags.'
          <div class="distance"></div>
          '.$naviYearsTags.'
          <div class="distance"></div>
          '.$naviMonthsTags.'
        </div>';

    // ######## ######## ########      PIE CHART      ######## ######## ########

    $piePathTags = '';
    $pieUseTags = '';
    $pieLegendTags = '';

    foreach($pieChartObject->{'pie'} as $piePieObject) {
      $pieAStartTag = '';
      $pieAEndTag = '';

      if ($piePieObject->{'has-link'}) {
        $pieAStartTag = '<a xlink:href="./'.$piePieObject->{'url-query'}.'">';
        $pieAEndTag = '</a>';
      }

      if ($piePieObject->{'text-percentage'} == '100.00%') {
        $pieTag = '
          <circle
            id="'.$piePieObject->{'path-id'}.'"
            class="'.$piePieObject->{'path-classes'}.'"
            cx="'.$pieRadius.'"
            cy="'.$pieRadius.'"
            r="'.$pieRadius.'"/>';
      } else {
        $pieTag = '
          <path
              id="'.$piePieObject->{'path-id'}.'"
              class="'.$piePieObject->{'path-classes'}.'"
              d="'.$piePieObject->{'path-d'}.'">
            <animateTransform
              attributeType="xml"
              attributeName="transform"
              type="rotate"
              from="'.$piePieObject->{'anim-from'}.'"
              to="'.$piePieObject->{'anim-to'}.'"
              dur="1"
              values="'.$piePieObject->{'anim-values'}.'"
              keyTimes="0; 0.2; 0.8; 1"
              fill="freeze">
          </path>';
      }

      $piePathTags = $piePathTags.
        $pieAStartTag.
        $pieTag.
        $pieAEndTag.'
        <text
            id="'.$piePieObject->{'text-id'}.'"
            class="pie-text"
            x="'.$piePieObject->{'text-x'}.'"
            y="'.$piePieObject->{'text-y'}.'"
            visibility="hidden">
          <tspan>'.$piePieObject->{'text-label'}.'</tspan>
          <tspan class="light" x="'.$piePieObject->{'text-x'}.'" y="'.$piePieObject->{'text-nodes-y'}.'">'.$piePieObject->{'text-nodes'}.'</tspan>
          <tspan class="light" x="'.$piePieObject->{'text-x'}.'" y="'.$piePieObject->{'text-percentage-y'}.'">'.$piePieObject->{'text-percentage'}.'</tspan>
          <set attributeName="visibility" to="visible" begin="'.$piePieObject->{'path-id'}.'.mouseover" end="piechart.mouseover"/>
        </text>';

      $pieUseTags = $pieUseTags.'
        <use xlink:href="#'.$piePieObject->{'text-id'}.'"/>';
    }

    // background colored pie center circle
    $piePathTags = $piePathTags.'<circle class="donut-hole" cx="'.$pieRadius.'" cy="'.$pieRadius.'" r="'.($pieRadius * 2 / 3).'"/>';

    foreach($pieChartObject->{'legend'} as $pieLegendObject) {
      $title = $pieLegendObject->{'title'};
      $subtitle = '';

      if ($pieLegendObject->{'has-link'}) {
        $title = '<a href="./'.$pieLegendObject->{'url-query'}.'">'.$title.'</a>';
      }

      if ($pieLegendObject->{'has-subtitle'}) {
        $subtitle = '
          <div class="subtitle">
            <div class="square-double"></div>
            <div>'.$pieLegendObject->{'subtitle'}.'</div>
          </div>';
      }

      $pieLegendTags = $pieLegendTags.'
        <div class="pie-legend-row">
          <div class="title bold">
            <div class="square '.$pieLegendObject->{'bg-class'}.'"></div>
            <div class="square"></div>
            <div>'.$title.'</div>
          </div>
          '.$subtitle.'
          <div class="values">
            <div class="square-double"></div>
            <div>'.$pieLegendObject->{'percentage'}->{'string'}.'&emsp;&bull;&emsp;'.$pieLegendObject->{'nodes'}->{'string'}.'</div>
          </div>
        </div>';
    }

    $pieTags = '
      <div class="pie">
        <div class="pie-chart">
          <svg id="piechart" viewbox="0 0 100 100">
            <g class="g-pie">
              '.$piePathTags.'
            </g>
            '.$pieUseTags.'
            Your browser is not able to display SVG graphics.
          </svg>
        </div>
        <div class="pie-legend">
          '.$pieLegendTags.'
        </div>
      </div>';

      $pieChartTags = $pieTags;

    // ######## ######## ########     TIME CHART     ######## ######## ########
    $timeObject = $timeChartObject->{'time'};
    $legendObject = $timeChartObject->{'legend'};
    $legendTableArray = $legendObject->{'table'};

    $timeNaviLinesTags = getNaviTags('lines', $timeChartObject, 'time');

    $noOfColumns = count($legendTableArray[0]->{'periods'});

    foreach($legendTableArray as $i => $timeLegendObject) {
      $tdArray = array();
      $rowTitleArray = array();

      $title = $timeLegendObject->{'title'};

      if ($timeLegendObject->{'has-link'}) {
        $title = '<a href="./'.$timeLegendObject->{'url-query'}.'">'.$title.'</a>';
      }

      $rowTitleArray[] = $title;  // &#9642;&#9726;&#9724;&#9632;
      $rowTitleArray[] = $timeLegendObject->{'percentage'};
      $rowTitleArray[] = $timeLegendObject->{'nodes'};

      $color = $timeLegendObject->{'color-class'};

      foreach($timeLegendObject->{'periods'} as $j => $periodObject) {
        // $evenClass = $j % 2 == 0 ? "even-bg" : '';

        if ($i == 0) {
          // $thArray[] = '<th class="'.$evenClass.'">'.$periodObject->{'period'}.'</th>';
          $thArray[] = '<th>'.$periodObject->{'period'}.'</th>';
          $tfArray[] = '<td>'.$periodObject->{'period'}.'</td>';
        }
        // $tdArray[] = '<td class="'.$color.'-bb '.$evenClass.'">'.$periodObject->{'uploads'}.'</td>';
        $tdArray[] = '<td class="'.$color.'-bb">'.$periodObject->{'uploads'}.'</td>';
      }

      $tbodyTrArray[] = '<tr><td class="row-distance" colspan="'.$noOfColumns.'">&nbsp;</td></tr>';
      $tbodyTrArray[] = '<tr><td class="row-title '.$color.'-bt" colspan="'.$noOfColumns.'">'.implode('&emsp;&bull;&emsp;', $rowTitleArray).'</td></tr>';
      $tbodyTrArray[] = '<tr class="right">'.implode('', $tdArray).'</tr>';
    }

    foreach ($timeObject->{'h-grids'} as $vGridObject) {
      $hGridPathArray[] = '<path d="'.$vGridObject->{'path-d'}.'"></path>';
    }

    foreach ($timeObject->{'v-grids'} as $hGridObject) {
      $vGridPathArray[] = '<path d="'.$hGridObject->{'path-d'}.'"></path>';
    }

    foreach ($timeObject->{'lines'} as $lineObject) {
      $lineAStartTag = '';
      $lineAEndTag = '';

      if ($lineObject->{'has-link'}) {
        $lineAStartTag = '<a xlink:href="./'.$lineObject->{'url-query'}.'">';
        $lineAEndTag = '</a>';
      }

      $lineTag = '
        <path
          id="'.$lineObject->{'path-id'}.'"
          class="'.$lineObject->{'path-classes'}.'"
          d="'.$lineObject->{'path-d'}.'">
        </path>';

      $textTag = '
        <text
            id="'.$lineObject->{'text-id'}.'"
            class="line-text"
            style="font-size: '.$lineObject->{'font-size'}.'px;"
            x="'.$lineObject->{'text-x'}.'"
            y="'.$lineObject->{'text-y'}.'"
            visibility="hidden">
          <tspan>'.$lineObject->{'text-label'}.'</tspan>
          <set attributeName="visibility" to="visible" begin="'.$lineObject->{'path-id'}.'.mouseover" end="linechart.mouseover"/>
        </text>';

      $linesPathArray[] =
        $lineAStartTag.
        $lineTag.
        $lineAEndTag.
        $textTag;
    }

    $hLabelsObject = $timeObject->{'h-labels'};
    foreach ($hLabelsObject->{'tspan'} as $tspanObject) {
      $hTspanArray[] = '<tspan class="light" x="'.$tspanObject->{'tspan-x'}.'" y="'.$tspanObject->{'tspan-y'}.'">'.$tspanObject->{'label'}.'</tspan>';
    }

    $vLabelsObject = $timeObject->{'v-labels'};
    foreach ($vLabelsObject->{'tspan'} as $tspanObject) {
      $vTspanArray[] = '<tspan class="light" x="'.$tspanObject->{'tspan-x'}.'" y="'.$tspanObject->{'tspan-y'}.'">'.$tspanObject->{'label'}.'</tspan>';
    }

    $timePathUseTags = '
      <svg id="linechart" class="'.$timeObject->{'period'}.'" viewbox="0 0 '.$timeObject->{'vb-width'}.' '.$timeObject->{'vb-height'}.'">
        <g class="h-grid">
          '.(implode('', $hGridPathArray)).'
        </g>
        <g class="v-grid">
          '.(implode('', $vGridPathArray)).'
        </g>
        <g class="g-time">
          '.(implode('', $linesPathArray)).'
        </g>
        <text
            x="'.$hLabelsObject->{'text-x'}.'"
            y="'.$hLabelsObject->{'text-y'}.'"
            style="font-size: '.$hLabelsObject->{'font-size'}.'px;">
          '.(implode('', $hTspanArray)).'
        </text>
        <text
            x="'.$vLabelsObject->{'text-x'}.'"
            y="'.$vLabelsObject->{'text-y'}.'"
            class="v-label"
            style="font-size: '.$vLabelsObject->{'font-size'}.'px;">
          '.(implode('', $vTspanArray)).'
        </text>
        Your browser is not able to display SVG graphics.
      </svg>';

    $timeLegendTags = '
      <table>
        <thead>
          <tr>
            '.(implode('', $thArray)).'
          </tr>
        </thead>
        <tfoot>
          <tr>
            '.(implode('', $tfArray)).'
          </tr>
        </tfoot>
        <tbody>
          '.(implode('', $tbodyTrArray)).'
        </tbody>
      </table>';

    $timeTags = '
      <div class="time">
        <div class="time-chart">
          '.$timePathUseTags.'
        </div>
        <div class="time-legend">
          <div class="title light">'.$legendObject->{'title'}.'</div>
          '.$timeLegendTags.'
        </div>
      </div>';

    $timeChartTags = $timeNaviLinesTags.$timeTags;

    return $totalTags.$naviTags.$pieChartTags.$timeChartTags;
  }

  function getNaviTags($mode, $naviObject, $id) {
    $naviEntryObjects = $naviObject->{$mode};
    $idString = !empty($id) ? 'id="'.$id.'"' : '';
    $naviTags = '<div '.$idString.' class="navi-row '.$mode.'">';

    if ($mode == 'lines') {
      foreach($naviEntryObjects as $naviEntryObject) {
        $naviTags = $naviTags.
          getNaviElement($naviEntryObject->{'selected-class'}, $naviEntryObject->{'url-query'}, $naviEntryObject->{'label'}, 10, 8, 'time');
      }
    } elseif ($mode == 'cats') {
      foreach($naviEntryObjects as $naviEntryObject) {
        $naviTags = $naviTags.
          getNaviElement($naviEntryObject->{'selected-class'}, $naviEntryObject->{'url-query'}, $naviEntryObject->{'label'}, 15, 12, null);
      }
    } else {
      $vbHeight = 24;
      $yText = 20;
      $naviElementsPerRow = 4;
      $emptyCell = '<div style="flex-grow: 1;"><svg viewbox="0 0 100 '.$vbHeight.'"></svg></div>';

      // start with the all element (first array element)
      $naviTags = $naviTags.
        getNaviElement($naviEntryObjects[0]->{'selected-class'}, $naviEntryObjects[0]->{'url-query'}, $naviEntryObjects[0]->{'label'}, $vbHeight, $yText, null);

      foreach($naviEntryObjects as $i => $naviEntryObject) {
        $modulo = $i % $naviElementsPerRow;

        // start loop with the second array element
        if ($i > 0) {
          $newRowTags = '';

          if ($modulo == 0 && $i + 1 < count($naviEntryObjects)) {
            $newRowTags = '</div><div class="navi-row '.$mode.'">'.$emptyCell;
          }

          $naviTags = $naviTags.
            getNaviElement($naviEntryObject->{'selected-class'}, $naviEntryObject->{'url-query'}, $naviEntryObject->{'label'}, $vbHeight, $yText, null).
            $newRowTags;
        }
      }

      if ($modulo != 0) {
        // fill the uncomplete row with empty cells
        for ($i=0; $i < $naviElementsPerRow - $modulo; $i++) {
          $naviTags = $naviTags.$emptyCell;
        }
      }
    }

    return $naviTags.'</div>';
  }

  function getNaviElement($selectedClass, $urlQuery, $label, $vbHeight, $yText, $id) {
    $idLink = !empty($id) ? '#'.$id : '';

    return '
      <div class="navi-element '.$selectedClass.'" style="flex-grow: 1;">
        <a href="./'.$urlQuery.$idLink.'">
          <svg viewbox="0 0 100 '.$vbHeight.'">
            <text class="navi-text" x="50" y="'.$yText.'">
              '.$label.'
            </text>
          </svg>
        </a>
      </div>';
  }

  function getPercentage($total, $part) {
    return number_format((($part*100)/$total), 2);
  }

  function getDegrees($percentage) {
    return 360*($percentage/100);
  }

  /*            |     x
               y|
   (4) ---------|--------- (1) X,Y
               r|    z
                |
  --------------+--------------
                |(100,100)
                |
   (3) ---------|--------- (2)
                |
                |*/
  function getXYCoordinates($degrees, $pieRadius) {
    if ($degrees <= 90) {
      // Coordinate in section (1)
      $angle = $degrees;
      $z = calculateZ($angle, $pieRadius);
      $x = calculateX($angle, $pieRadius);
      $y = calculateY($z, $x);
      $xyCoordinates['X'] = getCoordinateX12($x, $pieRadius);
      $xyCoordinates['Y'] = $y;
    } elseif ($degrees <= 180) {
      // Coordinate in section (2)
      $angle = 180 - $degrees;
      $z = calculateZ($angle, $pieRadius);
      $x = calculateX($angle, $pieRadius);
      $y = calculateY($z, $x);
      $xyCoordinates['X'] = getCoordinateX12($x, $pieRadius);
      $xyCoordinates['Y'] = getCoordinateY23($y, $pieRadius);
    } elseif ($degrees <= 270) {
      // Coordinate in section (3)
      $angle = $degrees - 180;
      $z = calculateZ($angle, $pieRadius);
      $x = calculateX($angle, $pieRadius);
      $y = calculateY($z, $x);
      $xyCoordinates['X'] = getCoordinateX34($x, $pieRadius);
      $xyCoordinates['Y'] = getCoordinateY23($y, $pieRadius);
    } else {
      // Coordinate in section (4)
      $angle = 360 - $degrees;
      $z = calculateZ($angle, $pieRadius);
      $x = calculateX($angle, $pieRadius);
      $y = calculateY($z, $x);
      $xyCoordinates['X'] = getCoordinateX34($x, $pieRadius);
      $xyCoordinates['Y'] = $y;
    }

    return $xyCoordinates;
  }

  // Calculate side z with the cosine rule [ z² = r² + r² – 2*r*r*cos(α) ]
  function calculateZ($angle, $pieRadius) {
    $doubleRadiusPow2 = 2*pow($pieRadius, 2);

    return sqrt($doubleRadiusPow2 - ($doubleRadiusPow2 * cos(deg2rad($angle))));
  }

  // Calculate side x using the sine rule [ sin(α) = x/r ]
  function calculateX($angle, $pieRadius) {
    return sin(deg2rad($angle)) * $pieRadius;
  }

  // Calculate side y using Mr. Pythagoras’ theorem [ x² + y² = z² ]
  function calculateY($z, $x) {
    return sqrt(pow($z, 2) - pow($x, 2));
  }

  // x-coordinate in section 1 or 2
  function getCoordinateX12($x, $pieRadius) {
    return $pieRadius + $x;
  }

  // x-coordinate in section 3 or 4
  function getCoordinateX34($x, $pieRadius) {
    return $pieRadius - $x;
  }

  // y-coordinate in section 2 or 3
  function getCoordinateY23($y, $pieRadius) {
    return (2 *($pieRadius - $y)) + $y;
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8"/>
    <title>SunderS &mdash; Statistics</title>

    <link rel="shortcut icon" href="<?php echo $pathToWebFolder.'favicon.ico' ?>">
    <link rel="icon" type="image/png" href="<?php echo $pathToWebFolder.'favicon.png' ?>" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $pathToWebFolder.'apple-touch-icon.png' ?>">
    <meta name="msapplication-TileColor" content="#f1eee8">
    <meta name="msapplication-TileImage" content="<?php echo $pathToWebFolder.'mstile-144x144.png' ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $pathToWebFolder.'css/stats.css' ?>">
  </head>
  <body>
    <a href="<?php echo $pathToWebFolder.$initialLanguage.'/' ?>">
      <div class="header">
        <img src="<?php echo $pathToWebFolder.'images/title-sunders.png' ?>" alt="Surveillance under Surveillance">
      </div>
    </a>
    <?php
      echo getCharts($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $initialPie, $initialTime, $colsArray, $valsArray, $year, $month, $pieLevel, $statsQueryObject);
      $mysqli->close();
    ?>
    <a href="<?php echo $pathToWebFolder.$initialLanguage.'/' ?>">
      <div class="footer">
        <img src="<?php echo $pathToWebFolder.'images/title-mea.png' ?>" alt="MAP 'EM ALL">
      </div>
    </a>
  </body>
</html>
