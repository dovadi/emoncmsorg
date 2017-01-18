<?php

$fp = fopen("socketserver2lock", "w");
if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

$redis = new Redis();
$redis->connect("127.0.0.1");

$server = stream_socket_server("tcp://0.0.0.0:PORT", $errno, $errorMessage);

if ($server === false) {
    throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
}

$usleep = 0;
$ltime = time();

while (true)
{
    $client = @stream_socket_accept($server);
    if(is_resource($client)) {
        $name = stream_socket_get_name($client,true);
        print "client connected: $name\n";

        while($client)
        {
            if ((time()-$ltime)>1) {
                $ltime = time();
                
                $usleep = (int) $redis->get('SS2:usleep');
                if ($usleep<0) $usleep = 0;
                
                if ($redis->get('socketserver2-stop')==1) {
                    $redis->set('socketserver2-stop',0);
                    die;
                }  
            }
            
            if ($redis->llen('feedpostqueue:2')>0)
            {
                $line = $redis->lpop("feedpostqueue:2");
                $redis->incr("socketserver2-count");
                $redis->incr("SS2:count");

                $result = fwrite($client,$line."\n");
                if (!$result) {
	    	            $client = false;
                    print "client disconnected\n";
	              }
            }
            
	          usleep($usleep);
        }
    }

    usleep(10000);

    if ($redis->get('socketserver2-stop')==1) {
        print "socketserver2-stop received in waiting loop\n";
        $redis->set('socketserver2-stop',0);
        die;
    }
}
