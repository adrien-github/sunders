<?php
  // Convert the content of the symbology JSON file to HTML.
  function addListSymbology($jsonPath, $i18n, $i18nDefault) {
    global $pathToWebFolder;

    $decodedJSON = getDecodedJSON($jsonPath);

    echo   '<div class="slider-item slider-list">';

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   '<div class="slider-list-title">'.translate($i18n, $i18nDefault, $listObject->{'listTitle'}, [], [], []).'</div>';

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        echo '<div class="slider-list-entry">
                <div class="w-45">';

        // Loop over the icons of the current list entry.
        foreach($listEntryObject->{'icons'} as $icon) {
          echo   '<img src="'.$pathToWebFolder.'images/'.$icon->{'src'}.'" alt="'.translate($i18n, $i18nDefault, $icon->{'alt'}, [], [], []).'">';
        }
        echo   '</div>
                <div class="pl-20 w-315">'.translate($i18n, $i18nDefault, $listEntryObject->{'description'}, [], [], []).'</div>
              </div>';
      }
    }
    echo   '</div>';
  }

  // Convert the content of the manual JSON file to HTML.
  function addListManual($jsonPath, $i18n, $i18nDefault) {
    global $pathToWebFolder;

    $decodedJSON = getDecodedJSON($jsonPath);

    echo   '<div class="slider-item slider-list text-small">';

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   '<div class="slider-list-title">'.translate($i18n, $i18nDefault, $listObject->{'listTitle'}, [], [], []).'</div>';

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        $keysAsHTMLArray   = array();
        $valuesAsHTMLArray = array();

        // Loop over the keys of the current list entry.
        foreach($listEntryObject->{'keys'} as $key) {

          if (is_null($key->{'href'})) {
            array_push($keysAsHTMLArray, translate($i18n, $i18nDefault, $key->{'key'}, [], [], []));
          } else {
            array_push($keysAsHTMLArray, translate($i18n, $i18nDefault, $key->{'key'}, [$key->{'href'}], [], []));
          }
        }

        // Loop over the values of the current list entry.
        foreach($listEntryObject->{'values'} as $value) {

          if (is_null($value->{'href'})) {
            array_push($valuesAsHTMLArray, translate($i18n, $i18nDefault, $value->{'value'}, [], [], []));
          } else {
            array_push($valuesAsHTMLArray, translate($i18n, $i18nDefault, $value->{'value'}, [$value->{'href'}], [], []));
          }
        }

        echo '<div class="slider-list-entry">
                <div class="w-100">';
                  echo implode('<br>', $keysAsHTMLArray);
        echo   '</div>';

        // Some lists have an icon column.
        if ($listObject->{'isListWithIcons'}) {
          echo '<div class="pl-20 w-240">'
                  .implode('<br>', $valuesAsHTMLArray).'
                </div>
                <div class="w-20">';
          $iconObject  = $listEntryObject->{'icon'};
          if (!is_null($iconObject)){
            echo '<img src="'.$pathToWebFolder.'images/'.$iconObject->{'src'}.'" alt="'.translate($i18n, $i18nDefault, $iconObject->{'alt'}, [], [], []).'">';
          }
        } else {
          echo '<div class="pl-20 w-260">'
                  .implode('<br>', $valuesAsHTMLArray).'';
        }

        echo   '</div>
              </div>';
      }

      // Some lists end with examples, i.e. 3 images with descriptions.
      if (!is_null($listObject->{'examples'})) {
        $examplesObject = $listObject->{'examples'};

        echo '<div class="slider-list-entry">
                <div class="w-100">
                  <br>'.translate($i18n, $i18nDefault, 'examples', [], [], []).':
                </div>
                <div class="pl-20 w-260">
                </div>
              </div>
              <div class="slider-list-entry">
                <div class="fieldofview">';

        foreach($examplesObject->{'images'} as $image) {
          echo   '<div class="fov-image">
                    <img src="'.$pathToWebFolder.'images/'.$image->{'src'}.'" alt="'.translate($i18n, $i18nDefault, $image->{'alt'}, [], [], []).'">
                  </div>';
        }

        echo   '</div>
              </div>
              <div class="slider-list-entry">
                <div class="fieldofview">';

        foreach($examplesObject->{'descriptions'} as $description) {
          $linesAsHTMLArray = array();

          foreach($description->{'lines'} as $line) {
            array_push($linesAsHTMLArray, translate($i18n, $i18nDefault, $line, [], [], []));
          }

          echo   '<div class="w-100">'
                    .implode('<br>', $linesAsHTMLArray).'
                  </div>';
        }

        echo   '</div>
              </div>';
      }

    }
    echo   '</div>';
  }

  // Convert the content of the links JSON file to HTML.
  function addListLinks($jsonPath, $i18n, $i18nDefault) {
    global $pathToWebFolder;
    $expandSectionCounter = 0;

    $decodedJSON = getDecodedJSON($jsonPath);
    echo   '<div class="slider-item slider-list">';

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   '<div class="slider-list-title">'.translate($i18n, $i18nDefault, $listObject->{'listTitle'}, [], [], []).'</div>';

      // Loop over the sections of the current list.
      foreach($listObject->{'sections'} as $sectionObject) {
        if (! empty($sectionObject->{'sectionTitle'})) {
          echo '<input class="slider-list-section-toggle" id="section'.$expandSectionCounter.'-id" type="checkbox">
                <label class="slider-list-section" for="section'.$expandSectionCounter++.'-id"> '.translate($i18n, $i18nDefault, $sectionObject->{'sectionTitle'}, [], [], []).'</label>';
        }

        echo   '<div>';

        // Loop over the entries of the current section.
        foreach($sectionObject->{'listEntries'} as $listEntryObject) {
          echo   '<div class="slider-list-entry">
                   <div class="w-20">';

          // Choose lock icon according to https or http connection.
          if (substr($listEntryObject->{'href'}, 0, 5) == 'https') {
            echo     '<img src="'.$pathToWebFolder.'images/lock-secure.png" alt="'.translate($i18n, $i18nDefault, 'secure-alt', [], [], []).'">';
          } else {
            echo     '<img src="'.$pathToWebFolder.'images/lock-insecure.png" alt="'.translate($i18n, $i18nDefault, 'insecure-alt', [], [], []).'">';
          }
          echo     '</div>
                    <div class="pl-20 w-340">
                      [ '.htmlentities($listEntryObject->{'sourceText'}).' ]<br><a href="'.$listEntryObject->{'href'}.'">'.htmlentities($listEntryObject->{'linkText'}).'</a>
                    </div>
                  </div>';
        }
        echo   '</div>';
      }
    }
    echo   '</div>';
  }

  // Convert the content of the credits and licenses JSON file to HTML.
  function addListCredits($jsonPath, $i18n, $i18nDefault) {
    global $pathToWebFolder;

    $decodedJSON = getDecodedJSON($jsonPath);

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   '<div class="slider-credits-title">'
                .translate($i18n, $i18nDefault, $listObject->{'listTitle'}, [], [], []).'
              </div>';

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        echo   '<div class="slider-credit-type">'
                  .translate($i18n, $i18nDefault, $listEntryObject->{'type'}, [], [], []).'
                </div>';

        // Loop over the credits of the current list entry.
        foreach($listEntryObject->{'credits'} as $credit) {
          $hrefCredit = $credit->{'href-credit'};
          $hrefLicense = $credit->{'href-license'};

          if (substr($hrefCredit, 0, 4) != 'http') {
            $hrefCredit = $pathToWebFolder.$hrefCredit;
          }

          if (substr($hrefLicense, 0, 4) != 'http') {
            $hrefLicense = $pathToWebFolder.$hrefLicense;
          }

          echo   '<div>
                    <a href="'.$hrefCredit.'">'.htmlentities($credit->{'credit'}).'</a> [<a href="'.$hrefLicense.'">'.htmlentities($credit->{'license'}).'</a>]
                  </div>';
        }
      }
    }
  }

  // Create a HTML list that contains the supported languages (i18n).
  function addListLanguages($initialLanguage, $i18n, $i18nDefault) {
    global $pathToWebFolder, $embedPerma;

    $supportedLanguages = [
      'de' => 'deutsch',
      'en' => 'english',
      'es' => 'espa&#x00F1;ol',
      'fr' => 'fran&#x00E7;ais',
      'ru' => '&#x0440;&#x0443;&#x0441;&#x0441;&#x043A;&#x0438;&#x0439;'
    ];

    echo '<ul class="bar-button" id="language">
            <li>
              <div title="'.translate($i18n, $i18nDefault, 'language-button-alt', [], [], []).'">&nbsp;</div>
              <ul>';

    foreach ($supportedLanguages as $languageID => $languageDisplay) {
      $classLanguangeCurrent = '';

      if ($languageID == $initialLanguage) {
        $classLanguangeCurrent = ' class="language-current"';
      }

      echo '    <a href="#" onClick="permalink(\''.$languageID.'\', '.$embedPerma.');return false;"><li'.$classLanguangeCurrent.'>'.$languageDisplay.'</li></a>';
    }

    echo '    </ul>
            </li>
          </ul>';
  }
?>
