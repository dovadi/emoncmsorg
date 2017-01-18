<?php

sleep(10);

$redis = new Redis();
$connected = $redis->connect("127.0.0.1");

$count = $redis->getset("socketserver2-count",0);

print date('Y-m-d H:i:s',time())." Count: ".$count."\n";

if ($count==0) {
    print "restarting socketserver2\n";
    // $redis-set("socketserver2-stop",1);
}

