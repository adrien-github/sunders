<?php
  // just a git test...

  $validPies = array('country', 'area', 'type');

  if (!isset($pathToWebFolder)) {
    $pathToWebFolder = '../';
  }

  include $pathToWebFolder.'config.php';

  if (!isset($initialLanguage)) {
    $initialLanguage = DEFAULT_LANGUAGE;
  }

  $initialPie = DEFAULT_PIE;

  if (array_key_exists('pie', $_GET)) {
    $initialPie = $_GET['pie'];
    if (!in_array($initialPie, $validPies)) {
      $initialPie = DEFAULT_PIE;
    }
  }

  include $pathToWebFolder.'decode-json.php';
  include $pathToWebFolder.'i18n.php';

  $surveillanceNodesTotal;
  $pieRadius = 50;

  /* Connect to database */
  $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
  if($mysqli->connect_errno) {
    echo 'Error while connecting to DB : $mysqli->error \n' ;
    exit(1);
  }
  $mysqli->autocommit(FALSE);

  /* ###################################
     #### functions for TOTAL chart ####
     ################################### */

  function getTotalChart($i18nStats, $i18nStatsDefault) {
    global $surveillanceNodesTotal;

    $surveillanceNodesTotal = getTotalDataFromDB($selectTotalStmt);

    $chartHTML = '
      <svg class="vb-total" viewbox="0 0 100 30">
        <g class="g-total">
          <text class="number" x="50" y="15">
            '.$surveillanceNodesTotal.'
          </text>
          <text class="text" x="50" y="24">
            <tspan>'.translate($i18nStats, $i18nStatsDefault, 'surv-nodes', [], [], []).'</tspan>
            <tspan class="bold" x="50" y="29">&rsaquo;&ensp;'.translate($i18nStats, $i18nStatsDefault, 'worldwide', [], [], []).'&ensp;&lsaquo;</tspan>
          </text>
        </g>
        Your browser is not able to display SVG graphics.
      </svg>';

    return $chartHTML;
  }

  function getTotalDataFromDB() {
    global $mysqli;

    if (! ($selectTotalStmt = $mysqli->prepare('SELECT count(*) FROM statistics'))) {
      echo 'Error while preparing select total statement : ' . $mysqli->error ;
      exit(1);
    }
    $selectTotalStmt->bind_result($total);

    $selectTotalStmt->execute();
    while ($selectTotalStmt->fetch()) {
      $result = $total;
    }
    $selectTotalStmt->close();

    return $result;
  }

  /* #################################
     #### functions for PIE chart ####
     ################################# */

  function getPieChart($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $pieType) {
    global $surveillanceNodesTotal, $pieRadius, $validPies;

    $piePathTags = '';
    $pieUseTags = '';
    $pieLegendTags = '';
    $rotateDegrees = 0;
    $sumOfNodesLT1 = 0;
    $sumOfPercentageLT1 = 0;
    $fillColor = 1;

    $surveillanceNodesPerX = getPieDataFromDB($pieType);

    foreach ($surveillanceNodesPerX as $sliceID => $noOfNodes) {
      $legendTitleArray = array();

      // The CSS file only knows 20 slice colors. Start again for every 20 slices.
      if ($fillColor > 20) {
        $fillColor = 1;
      }

      if (strlen($sliceID) == 0) {
        $sliceID = 'xxx';
      }

      // Strings for the legend title
      if ($pieType == 'country') {
        // Countries: country ISO - translated country name
        $legendTitleArray[] = ($sliceID == 'xxx' ? '??' : $sliceID);
        $legendTitleArray[] = translate($i18nCountries, $i18nCountriesDefault, $sliceID, [], [], []);
      } else {
        // Other: translated title - translated subtitle
        $legendTitleArray[] = translate($i18nStats, $i18nStatsDefault, ($pieType.$sliceID.'-0'), [], [], []);
        $legendSubtitle = translate($i18nStats, $i18nStatsDefault, ($pieType.$sliceID.'-1'), [], [], []);
      }

      $legendTitle = implode("&ensp;&bull;&ensp;", $legendTitleArray);

      $percentage = getPercentage($surveillanceNodesTotal, $noOfNodes);
      $degrees = getDegrees($percentage);

      if ($pieType == 'country' && $percentage < 1) {
        // Countries < 1% will be sumarized to one gray slice but displayed separately in the legend.
        $sumOfNodesLT1 = $sumOfNodesLT1 + $noOfNodes;
        $sumOfPercentageLT1 = $sumOfPercentageLT1 + $percentage;
        $fillClass = 'color0';
        $bgClass = $fillClass;
      } else {
        // Area slices are using color classes starting with 'icon'. Others are starting with 'color'.
        $fillClass = (($pieType == 'area') ? 'icon'.$sliceID : 'color'.$fillColor);
        $bgClass = $fillClass;
        $piePathUseTags = getPiePathUseTags($i18nStats, $i18nStatsDefault, $pieType, $degrees, $pieRadius, $rotateDegrees, $fillClass, $sliceID, $noOfNodes, $percentage);
        $piePathTags = $piePathTags.$piePathUseTags['path'];
        $pieUseTags = $pieUseTags.$piePathUseTags['use'];
        $rotateDegrees = $rotateDegrees + $degrees;
        $fillColor++;
      }

      $pieLegendTag = getPieLegendTag($legendTitle, $legendSubtitle, $percentage, $noOfNodes, $bgClass);
      $pieLegendTags = $pieLegendTags.$pieLegendTag;
    }

    // Display the slice that represents the sum of all < 1% slices.
    if ($pieType == 'country' && $sumOfNodesLT1 > 0) {
      $piePathUseTags = getPiePathUseTags($i18nStats, $i18nStatsDefault, $pieType, (360 - $rotateDegrees), $pieRadius, $rotateDegrees, $fillClass, 'lt1', $sumOfNodesLT1, $sumOfPercentageLT1);
      $piePathTags = $piePathTags.$piePathUseTags['path'];
      $pieUseTags = $pieUseTags.$piePathUseTags['use'];
    }

    // background colored pie center circle
    $piePathTags = $piePathTags.'<circle cx="'.$pieRadius.'" cy="'.$pieRadius.'" r="'.($pieRadius * 2 / 3).'">';

    $chartHTML = '
      <div class="pie-navi">
        '.getPieNavi($i18nStats, $i18nStatsDefault, $pieType, $validPies).'
      </div>
      <div class="pie">
        <div class="pie-chart">
          <svg viewbox="0 0 100 100">
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

    return $chartHTML;
  }

  function getPieNavi($i18nStats, $i18nStatsDefault, $pieType, $validPies) {
    $pieNaviTags = '';

    foreach ($validPies as $pie) {

      $selectedClass = (($pie == $pieType) ? 'selected' : '');

      $pieNaviTags = $pieNaviTags.'
        <div id="pie" class="pie-navi-element '.$selectedClass.'">
          <a href="./?pie='.$pie.'#pie">
            <svg viewbox="0 0 100 15">
              <text class="pie-navi-text" x="50" y="12">
                '.translate($i18nStats, $i18nStatsDefault, ('pie-navi-'.$pie), [], [], []).'
              </text>
            </svg>
          </a>
        </div>';
    }

    return $pieNaviTags;
  }

  function getPieDataFromDB($pieType) {
    global $mysqli;

    if (! ($selectPieStmt = $mysqli->prepare('SELECT count(*) total, '.$pieType.' FROM statistics GROUP BY '.$pieType.' ORDER BY total DESC'))) {
      echo 'Error while preparing select country statement : ' . $mysqli->error ;
      exit(1);
    }
    $selectPieStmt->bind_result($noOfNodes, $sliceID);

    $selectPieStmt->execute();
    while ($selectPieStmt->fetch()) {
      $result[$sliceID] = $noOfNodes;
    }
    $selectPieStmt->close();

    return $result;
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
  function getPiePathUseTags($i18nStats, $i18nStatsDefault, $pieType, $degrees, $pieRadius, $rotateDegrees, $fillClass, $sliceID, $noOfNodes, $percentage) {

    $sliceClass = (($sliceID == 'xxx') ? '' : 'pie-slice');
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

    $largeArcFlag = (($degrees > 180) ? '1' : '0');

    $fontSize = 6;
    $xyCoordinates = getXYCoordinates($degrees, $pieRadius);

    $piePathTag = '
      <path
          id="slice'.$pathTextID.'"
          class="'.$sliceClass.' '.$fillClass.'-fill"
          d="M'.$pieRadius.','.$pieRadius.' L'.$pieRadius.',0 A'.$pieRadius.','.$pieRadius.' 1 '.$largeArcFlag.',1 '.$xyCoordinates['X'].', '.$xyCoordinates['Y'].' z">
        <animateTransform
          attributeType="xml"
          attributeName="transform"
          type="rotate"
          from="0, '.$pieRadius.', '.$pieRadius.'"
          to="'.$rotateDegrees.', '.$pieRadius.', '.$pieRadius.'"
          dur="1"
          values="0, '.$pieRadius.', '.$pieRadius.'; '.($rotateDegrees / 10).', '.$pieRadius.', '.$pieRadius.'; '.($rotateDegrees * 9 / 10).', '.$pieRadius.', '.$pieRadius.'; '.$rotateDegrees.', '.$pieRadius.', '.$pieRadius.'"
          keyTimes="0; 0.2; 0.8; 1"
          fill="freeze">
      </path>
      <text
          id="tooltip'.$pathTextID.'"
          class="pie-text"
          x="'.$pieRadius.'"
          y="'.($pieRadius - 8).'"
          visibility="hidden">
        <tspan>'.$idText.'</tspan>
        <tspan class="light" x="'.$pieRadius.'" y="'.($pieRadius + 2).'">'.$noOfNodes.' nodes</tspan>
        <tspan class="light" x="'.$pieRadius.'" y="'.($pieRadius + 12).'">'.$percentage.'%</tspan>
        <set attributeName="visibility" from="hidden" to="visible" begin="slice'.$pathTextID.'.mouseover" end="slice'.$pathTextID.'.mouseout"/>
      </text>';

    $pieUseTag = '<use xlink:href="#tooltip'.$pathTextID.'"/>';

    $piePathUseTags = array(
        'path' => $piePathTag,
        'use' => $pieUseTag,
    );

    return $piePathUseTags;
  }

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

  function getPieLegendTag($legendTitle, $legendSubtitle, $percentage, $noOfNodes, $fillClass) {
    $subtitleTag = '';

    if($legendSubtitle) {
      $subtitleTag = '<div class="subtitle">
          <div class="square-double"></div>
          <div>'.$legendSubtitle.'</div>
        </div>';
    }

    return '
      <div class="pie-legend-row">
        <div class="title">
          <div class="square">
            <div class="square '.$fillClass.'-bg"></div>
          </div>
          <div class="square"></div>
          <div>'.$legendTitle.'</div>
        </div>
        '.$subtitleTag.'
        <div class="values">
          <div class="square-double"></div>
          <div>'.$percentage.'%&emsp;&bull;&emsp;'.$noOfNodes.' nodes</div>
        </div>
      </div>';
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

    <?php echo getTotalChart($i18nStats, $i18nStatsDefault); ?>
    <?php echo getPieChart($i18nStats, $i18nStatsDefault, $i18nCountries, $i18nCountriesDefault, $initialPie); ?>

    <?php $mysqli->close(); ?>

  </body>
</html>
