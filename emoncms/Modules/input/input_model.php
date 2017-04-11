<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');



class Input
{
    private $mysqli;
    private $feed;
    private $redis;

    public function __construct($mysqli,$redis,$feed)
    {
        $this->mysqli = $mysqli;
        $this->feed = $feed;
        $this->redis = $redis;
    }
    // Check a nodeID against the current node-ID limits to see if it's valid
    // True = Valid
    // False = not valid
    public function check_node_id_valid($nodeid)
    {
        global $max_node_id_limit;
        
        // As highlighted by developer:fake-name PHP's doesnt have a function
        // for checking if a string will cast to a valid integer.
        //
        // is_numeric is the closest function but it allows input of:
        // Octal, e-notation (+0123.45e6) & Hex. 
        // 
        // Casting with (int) will convert input such as Array({stuff}) to 1 
        // whereas NAN would be a more appropriate result.  
        //
        // Other languages such as Python will return an error if you try and 
        // cast a variable in this way.
        //
        // checking against isNumeric will probably catch *most*
        // of the potential issues for now but it may be good look at catching
        // non-integer numbers at some point
        
        return true;
        
        if (!is_numeric ($nodeid))
        {
            return false;
        }

        $nodeid = (int) $nodeid;

        if (!isset($max_node_id_limit))
        {
            $max_node_id_limit = 32;    // Default to 32 if not overridden
        }

        if ($nodeid<$max_node_id_limit)
        {
            return true;
        }
        else
        {
            return false;
        }

    }
    
    public function create_input($userid, $nodeid, $name)
    {
        global $max_node_id_limit;
        $userid = (int) $userid;
        
        $nodeid = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$nodeid);
        if (strlen($nodeid)>16) return false;
        $name = preg_replace('/[^\p{N}\p{L}_\s-.]/u','',$name);
        if (strlen($name)>16) return false;
        
