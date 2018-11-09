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

use Gibbon\Forms\Form;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_load.php") == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __("You do not have access to this action.");
    echo "</div>";
} else {
    $page->breadcrumbs
        ->add(__('Manage Snapshots', 'Data Admin'), 'snapshot_manage.php')
        ->add(__('Load Snapshot', 'Data Admin'));

    echo "<h3>";
    echo __("Load Snapshot", 'Data Admin');
    echo "</h3>";

    if (isset($_GET["return"])) {
        returnProcess($guid, $_GET["return"], null, null);
    }

    echo "<div class='warning'>";
    echo __('Loading a snapshot is a HIGHLY DESTRUCTIVE operation. It will overwrite all data in Gibbon. Do not proceed unless you are absolutly certain you know what you\'re doing.', 'Data Admin');
    echo "</div>";
    
    //Check if file exists
    $filename = (isset($_GET["file"])) ? $_GET["file"] : '';
    if ($filename == "") {
        echo "<div class='error'>";
        echo __("You have not specified one or more required parameters.");
        echo "</div>";
    } else {
        $snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
        $snapshotFolder = '/' . trim($snapshotFolder, '/ ');

        $snapshotFolderPath = $_SESSION[$guid]["absolutePath"] . '/uploads' . $snapshotFolder;
        $filepath = $snapshotFolderPath . '/' . $filename;

        if (!file_exists($filepath)) {
            echo "<div class='error'>";
            echo __("The specified record cannot be found.");
            echo "</div>";
        } else {
            //Let's go!

            $form = Form::create('deleteRecord', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/snapshot_manage_loadProcess.php?file='.$filename);
            $form->addHiddenValue('address', $_GET['q']);

            $col = $form->addRow()->addColumn();
                $col->addContent(__('Are you sure you want to load this snapshot? It will replace all data in Gibbon with the selected SQL file.', 'Data Admin'))->wrap('<strong>', '</strong>');
                $col->addContent(__('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'))
                    ->wrap('<span style="color: #cc0000"><i>', '</i></span>');

            $row = $form->addRow();
                $row->addLabel('confirm', sprintf(__('Type %1$s to confirm'), __('CONFIRM')));
                $row->addTextField('confirm')
                    ->isRequired()
                    ->addValidation(
                        'Validate.Inclusion',
                        'within: [\'' . __('CONFIRM') . '\'], failureMessage: "' . __('Please enter the text exactly as it is displayed to confirm this action.') . '", caseSensitive: false'
                    )
                    ->addValidationOption('onlyOnSubmit: true');

            $form->addRow()->addConfirmSubmit();


            echo $form->getOutput();
        }
    }
}
