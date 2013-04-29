<?php
session_start();
$cookieexpire=60*60*24*30;
$cookieidentity = "";
if (!isset($_COOKIE['identity'])) {
	try {
		require 'openid.php';
		$openid = new LightOpenID('lab.danfolkes.com');
		if(!$openid->mode) {
			if(isset($_GET['login'])) {
				$openid->identity = 'https://www.google.com/accounts/o8/id';
				header('Location: ' . $openid->authUrl());
			}
		} elseif($openid->mode == 'cancel') {
			//unset($_SESSION['identity']);
			$cookieidentity = null;
			setcookie("identity", $cookieidentity, time()-$cookieexpire);
		} else {
			if($openid->validate()) {
				
				$cookieidentity = $openid->identity;
				try {
					if ($db = new Sqlite3('db/biketrain.SQLite')) {
						$INSERT_User_SQL = "INSERT OR IGNORE INTO User (uname) ";
						$INSERT_User_SQL .= "SELECT '{$cookieidentity}'";
						$INSERT_User_SQL .= "WHERE NOT EXISTS (SELECT 1 FROM User WHERE uname = '{$cookieidentity}');";
						$db->query($INSERT_User_SQL);
						$db->close();
					}
				} catch(ErrorException $e) {
					$cookieidentity = null;
					$insertmessage = "Error:" . $e->getMessage();
				}
				setcookie("identity", $cookieidentity, time()+$cookieexpire);
				//$_SESSION['identity'] = $openid->identity;
			}
		}
	} catch(ErrorException $e) {
		setcookie("identity", "", time()-$cookieexpire);
	}
}
else
{
	$cookieidentity = $_COOKIE['identity'];
}
if((isset($_GET['logout'])) || (strlen($cookieidentity)<=0)) {
	//logout:
	$cookieidentity = null;
	setcookie("identity", $cookieidentity, time()-$cookieexpire);
	//unset($_SESSION['identity']);
	//session_destroy();
}
if ((isset($_GET['delete'])) && (isset($_POST['deleteid'])) && (isset($cookieidentity))) {
	//print_r($_POST);
	//print_r($_GET);
	//print_r($cookieidentity);
	//DELETE FROM 
	try {
		if ($db = new Sqlite3('db/biketrain.SQLite')) {
			$DELETERouteSQL = "DELETE FROM UserRoute WHERE id={$_POST['deleteid']} AND userid = (SELECT id FROM User WHERE uname='{$cookieidentity}' LIMIT 1 )";
			$db->query($DELETERouteSQL);
			$db->close();
			$insertmessage = "Success: {$DELETERouteSQL} ";
			//echo "INSERTRouteSQL: {$INSERTRouteSQL}";
		}
	} catch(ErrorException $e) {
		$insertmessage  = "Error:" . $e->getMessage();
	}
	
}

