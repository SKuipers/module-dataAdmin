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

@session_start() ;

//Module includes
require_once "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_history.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//New PDO DB connection
	$pdo = new Gibbon\sqlConnection();
	$connection2 = $pdo->getConnection();

	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Import History', 'Data Admin') . "</div>" ;
	print "</div>" ;

	print "<h3>" ;
	print __($guid, "Import History", 'Data Admin') ;
	print "</h3>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;

	// Get a list of available import options
	$importTypeList = DataAdmin\importType::loadImportTypeList($pdo, false);

	$sql="SELECT importLogID, surname, preferredName, type, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime FROM dataAdminImportLog as importLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID ORDER BY timestamp DESC" ;
	$result=$pdo->executeQuery(array(), $sql);

	if (empty($importTypeList) || $result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {

		print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 100px;'>" ;
					print __($guid, "Date") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "User") ;
				print "</th>" ;
				print "<th style='width: 80px;'>" ;
					print __($guid, "Category") ;
				print "</th>" ;
				print "<th >" ;
					print __($guid, "Import Type", 'Data Admin') ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Details") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;

		while ($row=$result->fetch()) {
			if (!isset($importTypeList[ $row['type'] ])) continue; // Skip invalid import types

			print "<tr class='".( $row['success'] == false? 'error' : '')."'>" ;
				$importType = $importTypeList[ $row['type'] ];

				print "<td>";
					printf("<span title='%s'>%s</span> ", $row['timestamp'], date('M j, Y', $row['unixtime']) );
				print "</td>";

				print "<td>";
					print $row['preferredName'].' '.$row['surname'];
				print "</td>";

				print "<td>" . $importType->getDetail('category'). "</td>" ;
				print "<td>" . $importType->getDetail('name'). "</td>" ;
				print "<td>" .( ($row['success'] == true)? 'Success' : 'Failed' ). "</td>";

				print "<td>";
					print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_history_view.php&importLogID=" . $row['importLogID'] . "&width=600&height=550'><img title='" . __($guid, 'View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
				print "</td>";

			print "</tr>" ;
		}
		print "</table>" ;

	}
	
}	
?>