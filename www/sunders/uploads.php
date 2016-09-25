<?php
  include './chart.php';
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8'/>
    <title>SunderS &mdash; Uploads</title>

    <link rel='shortcut icon' href='./favicon.ico'>
    <link rel='icon' type='image/png' href='./favicon.png' sizes='32x32'>
    <link rel='apple-touch-icon' sizes='180x180' href='./apple-touch-icon.png'>
    <meta name='msapplication-TileColor' content='#f1eee8'>
    <meta name='msapplication-TileImage' content='./mstile-144x144.png'>

    <link rel='stylesheet' href='./css/statistics.css'>
  </head>
  <body>

    <div class='page'>
      <?php
        echo "<div class='header'>\n
                <div class='title'>OSM surveillance uploads ".getUploadsPeriodString($statYear, $statMonth, $statPeriod)."</div>\n
              </div>\n";
        getPeriodNavi($statYear, $statMonth, $statPeriod);
        getUploadsTable($statYear, $statMonth, $statPeriod);
      ?>
      <div class="info text-small">This list contains worldwide uploads of OSM surveillance nodes within the selected period. Only the latest upload of each node is considered, i.e. if a node exists in version 3 the first and second version of this node are excluded. Click on an id to inspect the corresponding node.</div>
      <?php
        echo "<div class='footer'>\n
                <a href='./statistics.php?year=".$statYear."&month=".$statMonth."'>back to chart</a>\n
              </div>\n";
      ?>
    </div>

  </body>
</html>