$insertmessage = "";
if ((isset($cookieidentity)) && (isset($_POST['routeselector']))) {
	//print_r($_POST);
	$train_id = $_POST['routeselector'];
	
	$find = array(":", "am", "pm");
	$train_time = $_POST['train_time'];
	$train_direction = 1;
	if (isset($train_direction_radios_blue))
		$train_direction = 0;	
		
	$train_days = "";
	if (isset($_POST[train_days_m]))
		$train_days .= ",m";
	if (isset($_POST[train_days_t]))
		$train_days .= ",t";
	if (isset($_POST[train_days_w]))
		$train_days .= ",w";
	if (isset($_POST[train_days_th]))
		$train_days .= ",th";
	if (isset($_POST[train_days_f]))
		$train_days .= ",f";
	if (isset($_POST[train_days_sa]))
		$train_days .= ",sa";
	if (isset($_POST[train_days_su]))
		$train_days .= ",su";
	$train_days = trim($train_days,",");
	
	$train_nick = $_POST['train_nick'];
	
	$train_guest = $_POST['train_guest'];
	
	try {
		if ($db = new Sqlite3('db/biketrain.SQLite')) {
			$INSERTRouteSQL = "INSERT INTO UserRoute VALUES(NULL,(SELECT id FROM User WHERE uname='{$cookieidentity}' LIMIT 1 ),'{$train_id}','{$train_time}','{$train_days}', '{$train_direction}', '{$train_nick}')";
			
			$db->query($INSERTRouteSQL);
			$INSERTRoute_ID = $db->lastInsertRowID();
			$db->close();
			$insertmessage = "You have added a section to your train.  You can review your <a href='#SavedRoutes'>Saved Routes</a> below.";
			//echo "INSERTRouteSQL: {$INSERTRouteSQL}";
		}
	} catch(ErrorException $e) {
		$insertmessage  = "Error:" . $e->getMessage();
	}
}
?><!DOCTYPE html>
<html class='fat'>
  <head>
    <title>Richmond Bike Train</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8" />
	
    <link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<link href="favicon.gif" rel="icon" type="image/gif" />
	
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
	<!--<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css" />-->
	<link rel="stylesheet" type="text/css" href="jquery.timepicker.css" />
	<link rel="stylesheet" type="text/css" href="main.css" />
	
	<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<!--<script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>-->
	<script src="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.js"></script>
	<script src="jquery.timepicker.js" type="text/javascript" ></script>
	
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false"></script>
	
	<script src="jstorage.min.js" type="text/javascript"></script>
	<script type="text/javascript" >
		var requests = [];
		var extradata = [];
		var arrayiterator = 0;
	<?php
		try {
			if ($db = new Sqlite3('db/biketrain.SQLite')) {
				$Routes = $db->query('SELECT * FROM Route');
				while ($Route = $Routes->fetchArray()) {
					$Waypoints = $db->query("SELECT * FROM Waypoint WHERE routeid={$Route[id]} ORDER BY position");
					echo "\r\n requests.push({";
					$origin = "";
					$destination = "";
					$waypointjs = "";
					$wayname1 = "";
					$wayname2 = "";
					
					while ($Waypoint = $Waypoints->fetchArray()) {
						//var_dump($Waypoint);
						if ($Waypoint[position] == 0) {
							$origin = "	origin: '{$Waypoint[point]}',";
							$wayname1 .= ",name1 : '{$Waypoint[name]}'";
						}
						else if ($Waypoint[position] == 1) {
							$destination = "	destination: '{$Waypoint[point]}',";
							$wayname2 .= ",name2 : '{$Waypoint[name]}'";
						}
						else 
							$waypoints .= "{ location:'{$Waypoint[point]}', stopover:true }" ;
					}
					$waypointjs = "waypoints: [" . $waypointjs . "],";
					
					echo "	" . $origin;
					echo "	" . $destination;
					echo "	" . $waypointjs;
					
					echo "	travelMode: google.maps.DirectionsTravelMode.BICYCLING";
					echo "});";
					
					echo "extradata.push({";
					echo "		id:{$Route[id]}";
					echo "		,name:'{$Route[name]}'";
					echo "		,routeresult:null";
					echo "		,routestatus:null";
					echo "		{$wayname1}";
					echo "		{$wayname2}";
					echo "});";
				}
				$db->close();
			}
		} catch(ErrorException $e) {
			echo $e->getMessage();
		}
		
	?>
	
	
	</script>
	
	<script type="text/javascript"  src='main.js'></script>
  </head>
  <body>
<div data-role="page" id="mypage">
 <div data-role="header" >
	<h1>Richmond Bike Train</h1>
	<a href="#left-panel" data-icon="arrow-l" data-iconpos="notext" data-shadow="false" data-iconshadow="false" class="ui-icon-nodisc">Saved Routes</a>
