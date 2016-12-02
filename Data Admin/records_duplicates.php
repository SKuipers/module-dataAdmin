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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_duplicates.php") == FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/records_manage.php'>" . __($guid, 'Manage Records') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Duplicate Records') . "</div>" ;
	print "</div>" ;

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/databaseTools.class.php" ;

	$databaseTools = new DataAdmin\databaseTools(null, $pdo);

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';

	$importType = DataAdmin\importType::loadImportType( $type, $pdo );

	$duplicateRecords = $databaseTools->getDuplicateRecords($importType);

	$primaryKey = $importType->getPrimaryKey();
	$uniqueKeyList = $importType->getUniqueKeys();

    // Tables with no unique keys can't have duplicates
    if (empty($uniqueKeyList) || empty($uniqueKeyList[0]))  return '';

    // Currently only checks the first set of unique keys
    $uniqueKeys = (is_array($uniqueKeyList[0]) && count($uniqueKeyList[0]) > 0)? $uniqueKeyList[0] : array($uniqueKeyList[0]);

    //print_r($duplicateRecords);

	if (count($duplicateRecords)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		
		print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

			print "<tr class='head'>" ;

				print "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
					print __($guid, "Count") ;
				print "</th>" ;

				print "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
					print $primaryKey;
				print "</th>" ;

				foreach ($uniqueKeys as $uniqueKey) {
					print "<th style='width: 10%;padding: 5px !important;'>" ;
						print $uniqueKey;
					print "</th>" ;
				}

				print "<th style='width: 12%;padding: 5px !important;'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;


		foreach ($duplicateRecords as $row) {

			//print_r($row);
			
			$importTypeName = $importType->getDetail('type');
			$duplicates = explode(',', $row['list']);

			print "<tr>" ;

				print "<td>".count($duplicates). "</td>" ;
				
				print "<td>".implode('<br/>', $duplicates). "</td>" ;
				
				foreach ($uniqueKeys as $uniqueKey) {
					if (!empty($row[ $uniqueKey ])) {
						print "<td>" .$row[ $uniqueKey ]."</td>";
					} else {
						print "<td class='error'>" .__($guid, 'Missing')."</td>";
					}
				}
				
				print "<td>";
					if ( $importType->isImportAccessible( $guid, $connection2 ) ) {

						

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