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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/duplication_combine.php") == FALSE) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Combine Similar Fields', 'Data Admin') . "</div>" ;
    echo "</div>" ;

    $tableName = (isset($_POST['tableName']))? $_POST['tableName'] : '';
    $fieldName = (isset($_POST['fieldName']))? $_POST['fieldName'] : '';
    $mode = (isset($_POST['mode']))? $_POST['mode'] : '';
    $values = (isset($_POST['values']))? $_POST['values'] : '';
    
    if (empty($tableName) || empty($fieldName) || empty($values)) {
        echo '<div class="error">';
        echo __('Your request failed because your inputs were invalid.') ;
        echo '</div>';
    } else {
        $form = Form::create('combineFieldsConfirm', $_SESSION[$guid]['absoluteURL'].'/modules/Data Admin/duplication_combineProcess.php');
        $form->setClass('smallIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('tableName', $tableName);
        $form->addHiddenValue('fieldName', $fieldName);
        $form->addHiddenValue('mode', $mode);
        foreach ($values as $value) {
            $form->addHiddenValue('values[]', htmlprep($value));
        }

        $row = $form->addRow()->addClass('right');
        $row->addLabel('combine', __('Combine'));
        $column = $row->addColumn()->addClass('standardWidth');
        foreach ($values as $value) {
            $column->addContent(htmlprep($value));
        }
    
        $row = $form->addRow();
        $row->addLabel('renameValue', __('Rename to'));
        $row->addTextField('renameValue')->setValue(current($values));
    
        $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
        echo $form->getOutput();
    }
}	