</div><!-- /header -->
<div data-role="content">
	<div class='form'>
		<div class='biketrainbanner'></div>
			<p>
				The <b>Bike Train</b> is a way for commuter bicyclers to coordinate routes and schedules to make bike riding in the city <b>safer</b> and <b>more fun</b>.
				<br />
				<ul class='tt'>
					<li>
						<?php
						if (isset($cookieidentity)) {
							echo '1. You are logged in.  We can save routes now!';
						}
						else
						{
							?>
								1. <a data-ajax="false" href="?login" title="" data-icon="alert" data-theme="e"  data-role="button" data-inline="true">Login with Google</a>
								( this is only to verify your humanity and to remember your routes. )
							<?php
						}
						?>
						
					</li>
					<li title="You may take anywhere from one to five routes to get somewhere.">
						2. Review the <a data-ajax="false" href='#maplink'>map</a> and see which routes you would take. 
					</li>
					<li>
						3. for each leg of the train, fill out the form below.
					</li>
					<li>
						4. you will be able to <a href='#left-panel'>manage your routes</a> and see how many other people will be on the train section when you are.
					</li>
				</ul> 
				
			</p>
			
		
			<div class='JoinTheTrain'>
				<?php
					if (isset($insertmessage))
						echo "{$insertmessage}";
				?>
				<h3>Join a Bike Train:</h3>
				<form action="?save" method="post">
					<div class='formsection'>
						<h3>#1. Select a Train</h3>
						<div id="routeselector"></div>
						<div style='height:200px;overflow:auto;' class="routeselector">
							<fieldset data-role="controlgroup" data-mini="true">
							<?php
							try {
								if ($db = new Sqlite3('db/biketrain.SQLite')) {
									$Routes = $db->query('SELECT * FROM Route');

									while ($Route = $Routes->fetchArray()) {
										//echo "<br/>>route: {$Route[id]}-{$Route[name]}";
										$Waypoints = $db->query("SELECT *, (SELECT COUNT(*) FROM UserRoute WHERE routeid = {$Route[id]}) as Count , (SELECT (length(days) - length(replace(days, ',', ''))+1) FROM UserRoute WHERE routeid = {$Route[id]}) as DaysCount FROM Waypoint WHERE routeid={$Route[id]} AND position <= 1  ORDER BY position LIMIT 2 ");
										echo "\r\n<input type='radio' onclick='updateform(\"routeselector\")' id='routeselector_id{$Route[id]}' name='routeselector' value='{$Route[id]}' />";
										echo "<label for='routeselector_id{$Route[id]}' >";
										//echo "{$Route[id]}-{$Route[name]}<br/><hr />";
										$userroutes = 0;
										while ($Waypoint = $Waypoints->fetchArray()) {
											echo " - [{$Waypoint[name]}]";
											if ($Waypoint[DaysCount]>0)
												$userroutes = $Waypoint[DaysCount];
										}
										echo " ({$userroutes} riders)</label>";
									}
									$db->close();
								}
							} catch(ErrorException $e) {
								echo $e->getMessage();
							}
							?>
							</fieldset>
						</div>
					</div>
					<div class='formsection' >
						<h3>#S. Starting Point:</h3>
						<div class='train_direction'>
							<div id="train_direction_radios_div" >
								<fieldset data-role="controlgroup" data-mini="true" data-type="horizontal">
								<input onclick='updateform("direction")' type="radio" 
									id="train_direction_radios_blue" 
									name="train_direction_radios" 
									value='0'  />
								<label for="train_direction_radios_blue" class='tt' title='select this if you are leaving from this location.'>Blue Bike</label>
								
								<input onclick='updateform("direction")' type="radio" 
									id="train_direction_radios_red" 
									name="train_direction_radios" 
									value='1' />
								<label  for="train_direction_radios_red" class='tt' title='select this if you are leaving from this location.'>Red Bike</label>
								</fieldset>
							</div>
						</div>
						<label>please view the map and choose the direction.</label>
					</div>
					<div class='formsection'>
						<h3>#3 Time:</h3>
						<!--<input type="text" id="train_time" name="train_time" size="10" />
						<label for="train_time">select the time of day that you will be starting your trip</label>-->
						<div style='height:200px;overflow:auto;' class="train_time_div">
						<?php
							$sD = "2010-01-01 8:00 am";
							$date = new DateTime($sD);
							$date2 = new DateTime($sD);
							$date2->modify("+1 day");
							$i = 0;
							echo "<fieldset data-role=\"controlgroup\" data-mini=\"true\">";
							while ($date < $date2) {
								$i++;
								echo "<input onclick='updateform(\"time\")' type='radio' name='train_time' id='train_time_{$date->format('Hi')}' value='{$date->format('Hi')}' />";
								echo "<label for='train_time_{$date->format('Hi')}'>{$date->format('Hi')} - {$date->format('h:i A')}</label>";
								$date = $date->modify("+15 minutes");
							}	
							echo "</fieldset>";
						?>
						</div>
					</div>
					<div class='formsection'>
						<h3>#4 Day:</h3>
						<div id="train_days" class="train_days_count">
							<fieldset data-role="controlgroup" data-mini="true">
								<input type="checkbox" id="train_days_su" 	name="train_days_su" data-mini="true"/>
								<label for="train_days_su">su <span class="train_days_su">0</span></label>
								
								<input type="checkbox" id="train_days_m" 		name="train_days_m" 	checked="checked" data-mini="true"/>	
								<label for="train_days_m">m <span class="train_days_m">0</span></label>
									
								<input type="checkbox" id="train_days_t" 		name="train_days_t" 	checked="checked" data-mini="true"/>	
								<label for="train_days_t">t <span class="train_days_t">0</span></label>
										
								<input type="checkbox" id="train_days_w" 		name="train_days_w" 	checked="checked" data-mini="true"/>	
								<label for="train_days_w">w <span class="train_days_w">0</span></label>
									
								<input type="checkbox" id="train_days_th" 	name="train_days_th" 	checked="checked" data-mini="true"/>	
								<label for="train_days_th">th <span class="train_days_th">0</span></label>
								
								<input type="checkbox" id="train_days_f" 		name="train_days_f" 	checked="checked" data-mini="true"/>	
								<label for="train_days_f">f <span class="train_days_f">0</span></label>
								
								<input type="checkbox" id="train_days_sa" 	name="train_days_sa" data-mini="true"/>						
								<label for="train_days_sa">sa <span class="train_days_sa">0</span></label>
							</fieldset>
						</div>
					</div>
					<div class='formsection'>
						<input type="text" id="train_nick" name="train_nick" size="40" placeholder="Nickname.  Example: Work 1" />
						<label for="train_nick"></label>
					</div>
					<div class='formsection'>
						<select id="train_guest" name="train_guest" data-mini="true">
							<option value="0">0 Guests</option>
							<option>1 Guests</option>
							<option>2 Guests</option>
							<option>3 Guests</option>
							<option>4 Guests</option>
							<option>5 Guests</option>
						</select>
					</div>
					<div class='train_submit formsection' >
						<?php
						if (!isset($cookieidentity)) {
							?>
								<span>
								Login to save routes
								</span>
							<?php
						} else {
							?>
								Please review your selections before saving.
								<input type='submit' value='SAVE' data-theme="e" />
							<?php
						}
						?>
					</div>
				</form>
				<div class='mapandtimediv'>
					<a name="maplink" id="maplink">
					<h3>User Map:</h3>
					<p>This area displays where and when other users are on the train:</p>
					<div id="map_canvas" style="height:30em;"></div>
					<div class='timediv'>
						<fieldset class="ui-grid-a">
							<div class="ui-block-a">
								<input type="text" id="time" style="border: 0; color: #f6931f; font-weight: bold;" data-inline="true" />
							</div>
							<div class="ui-block-b"><input type='checkbox' name='chkAutoTime' id='chkAutoTime'  data-inline="true" /><label for="chkAutoTime">Keep Time</label></div>
						</fieldset>
						
						<div class="ui-grid-a">
							<div class="ui-block-a">
								<a href='' id="buttonSlideDown"  style="" data-role="button" data-theme="a" data-inline="true">&lt;&lt;</a>
								<a href='' id="buttonSlideUp" style="" data-role="button" data-theme="a" data-inline="true">&gt;&gt;</a>
							</div>
							<div class="ui-block-b">
								<input type="range" name="slider-time" id="slider-time" min="0" max="86400" value="0" data-inline="true">
							</div>
						</div>
						<div id="directions_panel" style="margin:20px;background-color:#FFEE77;"></div>
						<div class='loadingmap'>loading map...</div>
					</div>
				</div>
			</div>
	</div>
