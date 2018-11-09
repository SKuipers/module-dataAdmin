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
    $page->breadcrumbs->add(__('Combine Similar Fields', 'Data Admin'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<p>';
    echo __("With user-entered data it's common to end up with a variety of details that all mean the same thing or are spelled incorrectly. These discrepancies can have an effect on reports generated. Use the tool below to help find and combine fields with similar data.", 'Data Admin');
    echo '</p>';

    $tableName = (isset($_REQUEST['tableName']))? $_REQUEST['tableName'] : '';
    $fieldName = (isset($_REQUEST['fieldName']))? $_REQUEST['fieldName'] : '';
    $mode = (isset($_REQUEST['mode']))? $_REQUEST['mode'] : 'Assisted';

    $form = Form::create('combineFieldsFilder', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Admin/duplication_combine.php');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    
    $tableData = include __DIR__ . '/src/CombineableFields.php';

    // Build a set of options for the chained selects
    $tableOptions = array_combine(array_keys($tableData), array_column($tableData, 'label'));
    $fieldChained = array();
    $fieldOptions = array_reduce(array_keys($tableData), function($carry, $item) use (&$tableData, &$fieldChained) {
        if (empty($tableData[$item]['fields'])) return $carry;

        foreach ($tableData[$item]['fields'] as $fieldValue => $fieldName) {
            $carry[$fieldValue] = $fieldName;
            $fieldChained[$fieldValue] = $item;
        }
        return $carry;
    }, array() );

    $row = $form->addRow();
        $row->addLabel('tableName', __('Record Type'));
        $row->addSelect('tableName')->fromArray($tableOptions)->selected($tableName);

    $row = $form->addRow();
        $row->addLabel('fieldName', __('Field Name'));
        $row->addSelect('fieldName')
            ->isRequired()
            ->fromArray($fieldOptions)
            ->chainedTo('tableName', $fieldChained)
            ->selected($fieldName)
            ->placeholder();

    $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addRadio('mode')->fromArray(array('Assisted' => __('Assisted'), 'Manual' => __('Manual')))->checked($mode);
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();

    // Validate against data set to prevent unwanted values in SQL query
    if (!empty($fieldName) && array_key_exists($tableName, $tableData) && array_key_exists($fieldName, $tableData[$tableName]['fields'])) {

        if ($mode == 'Assisted') {
            $sql = "SELECT DISTINCT match1.`$fieldName` as matched, match2.`$fieldName` as value
            FROM `$tableName` as match1, `$tableName` as match2 
            WHERE match2.`$fieldName` LIKE CONCAT('%', match1.`$fieldName`, '%') 
            AND LENGTH(match1.`$fieldName`) < LENGTH(match2.`$fieldName`) 
            AND LENGTH(match1.`$fieldName`) > 2
            AND (match1.`$fieldName` <> match2.`$fieldName`)
            AND match1.`$fieldName` <> ''
            AND match2.`$fieldName` IS NOT NULL";
        } else {
            $sql = "SELECT `$fieldName` as value, count(*) as count FROM `$tableName` GROUP BY value ORDER BY value";
        }
        
        $result = $pdo->executeQuery(array(), $sql);

        if ($result->rowCount() > 0) {
            echo '<h3>';
            echo __('Results');
            echo '</h3>';

            if ($mode == 'Assisted') {
                echo '<p>';
                echo __('Assisted mode aims to help find matches between similar values, but can also result in false positives. Use manual mode to select and combine values listed alphabetically.');
                echo '</p>';
            }

            $form = Form::create('combineFields', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Data Admin/duplication_combineConfirm.php');
            // v15 only -- oops!
            //$form->getRenderer()->setWrapper('form', 'div');
            //$form->getRenderer()->setWrapper('row', 'div');
            //$form->getRenderer()->setWrapper('cell', 'div');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('tableName', $tableName);
            $form->addHiddenValue('fieldName', $fieldName);
            $form->addHiddenValue('mode', $mode);

            $row = $form->addRow()->setClass('right sticky');
            $column = $row->addColumn()->addClass('inline right');
                $column->addSelect('action')
                    ->fromArray(array('combine' => __('Combine Selected')))
                    ->selected($fieldName)
                    ->setClass('mediumWidth floatNone');
                $column->addSubmit(__('Go'));

            $table = $form->addRow()->addTable();
            $table->addClass('rowHighlight colorOddEven');

            $header = $table->addHeaderRow();
            $header->addContent($fieldOptions[$fieldName]);
            $header->addContent(__("Matches"));

            if ($mode == 'Assisted') {
                $fields = $result->fetchAll(\PDO::FETCH_GROUP);

                $count = 0;
                foreach ($fields as $fieldValue => $fieldMatches) {
                    $fieldMatches = array_column($fieldMatches, 'value');
                    array_unshift($fieldMatches, $fieldValue);

                    $row = $table->addRow();
                    $row->addContent($fieldValue);
                    $row->addCheckbox('values[]')->setID("valueSet$count")->fromArray($fieldMatches)->addClass('checkboxList floatNone');
                    $count++;
                }
            } else {
                $header->addContent('');

                while ($field = $result->fetch()) {
                    $row = $table->addRow();
                    $row->addContent($field['value']);
                    $row->addContent($field['count']);
                    $row->addCheckbox('values[]')->setClass()->setValue($field['value']);
                }
            }

            $row = $form->addRow()->setClass('right sticky');
            $column = $row->addColumn()->addClass('inline right');
                $column->addSelect('action')
                    ->fromArray(array('combine' => __('Combine Selected')))
                    ->selected($fieldName)
                    ->setClass('mediumWidth floatNone');
                $column->addSubmit(__('Go'));

            echo $form->getOutput();
        }
    }
}	
