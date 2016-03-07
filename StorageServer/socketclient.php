<?php

    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */

$redis = new Redis();
$connected = $redis->connect("127.0.0.1");

error_reporting(E_ALL);
ini_set('display_errors', 'on');

$fp = fopen("/home/trystan/runlock", "w");
if (! flock($fp, LOCK_EX | LOCK_NB)) { echo "Already running\n"; die; }

$client = stream_socket_client("tcp://127.0.0.1:1335", $errno, $errorMessage);

if ($client === false) {
    print "could not establish client\n";
    die;
}

$logger = new EmonLogger();
require "/var/www/html/PHPFina.php";
$phpfina = new PHPFina(array("datadir"=>"/home/trystan/phpfina/"),$logger);
require "/var/www/html/phpfiwa/PHPFiwa.php";
$phpfiwa = new PHPFiwa(array("datadir"=>"/home/trystan/phpfiwa/"),$logger);

while(true)
{
  $d = explode(",",fgets($client));

  if (count($d)==5) {

      $id = (int) $d[0];
      $time = (int) $d[1];
      $value = (float) $d[2];
      $engine = (int) $d[3][0];
      $padding_mode = (int) $d[4][0];
      // print "$id,$time,$value,$engine,$padding_mode\n";

      if ($engine==5) {
          if ($padding_mode==1) $phpfina->padding_mode = "join";
          $phpfina->post($id,$time,$value);
          if ($padding_mode==1) $phpfina->padding_mode = "nan";
      }
      if ($engine==6) $phpfiwa->post($id,$time,$value);

      $redis->incr("socketclient-count");
  }

  if (feof($client)) break;

  if ($redis->get('socketclient-stop')==1) {
      $redis->set('socketclient-stop',0);
      die;
  }
}

fclose($client);


class EmonLogger
{
    public function __construct()
    {
    
    }

    public function info ($message){
        // print $message."\n";
    }
    
    public function warn ($message){
        // print $message."\n";
    }
}