        // Add to mysql
        $this->mysqli->query("INSERT INTO input (userid,name,nodeid) VALUES ('$userid','$name','$nodeid')");
        $id = $this->mysqli->insert_id;
        // Add to redis
        if ($id>0) {
            $this->redis->sAdd("user:inputs:$userid", $id);
            $this->redis->hMSet("input:$id",array('id'=>$id,'nodeid'=>$nodeid,'name'=>$name,'description'=>"", 'processList'=>""));
        }
        return $id;

    }

    public function exists($inputid)
    {
        $inputid = (int) $inputid;
        $result = $this->mysqli->query("SELECT id FROM input WHERE `id` = '$inputid'");
        if ($result->num_rows == 1) return true; else return false;
    }

    public function set_timevalue($id, $time, $value)
    {
        $id = (int) $id;
        $time = (int) $time;
        $value = (float) $value;
        $this->redis->hMset("input:lastvalue:$id", array('value' => $value, 'time' => $time));
    }

    // used in conjunction with controller before calling another method
    public function belongs_to_user($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;

        $result = $this->mysqli->query("SELECT id FROM input WHERE userid = '$userid' AND id = '$inputid'");
        if ($result->fetch_array()) return true; else return false;
    }

    public function set_processlist($id, $processlist)
    {
        $this->redis->hset("input:$id",'processList',$processlist);
        $this->mysqli->query("UPDATE input SET processList = '$processlist' WHERE id='$id'");
    }

    public function set_fields($id,$fields)
    {
        $id = intval($id);
        $fields = json_decode(stripslashes($fields));

        $array = array();
        
        if (isset($fields->description)) {
            $description = preg_replace('/[^\w\s-]/','',$fields->description);
            $array[] = "`description` = '$description'";
            $this->redis->hset("input:$id",'description',$description);
        }
                
        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);
        $this->mysqli->query("UPDATE input SET ".$fieldstr." WHERE `id` = '$id'");

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

    public function add_process($process_class,$userid,$inputid,$processid,$arg)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;
        $processid = (int) $processid;                                    // get process type (ProcessArg::)
        
        $process = $process_class->get_process($processid);
        $processtype = $process[1];                                       // Array position 1 is the processtype: VALUE, INPUT, FEED
        $datatype = $process[4];                                          // Array position 4 is the datatype
        
        if (!$process) {
            return array('success'=>false, 'message'=>'This process does not exist');
        }
        
        if (isset($process[5]) && $process[5]=="Deleted") {
            return array('success'=>false, 'message'=>'This process is not supported on this server');
        }
        
        switch ($processtype) {
            case ProcessArg::VALUE:                                       // If arg type value
                if ($arg == '') return array('success'=>false, 'message'=>'Argument must be a valid number greater or less than 0.');
                
                $arg = (float)$arg;
                $arg = str_replace(',','.',$arg); // hack to fix locale issue that converts . to ,
                    
                break;
            case ProcessArg::INPUTID:                                     // If arg type input
                $arg = (int) $arg;
                if (!$this->exists($arg)) return array('success'=>false, 'message'=>'Input does not exist!');
                break;
            case ProcessArg::FEEDID:                                      // If arg type feed
                $arg = (int) $arg;
                if (!$this->feed->exist($arg)) return array('success'=>false, 'message'=>'Feed does not exist!');
                break;
            case ProcessArg::NONE:                                        // If arg type none
                $arg = 0;
                break;
        }

        $list = $this->get_processlist($inputid);
        
        // check to see if feed is already being written too
        $listarray = explode(",",$list);
        foreach ($listarray as $item) {
            $keyval = explode(":",$item);
            $tmp = $process_class->get_process((int)$keyval[0]);
            if ($tmp[1]==ProcessArg::FEEDID && $keyval[1]==$arg) {
                return array('success'=>false, 'message'=>'Feed is already being written to, select create new');
            }
        }
        
        if ($list) $list .= ',';
        $list .= $processid . ':' . $arg;
        $this->set_processlist($inputid, $list);

        return array('success'=>true, 'message'=>'Process added');
    }

    /******
    * delete input process by index
    ******/
    public function delete_process($inputid, $index)
    {
        $inputid = (int) $inputid;
        $index = (int) $index;

        $success = false;
        $index--; // Array is 0-based. Index from process page is 1-based.

        // Load process list
        $array = explode(",", $this->get_processlist($inputid));

        // Delete process
        if (count($array)>$index && $array[$index]) {unset($array[$index]); $success = true;}

        // Save new process list
        $this->set_processlist($inputid, implode(",", $array));

        return $success;
    }

    /******
    * move_input_process - move process up/down list of processes by $moveby (eg. -1, +1)
    ******/
    public function move_process($id, $index, $moveby)
    {
        $id = (int) $id;
        $index = (int) $index;
        $moveby = (int) $moveby;

        if (($moveby > 1) || ($moveby < -1)) return false;  // Only support +/-1 (logic is easier)

        $process_list = $this->get_processlist($id);
        $array = explode(",", $process_list);
        $index = $index - 1; // Array is 0-based. Index from process page is 1-based.

        $newindex = $index + $moveby; // Calc new index in array
        // Check if $newindex is greater than size of list
        if ($newindex > (count($array)-1)) $newindex = (count($array)-1);
        // Check if $newindex is less than 0
        if ($newindex < 0) $newindex = 0;

        $replace = $array[$newindex]; // Save entry that will be replaced
        $array[$newindex] = $array[$index];
        $array[$index] = $replace;

        // Save new process list
        $this->set_processlist($id, implode(",", $array));
        return true;
    }

    // USES: redis input
    public function reset_process($id)
    {
        $id = (int) $id;
        $this->set_processlist($id, "");
    }
    
    public function get_inputs($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:inputs:$userid")) $this->load_to_redis($userid);

        $dbinputs = array();
        $inputids = $this->redis->sMembers("user:inputs:$userid");

        foreach ($inputids as $id)
        {
            $row = $this->redis->hGetAll("input:$id");
            if ($row['nodeid']==null) $row['nodeid'] = 0;
            if (!isset($dbinputs[$row['nodeid']])) $dbinputs[$row['nodeid']] = array();
            $dbinputs[$row['nodeid']][$row['name']] = array('id'=>$row['id'], 'processList'=>$row['processList']);
        }

        return $dbinputs;
    }
    
    //-----------------------------------------------------------------------------------------------
    // This public function gets a users input list, its used to create the input/list page
    //-----------------------------------------------------------------------------------------------
    // USES: redis input & user & lastvalue
    
    public function getlist($userid)
    {
        $userid = (int) $userid;
        if (!$this->redis->exists("user:inputs:$userid")) $this->load_to_redis($userid);

        $inputs = array();
        $inputids = $this->redis->sMembers("user:inputs:$userid");
        foreach ($inputids as $id)
        {
            $row = $this->redis->hGetAll("input:$id");
            $lastvalue = $this->redis->hmget("input:lastvalue:$id",array('time','value'));
            $row['time'] = $lastvalue['time'];
            $row['value'] = $lastvalue['value'];
            $inputs[] = $row;
        }
        return $inputs;
    }
    
    public function get_name($id)
    {
        $id = (int) $id;
        if (!$this->redis->exists("input:$id")) $this->load_input_to_redis($id);
        return $this->redis->hget("input:$id",'name');
    }

    public function get_processlist($id)
    {
        $id = (int) $id;
        if (!$this->redis->exists("input:$id")) $this->load_input_to_redis($id);
        return $this->redis->hget("input:$id",'processList');
    }

    public function get_last_value($id)
    {
        $id = (int) $id;
        return $this->redis->hget("input:lastvalue:$id",'value');
    }
    
    public function delete($userid, $inputid)
    {
        $userid = (int) $userid;
        $inputid = (int) $inputid;
        
        $this->mysqli->query("DELETE FROM input WHERE userid = '$userid' AND id = '$inputid'");
        $this->redis->del("input:$inputid");
        $this->redis->srem("user:inputs:$userid",$inputid);
    }

    public function clean($userid)
    {
        $n = 0;
        $qresult = $this->mysqli->query("SELECT * FROM input WHERE `userid` = '$userid'");
        while ($row = $qresult->fetch_array())
        {
            $inputid = $row['id'];
            if ($row['processList']==NULL || $row['processList']=='')
            {
                $result = $this->mysqli->query("DELETE FROM input WHERE userid = '$userid' AND id = '$inputid'");
                
                $this->redis->del("input:$inputid");
                $this->redis->srem("user:inputs:$userid",$inputid);
                $n++;
            }
        }
        return "Deleted $n inputs";
    }

    // Redis cache loaders

    private function load_input_to_redis($inputid)
    {
        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList FROM input WHERE `id` = '$inputid'");
        $row = $result->fetch_object();

        $this->redis->sAdd("user:inputs:$userid", $row->id);
        $this->redis->hMSet("input:$row->id",array(
            'id'=>$row->id,
            'nodeid'=>$row->nodeid,
            'name'=>$row->name,
            'description'=>$row->description,
            'processList'=>$row->processList
        ));
    }

    private function load_to_redis($userid)
    {
        $result = $this->mysqli->query("SELECT id,nodeid,name,description,processList FROM input WHERE `userid` = '$userid'");
        while ($row = $result->fetch_object())
        {
            $this->redis->sAdd("user:inputs:$userid", $row->id);
            $this->redis->hMSet("input:$row->id",array(
                'id'=>$row->id,
                'nodeid'=>$row->nodeid,
                'name'=>$row->name,
                'description'=>$row->description,
                'processList'=>$row->processList
            ));
        }
    }

}
