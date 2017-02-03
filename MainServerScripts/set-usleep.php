<?php

    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");

    $redis->set('usleep:1',3000);
    $redis->set('usleep:2',4000);
    $redis->set('usleep:3',4000);
    $redis->set('usleep:4',4000);
    $redis->set('usleep:5',1500);
    $redis->set('usleep:6',4000);

    $redis->set('SS0:usleep',600);
    $redis->set('SS1:usleep',2000);
    $redis->set('SS2:usleep',600);

