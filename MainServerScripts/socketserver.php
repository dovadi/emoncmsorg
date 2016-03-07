<?php

    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */

$fp = fopen("feedqueuelock", "w");
if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

$redis = new Redis();
$redis->connect("127.0.0.1");

// $server = stream_socket_server("tcp://0.0.0.0:1330", $errno, $errorMessage);

$server = stream_socket_server("tcp://0.0.0.0:1334", $errno, $errorMessage);

if ($server === false) {
    throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
}

while (true)
{
    $client = @stream_socket_accept($server);
    if(is_resource($client)) {
        $name = stream_socket_get_name($client,true);
        print "client connected: $name\n";

        while($client)
        {
            if ($redis->llen('feedpostqueue:1')>0)
            {
                $line = $redis->lpop("feedpostqueue:1");
                $redis->incr("socketserver-count");

                $result = fwrite($client,$line."\n");
                if (!$result) {
	    	    $client = false;
                    print "client disconnected\n";
	        }
            }
	    usleep(1000);

            if ($redis->get('socketserver-stop')==1) {
                print "socketserver-stop received during active connection loop\n";
                $redis->set('socketserver-stop',0);
                die;
            }
        }
    }

    usleep(1000);

    if ($redis->get('socketserver-stop')==1) {
        print "socketserver-stop received in waiting loop\n";
        $redis->set('socketserver-stop',0);
        die;
    }
}
