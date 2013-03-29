<?php
session_start();
header('Content-type: application/json');
$retArray;
try {
	if ($db = new Sqlite3('db/biketrain.SQLite')) {
		if (isset($_POST['gettable'])){
			
			$SQL = "SELECT * ";
			if (isset($_POST['addcount']))
				$SQL .= ", COUNT(*) as Count ";
			$SQL .= " FROM {$_POST['gettable']} ";
			
			if (isset($_POST['where'])) {
				$SQL .= " WHERE {$_POST['where']} ";
			}	
			$SQL = str_replace("\\'", "'", $SQL);
			//$retArray[] = $SQL;
			$Results = $db->query($SQL);
			if ($Results) {
				while ($Result = $Results->fetchArray()) {
					$retArray[] = $Result;
				}
			} 
			
		}
		else if (isset($_POST['getrouteinfo']) && isset($_POST['routeid'])) {
			$RoutesSQL = "SELECT *, COUNT(UserRoute.id) as UserRouteCount 
				FROM Route 
				LEFT JOIN UserRoute ON UserRoute.routeid = Route.id
				LEFT JOIN Waypoint ON Waypoint.routeid= Route.id
				WHERE  Route.id = '{$_POST['routeid']}'
				";
			$Routes = $db->query($RoutesSQL);
			$i = 0;
			while ($Route = $Routes->fetchArray()) {
				if (i == 0) {
					print_r($Route);
					echo "<hr/>id: {$Route[id]}";
					echo "<hr/>name: {$Route[name]}";
					echo "<hr/>desc: {$Route[description]}";
				}
				else {
					echo "<hr/>nick: {$Route[nickname]}";
					echo "<br/>time: {$Route[id]} on days: {$Route[days]}";
					echo "<br/>point: {$Route[point]}";
			
				}
				$i++;
			}

			$db->close();
		}
		$db->close();
		echo "" . json_encode($retArray) . "";
	}
} catch(ErrorException $e) {
	$retArray[] = $e;
	echo "" . json_encode($retArray) . "";
}

?>
