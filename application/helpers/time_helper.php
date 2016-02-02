<?php 
if(!defined('BASEPATH'))
  exit('No direct script access allowed');
  
  function friendlyElapsedTime($datetime_object, $base_time_obj = false, $use_ago = true){
    date_default_timezone_set('America/Los_Angeles');
    
    if(!$base_time_obj) {
      $base_time_obj = new DateTime();
    }
    //convert to time object if string
    if(is_string($datetime_object)) { $datetime_object = new DateTime($time); }
    
    $nowTime = $base_time_obj;
    
    $diff = $nowTime->getTimestamp() - $datetime_object->getTimestamp();
    
    $result = "";
    
    //calc and subtract years
    $years = floor($diff/60/60/24/365);
    if($years > 0){ $diff -= $years*60*60*24*365; }
    
    //calc and subtract months
    $months = floor($diff/60/60/24/30);
    if($months > 0){ $diff -= $months*60*60*24*30; }
    
    //calc and subtract weeks
    $weeks = floor($diff/60/60/24/7);
    if($weeks > 0){ $diff -= $weeks*60*60*24*7; }
    
    //calc and subtract days
    $days = floor($diff/60/60/24);
    if($days > 0){ $diff -= $days*60*60*24; }
    
    //calc and subtract hours
    $hours = floor($diff/60/60);
    if($hours >0){ $diff -= $hours*60*60; }
    
    //calc and subtract minutes
    $min = floor($diff/60);
    if($min > 0){ $diff -= $min*60; }
    
    $qualifier = "about";
    
    
    
    if($years > 0){
//      $qualifier = $months > 1 ? "over" : "about";
      $unit = $years > 1 ? "years" : "year";
//      $years = $years == 1 ? "a" : $years;
      $result[] = "{$years} {$unit}";
    }
    if($months > 0){
//      $qualifier = $weeks > 1 ? "just over" : "about";
      $unit = $months > 1 ? "months" : "month";
//      $months = $months == 1 ? "a" : $months;
      $result[] = "{$months} {$unit}";
    }
    if($weeks > 0){
////      $qualifier = $days > 2 ? "about" : "about";
      $unit = $weeks > 1 ? "weeks" : "week";
      $result[] = "{$weeks} {$unit}";      
    }
    if($days > 0){
      $unit = $days > 1 ? "days" : "day";
//      $days = $days == 1 ? "a" : $days;
      $result[] = "{$days} {$unit}";      
    }
    if($hours > 0){
      $unit = $hours > 1 ? "hrs" : "hr";
//      $hours = $hours == 1 ? "a" : $hours;
      $result[] = "{$hours} {$unit}";      
    }
    if($min > 0){
//      $qualifier = $diff > 20 ? "a bit over" : "about";
      $unit = $min > 1 ? "min" : "min";
//      $min = $min == 1 ? "a" : $min;
      $result[] = "{$min} {$unit}";      
    }
    if($diff > 0){
      $unit = $diff > 1 ? "sec" : "sec";
      if(empty($result)){
        $result[] = "{$diff} {$unit}";
      }
    }else{
      $result[] = "0 seconds";
    }
    $ago = $use_ago ? " ago" : "";
    //format string
    $result_string = sizeof($result) > 1 ? "~".array_shift($result)." ".array_shift($result)."{$ago}" : "~".array_shift($result)."{$ago}"; 
    return $result_string;
  }

  function format_cart_display_time_element($time_obj){
    $elapsed_time = friendlyElapsedTime($time_obj);
    $formatted_time = $time_obj->format('d M Y g:ia');
    $iso_time = $time_obj->getTimestamp();
    
    return "<time title='{$formatted_time}' datetime='{$iso_time}'>{$elapsed_time}</time>";
    
  }
  
  function time_range_to_date_pair($time_range, $latest_available_date = false, $start_date = false, $end_date = false){
    if(!$latest_available_date){
      $latest_available_date = new DateTime();
    }
    if(is_string($latest_available_date)){
      $latest_available_date = new DateTime($latest_available_date);
    }
    
    //if start_date is valid, use time_range to go forward from that time
    //if end date is valid, use time_range to go back from that time
    $time_modifier = "-";
    if(strtotime($start_date)){
      $time_modifier = "+";
      $today = new DateTime($start_date);
    }elseif(strtotime($end_date)){
      $today = $latest_available_date->getTimestamp() < new DateTime($end_date) ? $latest_available_date : new DateTime();
    }else{
      $today = $latest_available_date;
    }
    $today->setTime(23,59,59);
    $earlier = clone($today);
    $earlier->modify("{$time_modifier}{$time_range}")->setTime(0,0,0);
    $times = array(
      'start_date' => $earlier->format('Y-m-d H:i:s'),
      'end_date' => $today->format('Y-m-d H:i:s'),
      'start_date_object' => $earlier,
      'end_date_object' => $today,
      'time_range' => $time_range,
      'message' => "<p>Using ".$today->format('Y-m-d')." as the new origin time</p>"
    );
    return $times;
    
  }
  
  function dates_covered($first_date,$last_date){
    $fd_object = new DateTime($first_date);
    $ld_object = new DateTime($last_date);
    $current_object = clone $fd_object;
    $results = array();
    
    while($current_object->getTimestamp() <= $ld_object->getTimestamp){
      $results[$current_object->format('Y-m-d')] = $current_object->format('D M j');
      $current_object->modify('+1 day');
    }
    
    return $results;
  }
  
  function day_graph_to_series($day_graph_info){
    // echo "<pre>";
    // var_dump($day_graph_info);
    // echo "</pre>";
    $keys = array_keys($day_graph_info['by_date']);
    $fd = array_shift($keys);
    $fd_object = new DateTime($fd);
    $ld = array_pop($keys);
    $ld_object = new DateTime($ld);

    $current_object = clone $fd_object;
    
    $results = array(
      'available_dates' => array(),
      'file_count' => array(),
      'file_volume' => array(),
      'transaction_count' => array()
    );
    while($current_object->getTimestamp() <= $ld_object->getTimestamp()){
      $date_key = $current_object->format('Y-m-d');
      $results['available_dates'][$date_key] = $current_object->format('D M j');
      if(array_key_exists($date_key,$day_graph_info['by_date'])){
        $results['file_count'][$date_key] = $day_graph_info['by_date'][$date_key]['file_count'];
        $results['file_volume'][$date_key] = floatval($day_graph_info['by_date'][$date_key]['file_size']);
        $results['transaction_count'][$date_key] = $day_graph_info['by_date'][$date_key]['upload_count'];
      }else{
        $results['file_count'][$date_key] = 0;
        $results['file_volume'][$date_key] = 0;
        $results['transaction_count'][$date_key] = 0;
      }
      $current_object->modify("+1 day");
    }
    return $results;
  }
  
  
  
?>