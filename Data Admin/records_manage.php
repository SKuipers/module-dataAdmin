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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/records_manage.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('Manage Records', 'Data Admin'));
    
    // Info
    echo "<div class='message'>" ;
    echo __('The following Gibbon tables can be exported to Excel. The full table export is still a beta feature, at this time it should not be relied upon as a backup method. <strong>Note:</strong> This list does not represent the entire Gibbon database, only tables with an existing import/export structure.', 'Data Admin');
    echo "</div>" ;

    $databaseTools = new DatabaseTools($gibbon->session, $pdo);

    // Get a list of available import options
    $importTypeList = ImportType::loadImportTypeList($pdo, false);

    // Get the unique tables used
    $importTables = array();
    foreach ($importTypeList as $importTypeName => $importType) {
        $table = $importType->getDetail('table');
        $modes = $importType->getDetail('modes');

        if ((isset($modes['export']) && $modes['export'] == true) && $modes['update'] == true && $modes['insert'] == true) {
            $importTables[$table] = $importType;
        }
    }

    if (count($importTypeList)<1) {
        echo "<div class='error'>" ;
        echo __("There are no records to display.") ;
        echo "</div>" ;
    } else {
        $checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

        $grouping = '';
        foreach ($importTables as $importType) {
            if ($grouping != $importType->getDetail('grouping')) {
                if ($grouping != '') {
                    echo "</table><br/>" ;
                }

                $grouping = $importType->getDetail('grouping');

                echo "<tr class='break'>" ;
                echo "<td colspan='5'><h4>".$grouping."</h4></td>" ;
                echo "</tr>" ;

                echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;

                echo "<tr class='head'>" ;
                echo "<th style='width: 15%;padding: 5px 5px 5px 20px !important;'>" ;
                echo __("Category") ;
                echo "</th>" ;
                echo "<th style='width: 25%;padding: 5px !important;'>" ;
                echo __("Table", 'Data Admin') ;
                echo "</th>" ;
                echo "<th style='width: 12%;padding: 5px !important;'>" ;
                echo __("Total Rows", 'Data Admin') ;
                echo "</th>" ;
                // echo "<th style='width: 12%;padding: 5px !important;'>" ;
                // 	echo __("Current Year") ;
                // echo "</th>" ;
                echo "<th style='width: 12%;padding: 5px !important;'>" ;
                echo __("Duplicates", 'Data Admin') ;
                echo "</th>" ;
                echo "<th style='width: 12%;padding: 5px !important;'>" ;
                echo __("Orphaned", 'Data Admin') ;
                echo "</th>" ;
                echo "<th style='width: 8%;padding: 5px !important;'>" ;
                echo __("Actions") ;
                echo "</th>" ;
                echo "</tr>" ;
            }

            $isImportAccessible = ($checkUserPermissions == 'Y' && $importType->isImportAccessible($guid, $connection2) != false);
            $importTypeName = $importType->getDetail('type');
            $recordCount = $databaseTools->getRecordCount($importType);
            //$recordYearCount = $databaseTools->getRecordCount($importType, true);
            $duplicateCount = $databaseTools->getDuplicateRecords($importType, true);
            $orphanCount = $databaseTools->getOrphanedRecords($importType, true);

            echo "<tr>" ;
            echo "<td>".$importType->getDetail('category'). "</td>" ;

            echo "<td>".$importType->getDetail('table')."</td>" ;

            echo "<td>".$recordCount."</td>";

            //echo "<td>".$recordYearCount."</td>";

            if ($isImportAccessible && $recordCount > 0 && $duplicateCount > 0 && $duplicateCount != '-') {
                echo "<td><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/records_duplicates.php&type=" . $importTypeName . "'>";
                echo $duplicateCount;
                echo "</a></td>" ;
            } else {
                echo "<td>".$duplicateCount."</td>";
            }

            if ($isImportAccessible && $recordCount > 0 && $orphanCount > 0 && $orphanCount != '-') {
                echo "<td><a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/records_orphaned.php&type=" . $importTypeName . "'>";
                echo $orphanCount;
                echo "</a></td>" ;
            } else {
                echo "<td>".$orphanCount."</td>";
            }

            echo "<td>";

            if ($isImportAccessible) {
                echo "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=". $importTypeName. "&data=1&all=1'><img title='" . __('Export Data (Beta)', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
            } else {
                echo "<img style='margin-left: 5px' title='" . __('You do not have access to this action.'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/>" ;
            }
        

            echo "</td>";
            echo "</tr>" ;
        }
        
        echo "</table><br/>" ;
    }
}
