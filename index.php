<?php
/*
The MIT License (MIT)

Copyright (c) 2014 Zachary Seguin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
 
require('classes/GTFS.php');
require('classes/TemplateRenderer.php');

date_default_timezone_set('America/Toronto');

/* Database */
$gtfs = new GTFS('mysql:host=localhost;port=3306;dbname=grt', 'root', 'root');

/* Path */
$request = explode('/', (isset($_GET['request']) ? $_GET['request'] : 'home'));

switch($request[0])
{
   case 'home':      
      TemplateRenderer::showTemplate('home', 'GRT Schedule', Array('stops' => $gtfs->getStops()));
      break;
      
   case 'stop':
      if (count($request) < 2) continue;
      
      $stop_id = $request[1]; 
      
      $stop = $gtfs->getStop($stop_id);
      $stop_times = $gtfs->getStopTimes($stop_id);
      
      foreach ($stop_times as $index => $stop_time)
      {
         if (strtotime($stop_time['departure_time']) < strtotime(date('H:i:s')))
         {
            unset($stop_times[$index]);
         }// End of if
      }// End of foreach
      
      TemplateRenderer::showTemplate('stop', $stop['stop_id'] . ' (' . $stop['stop_name'] . ')', Array(
         'stop' => $stop,
         'stop_times' => $stop_times,
      ));
      
      break;
      
      /*$stmt = $db->prepare('SELECT trips.*, stop_times.*, routes.*, calendar.* 
                           FROM trips 
                           INNER JOIN routes ON trips.route_id = routes.route_id
                           INNER JOIN stop_times ON trips.trip_id = stop_times.trip_id 
                                                 AND stop_times.stop_id=:stop_id 
                           INNER JOIN calendar ON calendar.service_id = trips.service_id
                           WHERE start_date <= CURDATE()
                     	   AND end_date >= CURDATE()
                           ORDER BY arrival_time');
      $stmt->execute(Array(
         ':stop_id' => $stop['stop_id']
      ));
      $stop_times = $stmt->fetchAll(PDO::FETCH_ASSOC);

      show_template('stop', Array(
         'page_title' => $stop['stop_id'] . ' - ' . $stop['stop_name'],
         'stop' => $stop,
         'stop_times' => $stop_times,
         'weekdays' => Array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
      ));
      */
      
   default: 
      show_template('not-found', Array(
         'page_title' => 'Page Not Found'
      ));
}// End of switch
   
?>