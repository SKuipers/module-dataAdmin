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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_add.php")==FALSE) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __("You do not have access to this action.") ;
	echo "</div>" ;
}
else {
    $page->breadcrumbs
        ->add(__('Manage Snapshots', 'Data Admin'), 'snapshot_manage.php')
        ->add(__('Create Snapshot', 'Data Admin'));

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

	echo "<div class='warning'>" ;
	echo __('Database snapshots allow you to save and restore your entire Gibbon database, which can be useful before importing data. They should NOT be used on live systems or when other users are online. Snapshots should NOT be used in place of standard backup procedures. A snapshot only saves MySQL data and does not save uploaded files or preserve any changes to the file system.', 'Data Admin');
	echo "</div>" ;

	echo "<div class='warning'>" ;
	echo __('Database files can be quite large, do not refresh the page after pressing submit. Also, this may fail if PHP does not have access to execute system commands.', 'Data Admin');
    echo "</div>" ;
    
    $form = Form::create('deleteRecord', $session->get('absoluteURL').'/modules/'.$session->get('module').'/snapshot_manage_addProcess.php');
    $form->addHiddenValue('address', $_GET['q']);

    $row = $form->addRow();
        $row->addSubmit(__('Create Snapshot', 'Data Admin'));

    echo $form->getOutput();
}
