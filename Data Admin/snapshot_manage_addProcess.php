<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/snapshot_manage_add.php" ;
$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/snapshot_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if file exists
	$filename = "snapshot-" . date("d-m-Y-Hi") . ".sql.gz";
	$filepath = $_SESSION[$guid]["absolutePath"] . "/uploads/snapshots/" . $filename;

	if (file_exists($filepath)) {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {

		if ( file_exists($_SESSION[$guid]["absolutePath"] . "config.php"))
			include $_SESSION[$guid]["absolutePath"].'config.php';

		$host = $databaseServer ;
		$user = $databaseUsername ;
		$pass = $databasePassword ;
		$db = $databaseName ;

		if (empty($databaseServer) || empty($databaseUsername) || empty($databasePassword) || empty($databaseName)) {
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		} else {

			try {
				set_time_limit(600);
				//Check for MAMP, because mysqldump is in a weird spot
				if ( stripos($_ENV["_"], 'MAMP') !== false ) {
					$command = "/Applications/MAMP/Library/bin/mysqldump --opt --user=$databaseUsername --password='$databasePassword' --host=$databaseServer $databaseName > $filepath";
				} else {
					$command = "mysqldump --opt --user=$databaseUsername --password='$databasePassword' --host=$databaseServer $databaseName > $filepath";
				}

				exec($command, $output, $return);

			} catch (Exception $e) {
				$URL.="&return=error1" ;
				header("Location: {$URL}");
				return;
			}

			// Error # returned by mysqldump
			if ($return != 0) {
				$URL.="&return=error1" ;
				header("Location: {$URL}");
			} else {
				$URLDelete=$URLDelete . "&return=success0" ;
				header("Location: {$URLDelete}");
			}
			
		}
		
	}
}
?>