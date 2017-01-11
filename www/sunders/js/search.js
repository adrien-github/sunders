var searchLimit;

function displaySearchOverlay() {
  document.getElementById('search-overlay').style.display = 'block';
}

// Close the search overlay.
function closeSearchOverlay() {
  document.getElementById('search-overlay').style.display = 'none';
  document.getElementById('search-results-overlay').style.display = 'none';
  document.getElementById('buttonbar').style.bottom = '';
}

// Search for possible locations
function searchLocation(maxResults) {
  var searchString = document.getElementById('search-input').value;

  if (searchString) {

    document.getElementById('buttonbar').style.bottom = 0;
    document.getElementById('search-results-overlay').style.display = 'block';

    var spinnerDiv = '<div class="spinner-bar">';
    for (var i=1; i<=10; i++) {
      spinnerDiv += '<div class="spinner spinner' + i + '">&#x25AC;&#x25AC;</div>';
    }
    spinnerDiv += '</div>';

    document.getElementById('search-results-list-block').style.display = 'none';
    document.getElementById('search-spinner').innerHTML = spinnerDiv;
    document.getElementById('search-spinner-block').style.display = 'block';

    if (maxResults) {
      searchLimit = maxResults;
    } else {
      // Search one more than required to check if there are more results and a 'next' button is required.
      searchLimit = 11;
    }

    var searchScript = document.createElement('script');
    searchScript.src = 'https://nominatim.openstreetmap.org/search.php?q=' + searchString + '&format=json&json_callback=parseResult&limit=' + searchLimit;
    document.body.appendChild(searchScript);
  }
}

function parseResult(response) {
  var searchResultDivs = '';

  if (response.length == searchLimit) {
    var lastResultIndex = searchLimit - 1;
    var nextSearchLimit = searchLimit + 10;
  } else {
    var lastResultIndex = response.length;
  }

  if (lastResultIndex > 10) {
    var previousSearchLimit = searchLimit - 10;
  }

  // Button to search for the previous 10 results.
  if (previousSearchLimit) {
    searchResultDivs += '<div class="search-more search-previous" onClick="searchLocation(' + previousSearchLimit + ');return false;"></div>';
  }

  searchResultDivs += '<div class="search-results">';

  // Show the current 10 search results.
  for (var i=searchLimit-11; i<lastResultIndex; i++) {
    searchResult = response[i];
    searchResultDivs +=
      '<div class="search-result" onClick="gotoLocation(' + searchResult.lat + ', ' + searchResult.lon + ');return false;">' +
        '<div class="text-medium">' + searchResult.class + ' / ' + searchResult.type + '</div>' +
        '<div>' + searchResult.display_name + '</div>' +
      '</div>';
  }

  searchResultDivs += '</div>';

  // Button to search for the next 10 results.
  if (nextSearchLimit) {
    searchResultDivs += '<div class="search-more search-next" onClick="searchLocation(' + nextSearchLimit + ');return false;"></div>'
  }

  document.getElementById('search-spinner-block').style.display = 'none';
  document.getElementById('search-results-list').innerHTML = searchResultDivs;
  document.getElementById('search-results-list-block').style.display = 'block';
}

// Open map for coordinates
function gotoLocation(lat, lon) {
  var serverUrl = location.protocol + '//' + location.host + location.pathname;
  var gotoUrl = serverUrl + '?lat=' + lat + '&lon=' + lon + '&zoom=' + map.getZoom();
  window.location = gotoUrl;
}
