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

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage.php")==false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('Manage Snapshots', 'Data Admin'));

    echo "<h3>" ;
    echo __("Manage Snapshots", 'Data Admin') ;
    echo "</h3>" ;

    if (isset($_GET["return"])) {
        returnProcess($guid, $_GET["return"], null, null);
    }

    echo "<div class='warning'>" ;
    echo __('Database snapshots allow you to save and restore your entire Gibbon database, which can be useful before importing data. They should NOT be used on live systems or when other users are online. Snapshots should NOT be used in place of standard backup procedures. A snapshot only saves MySQL data and does not save uploaded files or preserve any changes to the file system.', 'Data Admin');
    echo "</div>" ;
    
    if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_add.php")) {
        echo "<div class='linkTop'>" ;
        echo "<a href='" . $session->get('absoluteURL') ."/index.php?q=/modules/" . $session->get('module') . "/snapshot_manage_add.php'>" .  __('Create Snapshot', 'Data Admin') . "<img style='margin-left: 5px' title='" . __('Create Snapshot', 'Data Admin'). "' src='./themes/" . $session->get('gibbonThemeName') . "/img/page_new.png'/></a>" ;
        echo "</div>" ;
    }


    $snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
    $snapshotFolder = '/'.trim($snapshotFolder, '/ ');

    $snapshotFolderPath = $session->get('absolutePath').'/uploads'.$snapshotFolder;

    if (is_dir($snapshotFolderPath)==false) {
        mkdir($snapshotFolderPath, 0755, true) ;
    }

    $snapshotList = glob($snapshotFolderPath.'/*.sql.gz');

    usort($snapshotList, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    if (count($snapshotList)<1) {
        echo "<div class='error'>" ;
        echo __("There are no records to display.") ;
        echo "</div>" ;
    } else {
        echo "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
        echo "<tr class='head'>" ;
        echo "<th>" ;
        echo __("Date") ;
        echo "</th>" ;
        echo "<th style='width: 140px;'>" ;
        echo __("Size") ;
        echo "</th>" ;
        echo "<th style='width: 80px!important'>" ;
        echo __("Actions") ;
        echo "</th>" ;
        echo "</tr>" ;

        foreach ($snapshotList as $snapshotPath) {
            $snapshotFile = basename($snapshotPath);
            echo "<tr>" ;
            echo "<td>". date("F j, Y, g:i a", filemtime($snapshotPath)). "</td>" ;
            echo "<td>". readableFileSize(filesize($snapshotPath)) . "</td>" ;

            echo "<td>";
            echo "<a href='" . $session->get('absoluteURL') . "/index.php?q=/modules/" . $session->get('module') . "/snapshot_manage_load.php&file=". $snapshotFile. "'><img title='" . __('Load') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/delivery2.png'/></a> " ;
            echo "<a class='thickbox' href='" . $session->get('absoluteURL') . "/fullscreen.php?q=/modules/" . $session->get('module') . "/snapshot_manage_delete.php&file=". $snapshotFile. "&width=650&height=135'><img style='margin-left: 5px' title='" . __('Delete') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/garbage.png'/></a> " ;
            echo "</td>";
            echo "</tr>" ;
        }
        echo "</table>" ;
    }
}
