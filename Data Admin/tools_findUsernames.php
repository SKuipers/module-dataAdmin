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
use Gibbon\Forms\DatabaseFormFactory;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/tools_findUsernames.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('Find Usernames', 'Data Admin'));

    if (isset($_GET['return'])) {
        $page->return->addReturn('error4', __('Import cannot proceed, the file type cannot be read.'));
    }

    echo '<p>';
    echo __("Sometimes you'll be working with an import that requires usernames and all you have is the written names. This tool can help you find the best match for each username from a given spreadsheet of names.", 'Data Admin');
    echo '</p>';

    echo '<p>';
    echo __("The following steps are taken to ensure an accurate match:", 'Data Admin');
    echo '</p>';
    echo '<ol>';
    echo '<li>'.__('The name provided must match one any <b>only one</b> user of the same role category.').'</li>';
    echo '<li>'.__('Matches will be found using Preferred Name + Surname as well as First Name + Surname.').'</li>';
    echo '<li>'.__('No username will be returned if there are no matches, or if too many users match the same name.').'</li>';
    echo '<li>'.__('Only users with a status of Full or Expected will be matched.').'</li>';
    echo '<li>'.__('For students: the matching student must also be in the target year group from the column provided.').'</li>';
    echo '</ol><br/>';
    echo '<p>';
    echo __("If successful, this tool will return a new spreadsheet with an added column of usernames.", 'Data Admin');
    echo '</p>';

    $columns = array_reduce(range(0, 25), function ($group, $index) {
        $group[str_pad($index, 2, '0', STR_PAD_LEFT)] = chr($index % 26 + 0x41);
        return $group;
    }, array());

    $form = Form::create('findUsernames', $session->get('absoluteURL').'/modules/Data Admin/tools_findUsernamesProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $row = $form->addRow();
    $row->addLabel('gibbonSchoolYearID', __('School Year'));
    $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
    $row->addLabel('file', __('Spreadsheet'));
    $row->addFileUpload('file')->required()->accepts('.csv,.xls,.xlsx,.xml,.ods');

    $sql = "SELECT DISTINCT category AS value, category AS name FROM gibbonRole ORDER BY category";
    $row = $form->addRow();
    $row->addLabel('roleCategory', __('Role Category'));
    $row->addSelect('roleCategory')->fromQuery($pdo, $sql)->required()->placeholder();

    // COLUMN OPTIONS
    $form->toggleVisibilityByClass('columnTypeOptions')->onSelect('roleCategory')->whenNot('Please select...');
    $columnTypes = array(
        'one' => __('One column of names'),
        'multi' => __('More than one column'),
    );
    $row = $form->addRow()->addClass('columnTypeOptions');
    $row->addLabel('columnType', __('Columns'))->description(__('Are the first and surnames separated into columns, or all in one column?'));
    $row->addSelect('columnType')->fromArray($columnTypes)->required()->placeholder();

    $form->toggleVisibilityByClass('oneColumnOptions')->onSelect('columnType')->when('one');
    $form->toggleVisibilityByClass('multiColumnOptions')->onSelect('columnType')->when('multi');

    // ONE COLUMN
    $formats = array(
        'firstLast' => __('Name Surname'),
        'lastFirst' => __('Surname, Name'),
        'lastFirstAlt' => __('Surname, Name (Other Name)'),
    );
    $row = $form->addRow()->addClass('oneColumnOptions');
    $row->addLabel('nameFormat', __('Name Format'))->description(__('What format are the names currently in?'));
    $row->addSelect('nameFormat')->fromArray($formats)->required()->placeholder();

    $row = $form->addRow()->addClass('oneColumnOptions');
    $row->addLabel('nameColumn', __('Name Column'))->description(__('What column are the names in?'));
    $row->addSelect('nameColumn')->fromArray($columns)->required()->placeholder();

    // MULTIPLE COLUMNS
    $row = $form->addRow()->addClass('multiColumnOptions');
    $row->addLabel('nameColumn', __('Preferred Name Column'));
    $row->addSelect('nameColumn')->fromArray($columns)->required()->placeholder();

    $row = $form->addRow()->addClass('multiColumnOptions');
    $row->addLabel('firstNameColumn', __('First Name Column'))->description(__('Can be the same as preferred.'));
    $row->addSelect('firstNameColumn')->fromArray($columns)->required()->placeholder();

    $row = $form->addRow()->addClass('multiColumnOptions');
    $row->addLabel('surnameColumn', __('Surname Column'));
    $row->addSelect('surnameColumn')->fromArray($columns)->required()->placeholder();

    // STUDENT YEAR GROUP
    $form->toggleVisibilityByClass('yearGroupOptions')->onSelect('roleCategory')->when('Student');

    $row = $form->addRow()->addClass('yearGroupOptions');
    $row->addLabel('yearGroupColumn', __('Year Group Column'))->description(__('Only students with the same name AND same year group will be matched.'));
    $row->addSelect('yearGroupColumn')->fromArray($columns)->required()->placeholder();

    $row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

    echo $form->getOutput();
}
