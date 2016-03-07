<?php

// Socket Client Checker script

// This script runs from crontab every 5 minutes to check if socketclient is processing
// if no items have been processed in the last 5 minutes then it will stop the socket client
// so that it can be restarted from its own crontab entry.

$redis = new Redis();
$connected = $redis->connect("127.0.0.1");

$count = $redis->getset("socketclient-count",0);

print date('Y-m-d H:i:s',time())." Count: ".$count."\n";

if ($count==0) {
    sleep(5);
    print "restarting socketclient\n";
    $redis-set("socketclient-stop",1);
}
