<?php

function fast_input_post($redis,$userid)
{    
    $redis->incr("fiveseconds:inputhits");
    $valid = true; $error = "";

    $nodeid = 0;
    if (isset($_GET['node'])) $nodeid = $_GET['node'];
    
    if ($nodeid && !is_numeric($nodeid)) { $valid = false; $error = "Nodeid must be an integer between 0 and 30, nodeid given was not numeric"; }
    if ($nodeid<0 || $nodeid>31) { $valid = false; $error = "nodeid must be an integer between 0 and 30, nodeid given was out of range"; }
    $nodeid = (int) $nodeid;

    if (isset($_GET['time'])) $time = (int) $_GET['time']; else $time = time();

        $data = array();

        $datain = false;
        // code below processes input regardless of json or csv type
        if (isset($_GET['json'])) $datain = $_GET['json'];
        if (isset($_GET['csv'])) $datain = $_GET['csv'];
        if (isset($_GET['data'])) $datain = $_GET['data'];
        if (isset($_POST['data'])) $datain = $_POST['data'];

        if ($datain!="")
        {
            $json = preg_replace('/[^\w\s-.:,]/','',$datain);
            $datapairs = explode(',', $json);

            $csvi = 0;
            for ($i=0; $i<count($datapairs); $i++)
            {
                $keyvalue = explode(':', $datapairs[$i]);

                if (isset($keyvalue[1])) {
                    if ($keyvalue[0]=='') {$valid = false; $error = "Format error, json key missing or invalid character"; }
                    //if (!is_numeric($keyvalue[1])) {$valid = false; $error = "Format error, json value is not numeric"; }
                    $key = $keyvalue[0];
                    $value = (float) $keyvalue[1];
                } else {
                    //if (!is_numeric($keyvalue[0])) {$valid = false; $error = "Format error: csv value is not numeric"; }
                    $key = $csvi+1;
                    $value = $keyvalue[0];
                    $csvi ++;
                }
                
                $post_time = $time;
                
                // Start of rate limiter
                $lasttime = $redis->get("postlimiter:$userid:$nodeid:$key");
                if ($lasttime==null) $lasttime = 0;
                                
                if (($post_time-$lasttime)>=5)
                {
                    $redis->set("postlimiter:$userid:$nodeid:$key",$post_time);
                    $data[$key] = $value;
                } 
                else 
                {
                    // $redis->incr("dropped:$userid");
                }
            }

            $packet = array(
                'userid' => $userid,
                'time' => $time,
                'nodeid' => $nodeid,
                'data'=>$data
            );
            
            if (count($data)>0 && $valid) {
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
        }
        else
        {
            $valid = false;
            $error = "Request contains no data via csv, json or data tag";
        }
    
    $valid = true;
    if ($valid) $result = 'ok';
    else $result = "Error: $error\n";

    return $result;
}

function fast_input_bulk($redis,$userid)
{
    $redis->incr("fiveseconds:inputhits");
    $valid = true;
    
    if (!isset($_GET['data']) && isset($_POST['data']))
    {
        $data = json_decode($_POST['data']);
    }
    else 
    {
        $data = json_decode($_GET['data']);
    }

    $dropped = 0; $droppednegative = 0;
    
    $len = count($data);
    if ($len>0)
    {
        if (isset($data[$len-1][0]))
        {
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
                        // $redis->incr("dropped:$userid");
                    }
                } else { $valid = false; $error = "Format error, bulk item needs at least 3 values"; }
            }
        } else { $valid = false; $error = "Format error, last item in bulk data does not contain any data"; }
    } else { $valid = false; $error = "Format error, json string supplied is not valid"; }

    if ($dropped) {
        $valid = false; 
        $error = "Request exceed's max node update rate of 1 per second: $dropped $droppednegative times";
    }
    
    $valid = true;
    
    if ($valid) {
        $result = 'ok';
    } else { 
        $result = "Error: $error\n";
    }
    
    return $result;
}

