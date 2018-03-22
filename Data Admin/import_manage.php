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

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_manage.php") == FALSE) {
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
	echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Import From File', 'Data Admin') . "</div>" ;
	echo "</div>" ;

	// Get a list of available import options
	$importTypeList = ImportType::loadImportTypeList($pdo, false);

	if (count($importTypeList)<1) {
		echo "<div class='error'>" ;
		echo __("There are no records to display.") ;
		echo "</div>" ;
	}
	else {

		$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

		$grouping = '';
		foreach ($importTypeList as $importTypeName => $importType) {

			if ($grouping != $importType->getDetail('grouping') ) {

				if ($grouping != '') echo "</table><br/>" ;

				$grouping = $importType->getDetail('grouping');

				echo "<tr class='break'>" ;
					echo "<td colspan='5'><h4>".$grouping."</h4></td>" ;
				echo "</tr>" ;

				echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

				echo "<tr class='head'>" ;
					echo "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
						echo __("Category") ;
					echo "</th>" ;
					echo "<th style='width: 23%;padding: 5px !important;'>" ;
						echo __("Name") ;
					echo "</th>" ;
					echo "<th style='width: 35%;padding: 5px !important;'>" ;
						echo __("Description") ;
					echo "</th>" ;
					echo "<th style='width: 15%;padding: 5px !important;'>" ;
						echo __("Last Run", 'Data Admin') ;
					echo "</th>" ;
					echo "<th style='width: 12%;padding: 5px !important;'>" ;
						echo __("Actions") ;
					echo "</th>" ;
				echo "</tr>" ;
			}

			echo "<tr>" ;
				echo "<td>" . $importType->getDetail('category'). "</td>" ;
				echo "<td>" . $importType->getDetail('name'). "</td>" ;
				echo "<td>" . $importType->getDetail('desc'). "</td>" ;
				echo "<td>";

					$data=array("type"=>$importTypeName);
					$sql="SELECT surname, preferredName, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime FROM dataAdminImportLog as importLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID && type=:type ORDER BY timestamp DESC LIMIT 1" ;
					$result=$pdo->executeQuery($data, $sql);

					if ($pdo->getSuccess() && $result->rowCount()>0) {
						$log = $result->fetch();
						printf("<span title='%s by %s %s'>%s</span> ", $log['timestamp'], $log['preferredName'], $log['surname'], date('M j, Y', $log['unixtime']) );
					}

				echo "</td>";
				echo "<td>";

					if ( $checkUserPermissions == 'Y' && $importType->isImportAccessible( $guid, $connection2 ) ) {
						echo "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=" . $importTypeName . "'><img title='" . __('Import', 'Data Admin') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/run.png'/></a> " ;
						echo "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=". $importTypeName. "&data=0'><img style='margin-left: 5px' title='" . __('Export Structure', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
					} else {
						echo "<img style='margin-left: 5px' title='" . __('You do not have access to this action.'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/>" ;
					}


				echo "</td>";
			echo "</tr>" ;
		}

		echo "</table><br/>" ;
	}

	// Info
	echo "<div class='message'>" ;
	echo __('This list is being added to with each version. New import types may be added by request, please post requests for new import types on the forum thread <a href="https://ask.gibbonedu.org/discussion/895/data-import-module">here</a>.', 'Data Admin');
	echo "</div>" ;

}
?>
