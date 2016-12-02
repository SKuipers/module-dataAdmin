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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Records') . "</div>" ;
	print "</div>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/databaseTools.class.php" ;

	$databaseTools = new DataAdmin\databaseTools(null, $pdo);

	// Get a list of available import options
	$importTypeList = DataAdmin\importType::loadImportTypeList($pdo, false);

	// Get the unique tables used
	$importTables = array();
	foreach ($importTypeList as $importTypeName => $importType) {
		$table = $importType->getDetail('table');
		$modes = $importType->getDetail('modes');

		if ($modes['update'] == true && $modes['insert'] == true) {
			$importTables[$table] = $importType;
		}
	}

	if (count($importTypeList)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
			
		$module = '';
		foreach ($importTables as $importType) {

			if ($module != $importType->getAccessDetail('module') ) {

				if ($module != '') print "</table><br/>" ;

				$module = $importType->getAccessDetail('module');

				print "<tr class='break'>" ;
					print "<td colspan='5'><h4>".$module."</h4></td>" ;
				print "</tr>" ;

				print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

				print "<tr class='head'>" ;
					print "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
						print __($guid, "Category") ;
					print "</th>" ;
					print "<th style='width: 25%;padding: 5px !important;'>" ;
						print __($guid, "Table") ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Rows") ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Duplicates") ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Orphaned") ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Actions") ;
					print "</th>" ;
				print "</tr>" ;
			}

			$isImportAccessible = $importType->isImportAccessible( $guid, $connection2 );
			$importTypeName = $importType->getDetail('type');
			$recordCount = $databaseTools->getRecordCount($importType);
			$duplicateCount = $databaseTools->getDuplicateRecords($importType, true);
			$orphanCount = $databaseTools->getOrphanedRecords($importType, true);

			print "<tr>" ;
				print "<td>".$importType->getDetail('category'). "</td>" ;

				print "<td>".$importType->getDetail('table')."</td>" ;

				print "<td>".$recordCount."</td>";

				if ($isImportAccessible && $recordCount > 0 && $duplicateCount > 0 && $duplicateCount != '-') {
					print "<td><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/records_duplicates.php&type=" . $importTypeName . "'>";
						print $duplicateCount;
					print "</a></td>" ;
				} else {
					print "<td>".$duplicateCount."</td>";
				}

				if ($isImportAccessible && $recordCount > 0 && $orphanCount > 0 && $orphanCount != '-') {
					print "<td><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/records_orphaned.php&type=" . $importTypeName . "'>";
						print $orphanCount;
					print "</a></td>" ;
				} else {
					print "<td>".$orphanCount."</td>";
				}

				print "<td>";

					if ( $isImportAccessible ) {

						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=". $importTypeName. "&data=1&all=1'><img title='" . __($guid, 'Export Data (Beta)'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;

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