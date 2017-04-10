<?php

  $start = microtime(true);

  define('EMONCMS_EXEC', 1);
  
  chdir("/var/www/emoncms");

  require "process_settings.php";
  require "Modules/log/EmonLogger.php";
  $mysqli = @new mysqli($server,$username,$password,$database);

  $redis = new Redis();
  $redis->connect("127.0.0.1");

  include "Modules/feed/feed_model.php";
  $feed = new Feed($mysqli,$redis, $feed_settings);
  
  $result = $mysqli->query("SELECT id FROM users");
  while ($row = $result->fetch_object())
  {
      $userid = $row->id;
      $lastactive = 0;
      
      $result2 = $mysqli->query("SELECT id FROM feeds WHERE userid=$userid");
      while ($row2 = $result2->fetch_object())
      {
          $feedid = $row2->id;
          $timevalue = $feed->get_timevalue_seconds($feedid);
          
          if ($timevalue['time']>$lastactive) $lastactive = $timevalue['time'];
      }
  
      $result2 = $mysqli->query("SELECT id FROM input WHERE userid=$userid");
      while ($row2 = $result2->fetch_object())
      {
          $inputid = $row2->id;
          $inputtime = $redis->hget("input:lastvalue:$inputid",'time');
          
          if ($inputtime>$lastactive) $lastactive = $inputtime;
      }
      
      
      $mysqli->query("UPDATE users SET `lastactive` = '$lastactive' WHERE `id`= '$userid'");
  }
