<?php

  chdir("/var/www/emoncms/");

  define('EMONCMS_EXEC', 1);

  // 1) Load settings and core scripts
  require "process_settings.php";
  require "Modules/log/EmonLogger.php";
  // 2) Database
  $mysqli = @new mysqli($server,$username,$password,$database);

  $redis = new Redis();
  $redis->connect("127.0.0.1");

  require("Modules/user/user_model.php");
  $user = new User($mysqli,$redis,null);

  require "Modules/feed/feed_model.php";
  $feed = new Feed($mysqli,$redis,$feed_settings);
  
  $result = $mysqli->query("SELECT * FROM users");
  while ($row = $result->fetch_object()) {
      $userid = $row->id;
      
      // Number of feeds
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `feeds` = '$feeds' WHERE `id`= '$userid'");
      
      // PHPTimeSeries count
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid' AND engine='2'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `phptimeseries` = '$feeds' WHERE `id`= '$userid'");
      
      // PHPFina count
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid' AND engine='5'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `phpfina` = '$feeds' WHERE `id`= '$userid'");
      
      // Server 0 count
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid' AND server='0'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `server0` = '$feeds' WHERE `id`= '$userid'");
      
      /*
      // Server 1 count
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid' AND server='1'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `server1` = '$feeds' WHERE `id`= '$userid'");
      
      // Server 2 count
      $result2 = $mysqli->query("SELECT COUNT(*) FROM feeds WHERE userid='$userid' AND server='2'");
      $row2 = $result2->fetch_array();
      $feeds = (int) $row2[0];
      $mysqli->query("UPDATE users SET `server2` = '$feeds' WHERE `id`= '$userid'");      
      */
      
      // Number of inputs
      $result2 = $mysqli->query("SELECT COUNT(*) FROM input WHERE userid='$userid'");
      $row2 = $result2->fetch_array();
      $inputs = (int) $row2[0];
      $mysqli->query("UPDATE users SET `inputs` = '$inputs' WHERE `id`= '$userid'");
      
      // print $userid." ".$inputs." ".$feeds."\n";
  }
  
  
