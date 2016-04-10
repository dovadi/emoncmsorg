<?php

  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  global $user, $path, $session;

?>
<style>
.table td:nth-of-type(1) { width:40%;}
</style>

<h2><?php echo _("Feed API");?></h2>

<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in you have this options to authenticate with the API key:'); ?></p>
<ul><li><?php echo _('Append on the URL of your request: &apikey=APIKEY'); ?></li>
<li><?php echo _('Use POST parameter: "apikey=APIKEY"'); ?></li>
<li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li></ul>
<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _("Html");?></h3>
<p><a href="<?php echo $path; ?>feed/list"><?php echo $path; ?>feed/list</a> - <?php echo _("The feed list view");?></p>
<p><a href="<?php echo $path; ?>feed/api"><?php echo $path; ?>feed/api</a> - <?php echo _("This page");?></p>

<h3><?php echo _("JSON");?></h3>
<p><?php echo _("To use the json/text api the request url needs to include .json");?></p>


<p><b>Read feed actions</b></p>
<table class="table">
    <tr><td><?php echo _("List feeds for autenticated user"); ?></td><td>
		<a href="<?php echo $path; ?>feed/list.json"><?php echo $path; ?>feed/list.json</a>
	</td></tr>
    <tr><td><?php echo _("List public feeds for the given user"); ?></td><td>
		<a href="<?php echo $path; ?>feed/list.json?userid=0"><?php echo $path; ?>feed/list.json?userid=0</a>
	</td></tr>
    <tr><td><?php echo _("Get feed field");?></td><td>
		<a href="<?php echo $path; ?>feed/get.json?id=1&field=name"><?php echo $path; ?>feed/get.json?id=1&field=name</a>
	</td></tr>
    <tr><td><?php echo _("Get all feed fields");?></td><td>
		<a href="<?php echo $path; ?>feed/aget.json?id=1"><?php echo $path; ?>feed/aget.json?id=1</a>
	</td></tr>
</table>

<p><b>Read feed data actions</b></p>
<table class="table">
	<tr><td><?php echo _("Last updated time and value for feed");?></td><td>
		<a href="<?php echo $path; ?>feed/timevalue.json?id=1"><?php echo $path; ?>feed/timevalue.json?id=1</a>
	</td></tr>
	<tr><td><?php echo _("Last value of a given feed");?></td><td>
		<a href="<?php echo $path; ?>feed/value.json?id=1"><?php echo $path; ?>feed/value.json?id=1</a>
	</td></tr>
    <tr><td><?php echo _("Last value for multiple feeds");?></td><td>
		<a href="<?php echo $path; ?>feed/fetch.json?ids=1,2,3"><?php echo $path; ?>feed/fetch.json?ids=1,2,3</a>
	</td></tr>
    <tr><td><?php echo _("Returns feed data");?></td><td>
		<a href="<?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=5"><?php echo $path; ?>feed/data.json?id=0&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=5</a>
	</td></tr>
</table>

<p><b>Write feed data actions</b></p>
<table class="table">
    <tr><td>Insert new data point</td><td>
		<a href="<?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/insert.json?id=0&time=UNIXTIME&value=100.0</a>
	</td></tr>
    <tr><td>Update data point</td><td>
		<a href="<?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0"><?php echo $path; ?>feed/update.json?id=0&time=UNIXTIME&value=100.0</a>
	</td></tr>
</table>

<p><b>Feed setup actions</b></p>
<table class="table">
	<tr><td>Create new feed, emoncms.org supported engines:<br>5:PHPFINA, 2:PHPTIMESERIES</td><td>
		<a href='<?php echo $path; ?>feed/create.json?tag=Test&name=Power&datatype=1&engine=5&options={"interval":10}'><?php echo $path; ?>feed/create.json?tag=Test&name=Power&datatype=1&engine=5&options={"interval":10}</a>
	</td></tr>
    <tr><td>Delete existent feed</td><td>
		<a href="<?php echo $path; ?>feed/delete.json?id=0"><?php echo $path; ?>feed/delete.json?id=0</a>
	</td></tr>
    <tr><td>Update feed field</td><td>
		<a href="<?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}"><?php echo $path; ?>feed/set.json?id=0&fields={'name':'anewname'}</a>
	</td></tr>
    <tr><td><?php echo _("Return total engines size");?></td><td>
		<a href="<?php echo $path; ?>feed/updatesize.json"><?php echo $path; ?>feed/updatesize.json</a>
	</td></tr>
</table>
