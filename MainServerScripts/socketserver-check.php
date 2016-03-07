<?php

sleep(10);

$redis = new Redis();
$connected = $redis->connect("127.0.0.1");

$count = $redis->getset("socketserver-count",0);

print date('Y-m-d H:i:s',time())." Count: ".$count."\n";

if ($count==0) {
    print "restarting socketserver\n";
    $redis-set("socketserver-stop",1);
}

