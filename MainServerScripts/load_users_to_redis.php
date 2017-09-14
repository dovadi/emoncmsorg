<?php

  define('EMONCMS_EXEC', 1);
  
  chdir("/var/www/emoncms");

  require "process_settings.php";
  require "Lib/EmonLogger.php";
  $mysqli = @new mysqli($server,$username,$password,$database);

  $redis = new Redis();
  $redis->connect("127.0.0.1");
  
  $result = $mysqli->query("SELECT id,apikey_write FROM users");
  $row = $result->fetch_object();
  
  $userid = $row->id;
  
  $redis->hmset("user:$userid",array('apikey_write'=>$row->apikey_write));
  
  
