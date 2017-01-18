<?php

    //sleep(30);

    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");

    
    
    while (true) {
        $seconds = (int) date("s");
        print $seconds."\n";
        
        if ($seconds==55) {
            print "RESTARTING input processors\n";
            $redis->set('stopinputqueue',1);
            $redis->set('stopinputqueue2',1);
            $redis->set('stopinputqueue3',1);
            $redis->set('stopinputqueue4',1);
            $redis->set('stopinputqueue5',1);
            $redis->set('stopinputqueue6',1);
            exit;
        }
        
        sleep(1);
    
    }

