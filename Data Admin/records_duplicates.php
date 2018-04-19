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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_duplicates.php") == FALSE) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __("You do not have access to this action.") ;
	echo "</div>" ;
}
else {
	//New PDO DB connection
	$pdo = new Gibbon\sqlConnection();
	$connection2 = $pdo->getConnection();

	echo "<div class='trail'>" ;
	echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/records_manage.php'>" . __('Manage Records', 'Data Admin') . "</a> > </div><div class='trailEnd'>" . __('Duplicate Records', 'Data Admin') . "</div>" ;
	echo "</div>" ;

	// Info
	echo "<div class='warning'>" ;
	echo __('Duplicate records can potentially arise from import errors. At this time the duplicate records list is for informational purposes only. Tools to update or remove duplicate records will be added once the safest way to handle them has been determined.', 'Data Admin');
	echo "</div>" ;

	$databaseTools = new DatabaseTools($gibbon->session, $pdo);

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';

	$importType = ImportType::loadImportType( $type, $pdo );

	$duplicateRecords = $databaseTools->getDuplicateRecords($importType);

	$primaryKey = $importType->getPrimaryKey();
	$uniqueKeyList = $importType->getUniqueKeys();

    // Tables with no unique keys can't have duplicates
    if (empty($uniqueKeyList) || empty($uniqueKeyList[0]))  return '';

    // Currently only checks the first set of unique keys
    $uniqueKeys = (is_array($uniqueKeyList[0]) && count($uniqueKeyList[0]) > 0)? $uniqueKeyList[0] : array($uniqueKeyList[0]);

    //print_r($duplicateRecords);

	if (count($duplicateRecords)<1) {
		echo "<div class='error'>" ;
		echo __("There are no records to display.") ;
		echo "</div>" ;
	}
	else {
		
		echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

			echo "<tr class='head'>" ;

				echo "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
					echo __("Count") ;
				echo "</th>" ;

				echo "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
					echo $primaryKey;
				echo "</th>" ;

				foreach ($uniqueKeys as $uniqueKey) {
					echo "<th style='width: 10%;padding: 5px !important;'>" ;
						echo $uniqueKey;
					echo "</th>" ;
				}

				echo "<th style='width: 12%;padding: 5px !important;'>" ;
					echo __("Actions") ;
				echo "</th>" ;
			echo "</tr>" ;

		$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');
		$isImportAccessible = ($checkUserPermissions == 'Y' && $importType->isImportAccessible( $guid, $connection2 ) != false);

		foreach ($duplicateRecords as $row) {

			//print_r($row);
			
			$importTypeName = $importType->getDetail('type');
			$duplicates = explode(',', $row['list']);

			echo "<tr>" ;

				echo "<td>".count($duplicates). "</td>" ;
				
				echo "<td>".implode('<br/>', $duplicates). "</td>" ;
				
				foreach ($uniqueKeys as $uniqueKey) {
					if (!empty($row[ $uniqueKey ])) {
						echo "<td>" .$row[ $uniqueKey ]."</td>";
					} else {
						echo "<td class='error'>" .__('Missing', 'Data Admin')."</td>";
					}
				}
				
				echo "<td>";
					if ( $isImportAccessible ) {

						

					} else {
						echo "<img style='margin-left: 5px' title='" . __('You do not have access to this action.'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/>" ;
					}
				echo "</td>";

			echo "</tr>" ;
		}
		
		echo "</table><br/>" ;
	}
	
}	
?>