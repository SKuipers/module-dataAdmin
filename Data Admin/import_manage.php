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

use Gibbon\Module\DataAdmin\ImportType;

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
    $page->breadcrumbs->add(__('Import From File', 'Data Admin'));

    $logGateway = $container->get(LogGateway::class);
    $logsByType = $logGateway->selectLogsByModuleAndTitle('System Admin', 'Import - %')->fetchGrouped();

    $checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

    // Get a list of available import options
    $importTypeList = ImportType::loadImportTypeList($pdo, false);

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
        $table = DataTable::create('rollGroups');
        $table->setTitle(__($importGroupName));

        $table->addColumn('category', __('Category'))->width('20%');
        $table->addColumn('name', __('Name'));
        $table->addColumn('lastRun', __('Last Run'))
            ->width('25%')
            ->format(function ($importType) {
                if ($log = $importType['log']) {
                    return '<span title="'.Format::dateTime($log['timestamp']).' - '.Format::nameList([$log]).'">'.Format::dateReadable($log['timestamp']).'</span>';
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

                    $actions->addAction('export', __('Export Structure'))
                        ->isDirect()
                        ->addParam('q', $_GET['q'])
                        ->addParam('data', 0)
                        ->setIcon('download')
                        ->setURL('/modules/Data Admin/export_run.php');
                }
            });

        echo $table->render(new DataSet($importTypes));
    }
}
