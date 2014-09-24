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

class GTFS
{
   const EXCEPTION_SERVICE_ADDED = 1;
   const EXCEPTION_SERVICE_REMOVED = 2;
   
   private $db;
   
   public function __construct($db_dsn, $db_username, $db_password)
   {
      $this->db = new PDO($db_dsn, $db_username, $db_password);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   }// End of __construct function
   
   public function getStops()
   {
      $stmt = $this->db->prepare('SELECT * FROM stops ORDER BY stop_id');
      $stmt->execute();
      
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }// End of getStops function
   
   public function getStop($stop_id)
   {
      $stmt = $this->db->prepare('SELECT * FROM stops WHERE stop_id=:stop_id LIMIT 0,1');
      $stmt->execute(Array(':stop_id' => $stop_id));
         
      return $stmt->fetch(PDO::FETCH_ASSOC);
   }// End of getStop function
   
   public function getCalendar($date = null)
   {
      if ($date === null) $date = time();
      
      // 1 - Load normal service
      $weekday = date('l', $date);
      $stmt = $this->db->prepare("SELECT * FROM calendar 
                                    WHERE start_date <= :date
                                    AND end_date >= :date
                                    AND $weekday = 1");
      $stmt->execute(Array(':date' => date('Y-m-d', $date)));
      
      $calendar = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // 2 - Load service changes (removals or additions)
      $stmt = $this->db->prepare('SELECT * FROM calendar_dates
                                    WHERE date = :date');
      $stmt->execute(Array(':date' => date('Y-m-d', $date)));
      
      $calendar_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      foreach ($calendar_dates as $exception)
      {
         if ($exception['exception_type'] == static::EXCEPTION_SERVICE_ADDED)
         {
            $calendar[] = $exception;
         }// End of if
         else if ($exception['exception_type'] == static::EXCEPTION_SERVICE_REMOVED)
         {
            foreach ($calendar as $index => $service)
            {
               if ($service['service_id'] === $exception['service_id'])
               {
                  unset($calendar[$index]);
               }// End of if
            }// End of foreach
         }// End of else
      }// End of foreach
      
      return $calendar;
   }// End of getCalendar function
   
   public function getTrips($service_id)
   {
      $stmt = $this->db->prepare('SELECT trips.*, routes.* FROM trips 
                                    INNER JOIN routes ON trips.route_id = routes.route_id
                                    WHERE service_id = :service_id
                                 ');
      $stmt->execute(Array(':service_id' => $service_id));
      
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }// End of getTrips function
   
   public function getTripStopTimes($trip_id)
   {
      $stmt = $this->db->prepare('SELECT * FROM stop_times WHERE trip_id = :trip_id');
      $stmt->execute(Array(':trip_id' => $trip_id));
      
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }// End of getTripStopTimes function
   
   private static function stopTimesArraySort($a, $b)
   {
      return $a['departure_time'] > $b['departure_time'];
   }// End of stopTimesArraySort method
   
   public function getStopTimes($stop_id, $date = null)
   {
      if ($date === null) $date = time();
                  
      $calendar = $this->getCalendar($date);
      $trips = Array();
      
      foreach ($calendar as $service)
      {
         $trips = array_merge($trips, $this->getTrips($service['service_id']));
      }// End of foreach
      
      $stop_times = Array();
      
      foreach ($trips as $trip)
      {
         $trip_stop_times = $this->getTripStopTimes($trip['trip_id']);
         
         foreach ($trip_stop_times as $stop)
         {
            if ($stop['stop_id'] === $stop_id)
            {
               $stop['calendar'] = $service;
               $stop['trip'] = $trip;
               $stop_times[] = $stop;
            }// End of if
         }// End of foreach
      }// End of trip
      
      uasort($stop_times, 'GTFS::stopTimesArraySort');
      
      return $stop_times;
   }// End of getStopTimes function
}// End of class

?>