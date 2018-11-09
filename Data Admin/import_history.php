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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_history.php")==false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('View Import History', 'Data Admin'));

    echo "<h3>" ;
    echo __("Import History", 'Data Admin') ;
    echo "</h3>" ;

    // Get a list of available import options
    $importTypeList = ImportType::loadImportTypeList($pdo, false);

    $sql="SELECT importLogID, surname, preferredName, type, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime FROM dataAdminImportLog as importLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID ORDER BY timestamp DESC" ;
    $result=$pdo->executeQuery(array(), $sql);

    if (empty($importTypeList) || $result->rowCount()<1) {
        echo "<div class='error'>" ;
        echo __("There are no records to display.") ;
        echo "</div>" ;
    } else {
        echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
        echo "<tr class='head'>" ;
        echo "<th style='width: 100px;'>" ;
        echo __("Date") ;
        echo "</th>" ;
        echo "<th>" ;
        echo __("User") ;
        echo "</th>" ;
        echo "<th style='width: 80px;'>" ;
        echo __("Category") ;
        echo "</th>" ;
        echo "<th >" ;
        echo __("Import Type", 'Data Admin') ;
        echo "</th>" ;
        echo "<th>" ;
        echo __("Details") ;
        echo "</th>" ;
        echo "<th>" ;
        echo __("Actions") ;
        echo "</th>" ;
        echo "</tr>" ;

        while ($row=$result->fetch()) {
            if (!isset($importTypeList[ $row['type'] ])) {
                continue;
            } // Skip invalid import types

            echo "<tr class='".($row['success'] == false? 'error' : '')."'>" ;
            $importType = $importTypeList[ $row['type'] ];

            echo "<td>";
            printf("<span title='%s'>%s</span> ", $row['timestamp'], date('M j, Y', $row['unixtime']));
            echo "</td>";

            echo "<td>";
            echo $row['preferredName'].' '.$row['surname'];
            echo "</td>";

            echo "<td>" . $importType->getDetail('category'). "</td>" ;
            echo "<td>" . $importType->getDetail('name'). "</td>" ;
            echo "<td>" .(($row['success'] == true)? 'Success' : 'Failed'). "</td>";

            echo "<td>";
            echo "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_history_view.php&importLogID=" . $row['importLogID'] . "&width=600&height=550'><img title='" . __('View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
            echo "</td>";

            echo "</tr>" ;
        }
        echo "</table>" ;
    }
}
