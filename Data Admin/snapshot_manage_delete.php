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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_delete.php")==false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    //Proceed!
    if (isset($_GET["return"])) {
        returnProcess($guid, $_GET["return"], null, null);
    }
    
    //Check if file exists
    $filename=(isset($_GET["file"]))? $_GET["file"] : '' ;
    
    if ($filename=="") {
        echo "<div class='error'>" ;
        echo __("You have not specified one or more required parameters.") ;
        echo "</div>" ;
    } else {
        $snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
        $snapshotFolder = '/'.trim($snapshotFolder, '/ ');

        $snapshotFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads'.$snapshotFolder;
        $filepath = $snapshotFolderPath.'/'.$filename;

        if (!file_exists($filepath)) {
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
