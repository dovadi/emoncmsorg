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

function input_controller()
{
    //return array('content'=>"ok");

    global $path,$mysqli, $redis, $user, $session, $route, $max_node_id_limit, $feed_settings, $IQL;
    // There are no actions in the input module that can be performed with less than write privileges
    if (!isset($session['write'])) return array('content'=>false);
    if (!$session['write']) return array('content'=>false);

    global $feed;
    $result = false;

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis, $feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);
    
    require "fast_input.php";
    
    $blocked_users = array();

    $route->format = 'json';

    // --------------------------------------------------------------------------------
    if ($route->action == 'bulk') $result = fast_input_bulk($redis,$session['userid']);
    else if ($route->action == 'post') $result = fast_input_post($redis,$session['userid']);
    // --------------------------------------------------------------------------------
    else if ($route->action == "clean") $result = $input->clean($session['userid']);
    else if ($route->action == "list") $result = $input->getlist($session['userid']);
    else if ($route->action == "getinputs") $result = $input->get_inputs($session['userid']);
    else if ($route->action == "getallprocesses") $result = $process->get_process_list();
    
    else if (isset($_GET['inputid']) && $input->belongs_to_user($session['userid'],get("inputid")))
    {
        if ($route->action == "delete") $result = $input->delete($session['userid'],get("inputid"));

        else if ($route->action == 'set') $result = $input->set_fields(get('inputid'),get('fields'));

        else if ($route->action == "process")
        {
            if ($route->subaction == "add") $result = $input->add_process($process,$session['userid'], get('inputid'), get('processid'), get('arg'), get('newfeedname'), get('newfeedinterval'),get('engine'));
            else if ($route->subaction == "list") $result = $input->get_processlist(get("inputid"));
            else if ($route->subaction == "delete") $result = $input->delete_process(get("inputid"),get('processid'));
            else if ($route->subaction == "move") $result = $input->move_process(get("inputid"),get('processid'),get('moveby'));
            else if ($route->subaction == "reset") $result = $input->reset_process(get("inputid"));
        }           
    }
    
    else if ($route->action == 'api') {
        $route->format = 'html';
        $result = view("Modules/input/Views/input_api.php", array());
    }
    else if ($route->action == 'view') {
        $route->format = 'html';
        $result =  view("Modules/input/Views/input_view.php", array());
    } 
    else {
        $route->format = 'html';
        $result = "<br><b>Input module:</b> Page not found. Back to <a href='".$path."input/view'>input/view</a>";
    }

    return array('content'=>$result);
}
