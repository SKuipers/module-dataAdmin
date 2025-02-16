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
use Gibbon\Domain\System\SettingGateway;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/settings.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('Data Admin Settings', 'Data Admin'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $trueIcon = "<img title='" . __('Yes'). "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconTick.png' width=16 height=16 />";
    $falseIcon = "<img title='" . __('No'). "' src='./themes/" . $session->get('gibbonThemeName') . "/img/iconCross.png' width=16 height=16 />";

    $settingGateway = $container->get(SettingGateway::class);

    // Include the module version info, with required versions
    include $session->get('absolutePath').'/modules/'.$session->get('module').'/version.php'; ?>

	<table class='smallIntBorder' cellspacing='0' style='width:60%;margin:0 auto;'>
		<tr class="break" style="line-height:20px;">
			<td>
				<?php echo __('Compatability Check', 'Data Admin'); ?>
			</td>
			<td class="right">
				<?php echo $session->get('module').' '.$moduleVersion; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('%s version %s or higher', 'Data Admin'), 'Gibbon', mb_strstr($coreVersion, '.', true)); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
                    echo '<span style="margin-right:20px;">Gibbon '.$version.'</span>';
    echo (version_compare($version, $coreVersion, '>='))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('%s version %s or higher', 'Data Admin'), 'PHP', $phpVersionRequired); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
                    $phpVersion = phpversion();

    echo '<span style="margin-right:20px;">PHP '.$phpVersion.'</span>';
    echo (version_compare($phpVersion, $phpVersionRequired, '>='))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('%s version %s or higher', 'Data Admin'), 'MySQL', $mysqlVersionRequired); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
                    $mysqlVersion = $pdo->executeQuery(array(), 'select version()')->fetchColumn();

    echo '<span style="margin-right:20px;">MySQL '.$mysqlVersion.'</span>';
    echo (version_compare($mysqlVersion, $mysqlVersionRequired, '>='))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>

		<tr>
			<td style="width: 275px">
				<b><?php printf(__('Extension %s enabled', 'Data Admin'), 'php_zip'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('zip'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('Extension %s enabled', 'Data Admin'), 'php_xml'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('xml'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('Extension %s enabled', 'Data Admin'), 'php_gd'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('gd'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('%s is writeable', 'Data Admin'), __('Custom Imports Folder', 'Data Admin')); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$importsFolder = $settingGateway->getSettingByScope('Data Admin', 'importCustomFolderLocation');
					$importsFolderPath = $session->get('absolutePath').'/uploads/'.trim($importsFolder, '/ ');

    echo (is_writable($importsFolderPath))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf(__('%s is writeable', 'Data Admin'), __('Snapshots Folder', 'Data Admin')); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$snapshotFolder = $settingGateway->getSettingByScope('Data Admin', 'exportSnapshotsFolderLocation');
					$snapshotFolderPath = $session->get('absolutePath').'/uploads/'.trim($snapshotFolder, '/ ');

    echo (is_writable($snapshotFolderPath))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>

    </table></br>
    
    <?php

    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/settingsProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $fileTypes = array(
        'Excel2007'    => __('Excel 2007 and above (.xlsx)', 'Data Admin'),
        'Excel5'       => __('Excel 95 and above (.xls)', 'Data Admin'),
        'OpenDocument' => __('OpenDocument (.ods)', 'Data Admin'),
        'CSV'          => __('Comma Separated (.csv)', 'Data Admin'),
    );
    $setting = $settingGateway->getSettingByScope('Data Admin', 'exportDefaultFileType', true);
    $row = $form->addRow();
    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
    $row->addSelect($setting['name'])->fromArray($fileTypes)->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Data Admin', 'exportSnapshotsFolderLocation', true);
    $row = $form->addRow();
    $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
    $row->addTextField($setting['name'])->required()->setValue($setting['value']);

    $row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

    echo $form->getOutput();
}
