<?php
/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/
error_reporting(E_ALL);
ini_set('display_errors', 'on');

if (!isset($_GET['q'])) die;
if (!isset($_GET['id'])) die;

$q = $_GET['q'];
$id = (int) $_GET['id'];

$logger = new EmonLogger();

require "PHPFina.php";
$phpfina = new PHPFina(array("datadir"=>"/home/trystan/phpfina/"),$logger);

header('Content-Type: application/json');
switch ($q)
{
    case "create":
        print $phpfina->create($id,array("interval"=>get('interval')));
        break;
    
    case "post":
        print $phpfina->post($id,get('time'),get('value'));
        break;
    
    case "update":
        print $phpfina->update($id,get('time'),get('value'));
        break;

    case "datanew":
        print json_encode($phpfina->get_data_new($id,get('start'),get('end'),get('interval'),get('skipmissing'),get('limitinterval')));
        break;
        
    case "data":
        print json_encode($phpfina->get_data($id,get('start'),get('end'),get('interval')));
        break;
    
    case "lastvalue":
        print json_encode($phpfina->lastvalue($id));
        break;
    
    case "export":
        $phpfina->export($id,get('start'));
        break;
    
    case "delete":    
        print $phpfina->delete($id);
        break;
        
    case "size":
        print $phpfina->get_feed_size($id);
        break;
    
    case "meta":
        print json_encode($phpfina->get_meta($id));
        break;
    
    case "csvexport":
        print $phpfina->csv_export($id,get('start'),get('end'),get('interval'));
        break;
}
    
function get($index)
{
    $val = null;
    if (isset($_GET[$index])) $val = $_GET[$index];
    
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}

class EmonLogger
{
    public function __construct()
    {
    
    }

    public function info ($message){
        print $message;
    }
    
    public function warn ($message){
        print $message;
    }
}
