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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Filesystem\FileHandler;

// Gibbon Bootstrap
include __DIR__ . '/../../gibbon.php';

// Module Bootstrap
require __DIR__ . '/module.php';

$filename = basename(str_replace('\\', '/', (string) ($_GET['file'] ?? '')));

$URL=$session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/snapshot_manage_delete.php&file=$filename" ;
$URLDelete=$session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/snapshot_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_delete.php")==false) {
    $URL.="&return=error0" ;
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if file exists
    $snapshotFolder = trim(str_replace('\\', '/', (string) $container->get(SettingGateway::class)->getSettingByScope('Data Admin', 'exportSnapshotsFolderLocation')), '/ ');
    $relativePath = 'uploads'.($snapshotFolder === '' ? '' : '/'.$snapshotFolder).'/'.$filename;
    $filepath = $container->get(FileHandler::class)->resolveStoredPath((string) $session->get('absolutePath'), $relativePath);

    if ($filepath === null) {
        $URL.="&return=error1" ;
        header("Location: {$URL}");
        exit;
    } else {
        unlink($filepath);
            
        $URLDelete=$URLDelete . "&return=success0" ;
        header("Location: {$URLDelete}");
        exit;
    }
}
