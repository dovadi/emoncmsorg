<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */
    
    
    $specialuser = array();
    
    ini_set("error_log", "/var/log/emoncms/inputqueue_error1.log");

    define('EMONCMS_EXEC', 1);

    $fp = fopen("inputqueue1-lock", "w");
    if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

    require "/home/username/scripts/script-settings.php";
    chdir($emoncms_root);

    require "process_settings.php";
    require "Lib/EmonLogger.php";
   
    error_log("Start of error log file");
    
    
    $mysqli = new mysqli($server,$username,$password,$database);

    $redis = new Redis();
    $connected = $redis->connect($redis_server);
    if (!$connected) {
        echo "Can't connect to redis database"; die;
    }

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
    $buflength = 0;

    while(true)
    {
        if ((time()-$ltime)>=1)
        {
            $ltime = time();

            $last_buflength = $buflength;
            $buflength = $redis->llen('inputbuffer');

            $target = 100;
            
             
            $change = ($buflength - $target)*0.05;
            
            if ($buflength-$last_buflength<0) $change = 0;
            // A basic throthler to stop the script using up cpu when there is nothing to do.
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
            
            $usleep = (int) $redis->get('usleep:1');
            if ($usleep<0) $usleep = 0;

            $averagewrktime = 0;
            if ($rn>0) $averagewrktime = (int) ($totalwrktime / $rn);
            $totalwrktime = 0;
            
            $redis->set('queue:wrktime',$averagewrktime);
            $redis->set('queue:usleep',$usleep);

            echo "Buffer length: ".$buflength." ".$usleep." ".$rn."\n";
            $redis->incrby('queue:rn',$rn);
            $rn = 0;
            
            if ($redis->get('stopinputqueue')==1) {
              $redis->set('stopinputqueue',0);
              die;
            }
        }

        // check if there is an item in the queue to process
        $line_str = false;
        
        

        if ($redis->llen('inputbuffer')>0)
        {
            // check if there is an item in the queue to process
            $line_str = $redis->lpop('inputbuffer');
        }
        
        /*
        if ($line_str)
        {
            $packet = json_decode($line_str);
            $userid = $packet->userid;

            // Shift users out of queue 5 into queue 3
            
            if ($userid<4000) {
                $redis->rpush('inputbuffer5',$line_str);
                $line_str = false;
            }
        }*/
        

        if ($line_str)
        {
            $wrkstart = microtime(true);
            $rn++;

            //echo $line_str."\n";
            $packet = json_decode($line_str);

            $userid = $packet->userid;
            $time = $packet->time;
            $nodeid = $packet->nodeid;
            $data = $packet->data;
            
            // if ($userid==4351) error_log("User 4351: $time, $nodeid, ".json_encode($data));

            // Load current user input meta data
            // It would be good to avoid repeated calls to this
            $dbinputs = $input->get_inputs($userid);

            $tmp = array();

            foreach ($data as $name => $value)
            {
            
                $bypassnodelimit = false;
                foreach ($specialuser as $nbsu)
                {
                  if ($nbsu==$userid) $bypassnodelimit = true;
                }
            
                if ($input->check_node_id_valid($nodeid) || $bypassnodelimit)
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
            
            $wrktime = (int)((microtime(true) - $wrkstart)*1000000);
            
            if ($wrktime>100000) error_log("$userid, $nodeid, $wrktime");
            
            $totalwrktime += $wrktime;
        }
        usleep($usleep);
    }