</div><!-- /content -->
<div data-role="panel" id="left-panel" data-position="left" data-theme="c" data-dismissible="false">
	<a href="#" data-rel="close">Close menu</a>
				<?php
					if (isset($cookieidentity)) {
						try {
							if ($db = new Sqlite3('db/biketrain.SQLite')) {
								?> 
								<h3>Saved Routes:</h3>
				
								<?php
								echo "<div class=\"accordion\"> ";
								$SavedRoutesSQL = "SELECT *, UserRoute.id as UserRouteID FROM Route 
									INNER JOIN UserRoute ON UserRoute.routeid = Route.id
									INNER JOIN User ON UserRoute.userid= User.id
									WHERE  User.uname = '{$cookieidentity}'
									";
								$SavedRoutes = $db->query($SavedRoutesSQL);
								
								while ($SavedRoute = $SavedRoutes->fetchArray()) {
									if (!isset($SavedRoute[nickname]) || strlen($SavedRoute[nickname]) <= 0)
										$SavedRoute[nickname] = "{$SavedRoute[name]} - {$SavedRoute[time]} - {$SavedRoute[days]}";
									echo "<h3>{$SavedRoute[nickname]}</h3>";
									echo "<div>";
									echo "<br/>name: {$SavedRoute[name]}";
									echo "<br/>description: {$SavedRoute[description]}";
									echo "<br/>startposition: {$SavedRoute[startposition]}";
									echo "<br/>days: {$SavedRoute[days]}";
									echo "<br/>time: {$SavedRoute[time]}";
									echo "<br/># other bikers: 1";
									echo "<form action='?delete' method='post'>";
									echo "<br/>UserRouteID:{$SavedRoute[UserRouteID]}<br/>";
									echo "<input type='hidden' name='deleteid' value='{$SavedRoute[UserRouteID]}' />";
									echo "<button>Delete</button>";
									echo "</form>";
									echo "<br/>use this to create new";
									echo "</div>";
								}
								echo "</div>";
								$db->close();
							}
						} catch(ErrorException $e) {
							echo $e->getMessage();
						}
					}
					
					?>
				
</div><!-- /panel --> 
<div data-role="footer">
	<h1>
		<a href="https://github.com/danfolkes/bike-train/">Start a Bike Train</a>
		| <a href="https://twitter.com/search/realtime?q=%23BikeTrain">#BikeTrain</a>
		| <a href="https://docs.google.com/forms/d/1hlYE0qoImoh8scdKU_alUK-47ppjisRKmZALE-YIsAo/viewform" >Contact Form</a>
		| <a data-ajax="false" href="#maplink" data-icon="arrow-r" data-iconpos="notext2" data-shadow="false" data-iconshadow="false" class="ui-icon-nodisc">Map</a>
		<?php
		if (isset($cookieidentity)) {
		?>
			| <a href="#left-panel" data-icon="arrow-l" data-iconpos="notext2" data-shadow="false" data-iconshadow="false" class="ui-icon-nodisc22">Saved Routes</a>
			| <a href="?logout">logout</a>
		<?php
		}
		else
		{
		?>
			| Saved Routes
			| <a href="?login">login</a>
		<?php
		}
		?>
	</h1>
	
	
</div><!-- /footer -->
</div><!-- page -->

  </body>
</html>
