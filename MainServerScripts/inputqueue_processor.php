<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */
    
    $queue_name = "inputqueue:1";
    $stop_key = "stopinputqueue:1";
    
    ini_set("error_log", "error.log");

    define('EMONCMS_EXEC', 1);

    $fp = fopen("runlock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    chdir("/var/www/emoncms");

    require "process_settings.php";
    require "Modules/log/EmonLogger.php";
   
    error_log("Start of error log file");
    
    $mysqli = new mysqli($server,$username,$password,$database);

    $redis = new Redis();
    $redis->connect("127.0.0.1");

    require("Modules/user/user_model.php");
    $user = new User($mysqli,$redis,null);

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);

    $rn = 0;
    $ltime = time();

    $usleep = 1000;
    
    $totalwrktime = 0;
    
    $last_buflength = 0;

    while(true)
    {
        if ((time()-$ltime)>=1)
        {
            $ltime = time();

            $last_buflength = $buflength;
            $buflength = $redis->llen($queue_name);

            $target = 100;
            $change = ($buflength - $target)*0.05;
            
            if ($buflength-$last_buflength<0) $change = 0;
            // A basic throttler to stop the script using up cpu when there is nothing to do.
            $usleep -= $change;

            // Fine tune sleep
            /*
            if ($buflength<50) {
                $usleep += 50;
            } else {
                $usleep -= 50;
            }
            */
            // if there is a big buffer reduce sleep to zero to clear buffer.
            //if ($buflength>500) $usleep = 100;

            // if throughput is low then increase sleep significantly
            if ($rn==0) $usleep = 100000;

            // sleep cant be less than zero
            if ($usleep<0) $usleep = 0;
            if ($usleep>1500) $usleep = 1500;

            $averagewrktime = (int) ($totalwrktime / $rn);
            $totalwrktime = 0;
            
            echo "Buffer length: ".$buflength." ".$usleep." ".$rn."\n";

            $rn = 0;
            
            if ($redis->get($stop_key)==1) {
              $redis->set($stop_key,0);
              die;
            }
        }

        // check if there is an item in the queue to process
        $line_str = false;
        
        

        if ($redis->llen($queue_name)>0)
        {
            // check if there is an item in the queue to process
            $line_str = $redis->lpop($queue_name);
        }

        if ($line_str)
        {
            $wrkstart = microtime(true);
            $rn++;

            $packet = json_decode($line_str);

            $userid = $packet->userid;
            $time = $packet->time;
            $nodeid = $packet->nodeid;
            $data = $packet->data;
            
            // Load current user input meta data
            // It would be good to avoid repeated calls to this
            $dbinputs = $input->get_inputs($userid);

            $tmp = array();

            foreach ($data as $name => $value)
            {
                if ($input->check_node_id_valid($nodeid))
                {
                    if (!isset($dbinputs[$nodeid][$name])) {
                        $inputid = $input->create_input($userid, $nodeid, $name);
                        $dbinputs[$nodeid][$name] = true;
                        $dbinputs[$nodeid][$name] = array('id'=>$inputid);
                        $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    } else {
                        $inputid = $dbinputs[$nodeid][$name]['id'];
                        // Start of rate limiter
                        $lasttime = 0; if ($redis->exists("inputlimiter:$inputid")) $lasttime = $redis->get("inputlimiter:$inputid");
                        if (($time-$lasttime)>=4)
                        {
                            $redis->set("inputlimiter:$inputid",$time);
                            $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                            if ($dbinputs[$nodeid][$name]['processList']) {
                                $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
                            }
                        } else { 
                            //error_log("Error: input $userid $inputid $time - $lasttime posting too fast, dropped"); 
                        }
                    }
                }
                else
                {
                  error_log("Nodeid $nodeid is not valid user $userid"); 
                }
            }
            
            foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        }
        usleep($usleep);
    }
