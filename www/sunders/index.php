<?php
  if (!isset($pathToWebFolder)) {
    $pathToWebFolder = './';
  }

  include $pathToWebFolder.'config.php';

  if (!isset($initialLanguage)) {
    $initialLanguage = DEFAULT_LANGUAGE;
  }
  $initialZoom = DEFAULT_ZOOM;
  $initialLat = DEFAULT_LAT;
  $initialLon = DEFAULT_LON;

  include $pathToWebFolder.'decode-json.php';
  include $pathToWebFolder.'i18n.php';
  include $pathToWebFolder.'add-lists.php';

  /* Check if the URL contains a numeric zoom value and if that value is between 1 and 18.
      If not use DEFAULT_ZOOM from config.php. */
  if (array_key_exists('zoom', $_GET)) {
    $initialZoom = $_GET['zoom'];
    if (!is_numeric($initialZoom) || intval($initialZoom) < 1 || intval($initialZoom) > 18) {
      $initialZoom = DEFAULT_ZOOM;
    }
  }

  /* Check if the URL contains a numeric lat value and a numeric lon value.
      If not use DEFAULT_LAT and DEFAULT_LON from config.php. */
  if (array_key_exists('lat', $_GET) && array_key_exists('lon', $_GET)) {
    $initialLat = $_GET['lat'];
    $initialLon = $_GET['lon'];
    if (!is_numeric($initialLat) || !is_numeric($initialLon)) {
      $initialLat = DEFAULT_LAT;
      $initialLon = DEFAULT_LON;
    }
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8"/>
    <title>Surveillance under Surveillance</title>

    <link rel="shortcut icon" href="<?php echo $pathToWebFolder.'favicon.ico' ?>">
    <link rel="icon" type="image/png" href="<?php echo $pathToWebFolder.'favicon.png' ?>" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $pathToWebFolder.'apple-touch-icon.png' ?>">
    <meta name="msapplication-TileColor" content="#f1eee8">
    <meta name="msapplication-TileImage" content="<?php echo $pathToWebFolder.'mstile-144x144.png' ?>">

    <link rel="stylesheet" href="<?php echo $pathToWebFolder.'Leaflet/leaflet.css' ?>">
    <link rel="stylesheet" href="<?php echo $pathToWebFolder.'Leaflet.label/leaflet.label.css' ?>">
    <link rel="stylesheet" href="<?php echo $pathToWebFolder.'css/sunders.css' ?>">
  </head>
  <body>

    <input class="slider-toggle-input" type="checkbox" id="slider-id" checked="checked">
    <label class="slider-toggle" for="slider-id">
      <img src="<?php echo $pathToWebFolder.'images/slider-toggle.png' ?>">
    </label>

    <div id="map"></div>

    <div class="topbar anchorlinkbar">
      <a title="<?php echo translate($i18nCommon, $i18nCommonDefault, 'what-alt', [], [], []) ?>" href="#what">
        <div class="bar-button what" style="<?php echo 'background-image: url(\''.$pathToWebFolder.'images/link-what-'.$initialLanguage.'.png\');' ?>"></div>
      </a>
      <a title="<?php echo translate($i18nCommon, $i18nCommonDefault, 'how-alt', [], [], []) ?>" href="#how">
        <div class="bar-button how" style="<?php echo 'background-image: url(\''.$pathToWebFolder.'images/link-how-'.$initialLanguage.'.png\');' ?>"></div>
      </a>
      <a title="<?php echo translate($i18nCommon, $i18nCommonDefault, 'where-alt', [], [], []) ?>" href="#where">
        <div class="bar-button where" style="<?php echo 'background-image: url(\''.$pathToWebFolder.'images/link-where-'.$initialLanguage.'.png\');' ?>"></div>
      </a>
    </div>

    <div class="topbar buttonbar">
      <div title="<?php echo translate($i18nCommon, $i18nCommonDefault, 'permalink-button-alt', [], [], []) ?>" class="bar-button permalink" onClick="permalink(null);return false;"></div>
      <a title="<?php echo translate($i18nCommon, $i18nCommonDefault, 'stats-button-alt', [], [], []) ?>" href="<?php echo $pathToWebFolder.$initialLanguage.'/statistics.php' ?>">
        <div class="bar-button stats"></div>
      </a>
      <?php
        addListLanguages($initialLanguage, $i18nCommon, $i18nCommonDefault);
      ?>
    </div>

    <div class="slider">
      <div class="slider-item slider-logo">
        <img src="<?php echo $pathToWebFolder.'images/logo.png' ?>" alt="Surveillance under Surveillance">
      </div>

      <?php
        $subtitle = translate($i18nCommon, $i18nCommonDefault, 'subtitle', [], [], []);
        $translation = translate($i18nCommon, $i18nCommonDefault, 'translation', [], [], []);

        if (! empty($subtitle)) {
          echo '<div class="slider-subtitle">'.$subtitle.'</div>';
        }

        if (! empty($translation)) {
          echo '<div class="slider-translation">'.$translation.'</div>';
        }
      ?>

      <div id="what"></div>
      <div class="slider-item slider-title">
        <img src="<?php echo $pathToWebFolder.'images/title-what-'.$initialLanguage.'.png' ?>" alt="<?php echo translate($i18nCommon, $i18nCommonDefault, 'what-alt', [], [], []) ?>">
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'what-show', [], [], []); ?></p>
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'what-icons', [], [], []); ?><p>
      </div>

      <?php
        addListSymbology($pathToWebFolder.'json/symbology.json', $i18nSymbology, $i18nSymbologyDefault);
      ?>

      <div id="how"></div>
      <div class="slider-item slider-title">
        <img src="<?php echo $pathToWebFolder.'images/title-how-'.$initialLanguage.'.png' ?>" alt="<?php echo translate($i18nCommon, $i18nCommonDefault, 'how-alt', [], [], []) ?>">
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'how-osm', ['https://www.openstreetmap.org/login', 'https://www.openstreetmap.org/user/new'], [['https://www.openstreetmap.org'], ['OpenStreetMap']], []); ?></p>
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'how-update', [], [], []); ?></p>
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'how-keyval', [], [], []); ?></p>
      </div>

      <?php
        addListManual($pathToWebFolder.'json/manual.json', $i18nManual, $i18nManualDefault);
      ?>

      <div class="slider-item">
        <p><br><br>

      <?php
        echo translate($i18nCommon, $i18nCommonDefault, 'how-fork', [], [['https://github.com/kamba4/sunders'], ['GitHub']], []);
      ?>

        </p>
      </div>

      <div id="where"></div>
      <div class="slider-item slider-title">
        <img src="<?php echo $pathToWebFolder.'images/title-where-'.$initialLanguage.'.png' ?>" alt="<?php echo translate($i18nCommon, $i18nCommonDefault, 'where-alt', [], [], []) ?>">
      </div>
      <div class="slider-item">
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'where-sites', [], [], []); ?></p>
      </div>

      <?php
        addListLinks($pathToWebFolder.'json/links.json', $i18nLinks, $i18nLinksDefault);
      ?>

      <div class="slider-item slider-footer text-small">
        &#x2756; &#x2756; &#x2756;
        <br><br><br>
        <p><?php echo translate($i18nCommon, $i18nCommonDefault, 'footer-credits', [], [
            [
              'https://github.com/khris78/osmcamera',
              $pathToWebFolder.'files/license_osmcamera.txt',
              'https://github.com/khris78',
              'https://github.com/Leaflet/Leaflet',
              $pathToWebFolder.'files/license_Leaflet.txt',
              'https://github.com/Leaflet/Leaflet.label',
              $pathToWebFolder.'files/license_Leaflet.label.txt',
              'https://www.openstreetmap.org',
              'https://www.openstreetmap.org/copyright',
              'http://fontawesome.io',
              'http://fontawesome.io/license/',
              'https://fontlibrary.org/de/font/grabstein-grotesk',
              'http://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL'
            ], [
              'osmcamera',
              'CC-BY-SA / MIT / GPLv3 / WTFPL',
              'khris78',
              'Leaflet/Leaflet',
              'BSD-2-Clause',
              'Leaflet/Leaflet.label',
              'MIT',
              'OpenStreetMap',
              'CC BY-SA',
              'Font Awesome',
              'SIL OFL 1.1 / MIT / CC BY 3.0',
              'Grabstein Grotesk',
              'OLF'
            ]
          ], []); ?></p>
        <br><br>
        &#x041C;&#x0410;&#x041A;&#x0421; &#x041A;&#x0410;&#x041C;&#x0412;&#x0410;&#x0427;<br>
        Aljoscha Rompe Laan 5<br>
        2517 AR Den Haag<br>
        &#x73;&#x75;&#x6e;&#x64;&#x65;&#x72;&#x73; &#x28;<?php echo translate($i18nCommon, $i18nCommonDefault, 'at', [], [], []); ?>&#x29; &#x6b;&#x61;&#x6d;&#x62;&#x61;&#x34; &#x28;<?php echo translate($i18nCommon, $i18nCommonDefault, 'dot', [], [], []); ?>&#x29; &#x63;&#x72;&#x75;&#x78; &#x28;<?php echo translate($i18nCommon, $i18nCommonDefault, 'dot', [], [], []); ?>&#x29; &#x75;&#x62;&#x65;&#x72;&#x73;&#x70;&#x61;&#x63;&#x65; &#x28;<?php echo translate($i18nCommon, $i18nCommonDefault, 'dot', [], [], []); ?>&#x29; &#x64;&#x65;<br><br>
        <?php echo translate($i18nCommon, $i18nCommonDefault, 'footer-pgp', [$pathToWebFolder.'files/sunders.asc'], [], []); ?><br>
        EE12 1A7D C3FB 52BD 46AA<br>
        DD0D 547B 21CD C20D DD88<br><br>
        <i>
          <?php echo translate($i18nCommon, $i18nCommonDefault, 'footer-note', [], [], []); ?><br>
          <?php echo translate($i18nCommon, $i18nCommonDefault, 'footer-your-key', [], [], []); ?><br>
          <?php
            if ($initialLanguage == 'de') {
              echo translate($i18nCommon, $i18nCommonDefault, 'footer-help', ['https://netzpolitik.org/2013/anleitung-so-verschlusselt-ihr-eure-e-mails-mit-pgp/'], [], []);
            } elseif ($initialLanguage == 'es') {
              echo translate($i18nCommon, $i18nCommonDefault, 'footer-help', [], [['https://ssd.eff.org/es/module/como-usar-pgp-para-windows-pc', 'https://ssd.eff.org/es/module/c%C3%B3mo-usar-pgp-para-mac-os-x', 'https://ssd.eff.org/es/module/como-usar-pgp-para-linux'], ['Windows', 'macOS', 'GNU/Linux']], []);
            } elseif ($initialLanguage == 'fr') {
              echo translate($i18nCommon, $i18nCommonDefault, 'footer-help', [], [['https://ssd.eff.org/fr/module/pgp-sous-windows-le-ba-ba', 'https://ssd.eff.org/fr/module/guide-dutilisation-de-pgp-pour-mac-os-x', 'https://ssd.eff.org/fr/module/pgp-sous-linux-le-ba-ba'], ['Windows', 'macOS', 'GNU/Linux']], []);
            } elseif ($initialLanguage == 'ru') {
              echo translate($i18nCommon, $i18nCommonDefault, 'footer-help', [], [['https://ssd.eff.org/ru/module/%D1%80%D1%83%D0%BA%D0%BE%D0%B2%D0%BE%D0%B4%D1%81%D1%82%D0%B2%D0%BE-%D0%BF%D0%BE-pgp-%D0%B4%D0%BB%D1%8F-windows', 'https://ssd.eff.org/ru/module/%D1%80%D1%83%D0%BA%D0%BE%D0%B2%D0%BE%D0%B4%D1%81%D1%82%D0%B2%D0%BE-%D0%BF%D0%BE-pgp-%D0%B4%D0%BB%D1%8F-mac', 'https://ssd.eff.org/ru/module/%D1%80%D1%83%D0%BA%D0%BE%D0%B2%D0%BE%D0%B4%D1%81%D1%82%D0%B2%D0%BE-%D0%BF%D0%BE-pgp-%D0%B4%D0%BB%D1%8F-linux'], ['Windows', 'macOS', 'GNU/Linux']], []);
            } else {
              echo translate($i18nCommon, $i18nCommonDefault, 'footer-help', [], [['https://ssd.eff.org/en/module/how-use-pgp-windows', 'https://ssd.eff.org/en/module/how-use-pgp-mac-os-x', 'https://ssd.eff.org/en/module/how-use-pgp-linux'], ['Windows', 'macOS', 'GNU/Linux']], []);
            }
          ?>
        </i>
        <br><br>
      </div>
    </div>

    <script language="javascript">
      <?php
        echo 'var initialZoom = '.$initialZoom.';
              var initialLat = '.$initialLat.';
              var initialLon = '.$initialLon.';';
      ?>
    </script>

    <script src="<?php echo $pathToWebFolder.'Leaflet/leaflet.js' ?>"></script>
    <script src="<?php echo $pathToWebFolder.'Leaflet.label/leaflet.label.js' ?>"></script>
    <script src="<?php echo $pathToWebFolder.'js/leafletembed_icons.js' ?>"></script>
    <script src="<?php echo $pathToWebFolder.'js/leafletembed_functions.js' ?>"></script>

  </body>
</html>

<?php
  header('Content-type: text/html; charset="UTF-8"');
?>
