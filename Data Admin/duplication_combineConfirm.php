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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/duplication_combine.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs
        ->add(__('Combine Similar Fields', 'Data Admin'), 'duplication_combine.php')
        ->add(__('Confirm'));

    $tableName = $_POST['tableName'] ?? '';
    $fieldName = $_POST['fieldName'] ?? '';
    $mode = $_POST['mode'] ?? '';
    $values = $_POST['values'] ?? '';

    if (empty($tableName) || empty($fieldName) || empty($values)) {
        echo '<div class="error">';
        echo __('Your request failed because your inputs were invalid.') ;
        echo '</div>';
    } else {
        $form = Form::create('combineFieldsConfirm', $session->get('absoluteURL').'/modules/Data Admin/duplication_combineProcess.php');
        $form->setClass('smallIntBorder fullWidth');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('tableName', $tableName);
        $form->addHiddenValue('fieldName', $fieldName);
        $form->addHiddenValue('mode', $mode);
        foreach ($values as $value) {
            $form->addHiddenValue('values[]', htmlprep($value));
        }

        $row = $form->addRow()->addClass('right');
        $row->addLabel('combine', __('Combine'));
        $column = $row->addColumn()->addClass('standardWidth');

        for ($i = 0; $i < count($values); $i++) {
            $column->addTextField('label'.$i)->readonly()->setValue(htmlprep($values[$i]));
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
