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

$fieldName = (isset($_POST['fieldName']))? $_POST['fieldName'] : '';
$tableName = (isset($_POST['tableName']))? $_POST['tableName'] : '';
$mode = (isset($_POST['mode']))? $_POST['mode'] : '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Admin/duplication_combine.php&fieldName='.$fieldName.'&tableName='.$tableName.'&mode='.$mode;

if (isActionAccessible($guid, $connection2, '/modules/Data Admin/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $fail = false;

    $tableData = include __DIR__ . '/src/CombineableFields.php';

    $values = (isset($_POST['values']))? $_POST['values'] : '';
    $values = (!is_array($values))? array($values) : $values;
    $renameValue = (isset($_POST['renameValue']))? $_POST['renameValue'] : '';

    $fieldName = preg_replace('/[^a-zA-Z0-9_-]/', '', $fieldName);

    if (empty($fieldName) || empty($values) || empty($renameValue)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate against data set to prevent unwanted values in SQL query
    if (!array_key_exists($tableName, $tableData) || !array_key_exists($fieldName, $tableData[$tableName]['fields'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    try {
        $valueList = array();
        $data = array('renameValue' => $renameValue);

        for ($i = 0; $i < count($values); $i++) {
            $valueList[] = "`$fieldName` = :oldValue$i";
            $data["oldValue$i"] = $values[$i];
        }
  
        $sql = "UPDATE `$tableName` SET `$fieldName`=:renameValue WHERE ".implode(' OR ', $valueList);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    if ($fail == true) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    }
}
