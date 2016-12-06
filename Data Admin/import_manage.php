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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_manage.php") == FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Imports', 'Data Admin') . "</div>" ;
	print "</div>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;

	// Get a list of available import options
	$importTypeList = DataAdmin\importType::loadImportTypeList($pdo, false);

	if (count($importTypeList)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
		$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

		$grouping = '';
		foreach ($importTypeList as $importTypeName => $importType) {

			if ($grouping != $importType->getDetail('grouping') ) {

				if ($grouping != '') print "</table><br/>" ;

				$grouping = $importType->getDetail('grouping');

				print "<tr class='break'>" ;
					print "<td colspan='5'><h4>".$grouping."</h4></td>" ;
				print "</tr>" ;

				print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

				print "<tr class='head'>" ;
					print "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
						print __($guid, "Category") ;
					print "</th>" ;
					print "<th style='width: 23%;padding: 5px !important;'>" ;
						print __($guid, "Name") ;
					print "</th>" ;
					print "<th style='width: 35%;padding: 5px !important;'>" ;
						print __($guid, "Description") ;
					print "</th>" ;
					print "<th style='width: 15%;padding: 5px !important;'>" ;
						print __($guid, "Last Run", 'Data Admin') ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Actions") ;
					print "</th>" ;
				print "</tr>" ;
			}

			print "<tr>" ;
				print "<td>" . $importType->getDetail('category'). "</td>" ;
				print "<td>" . $importType->getDetail('name'). "</td>" ;
				print "<td>" . $importType->getDetail('desc'). "</td>" ;
				print "<td>";

					$data=array("type"=>$importTypeName); 
					$sql="SELECT surname, preferredName, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime FROM dataAdminImportLog as importLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID && type=:type ORDER BY timestamp DESC LIMIT 1" ;
					$result=$pdo->executeQuery($data, $sql);

					if ($pdo->getSuccess() && $result->rowCount()>0) {
						$log = $result->fetch();
						printf("<span title='%s by %s %s'>%s</span> ", $log['timestamp'], $log['preferredName'], $log['surname'], date('M j, Y', $log['unixtime']) );
					}

				print "</td>";
				print "<td>";

					if ( $checkUserPermissions == 'Y' && $importType->isImportAccessible( $guid, $connection2 ) ) {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=" . $importTypeName . "'><img title='" . __($guid, 'Import', 'Data Admin') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/run.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=". $importTypeName. "&data=0'><img style='margin-left: 5px' title='" . __($guid, 'Export Structure', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
					} else {
						print "<img style='margin-left: 5px' title='" . __($guid, 'You do not have access to this action.'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/>" ;
					}
		

				print "</td>";
			print "</tr>" ;
		}
		
		print "</table><br/>" ;
	}
	
}	
?>