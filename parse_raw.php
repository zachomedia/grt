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

/** Config **/
ini_set('memory_limit', '5G');
set_time_limit(0);

/** Functions **/

// parse_csv_file(file, [skip_first_row = false])
//  Reads and parses the specified CSV file
//     file: Path to the CSV file
//     skip_first_row: If the first row (sometimes header) should be skipped
//     new_line:  The characters forming a new line
function parse_csv_file($file, $skip_first_row = false, $new_line = '\r\n')
{
   if (!file_exists($file)) return array();
   
   $csv = file_get_contents($file);
   $rows = preg_split("/$new_line/", $csv);
   
   $data = array();
   
   foreach ($rows as $row)
   {
      if ($row == '') continue;
      $data[] = explode(',', $row);
   }// End of foreach
   
   return $data;
}// End of parse_csv_file function

function map_data($headers, $data)
{
   $mapped = array();
   
   foreach ($data as $row)
   {
      $mapped_row = array();
      
      foreach ($headers as $index => $header)
      {
         $mapped_row[$header] = $row[$index];
      }// End of foreach
      
      $mapped[] = $mapped_row;
   }// End of foreach
   
   return $mapped;
}// End of map data

function build_sql_values($values)
{
   if (count($values) === 0) return '';
   return ':' . implode(',:', $values);
}// End of build_sql_values

function insert_into_database($db, $table, $params, $data)
{
   $stmt = $db->prepare("INSERT INTO $table (" . implode(',', $params) . ") VALUES (" . build_sql_values($params) . ")");

   foreach ($data as $row)
   {   
      foreach ($params as $param)
      {
         if (!isset($row[$param]))
         {
            $row[$param] = NULL;
         }// End of if
         
         $stmt->bindParam(':' . $param, $row[$param]);
      }// End of foreach

      $stmt->execute();
   }// End of foreach
}

header('Content-Type: text/plain');

/** Connections **/
$db = new PDO("mysql:host=localhost;port=3306;dbname=grt", 'root', 'root');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Parse Agency **/
$agency_params = array('agency_id', 'agency_name', 'agency_url', 'agency_timezone', 'agency_lang', 'agency_phone', 'agency_fare_url');
$agencies_csv = parse_csv_file('raw_data/agency.txt');
$agencies_data = map_data($agencies_csv[0], array_slice($agencies_csv, 1));

insert_into_database($db, 'agencies', $agency_params, $agencies_data);

/** Parse Stops */
$stop_params = array('stop_id', 'stop_code', 'stop_name', 'stop_desc', 'stop_lat', 'stop_lon', 'zone_id', 'stop_url', 'location_type', 'parent_station', 'stop_timezone', 'wheelchair_boarding');
$stops_csv = parse_csv_file('raw_data/stops.txt');
$stops_data = map_data($stops_csv[0], array_slice($stops_csv, 1));

insert_into_database($db, 'stops', $stop_params, $stops_data);

/** Parse Routes */
$route_params = array('route_id', 'agency_id', 'route_short_name', 'route_long_name', 'route_desc', 'route_type', 'route_url', 'route_color', 'route_text_color');
$routes_csv = parse_csv_file('raw_data/routes.txt');
$routes_data = map_data($routes_csv[0], array_slice($routes_csv, 1));

insert_into_database($db, 'routes', $route_params, $routes_data);

/** Parse Trips */
$trip_params = array('route_id', 'service_id', 'trip_id', 'trip_headsign', 'trip_short_name', 'direction_id', 'block_id', 'shape_id', 'wheelchair_accessible', 'bikes_allowed');
$trips_csv = parse_csv_file('raw_data/trips.txt');
$trips_data = map_data($trips_csv[0], array_slice($trips_csv, 1));

insert_into_database($db, 'trips', $trip_params, $trips_data);

/** Parse Stop Times */
$stop_time_params = array('trip_id', 'arrival_time', 'departure_time', 'stop_id', 'stop_sequence', 'stop_headsign', 'pickup_type', 'drop_off_type', 'shape_dist_traveled');
$stop_times_csv = parse_csv_file('raw_data/stop_times.txt');
$stop_times_data = map_data($stop_times_csv[0], array_slice($stop_times_csv, 1));

insert_into_database($db, 'stop_times', $stop_time_params, $stop_times_data);

/** Parse Calendar **/
$calendar_params = array('service_id', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'start_date', 'end_date');
$calendar_csv = parse_csv_file('raw_data/calendar.txt');
$calendar_data = map_data($calendar_csv[0], array_slice($calendar_csv, 1));

insert_into_database($db, 'calendar', $calendar_params, $calendar_data);

/** Parse Calendar Dates **/
$calendar_dates_params = array('service_id', 'date', 'exception_type');
$calendar_dates_csv = parse_csv_file('raw_data/calendar_dates.txt');
$calendar_dates_data = map_data($calendar_dates_csv[0], array_slice($calendar_dates_csv, 1));

insert_into_database($db, 'calendar_dates', $calendar_dates_params, $calendar_dates_data);

/** Parse Fare Attributes **/
$fare_attribute_params = array('fare_id', 'price', 'currency_type', 'payment_method', 'transfers', 'transfer_duration');
$fare_attributes_csv = parse_csv_file('raw_data/fare_attributes.txt');
$fare_attributes_data = map_data($fare_attributes_csv[0], array_slice($fare_attributes_csv, 1));

insert_into_database($db, 'fare_attributes', $fare_attribute_params, $fare_attributes_data);

/** Parse Fare Rules **/
$fare_rule_params = array('fare_id', 'route_id', 'origin_id', 'destination_id', 'contains_id');
$fare_rules_csv = parse_csv_file('raw_data/fare_rules.txt');
$fare_rules_data = map_data($fare_rules_csv[0], array_slice($fare_rules_csv, 1));

insert_into_database($db, 'fare_rules', $fare_rule_params, $fare_rules_data);

/** Parse Shapes **/
$shape_params = array('shape_id', 'shape_pt_lat', 'shape_pt_lon', 'shape_pt_sequence', 'shape_dist_travelled');
$shapes_csv = parse_csv_file('raw_data/shapes.txt');
$shapes_data = map_data($shapes_csv[0], array_slice($shapes_csv, 1));

insert_into_database($db, 'shapes', $shape_params, $shapes_data);

/** Parse Frequencies **/
$frequency_params = array('trip_id', 'start_time', 'end_time', 'headway_secs', 'exact_times');
$frequencies_csv = parse_csv_file('raw_data/frequencies.txt');
$frequencies_data = map_data($frequencies_csv[0], array_slice($frequencies_csv, 1));

insert_into_database($db, 'frequencies', $frequency_params, $frequencies_data);

/** Parse Transfers **/
$transfer_params = array('from_stop_id', 'to_stop_id', 'transfer_type', 'min_transfer_time');
$transfers_csv = parse_csv_file('raw_data/transfers.txt');
$transfers_data = map_data($transfers_csv[0], array_slice($transfers_csv, 1));

insert_into_database($db, 'transfers', $transfer_params, $transfers_data);

/** Parse Feeds Info **/
$feed_info_params = array('from_stop_id', 'to_stop_id', 'transfer_type', 'min_transfer_time');
$feeds_info_csv = parse_csv_file('raw_data/transfers.txt');
$feeds_info_data = map_data($feeds_info_csv[0], array_slice($feeds_info_csv, 1));

insert_into_database($db, 'feeds_info', $feed_info_params, $feeds_info_data);

echo 'done';