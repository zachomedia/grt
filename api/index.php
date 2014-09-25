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

require('../config.php');

require('../classes/GTFS.php');

date_default_timezone_set('America/Toronto');

header('Content-Type: application/json');

/* Database */
$gtfs = new GTFS('mysql:host=' . MYSQL_HOSTNAME . ';port=' . MYSQL_PORT . ';dbname=' . MYSQL_DBNAME, MYSQL_USERNAME, MYSQL_PASSWORD);

/* Path */
$request_uri = str_replace(str_replace('index.php', '', $_SERVER['PHP_SELF']), '', $_SERVER['REQUEST_URI']);
$request = explode('/', (empty($request_uri)) ? 'home' : $request_uri);

/* Response */
$response = Array();

$response['meta'] = Array(
   'method' => $request_uri,
   'response_code' => 200,
   'status' => 'OK'
);

switch($request[0])
{
   case 'home':
      $response['data'] = Array();
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

      $response['data'] = Array(
         'stop' => $stop,
         'stop_times' => $stop_times
      );

      break;

   default:
      $response['meta']['response_code'] = 404;
      $response['meta']['status'] = 'Method Not Found';
      break;
}// End of switch

echo json_encode($response);

?>