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

if (isModuleAccessible($guid, $connection2)==FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Imports') . "</div>" ;
	print "</div>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/import.php" ;
	$importer = new Gibbon\ExtendedImporter( NULL, NULL, $pdo );

	//$importer->test();

	//$importer->createLog( $_SESSION[$guid]["gibbonPersonID"], 'usersBasic', true );

	// Get a list of available import options
	$importTypeList = $importer->getImportTypeList();

	if (count($importTypeList)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {

		print "<h3>" ;
		print __($guid, "Imports") ;
		print "</h3>" ;

		print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 80px;'>" ;
					print __($guid, "Category") ;
				print "</th>" ;
				print "<th >" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th >" ;
					print __($guid, "Description") ;
				print "</th>" ;
				print "<th style='width: 100px;'>" ;
					print __($guid, "Last Run") ;
				print "</th>" ;
				print "<th style='width: 80px!important'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;

		foreach ($importTypeList as $importTypeName => $importType) {
			print "<tr>" ;
				print "<td>" . $importType->getDetail('category'). "</td>" ;
				print "<td>" . $importType->getDetail('name'). "</td>" ;
				print "<td>" . $importType->getDetail('desc'). "</td>" ;
				print "<td>";

					$data=array("type"=>$importTypeName); 
					$sql="SELECT surname, preferredName, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime FROM importLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID && type=:type ORDER BY timestamp DESC LIMIT 1" ;
					$result=$pdo->executeQuery($data, $sql);

					if ($pdo->getSuccess() && $result->rowCount()>0) {
						$log = $result->fetch();
						printf("<span title='%s by %s %s'>%s</span> ", $log['timestamp'], $log['preferredName'], $log['surname'], date('M j, Y', $log['unixtime']) );
					}

				print "</td>";
				print "<td>";
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=" . $importTypeName . "'><img title='" . __($guid, 'Run') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/run.png'/></a> " ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/import_run_export.php?type=". $importTypeName. "&data=1'><img style='margin-left: 5px' title='" . __($guid, 'Export to Excel'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
		
				print "</td>";
			print "</tr>" ;
		}
		print "</table>" ;

	}
	
}	
?>