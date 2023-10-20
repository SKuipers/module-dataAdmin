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

use Gibbon\Services\Format;
use Gibbon\Data\ImportType;
use Gibbon\Domain\System\SettingGateway;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Gibbon\Data\PasswordPolicy;

// Increase max execution time, as this stuff gets big
ini_set('max_execution_time', 7200);
ini_set('memory_limit','1024M');
set_time_limit(1200);

$_POST['address'] = '/modules/Data Admin/export_run.php';

// Gibbon Bootstrap
include __DIR__ . '/../../gibbon.php';

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/export_run.php")==false) {
    // Acess denied
    echo '<div class="error">';
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $dataExport = (isset($_GET['data']) && $_GET['data'] == true);
    $dataExportAll = (isset($_GET['all']) && $_GET['all'] == true);

    $settingGateway = $container->get(SettingGateway::class);
    $passwordPolicy = $container->get(PasswordPolicy::class);

    // Get the importType information
    $type = (isset($_GET['type']))? $_GET['type'] : '';
    $importType = ImportType::loadImportType($type, $settingGateway, $passwordPolicy, $pdo);

    if ($importType->isImportAccessible($guid, $connection2) == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } elseif (empty($importType) || !$importType->isValid()) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $checkUserPermissions = $settingGateway->getSettingByScope('Data Admin', 'enableUserLevelPermissions');


    if ($checkUserPermissions == 'Y' && $importType->isImportAccessible($guid, $connection2) == false) {
        echo "<div class='error'>" ;
        echo __('You do not have access to this action.') ;
        echo "</div>" ;
        return;
    } elseif (empty($importType)) {
        echo "<div class='error'>" ;
        echo __('Your request failed because your inputs were invalid.') ;
        echo "</div>" ;
        return;
    } elseif (!$importType->isValid()) {
        echo "<div class='error'>";
        echo __('There was an error reading the file {value}.', ['value' => $type]);
        echo "<br/></div>";
        return;
    }
    $excel = new Spreadsheet();

    //Create border styles
    $style_head_fill= array(
        'fill' => array('fillType' => Fill::FILL_SOLID, 'color' => array('rgb' => 'eeeeee')),
        'borders' => array('top' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '444444'), ), 'bottom' => array('borderStyle' => Border::BORDER_THIN, 'color' => array('argb' => '444444'), )),
    );

    // Set document properties
    $excel->getProperties()->setCreator(Format::name("", $session->get("preferredName"), $session->get("surname"), "Staff"))
         ->setLastModifiedBy(Format::name("", $session->get("preferredName"), $session->get("surname"), "Staff"))
         ->setTitle($importType->getDetail('name'))
         ->setDescription(__('This information is confidential. Generated by Gibbon (https://gibbonedu.org).')) ;

    $excel->setActiveSheetIndex(0) ;

    $count = 0;

    $rowData = [];
    $queryFields = [];
    $columnFields = $importType->getAllFields();

    $columnFields = array_values(array_filter($columnFields, function ($fieldName) use ($importType) {
        return !$importType->isFieldHidden($fieldName);
    }));

    // Create the header row
    foreach ($columnFields as $fieldName) {
        $excel->getActiveSheet()->setCellValue(num2alpha($count).'1', $importType->getField($fieldName, 'name', $fieldName));
        $excel->getActiveSheet()->getStyle(num2alpha($count).'1')->applyFromArray($style_head_fill);

        // Dont auto-size giant text fields
        if ($importType->getField($fieldName, 'kind') == 'text') {
            $excel->getActiveSheet()->getColumnDimension(num2alpha($count))->setWidth(25);
        } else {
            $excel->getActiveSheet()->getColumnDimension(num2alpha($count))->setAutoSize(true);
        }

        // Add notes to column headings
        $info = ($importType->isFieldRequired($fieldName))? "* required\n" : '';
        $info .= $importType->readableFieldType($fieldName)."\n";
        $info .= $importType->getField($fieldName, 'desc', '');
        $info = strip_tags($info);

        if (!empty($info)) {
            $excel->getActiveSheet()->getComment(num2alpha($count).'1')->getText()->createTextRun($info);
        }

        $count++;
    }

    if ($dataExport) {
        // Build some relational data arrays, if needed (do this first to avoid duplicate queries per-row)
        $relationalData = [];

        foreach ($importType->getAllFields() as $fieldName) {
            if ($importType->isFieldRelational($fieldName)) {
                $join = $on = '';
                extract($importType->getField($fieldName, 'relationship'));

                // Handle fields that have multiple value options
                if (!is_array($field) && stripos($field, '|') !== false) {
                    $field = explode('|', $field);
                    $field = current($field);
                }

                $queryFieldsRelational = (is_array($field))? implode(',', $field) : $field;
                
                // Build a query to grab data from relational tables
                $relationalSQL = "SELECT `{$table}`.`{$key}` id, {$queryFieldsRelational} FROM `{$table}`";

                if (!empty($join) && !empty($on)) {
                    if (is_array($on) && count($on) == 2) {
                        $relationalSQL .= " JOIN {$join} ON ({$join}.{$on[0]}={$table}.{$on[1]})";
                    }
                }

                $resultRelation = $pdo->select($relationalSQL);

                if ($resultRelation->rowCount() > 0) {

                    // Fetch into an array as:  id => array( field => value, field => value, ... )
                    $relationalData[$fieldName] = $resultRelation->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
                }
            }
        }
    }

      
    if ($dataExport) {

        $allTables = $importType->getTables();

        // Load the main table info
        $mainTableName = current($allTables);
        $importType->switchTable($mainTableName);
        $primaryKey = $importType->getPrimaryKey();
        $uniqueKeys = $importType->getUniqueKeys()[0] ?? [];

        $join = [];
    
        foreach ($allTables as $tableIndex => $tableName) {
            
            $importType->switchTable($tableName);
            $tablePrimaryKey = $importType->getPrimaryKey();
            $tableUniqueKeys = $importType->getUniqueKeys()[0] ?? [];
    
            if ($tableName != $mainTableName) {
    
                if (in_array($tablePrimaryKey, $uniqueKeys)) {
                    $join[$tableName] = " LEFT JOIN `{$tableName}` ON (`{$tableName}`.{$tablePrimaryKey} = `{$mainTableName}`.{$tablePrimaryKey}) ";
                } elseif (in_array($primaryKey, $tableUniqueKeys)) {
                    $join[$tableName] = " LEFT JOIN `{$tableName}` ON (`{$tableName}`.{$primaryKey} = `{$mainTableName}`.{$primaryKey}) ";
                } else {
                    continue;
                }
            }
    
            foreach ($importType->getTableFields() as $fieldName) {
                if ($importType->isFieldHidden($fieldName)) {
                    continue; // Skip hidden fields
                }
                
                if ($importType->isFieldReadOnly($fieldName) && $dataExport == true) {
                    continue;  // Skip readonly fields when exporting data
                }
    
                if ($fieldName == 'passwordStrong' && $dataExport == true) {
                    continue;  // Skip password fields when exporting data
                }
                
                $queryFields[] = "`{$tableName}`.{$fieldName}";
            }
    
            if (!empty($tablePrimaryKey)) {
                $queryFields = array_merge(["`{$tableName}`.{$tablePrimaryKey}"], $queryFields);
                // $columnFields = array_merge(array($primaryKey), $columnFields);
            }
    
        }

        // Get the data
        $data = [];
        $sql = "SELECT ".implode(', ', $queryFields)." FROM `{$mainTableName}` " .implode(' ', $join);

        $importType->switchTable($mainTableName);

        if ($dataExportAll == false) {
            // Optionally limit all exports to the current school year by default, to avoid massive files
            $gibbonSchoolYearID = $importType->getField('gibbonSchoolYearID', 'name', null);
            
            if ($gibbonSchoolYearID != null && $importType->isFieldReadOnly('gibbonSchoolYearID') == false) {
                $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
                $sql .= " WHERE gibbonSchoolYearID=:gibbonSchoolYearID ";
            }
        }

        $sql .= " ORDER BY `{$mainTableName}`.{$primaryKey} ASC";
        $result = $pdo->select($sql, $data);

        if (!empty($pdo->getErrorMessage())) {
            echo $pdo->getErrorMessage();
            die();
        }

        // Continue if there's data
        if ($result && $result->rowCount() > 0) {

            $rowCount = 2;
            while ($row = $result->fetch()) {

                $primaryKeyID = $row[$primaryKey] ?? null;

                // Work backwards, so we can potentially fill in any relational read-only fields
                for ($i=count($columnFields)-1; $i >= 0; $i--) {
                    $fieldName = $columnFields[$i];
                    
                    $value = $row[$fieldName] ?? null;

                    // Handle relational fields
                    if ($importType->isFieldRelational($fieldName)) {
                        extract($importType->getField($fieldName, 'relationship'));
                        $filter = $importType->getField($fieldName, 'filter');

                        // Handle fields that have multiple value options
                        if (!is_array($field) && stripos($field, '|') !== false) {
                            $field = explode('|', $field);
                            $field = current($field);
                        }

                        $values = $filter == 'csv' ? array_map('trim', explode(',', $value)) : [$value];
                        $relationalValue = [];

                        foreach ($values as $valueRel) {
                            // Single key relational field -- value is the ID from other table
                            $relationalField = (is_array($field))? $field[0] : $field;
                            $relationalValue[] = @$relationalData[$fieldName][$valueRel][$relationalField];

                            // Multi-key relational field (fill in backwards, slightly hacky but works)
                            if (is_array($field) && count($field) > 1) {
                                for ($n=1; $n < count($field); $n++) {
                                    $relationalField = $field[$n];

                                    // Does the field exist in the import definition but not in the current table?
                                    // Add the value to the row to fill-in the link between read-only relational fields
                                    if ($importType->isFieldReadOnly($relationalField)) {
                                        $row[ $relationalField ] = @$relationalData[$fieldName][$valueRel][$relationalField];
                                    }
                                }
                            }
                        }
                        
                        // Replace the relational ID value with the actual value
                        $value = implode(',', $relationalValue);
                    }

                    if (!empty($value)) {
                        // Set the cell value
                        $excel->getActiveSheet()->setCellValue(num2alpha($i).$rowCount, (string)$value);
                        //$allData[$tableIndex][$rowCount][num2alpha($i)] = (string)$value;
                    }
                }

                $rowCount++;
            }
        }
    }


    $filename = ($dataExport) ? 'DataExport'.'-'.$type : 'DataStructure'.'-'.$type;

    $exportFileType = $settingGateway->getSettingByScope('System Admin', 'exportDefaultFileType');
    if (empty($exportFileType)) {
        $exportFileType = 'Excel2007';
    }

    switch($exportFileType) {
        case 'Excel2007':
            $filename .= '.xlsx';
            $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $objWriter = IOFactory::createWriter($excel, 'Xlsx');
            break;
        case 'Excel5':
            $filename .= '.xls';
            $mimetype = 'application/vnd.ms-excel';
            $objWriter = IOFactory::createWriter($excel, 'Xls');
            break;
        case 'OpenDocument':
            $filename .= '.ods';
            $mimetype = 'application/vnd.oasis.opendocument.spreadsheet';
            $objWriter = IOFactory::createWriter($excel, 'Ods');
            break;
        case 'CSV':
            $filename .= '.csv';
            $mimetype = 'text/csv';
            $objWriter = IOFactory::createWriter($excel, 'Csv');
            break;
    }

    // FINALIZE THE DOCUMENT SO IT IS READY FOR DOWNLOAD
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $excel->setActiveSheetIndex(0);

    // Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: '.$mimetype);
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter->save('php://output');
    exit;
}
