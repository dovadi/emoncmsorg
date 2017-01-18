<?php

    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");
    
    $redis->set('usleep:1',3000);
    $redis->set('usleep:2',3000);
    $redis->set('usleep:3',3000);
    $redis->set('usleep:4',3000);
    $redis->set('usleep:5',3000);
    $redis->set('usleep:6',3000);
    
    $redis->set('SS0:usleep',1000);
    $redis->set('SS1:usleep',1000);
    $redis->set('SS2:usleep',1000);

