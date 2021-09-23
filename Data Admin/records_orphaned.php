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

use Gibbon\Data\ImportType;
use Gibbon\Module\DataAdmin\DatabaseTools;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_orphaned.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs
        ->add(__('Manage Records', 'Data Admin'), 'records_manage.php')
        ->add(__('Orphaned Records', 'Data Admin'));
    
    // Info
    echo "<div class='warning'>" ;
    echo __('Orphaned records are those where the link between this record and any related records on other tables has been broken. This can happen if other records are deleted or replaced without removing the linked records. At this time the orphaned records list is for informational purposes only. Tools to update or remove orphaned records will be added once the safest way to handle them has been determined.', 'Data Admin');
    echo "</div>" ;

    $databaseTools = new DatabaseTools($session, $pdo);

    // Get the importType information
    $type = (isset($_GET['type']))? $_GET['type'] : '';

    $importType = ImportType::loadImportType($type, $pdo);

    $orphanedRecords = $databaseTools->getOrphanedRecords($importType);

    $primaryKey = $importType->getPrimaryKey();
    $relationships = array();

    // Get the relational fields
    foreach ($importType->getTableFields() as $fieldName) {
        if ($importType->isFieldrequired($fieldName) == false) {
            continue;
        } // Skip non-required fields for orphan checks

        if ($importType->isFieldRelational($fieldName) && !$importType->isFieldReadOnly($fieldName)) {
            $relationships[$fieldName] = $importType->getField($fieldName, 'relationship');
        }
    }

    if (count($orphanedRecords)<1) {
        echo "<div class='error'>" ;
        echo __("There are no records to display.") ;
        echo "</div>" ;
    } else {
        echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

        echo "<tr class='head'>" ;
        echo "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
        echo $primaryKey;
        echo "</th>" ;

        foreach ($relationships as $relationship) {
            echo "<th style='width: 10%;padding: 5px !important;'>" ;
            echo $relationship['key'];
            echo "</th>" ;
        }

        echo "<th style='width: 12%;padding: 5px !important;'>" ;
        echo __("Actions") ;
        echo "</th>" ;
        echo "</tr>" ;

        $checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');
        $isImportAccessible = ($checkUserPermissions == 'Y' && $importType->isImportAccessible($guid, $connection2) != false);

        foreach ($orphanedRecords as $row) {

            //print_r($row);
            
            $importTypeName = $importType->getDetail('type');

            echo "<tr>" ;
            echo "<td>".$row[$primaryKey]. "</td>" ;

            foreach ($relationships as $relationship) {
                if (!empty($row[ $relationship['key'] ])) {
                    echo "<td>" .$row[ $relationship['key'] ]."</td>";
                } else {
                    echo "<td class='error'>" .__('Missing', 'Data Admin')."</td>";
                }
            }
                
            echo "<td>";
            if ($isImportAccessible) {
            } else {
                echo "<img style='margin-left: 5px' title='" . __('You do not have access to this action.'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/>" ;
            }
            echo "</td>";

            echo "</tr>" ;
        }
        
        echo "</table><br/>" ;
    }
}
