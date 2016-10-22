<?php
  include './chart.php';
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8'/>
    <title>SunderS &mdash; Statistics</title>

    <link rel='shortcut icon' href='./favicon.ico'>
    <link rel='icon' type='image/png' href='./favicon.png' sizes='32x32'>
    <link rel='apple-touch-icon' sizes='180x180' href='./apple-touch-icon.png'>
    <meta name='msapplication-TileColor' content='#f1eee8'>
    <meta name='msapplication-TileImage' content='./mstile-144x144.png'>

    <link rel='stylesheet' href='./css/statistics.css'>
    <script src='./js/statistics.js'></script>
  </head>
  <body>

    <div class='page'>
      <form class='header form'>
        <a href='./index.php'><img src='./images/title-sunders.png' alt='Surveillance under Surveillance'></a>
        <?php getButtongroupYear($statYear); ?>
        <?php getButtongroupMonth($statYear, $statMonth); ?>
      </form>
      <div class='chart'>
        <?php getUploadsChart($statYear, $statMonth); ?>
      </div>
      <div class="info text-small">This chart represents worldwide uploads of OSM surveillance nodes within the selected period. Only the latest upload of each node is considered, i.e. if a node exists in version 3 the first and second version of this node are excluded.</div>
      <div class='footer'>
        <a href='./index.php'>MAP 'EM ALL</a>
      </div>
    </div>

  </body>
</html>
