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
    <title>Bike Train</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <meta charset="utf-8" />
	
    <link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<link href="favicon.gif" rel="icon" type="image/gif" />
	
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css" />
	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&amp;sensor=false"></script>
	<script type="text/javascript" src="jquery.timepicker.js"></script>
	<link rel="stylesheet" type="text/css" href="jquery.timepicker.css" />
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script src="jstorage.min.js"></script>
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
	<div class='form'>
		<h1><a href='./'><img src="BikeTrain100x100.gif" alt="BikeTrain100x100" /> Bike Train</a></h1>
		<?php
  
		if (isset($cookieidentity)) {
			echo '<small> | <a href="?logout">Logout</a></small>';
		}
		else
		{
			?>
			<form action="?login" method="post">
				<small><button>Login with Google</button></small>
			</form>
			<?php
		}
		?>
			<p >
				The Bike Train is a way for commuter bicyclers to coordinate routes and schedules to make bike riding in the city safer and more fun.
				<br />
				<ul class='tt'>
					<li>
						<a href="?login" title="this is only to verify your humanity and to remember your routes.">Login with Google</a>
					</li>
					<li title="You may take anywhere from one to five routes to get somewhere.">
						Second, Review the map and see which routes you would take. 
					</li>
					<li>
						Then, for each leg of the train, fill out the form below.
					</li>
					<li>
						Finally, you will be able to manage your routes and see how many other people will be on the train section when you are.
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
							<?php
							try {
								if ($db = new Sqlite3('db/biketrain.SQLite')) {
									$Routes = $db->query('SELECT * FROM Route');

									while ($Route = $Routes->fetchArray()) {
										//echo "<br/>>route: {$Route[id]}-{$Route[name]}";
										$Waypoints = $db->query("SELECT *, (SELECT COUNT(*) FROM UserRoute WHERE routeid = {$Route[id]}) as Count FROM Waypoint WHERE routeid={$Route[id]} AND position <= 1  ORDER BY position LIMIT 2 ");
										echo "\r\n<input type='radio' onclick='updateform(\"routeselector\")' id='routeselector_id{$Route[id]}' name='routeselector' value='{$Route[id]}' />";
										echo "<label for='routeselector_id{$Route[id]}' >";
										//echo "{$Route[id]}-{$Route[name]}<br/><hr />";
										$userroutes = 0;
										while ($Waypoint = $Waypoints->fetchArray()) {
											echo " - [{$Waypoint[name]}]";
											$userroutes = $Waypoint[Count];
										}
										echo " ({$userroutes} riders)</label><br/>";
									}
									$db->close();
								}
							} catch(ErrorException $e) {
								echo $e->getMessage();
							}
							?>
						</div>
					</div>
					<div class='formsection'>
						<h3>#S. Starting Point:</h3>
						<div class='train_direction'>
							<div id="train_direction_radios_div">
								<input onclick='updateform("direction")' type="radio" 
									id="train_direction_radios_blue" 
									name="train_direction_radios" 
									value='0' />
								<label for="train_direction_radios_blue" class='tt' title='select this if you are leaving from this location.'>Blue Bike</label>
								
								<input onclick='updateform("direction")' type="radio" 
									id="train_direction_radios_red" 
									name="train_direction_radios" 
									value='1' />
								<label for="train_direction_radios_red" class='tt' title='select this if you are leaving from this location.'>Red Bike</label>
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
							$date = new DateTime("2010-01-01 8:00 am");
							$date2 = new DateTime("2010-01-01");
							$date2->modify("+1 day");
							$i = 0;
							while ($date < $date2) {
								$i++;
								echo "<br /><input onclick='updateform(\"time\")' type='radio' name='train_time' id='train_time_{$date->format('Hi')}' value='{$date->format('Hi')}' />";
								echo "<label for='train_time_{$date->format('Hi')}'>{$date->format('Hi')} - {$date->format('h:i A')}</label>";
								$date = $date->modify("+15 minutes");
							}	
						?>
						</div>
					</div>
					<div class='formsection'>
						<h3>#4 Day:</h3>
						<div id="train_days" class="train_days_count">
							<table>
								<tr>
									<td>
										Days:
									</td>
									<td>
										<input type="checkbox" id="train_days_su" 	name="train_days_su" />						<label for="train_days_su">su</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_m" 		name="train_days_m" 	checked="checked"/>	<label for="train_days_m">m</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_t" 		name="train_days_t" 	checked="checked"/>	<label for="train_days_t">t</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_w" 		name="train_days_w" 	checked="checked"/>	<label for="train_days_w">w</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_th" 	name="train_days_th" 	checked="checked"/>	<label for="train_days_th">th</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_f" 		name="train_days_f" 	checked="checked"/>	<label for="train_days_f">f</label>
									</td>
									<td>
										<input type="checkbox" id="train_days_sa" 	name="train_days_sa" />						<label for="train_days_sa">sa</label>
									</td>
								</tr>
								<tr>
									<td>
										Count:
									</td>
									<td>
										<span class="train_days_su">0</span>
									</td>
									<td>
										<span class="train_days_m">0</span>
									</td>
									<td>
										<span class="train_days_t">0</span>
									</td>
									<td>
										<span class="train_days_w">0</span>
									</td>
									<td>
										<span class="train_days_th">0</span>
									</td>
									<td>
										<span class="train_days_f">0</span>
									</td>
									<td>
										<span class="train_days_sa">0</span>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<div class='formsection'>
						<h3>#5 Nickname: (optional)</h3>
						<input type="text" id="train_nick" name="train_nick" size="40" />
						<label for="train_nick">Name your route.  Example: Work 1</label>
					</div>
					<div class='formsection'>
						<h3>Guests: (optional)</h3>
						<select id="train_guest" name="train_guest">
							<option>0</option>
							<option>1</option>
							<option>2</option>
							<option>3</option>
							<option>4</option>
							<option>5</option>
						</select>
						<label for="train_guest">Use this if you know others will be coming but they have not signed up</label>
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
								<input type='submit' value='save' />
							<?php
							
						}
						?>
					</div>
				</form>
			</div>
			
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
				
			<div>
				<a href="https://docs.google.com/forms/d/1hlYE0qoImoh8scdKU_alUK-47ppjisRKmZALE-YIsAo/viewform" >Contact Form</a>
			</div>
	</div>
	<div class='mapandtimediv'>
		<div class='timediv'>
			<div>
				<label for="time">Time:</label>
				<input type="text" id="time" style="border: 0; color: #f6931f; font-weight: bold;" />
				<input type='checkbox' name='chkAutoTime' id='chkAutoTime' /><label for="chkAutoTime">Keep Time</label>
			</div>
			
			<a href='' id="buttonSlideDown"  style="display:inline-block;width:5%">&lt;</a>
			<div id="slider-time" style="display:inline-block;width:80%"></div>
			<a href='' id="buttonSlideUp" style="display:inline-block;width:5%" >&gt;</a>
				
			<div id="directions_panel" style="margin:20px;background-color:#FFEE77;"></div>
			<div class='loadingmap'>loading map...</div>
		</div>
		<div id="map_canvas" ></div>
	</div>
  </body>
</html>
