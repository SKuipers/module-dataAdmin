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

use Modules\DataAdmin\ImportType;
use Modules\DataAdmin\DatabaseTools;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_manage.php") == FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Records', 'Data Admin') . "</div>" ;
	print "</div>" ;

	// Info
	print "<div class='message'>" ;
	print __($guid, 'The following Gibbon tables can be exported to Excel. The full table export is still a beta feature, at this time it should not be relied upon as a backup method. <strong>Note:</strong> This list does not represent the entire Gibbon database, only tables with an existing import/export structure.', 'Data Admin');
	print "</div>" ;

	$databaseTools = new DatabaseTools(null, $pdo);

	// Get a list of available import options
	$importTypeList = ImportType::loadImportTypeList($pdo, false);

	// Get the unique tables used
	$importTables = array();
	foreach ($importTypeList as $importTypeName => $importType) {
		$table = $importType->getDetail('table');
		$modes = $importType->getDetail('modes');

		if ( (isset($modes['export']) && $modes['export'] == true) && $modes['update'] == true && $modes['insert'] == true) {
			$importTables[$table] = $importType;
		}
	}

	if (count($importTypeList)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
		$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

		$grouping = '';
		foreach ($importTables as $importType) {

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
					print "<th style='width: 25%;padding: 5px !important;'>" ;
						print __($guid, "Table", 'Data Admin') ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Total Rows", 'Data Admin') ;
					print "</th>" ;
					// print "<th style='width: 12%;padding: 5px !important;'>" ;
					// 	print __($guid, "Current Year") ;
					// print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Duplicates", 'Data Admin') ;
					print "</th>" ;
					print "<th style='width: 12%;padding: 5px !important;'>" ;
						print __($guid, "Orphaned", 'Data Admin') ;
					print "</th>" ;
					print "<th style='width: 8%;padding: 5px !important;'>" ;
						print __($guid, "Actions") ;
					print "</th>" ;
				print "</tr>" ;
			}

			$isImportAccessible = ($checkUserPermissions == 'Y' && $importType->isImportAccessible( $guid, $connection2 ) != false);
			$importTypeName = $importType->getDetail('type');
			$recordCount = $databaseTools->getRecordCount($importType);
			//$recordYearCount = $databaseTools->getRecordCount($importType, true);
			$duplicateCount = $databaseTools->getDuplicateRecords($importType, true);
			$orphanCount = $databaseTools->getOrphanedRecords($importType, true);

			print "<tr>" ;
				print "<td>".$importType->getDetail('category'). "</td>" ;

				print "<td>".$importType->getDetail('table')."</td>" ;

				print "<td>".$recordCount."</td>";

				//print "<td>".$recordYearCount."</td>";

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

						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=". $importTypeName. "&data=1&all=1'><img title='" . __($guid, 'Export Data (Beta)', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;

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