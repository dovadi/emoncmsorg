<?php

function fast_input_post($redis,$userid)
{    
    $redis->incr("fiveseconds:inputhits");

    // input/post nodeid
    $_nodeid = 0;
    if (isset($_GET['node'])) $_nodeid = $_GET['node'];
    $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$_nodeid);
    if ($nodeid!=$_nodeid) return "Error: invalid node name";
    if (strlen($nodeid)>16) return "Error: node name must be 16 characters or less";
    
    // input/post time
    if (isset($_GET['time'])) $time = (int) $_GET['time']; else $time = time();

    // input/post data
    $datain = false;
    if (isset($_GET['json'])) $datain = $_GET['json'];
    if (isset($_GET['csv'])) $datain = $_GET['csv'];
    if (isset($_GET['data'])) $datain = $_GET['data'];
    if (isset($_POST['data'])) $datain = $_POST['data'];
    if ($datain=="" || $datain==false) return "Error: Request contains no data via csv, json or data tag";

    $json = preg_replace('/[^\w\s-.:,]/','',$datain);
    $datapairs = explode(',', $json);

    $csvi = 0;
    $data = array();
    for ($i=0; $i<count($datapairs); $i++)
    {
        $keyvalue = explode(':', $datapairs[$i]);

        if (isset($keyvalue[1])) {
            $key = $keyvalue[0];
            if ($key=='') return "Error: Format error, json key missing or invalid character";
            if (strlen($key)>64) return "Error: input name must be 64 characters or less";
            $value = (float) $keyvalue[1];
        } else {
            $key = $csvi+1;
            $value = $keyvalue[0];
            $csvi ++;
        }
        
        $post_time = $time;
        
        // Start of rate limiter
        $lasttime = $redis->get("postlimiter:$userid:$nodeid:$key");
        if ($lasttime==null) $lasttime = 0;
                        
        if (($post_time-$lasttime)>=5) {
            $redis->set("postlimiter:$userid:$nodeid:$key",$post_time);
            $data[$key] = $value;
        }
    }

    $packet = array(
        'userid' => $userid,
        'time' => $time,
        'nodeid' => $nodeid,
        'data'=>$data
    );
    
    if (count($data)>0) {
        $str = json_encode($packet);
        $uid = intval($userid);

        global $IQL;
        
        if ($uid<$IQL["L1"]) {
            $redis->rpush('inputbuffer1',$str);
        } elseif ($uid>=$IQL["L1"] && $uid<$IQL["L2"]) {
            $redis->rpush('inputbuffer2',$str);
        } elseif ($uid>=$IQL["L2"] && $uid<$IQL["L3"]) {
            $redis->rpush('inputbuffer3',$str);
        } elseif ($uid>=$IQL["L3"] && $uid<$IQL["L4"]) {
            $redis->rpush('inputbuffer4',$str);
        } elseif ($uid>=$IQL["L4"] && $uid<$IQL["L5"]) {
            $redis->rpush('inputbuffer5',$str);
        } else {
            $redis->rpush('inputbuffer6',$str);
        }
    }
    
    return "ok";
}

/*

input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]

The first number of each node is the time offset (see below).

The second number is the node id, this is the unique identifer for the wireless node.

All the numbers after the first two are data values. The first node here (node 16) has only one data value: 1137.

Optional offset and time parameters allow the sender to set the time
reference for the packets.
If none is specified, it is assumed that the last packet just arrived.
The time for the other packets is then calculated accordingly.

offset=-10 means the time of each packet is relative to [now -10 s].
time=1387730127 means the time of each packet is relative to 1387730127
(number of seconds since 1970-01-01 00:00:00 UTC)

Examples:

// legacy mode: 4 is 0, 2 is -2 and 0 is -4 seconds to now.
  input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
// offset mode: -6 is -16 seconds to now.
  input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
// time mode: -6 is 1387730121
  input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387730127
// sentat (sent at) mode:
  input/bulk.json?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&offset=543

See pull request for full discussion:
https://github.com/emoncms/emoncms/pull/118
*/
function fast_input_bulk($redis,$userid)
{
    $redis->incr("fiveseconds:inputhits");
    
    if (!isset($_GET['data']) && isset($_POST['data'])) {
        $data = json_decode($_POST['data']);
    } else {
        $data = json_decode($_GET['data']);
    }
    if ($data==null) return "Error: Format error, json string supplied is not valid";

    $dropped = 0; $droppednegative = 0;
    
    $len = count($data);
    if ($len==0) return "Error: Format error, json string supplied is not valid";
    if (!isset($data[$len-1][0])) return "Error: Format error, last item in bulk data does not contain any data";

    // Sent at mode: input/bulk.json?data=[[45,16,1137],[50,17,1437,3164],[55,19,1412,3077]]&sentat=60
    if (isset($_GET['sentat'])) {
        $time_ref = time() - (int) $_GET['sentat'];
    }  elseif (isset($_POST['sentat'])) {
        $time_ref = time() - (int) $_POST['sentat'];
    } 
    // Offset mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10
    elseif (isset($_GET['offset'])) {
        $time_ref = time() - (int) $_GET['offset'];
    } elseif (isset($_POST['offset'])) {
        $time_ref = time() - (int) $_POST['offset'];
    }
    // Time mode: input/bulk.json?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=1387729425
    elseif (isset($_GET['time'])) {
        $time_ref = (int) $_GET['time'];
    } elseif (isset($_POST['time'])) {
        $time_ref = (int) $_POST['time'];
    } 
    // Legacy mode: input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]
    else {
        $time_ref = time() - (int) $data[$len-1][0];
    }
    
    foreach ($data as $item)
    {
        if (count($item)>2)
        {
            // check for correct time format
            $itemtime = (int) $item[0];

            $time = $time_ref + (int) $itemtime;
            $nodeid = $item[1];
            $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);

            $inputs = array();
            $name = 1;
            for ($i=2; $i<count($item); $i++)
            {
                $value = (float) $item[$i];
                $inputs[$name] = $value;
                $name ++;
            }

            $array = array(
                'userid'=>$userid,
                'time'=>$time,
                'nodeid'=>$nodeid,
                'data'=>$inputs
            );

            // Start of rate limiter
            $lasttime = $redis->get("limiter:$userid:$nodeid");
            if ($lasttime==null) $lasttime = 0;
            
            $post_time = $time;
            if (($post_time-$lasttime)>=1)
            {
                $redis->set("limiter:$userid:$nodeid",$post_time);
                $str = json_encode($array);
                $uid = intval($userid);
                
                global $IQL;
                
                if ($uid<$IQL["L1"]) {
                    $redis->rpush('inputbuffer1',$str);
                } elseif ($uid>=$IQL["L1"] && $uid<$IQL["L2"]) {
                    $redis->rpush('inputbuffer2',$str);
                } elseif ($uid>=$IQL["L2"] && $uid<$IQL["L3"]) {
                    $redis->rpush('inputbuffer3',$str);
                } elseif ($uid>=$IQL["L3"] && $uid<$IQL["L4"]) {
                    $redis->rpush('inputbuffer4',$str);
                } elseif ($uid>=$IQL["L4"] && $uid<$IQL["L5"]) {
                    $redis->rpush('inputbuffer5',$str);
                } else {
                    $redis->rpush('inputbuffer6',$str);
                }
            } else { 
                if (($post_time-$lasttime)<0) $droppednegative ++;
                $dropped ++;
            }
        }
    }
    return "ok";
}

