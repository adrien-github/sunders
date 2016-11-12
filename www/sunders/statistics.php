<?php
  if (!isset($pathToWebFolder)) {
    $pathToWebFolder = './';
  }

  include $pathToWebFolder.'config.php';

  if (!isset($initialLanguage)) {
    $initialLanguage = DEFAULT_LANGUAGE;
  }

  include $pathToWebFolder.'decode-json.php';
  include $pathToWebFolder.'i18n.php';
  include $pathToWebFolder.'chart.php';
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

    <link rel="stylesheet" href="<?php echo $pathToWebFolder.'css/statistics.css' ?>">
    <script src="<?php echo $pathToWebFolder.'js/statistics.js' ?>"></script>
  </head>
  <body>

    <div class="page">
      <form class="header form">
        <a href="<?php echo $pathToWebFolder.'index.php' ?>"><img src="<?php echo $pathToWebFolder.'images/title-sunders.png' ?>" alt="Surveillance under Surveillance"></a>
        <?php getButtongroupYear($statYear); ?>
        <?php getButtongroupMonth($statYear, $statMonth, $i18nStatistics, $i18nStatisticsDefault); ?>
      </form>
      <div class="chart">
        <?php getUploadsChart($statYear, $statMonth, $i18nStatistics, $i18nStatisticsDefault); ?>
      </div>
      <div class="info text-small"><?php echo translate($i18nStatistics, $i18nStatisticsDefault, 'description', [], [], []); ?></div>
      <div class="footer">
        <a href="<?php echo $pathToWebFolder.'index.php' ?>"><?php echo translate($i18nStatistics, $i18nStatisticsDefault, 'footer-text', [], [], []); ?></a>
      </div>
    </div>

  </body>
</html>
