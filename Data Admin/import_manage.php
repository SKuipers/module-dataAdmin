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

use Gibbon\Data\ImportType;

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\System\LogGateway;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_manage.php") == false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $page->breadcrumbs->add(__('Import From File'));

    $logGateway = $container->get(LogGateway::class);
    $logsByType = $logGateway->selectLogsByModuleAndTitle('System Admin', 'Import - %')->fetchGrouped();

    $checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

    // Get a list of available import options
    $importTypeList = ImportType::loadImportTypeList($pdo, false);

    // Build an array of combined import type info and log data
    $importTypeGroups = array_reduce($importTypeList, function ($group, $importType) use ($checkUserPermissions, $guid, $connection2, $logsByType) {
        if ($importType->isValid()) {
            $type = $importType->getDetail('type');
            $log = $logsByType['Import - '.$type] ?? [];

            $group[$importType->getDetail('grouping', 'System')][] = [
                'type'         => $type,
                'log'          => current($log),
                'category'     => $importType->getDetail('category'),
                'name'         => $importType->getDetail('name'),
                'isAccessible' => $checkUserPermissions == 'Y'
                    ? $importType->isImportAccessible($guid, $connection2)
                    : true,
            ];
        }
        return $group;
    }, []);

    foreach ($importTypeGroups as $importGroupName => $importTypes) {
        $table = DataTable::create('imports');
        $table->setTitle(__($importGroupName));

        $table->addColumn('category', __('Category'))
            ->width('20%')
            ->format(function ($importType) {
                return __($importType['category']);
            });
        $table->addColumn('name', __('Name'))
            ->format(function ($importType) {
                $nameParts = array_map('trim', explode('-', $importType['name']));
                return implode(' - ', array_map('__', $nameParts));
            });
        $table->addColumn('timestamp', __('Last Import'))
            ->width('25%')
            ->format(function ($importType) use ($guid) {
                if ($log = $importType['log']) {
                    $text = Format::dateReadable($log['timestamp']);
                    $url = $_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Data Admin/import_history_view.php&gibbonLogID='.$log['gibbonLogID'].'&width=600&height=550';
                    $title = Format::dateTime($log['timestamp']).' - '.Format::nameList([$log]);
                    return Format::link($url, $text, ['title' => $title, 'class' => 'thickbox']);
                }
                return '';
            });

        $table->addActionColumn()
            ->addParam('type')
            ->format(function ($importType, $actions) {
                if ($importType['isAccessible']) {
                    $actions->addAction('import', __('Import'))
                        ->setIcon('run')
                        ->setURL('/modules/Data Admin/import_run.php');

                    $actions->addAction('export', __('Export Columns'))
                        ->directLink()
                        ->addParam('q', $_GET['q'])
                        ->addParam('data', 0)
                        ->setIcon('download')
                        ->setURL('/modules/Data Admin/export_run.php');
                }
            });

        echo $table->render(new DataSet($importTypes));
    }
}
