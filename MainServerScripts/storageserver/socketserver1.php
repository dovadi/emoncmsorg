<?php

$fp = fopen("socketserver1lock", "w");
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
                
                $usleep = (int) $redis->get('SS1:usleep');
                if ($usleep<0) $usleep = 0;
                
                if ($redis->get('socketserver1-stop')==1) {
                    $redis->set('socketserver1-stop',0);
                    die;
                }  
            }
        
            if ($redis->llen('feedpostqueue:1')>0)
            {
                $line = $redis->lpop("feedpostqueue:1");
                $redis->incr("socketserver1-count");
                $redis->incr("SS1:count");

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

    if ($redis->get('socketserver1-stop')==1) {
        print "socketserver1-stop received in waiting loop\n";
        $redis->set('socketserver1-stop',0);
        die;
    }
}
