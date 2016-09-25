function refreshChart() {
  location.href = 'statistics.php?year=' + checkedValue('year') + '&month=' + checkedValue('month');
  return false;
}

function checkedValue(name) {
  var radios = document.getElementsByName(name);
  for (var i = 0, length = radios.length; i < length; i++) {
    if (radios[i].checked) {
      var checkedVal = radios[i].value;
      break;
    }
  }
  return checkedVal;
}
