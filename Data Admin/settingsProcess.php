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

// Gibbon Bootstrap
include __DIR__ . '/../../gibbon.php';

// Module Bootstrap
require __DIR__ . '/module.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/settings.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Admin/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $fail = false;

    $exportDefaultFileType = (isset($_POST['exportDefaultFileType'])) ? $_POST['exportDefaultFileType'] : null;
    try {
        $data = array('value' => $exportDefaultFileType);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Admin' AND name='exportDefaultFileType'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }
    
    $enableUserLevelPermissions = (isset($_POST['enableUserLevelPermissions'])) ? $_POST['enableUserLevelPermissions'] : null;
    try {
        $data = array('value' => $enableUserLevelPermissions);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Admin' AND name='enableUserLevelPermissions'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $importCustomFolderLocation = (isset($_POST['importCustomFolderLocation'])) ? $_POST['importCustomFolderLocation'] : null;
    try {
        $data = array('value' => $importCustomFolderLocation);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Admin' AND name='importCustomFolderLocation'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    $exportSnapshotsFolderLocation = (isset($_POST['exportSnapshotsFolderLocation'])) ? $_POST['exportSnapshotsFolderLocation'] : null;
    try {
        $data = array('value' => $exportSnapshotsFolderLocation);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Data Admin' AND name='exportSnapshotsFolderLocation'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    //RETURN RESULTS
    if ($fail == true) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        //Success 0
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
