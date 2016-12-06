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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_orphaned.php") == FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/records_manage.php'>" . __($guid, 'Manage Records', 'Data Admin') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Orphaned Records', 'Data Admin') . "</div>" ;
	print "</div>" ;

	// Info
	print "<div class='warning'>" ;
	print __($guid, 'Orphaned records are those where the link between this record and any related records on other tables has been broken. This can happen if other records are deleted or replaced without removing the linked records. At this time the orphaned records list is for informational purposes only. Tools to update or remove orphaned records will be added once the safest way to handle them has been determined.', 'Data Admin');
	print "</div>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/databaseTools.class.php" ;

	$databaseTools = new DataAdmin\databaseTools(null, $pdo);

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';

	$importType = DataAdmin\importType::loadImportType( $type, $pdo );

	$orphanedRecords = $databaseTools->getOrphanedRecords($importType);

	$primaryKey = $importType->getPrimaryKey();
	$relationships = array();

    // Get the relational fields
    foreach ($importType->getTableFields() as $fieldName) {
        if ($importType->isFieldrequired($fieldName) == false) continue; // Skip non-required fields for orphan checks

        if ($importType->isFieldRelational($fieldName) && !$importType->isFieldReadOnly($fieldName)) {
            $relationships[$fieldName] = $importType->getField($fieldName, 'relationship');
        }
    }

	if (count($orphanedRecords)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
		print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

			print "<tr class='head'>" ;
				print "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
					print $primaryKey;
				print "</th>" ;

				foreach ($relationships as $relationship) {
					print "<th style='width: 10%;padding: 5px !important;'>" ;
						print $relationship['key'];
					print "</th>" ;
				}

				print "<th style='width: 12%;padding: 5px !important;'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;

		$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');
		$isImportAccessible = ($checkUserPermissions == 'Y' && $importType->isImportAccessible( $guid, $connection2 ) != false);

		foreach ($orphanedRecords as $row) {

			//print_r($row);
			
			$importTypeName = $importType->getDetail('type');

			print "<tr>" ;
				print "<td>".$row[$primaryKey]. "</td>" ;

				foreach ($relationships as $relationship) {
					if (!empty($row[ $relationship['key'] ])) {
						print "<td>" .$row[ $relationship['key'] ]."</td>";
					} else {
						print "<td class='error'>" .__($guid, 'Missing', 'Data Admin')."</td>";
					}
				}
				
				print "<td>";
					if ( $isImportAccessible ) {

						

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