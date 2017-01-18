<?php

    //sleep(30);

    $redis = new Redis();
    $connected = $redis->connect("127.0.0.1");

    
    
    while (true) {
        $seconds = (int) date("s");
        print $seconds."\n";
        
        if ($seconds==45) {
            print "RESTARTING socket servers\n";
            $redis->set('storageserver0-stop',1);
            $redis->set('socketserver1-stop',1);
            $redis->set('socketserver2-stop',1);
            exit;
        }
        
        sleep(1);
    
    }

