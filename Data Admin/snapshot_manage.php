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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {

	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Snapshots', 'Data Admin') . "</div>" ;
	print "</div>" ;

	print "<h3>" ;
	print __($guid, "Manage Snapshots", 'Data Admin') ;
	print "</h3>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

	print "<div class='warning'>" ;
	print __($guid, 'Database snapshots allow you to save and restore your entire Gibbon database, which can be useful before importing data. They should NOT be used on live systems or when other users are online. Snapshots should NOT be used in place of standard backup procedures. A snapshot only saves MySQL data and does not save uploaded files or preserve any changes to the file system.', 'Data Admin');
	print "</div>" ;
	
	if ( isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_add.php") ) {
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] ."/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/snapshot_manage_add.php'>" .  __($guid, 'Create Snapshot', 'Data Admin') . "<img style='margin-left: 5px' title='" . __($guid, 'Create Snapshot', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
		print "</div>" ;
	}


	$snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
	$snapshotFolder = '/'.trim($snapshotFolder, '/ ');

	$snapshotFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads'.$snapshotFolder;

	if (is_dir($snapshotFolderPath)==FALSE) {
		mkdir($snapshotFolderPath, 0777, TRUE) ;
	}

	$snapshotList = glob( $snapshotFolderPath.'/*.sql.gz' );

	usort($snapshotList, function($a,$b){
	  return filemtime($b) - filemtime($a);
	});

	if (count($snapshotList)<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Date") ;
				print "</th>" ;
				print "<th style='width: 140px;'>" ;
					print __($guid, "Size") ;
				print "</th>" ;
				print "<th style='width: 80px!important'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;

		foreach ($snapshotList as $snapshotPath) {
			$snapshotFile = basename( $snapshotPath );
			print "<tr>" ;
				print "<td>". date("F j, Y, g:i a", filemtime($snapshotPath)). "</td>" ;
				print "<td>". readableFileSize( filesize($snapshotPath)) . "</td>" ;

				print "<td>";
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/snapshot_manage_load.php&file=". $snapshotFile. "'><img title='" . __($guid, 'Load') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/delivery2.png'/></a> " ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/snapshot_manage_delete.php&file=". $snapshotFile. "'><img style='margin-left: 5px' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
				print "</td>";
			print "</tr>" ;
		}
		print "</table>" ;

	}
}	
?>