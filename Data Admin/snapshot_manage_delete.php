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

use Gibbon\Forms\Prefab\DeleteForm;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_delete.php")==FALSE) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __("You do not have access to this action.") ;
	echo "</div>" ;
}
else {
	//Proceed!
	echo "<div class='trail'>" ;
	echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/snapshot_manage.php'>" . __('Manage Snapshots', 'Data Admin') . "</a> > </div><div class='trailEnd'>" . __('Delete Snapshot', 'Data Admin') . "</div>" ;
	echo "</div>" ;
	

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Check if file exists
	$filename=(isset($_GET["file"]))? $_GET["file"] : '' ;
	
	if ($filename=="") {
		echo "<div class='error'>" ;
			echo __("You have not specified one or more required parameters.") ;
		echo "</div>" ;
	}
	else {

		$snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
		$snapshotFolder = '/'.trim($snapshotFolder, '/ ');

		$snapshotFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads'.$snapshotFolder;
		$filepath = $snapshotFolderPath.'/'.$filename;

		if ( !file_exists( $filepath ) ) {
			echo "<div class='error'>" ;
				echo __("The specified record cannot be found.") ;
			echo "</div>" ;
		} else {
            //Let's go!
            
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/snapshot_manage_deleteProcess.php?file='.$filename);
            echo $form->getOutput();
		}
	}
}
