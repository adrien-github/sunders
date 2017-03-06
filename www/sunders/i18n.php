<?php
  $i18nCommon     = getI18nArray('common', $initialLanguage);
  $i18nLinks      = getI18nArray('links', $initialLanguage);
  $i18nManual     = getI18nArray('manual', $initialLanguage);
  $i18nSymbology  = getI18nArray('symbology', $initialLanguage);
  $i18nCredits    = getI18nArray('credits', $initialLanguage);
  $i18nStatistics = getI18nArray('statistics', $initialLanguage);
  $i18nStats      = getI18nArray('stats', $initialLanguage);
  $i18nCountries  = getI18nArray('countries', $initialLanguage);

  if ($initialLanguage != DEFAULT_LANGUAGE) {
    $i18nCommonDefault     = getI18nArray('common', DEFAULT_LANGUAGE);
    $i18nLinksDefault      = getI18nArray('links', DEFAULT_LANGUAGE);
    $i18nManualDefault     = getI18nArray('manual', DEFAULT_LANGUAGE);
    $i18nSymbologyDefault  = getI18nArray('symbology', DEFAULT_LANGUAGE);
    $i18nCreditsDefault    = getI18nArray('credits', DEFAULT_LANGUAGE);
    $i18nStatisticsDefault = getI18nArray('statistics', DEFAULT_LANGUAGE);
    $i18nStatsDefault      = getI18nArray('stats', DEFAULT_LANGUAGE);
    $i18nCountriesDefault  = getI18nArray('countries', DEFAULT_LANGUAGE);
  } else {
    $i18nCommonDefault     = $i18nCommon;
    $i18nLinksDefault      = $i18nLinks;
    $i18nManualDefault     = $i18nManual;
    $i18nSymbologyDefault  = $i18nSymbology;
    $i18nCreditsDefault    = $i18nCredits;
    $i18nStatisticsDefault = $i18nStatistics;
    $i18nStatsDefault      = $i18nStats;
    $i18nCountriesDefault  = $i18nCountries;
  }

  function getI18nArray($folder, $languange) {
    return getDecodedJSON(getI18nPath($folder, $languange));
  }

  function getI18nPath($folder, $languange) {
    global $pathToWebFolder;
    return $pathToWebFolder.'i18n/'.$folder.'/'.$languange.'.json';
  }

  function translate($i18n, $i18nDefault, $key, $linksWithVariableText, $linksWithFixedText, $fixedTexts) {
    $openTagToBeTranslated = '';
    $closeTagToBeTranslated = '';
    $text = $i18n->{'texts'}->{$key};

    if (empty($text)) {
      $i18n = $i18nDefault;
      $text = $i18n->{'texts'}->{$key};

      if (!empty($text) && substr($key, -4) != '-alt') {
        $openTagToBeTranslated = '<span class="to-be-translated">';
        $closeTagToBeTranslated = '</span>';
      }
    }

    $textLinkArray = explode ('@@' , $text); // A @@ at the beginning/end of $text leads to an empty string as the first/last element of $textLinkArray.
    $isTextWithoutLinks = count($textLinkArray) == 1;
    $translatedText = '';

    if ($isTextWithoutLinks) {
      $translatedText = $translatedText.htmlentities($text);
    } else {
      foreach ($textLinkArray as $phrase) {
        if (substr($phrase, 0, 2) == 'LT') { // link with a text to be translated
          $index = (int)substr($phrase, 2, 2);
          $translatedText = $translatedText.'<a href="'.htmlentities($linksWithVariableText[$index]).'">'.htmlentities($i18n->{'links'}->{$key}[$index]).'</a>'.htmlentities(substr($phrase, 4));
        } elseif (substr($phrase, 0, 2) == 'LF') { // link with a fix text
          $index = (int)substr($phrase, 2, 2);
          $translatedText = $translatedText.'<a href="'.htmlentities($linksWithFixedText[0][$index]).'">'.htmlentities($linksWithFixedText[1][$index]).'</a>'.htmlentities(substr($phrase, 4));
        } elseif (substr($phrase, 0, 2) == 'TF') { // fix text
          $index = (int)substr($phrase, 2, 2);
          $translatedText = $translatedText.htmlentities($fixedTexts[$index].substr($phrase, 4));
        } else {
          $translatedText = $translatedText.htmlentities($phrase);
        }
      }
    }

    return $openTagToBeTranslated.$translatedText.$closeTagToBeTranslated;
  }
?>
