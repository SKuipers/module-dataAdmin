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
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/tools_fileExport.php") == false) {
   // Access denied
   $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__m('Export Files'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $exportPath = $session->get('absolutePath') .'/../archive/export/';
    if (is_dir($exportPath) === false) {
        mkdir($exportPath, 0755, true);
    }

    if (!empty($_GET['return'])) {
        echo Format::alert(__('Export completed: {photos} photos, {reports} reports, {docs} documents exported successfully.', [
            'photos'  => $_GET['photoCount'] ?? '0',
            'reports' => $_GET['reportCount'] ?? '0',
            'docs'    => $_GET['documentCount'] ?? '0',
        ]), 'message');
    } else {
        echo Format::alert(__m('This is an experimental tool to help export files from Gibbon for students. It currently supports exporting user photos, report PDFs and personal document files.').'<br><br>'.__m('Exported files will be saved to: {path}', ['path' => realpath($exportPath)]), 'message');
    }

    $form = Form::create('fileExport', $session->get('absoluteURL').'/modules/Data Admin/tools_fileExportProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $row = $form->addRow();
    $row->addLabel('gibbonSchoolYearID', __('School Year'));
    $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($gibbonSchoolYearID);

    $row = $form->addRow();
    $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
    $row->addCheckboxYearGroup('gibbonYearGroupIDList')->required();

    $row = $form->addRow();
    $row->addSubmit();
    
    echo $form->getOutput();
}
