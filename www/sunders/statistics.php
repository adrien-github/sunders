<?php

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8'/>
    <title>Surveillance under Surveillance &mdash; Statistics</title>

    <link rel='shortcut icon' href='./favicon.ico'>
    <link rel='icon' type='image/png' href='./favicon.png' sizes='32x32'>
    <link rel='apple-touch-icon' sizes='180x180' href='./apple-touch-icon.png'>
    <meta name='msapplication-TileColor' content='#f1eee8'>
    <meta name='msapplication-TileImage' content='./mstile-144x144.png'>
  </head>
  <body>

    <link rel='stylesheet' href='./css/statistics.css'>

    <form class="form">

      <p>Display the OSM surveillance uploads during a certain</p>

      <div class="buttongroup">
        <input type="radio" id="bg_period_year" name="bg_period" value="year" checked>
        <label for="bg_period_year">year</label>
        <input type="radio" id="bg_period_month" name="bg_period" value="month">
        <label for="bg_period_month">month</label>
        <input type="radio" id="bg_period_week" name="bg_period" value="week">
        <label for="bg_period_week">week</label>
        <input type="radio" id="bg_period_day" name="bg_period" value="day">
        <label for="bg_period_day">day</label>
      </div>

      <p>Scale the diagram by</p>

<!--  PERIOD    SCALE                 ENTRY
      year      month | week | day		year
      month     week | day				    year / month
      week      day | hour				    year / week
      day       hour					        year / month / day -->

      <div class="buttongroup">
        <input type="radio" id="bg_scale_year_month" name="bg_scale_year" value="month" checked>
        <label for="bg_scale_year_month">month</label>
        <input type="radio" id="bg_scale_year_week" name="bg_scale_year" value="week">
        <label for="bg_scale_year_week">week</label>
        <input type="radio" id="bg_scale_year_day" name="bg_scale_year" value="day">
        <label for="bg_scale_year_day">day</label>
      </div>

      <div class="buttongroup">
        <input type="radio" id="bg_scale_month_week" name="bg_scale_month" value="week" checked>
        <label for="bg_scale_month_week">week</label>
        <input type="radio" id="bg_scale_month_day" name="bg_scale_month" value="day">
        <label for="bg_scale_month_day">day</label>
      </div>

      <div class="buttongroup">
        <input type="radio" id="bg_scale_week_day" name="bg_scale_week" value="day" checked>
        <label for="bg_scale_week_day">day</label>
        <input type="radio" id="bg_scale_week_hour" name="bg_scale_week" value="hour">
        <label for="bg_scale_week_hour">hour</label>
      </div>

    </form>

  </body>
</html>
