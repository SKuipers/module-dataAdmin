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
use Gibbon\Module\DataAdmin\Importer;
use Gibbon\Module\DataAdmin\ImportType;
use Gibbon\Module\DataAdmin\ParseCSV;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_run.php")==false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $type = $_GET['type'] ?? '';
    $step = isset($_GET['step'])? min(max(1, $_GET['step']), 4) : 1;

    $importType = ImportType::loadImportType($type, $pdo);

    $nameParts = array_map('trim', explode('-', $importType->getDetail('name')));
    $name = implode(' - ', array_map('__', $nameParts));

    $page->breadcrumbs
        ->add(__('Import From File'), 'import_manage.php')
        ->add($name, 'import_run.php', ['type' => $type])
        ->add(__('Step {number}', ['number' => $step]));

    // Some script performance tracking
    $memoryStart = memory_get_usage();
    $timeStart = microtime(true);

    $importer = new Importer($pdo);

    $checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

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
        echo __('Import cannot proceed, there was an error reading the import file type {type}.', ['type' => $type]);
        echo "<br/></div>";
        return;
    }

    $steps = [
        1 => __('Select File'),
        2 => __('Confirm Data'),
        3 => __('Dry Run'),
        4 => __('Live Run'),
    ];

    echo "<ul id='progressbar'>";
    printf("<li class='%s'>%s</li>", ($step >= 1)? "active" : "", $steps[1]);
    printf("<li class='%s'>%s</li>", ($step >= 2)? "active" : "", $steps[2]);
    printf("<li class='%s'>%s</li>", ($step >= 3)? "active" : "", $steps[3]);
    printf("<li class='%s'>%s</li>", ($step >= 4)? "active" : "", $steps[4]);
    echo "</ul>";

    echo '<h2>';
    echo __('Step {number} - {name}', ['number' => $step, 'name' => $steps[$step]]);
    echo '</h2>';

    //STEP 1, SELECT TERM -----------------------------------------------------------------------------------
    if ($step==1) {
        $data = array('type' => $type);
        $sql = "SELECT gibbonLog.gibbonLogID
                FROM gibbonLog WHERE gibbonLog.title = CONCAT('Import - ', :type) 
                ORDER BY gibbonLog.timestamp DESC LIMIT 1" ;
        $importLog = $pdo->selectOne($sql, $data);

        echo '<div class="message">';
        echo __("Always backup your database before performing any imports. You will have the opportunity to review the data on the next step, however there's no guarantee the import won't change or overwrite important data.");
        echo '</div>';

        $form = Form::create('importStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $availableModes = array();
        $modes = $importType->getDetail('modes');
        if (!empty($modes['update']) && !empty($modes['insert'])) {
            $availableModes['sync'] = __('UPDATE & INSERT');
        }
        if (!empty($modes['update'])) {
            $availableModes['update'] = __('UPDATE only');
        }
        if (!empty($modes['insert'])) {
            $availableModes['insert'] = __('INSERT only');
        }

        $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addSelect('mode')->fromArray($availableModes)->isRequired();

        $columnOrders = array(
            'guess'      => __('Best Guess'),
            'last'       => __('From Last Import'),
            'linearplus' => __('From Export Data'),
            'linear'     => __('Same as Below'),
        );
        $selectedOrder = (!empty($importLog))? 'last' : 'guess';
        $row = $form->addRow();
        $row->addLabel('columnOrder', __('Column Order'));
        $row->addSelect('columnOrder')->fromArray($columnOrders)->isRequired()->selected($selectedOrder);

        $row = $form->addRow();
        $row->addLabel('file', __('File'))->description(__('See Notes below for specification.'));
        $row->addFileUpload('file')->isRequired()->accepts('.csv,.xls,.xlsx,.xml,.ods');

        $row = $form->addRow();
        $row->addLabel('fieldDelimiter', __('Field Delimiter'));
        $row->addTextField('fieldDelimiter')->isRequired()->maxLength(1)->setValue(',');

        $row = $form->addRow();
        $row->addLabel('stringEnclosure', __('String Enclosure'));
        $row->addTextField('stringEnclosure')->isRequired()->maxLength(1)->setValue('"');

        $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

        echo $form->getOutput();
    

        echo '<h4>';
        echo __('Notes');
        echo '</h4>';

        echo '<ol>';
        echo '<li style="color: #c00; font-weight: bold">'.__('Always include a header row in the uploaded file.').'</li>';
        echo '<li>'.__('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).').'</li>';
        echo '</ol>';

        if (isActionAccessible($guid, $connection2, "/modules/Data Admin/export_run.php")) {
            echo "<div class='linkTop'>" ;
            echo "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=$type'>" .  __('Export Columns') . "<img style='margin-left: 5px' title='" . __('Export Columns'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
            echo "</div>" ;
        }

        echo "<table class='smallIntBorder fullWidth colorOddEven' cellspacing='0'>" ;
        echo "<tr class='head'>" ;
        echo "<th style='width: 20px;'>" ;
        echo "</th>" ;
        echo "<th style='width: 180px;'>" ;
        echo __("Name") ;
        echo "</th>" ;
        echo "<th >" ;
        echo __("Description") ;
        echo "</th>" ;
        echo "<th style='width: 200px;'>" ;
        echo __("Type") ;
        echo "</th>" ;
        echo "</tr>" ;

        if (!empty($importType->getTableFields())) {
            $count = 0;
            foreach ($importType->getTableFields() as $fieldName) {
                $count++;

                if ($importType->isFieldHidden($fieldName)) {
                    continue;
                }

                echo "<tr>" ;
                echo "<td>" . $count. "</td>" ;
                echo "<td>";
                echo __($importType->getField($fieldName, 'name'));
                if ($importType->isFieldRequired($fieldName) == true) {
                    echo " <strong class='highlight'>*</strong>";
                }
                echo "</td>" ;
                echo "<td><em>" . __($importType->getField($fieldName, 'desc')). "</em></td>" ;
                echo "<td>";
                echo $importType->readableFieldType($fieldName);
                echo "</td>" ;
                echo "</tr>" ;
            }
        }
        echo "</table><br/>" ;
    }

    //STEP 2, CONFIG -----------------------------------------------------------------------------------
    elseif ($step==2) {
        $mode = (isset($_POST['mode']))? $_POST['mode'] : null;

        //Check file type
        if ($importer->isValidMimeType($_FILES['file']['type']) == false) {
            echo "<div class='error'>";
            printf(__('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a valid file.'), $_FILES['file']['type']);
            echo "<br/></div>";
        } elseif (empty($_POST["fieldDelimiter"]) or empty($_POST["stringEnclosure"])) {
            echo "<div class='error'>";
            echo __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.');
            echo "<br/></div>";
        } elseif ($mode != "sync" and $mode != "insert" and $mode != "update") {
            echo "<div class='error'>";
            echo __('Import cannot proceed, as the "Mode" field have been left blank.');
            echo "<br/></div>";
        } else {
            $proceed=true ;
            $columnOrder=(isset($_POST['columnOrder']))? $_POST['columnOrder'] : 'guess';

            if ($columnOrder == 'last') {
                $data = array('type' => $type);
                $sql = "SELECT * FROM gibbonLog WHERE gibbonLog.title = CONCAT('Import - ', :type) 
                        ORDER BY gibbonLog.timestamp DESC LIMIT 1" ;

                $importLog = $pdo->selectOne($sql, $data);
                $importLog = isset($importLog['serialisedArray'])? unserialize($importLog['serialisedArray']) : [];
                $columnOrderLast = $importLog['columnOrder'] ?? [];
            }

            $importer->fieldDelimiter = (!empty($_POST['fieldDelimiter']))? stripslashes($_POST['fieldDelimiter']) : ',';
            $importer->stringEnclosure = (!empty($_POST['stringEnclosure']))? stripslashes($_POST['stringEnclosure']) : '"';

            // Load the CSV or Excel data from the uploaded file
            $csvData = $importer->readFileIntoCSV();

            $headings = $importer->getHeaderRow();
            $firstLine = $importer->getFirstRow();

            if (empty($csvData) || empty($headings) || empty($firstLine)) {
                echo "<div class='error'>";
                echo __('Import cannot proceed, the file type cannot be read.');
                echo "<br/></div>";
                return;
            }

            echo "<script>";
            echo "var csvFirstLine = " . json_encode($firstLine) .";";
            echo "var columnDataSkip = " . Importer::COLUMN_DATA_SKIP .";";
            echo "var columnDataCustom = " . Importer::COLUMN_DATA_CUSTOM .";";
            echo "var columnDataFunction = " . Importer::COLUMN_DATA_FUNCTION .";";
            echo "</script>";
            
            $form = Form::create('importStep2', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=3');
            $form->getRenderer()->setWrapper('form', 'div')->setWrapper('row', 'div')->setWrapper('cell', 'div');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('mode', $mode);
            $form->addHiddenValue('fieldDelimiter', urlencode($_POST['fieldDelimiter']));
            $form->addHiddenValue('stringEnclosure', urlencode($_POST['stringEnclosure']));
            $form->addHiddenValue('ignoreErrors', 0);

            // SYNC SETTINGS
            if ($mode == "sync" || $mode == "update") {
                $lastFieldValue = ($columnOrder == 'last' && isset($columnOrderLast['syncField']))? $columnOrderLast['syncField'] : 'N';
                $lastColumnValue = ($columnOrder == 'last' && isset($columnOrderLast['syncColumn']))? $columnOrderLast['syncColumn'] : '';

                if ($columnOrder == 'linearplus') {
                    $lastFieldValue = 'Y';
                    $lastColumnValue = $importType->getPrimaryKey();
                }

                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

                $row = $table->addRow();
                $row->addLabel('syncField', __('Use database ID field?'))->description(__('Only entries with a matching database ID will be updated.'));
                $row->addYesNoRadio('syncField')->checked($lastFieldValue);

                $form->toggleVisibilityByClass('syncDetails')->onRadio('syncField')->when('Y');
                $row = $table->addRow()->addClass('syncDetails');
                $row->addLabel('syncColumn', __('Primary Key ID'))->description(sprintf(__("Sync field %s with CSV column:"), '<code>'.$importType->getPrimaryKey().'</code>'));
                $row->addSelect('syncColumn')->fromArray($headings)->selected($lastColumnValue)->placeholder()->isRequired();
            }

            $form->addRow()->addContent('&nbsp;');
            
            // IMPORT RESTRICTIONS
            $importRestrictions = $importType->getImportRestrictions();

            if (!empty($importRestrictions)) {
                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');
                $table->addHeaderRow()->addContent(__('Import Restrictions'));

                foreach ($importRestrictions as $count => $restriction) {
                    $table->addRow()->addContent(($count+1).'. &nbsp;&nbsp; '.$restriction);
                }
            }

            $form->addRow()->addContent('&nbsp;');

            // COLUMN SELECTION
            if (!empty($importType->getTableFields())) {
                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                $header->addContent(__('Field Name'));
                $header->addContent(__('Type'));
                $header->addContent(__('Column'));
                $header->addContent(__('Example'));

                $count = 0;

                $defaultColumns = function ($fieldName) use (&$importType) {
                    $columns = [];
                    
                    if ($importType->isFieldRequired($fieldName) == false) {
                        $columns[Importer::COLUMN_DATA_SKIP] = '[ '.__('Skip this Column').' ]';
                    }
                    if ($importType->getField($fieldName, 'custom')) {
                        $columns[Importer::COLUMN_DATA_CUSTOM] = '[ '.__('Custom Value').' ]';
                    }
                    if ($importType->getField($fieldName, 'function')) {
                        $columns[Importer::COLUMN_DATA_FUNCTION] = '[ '.__('Generate Value').' ]';
                        //data-function='". $importType->getField($fieldName, 'function') ."'
                    }
                    return $columns;
                };

                $columns = array_reduce(range(0, count($headings)-1), function ($group, $index) use (&$headings) {
                    $group[strval($index)." "] = $headings[$index];
                    return $group;
                }, array());

                $columnIndicators = function ($fieldName) use (&$importType) {
                    $output = '';
                    if ($importType->isFieldRequired($fieldName)) {
                        $output .= " <strong class='highlight'>*</strong>";
                    }
                    if ($importType->isFieldUniqueKey($fieldName)) {
                        $output .= "<img title='" . __('Unique Key') . "' src='./themes/Default/img/target.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    if ($importType->isFieldRelational($fieldName)) {
                        $output .= "<img title='" . __('Relational') . "' src='./themes/Default/img/refresh.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    return $output;
                };

                foreach ($importType->getTableFields() as $fieldName) {
                    if ($importType->isFieldHidden($fieldName)) {
                        $columnIndex = Importer::COLUMN_DATA_HIDDEN;
                        if ($importType->isFieldLinked($fieldName)) {
                            $columnIndex = Importer::COLUMN_DATA_LINKED;
                        }
                        if (!empty($importType->getField($fieldName, 'function'))) {
                            $columnIndex = Importer::COLUMN_DATA_FUNCTION;
                        }

                        $form->addHiddenValue("columnOrder[$count]", $columnIndex);
                        $count++;
                        continue;
                    }
                    
                    $selectedColumn = '';
                    if ($columnOrder == 'linear' || $columnOrder == 'linearplus') {
                        $selectedColumn = ($columnOrder == 'linearplus')? $count+1 : $count;
                    } elseif ($columnOrder == 'last') {
                        $selectedColumn = isset($columnOrderLast[$count])? $columnOrderLast[$count] : '';
                    } elseif ($columnOrder == 'guess') {
                        foreach ($headings as $index => $columnName) {
                            if (mb_strtolower($columnName) == mb_strtolower($fieldName) || mb_strtolower($columnName) == mb_strtolower($importType->getField($fieldName, 'name'))) {
                                $selectedColumn = $index;
                                break;
                            }
                        }
                    }

                    $row = $table->addRow();
                    $row->addContent(__($importType->getField($fieldName, 'name')))
                            ->wrap('<span class="'.$importType->getField($fieldName, 'desc').'">', '</span>')
                            ->append($columnIndicators($fieldName));
                    $row->addContent($importType->readableFieldType($fieldName));
                    $row->addSelect('columnOrder['.$count.']')
                            ->setID('columnOrder'.$count)
                            ->fromArray($defaultColumns($fieldName))
                            ->fromArray($columns)
                            ->isRequired()
                            ->setClass('columnOrder mediumWidth')
                            ->selected($selectedColumn)
                            ->placeholder();
                    $row->addTextField('columnText['.$count.']')
                            ->setID('columnText'.$count)
                            ->setClass('shortWidth columnText')
                            ->readonly()
                            ->isDisabled();

                    $count++;
                }
            }

            $form->addRow()->addContent('&nbsp;');

            // CSV PREVIEW
            $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

            $row = $table->addRow();
            $row->addLabel('csvData', __('Data'));
            $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

            $row = $table->addRow();
            $row->addFooter();
            $row->addSubmit();

            echo $form->getOutput();
        }
    }

    //STEP 3 & 4, DRY & LIVE RUN  -----------------------------------------------------------------------------------
    elseif ($step==3 || $step==4) {
        // Gather our data
        $mode = $_POST['mode'] ?? null;
        $syncField = $_POST['syncField'] ?? null;
        $syncColumn = $_POST['syncColumn'] ?? null;

        $csvData = $_POST['csvData'] ?? null;
        if ($step==4) {
            $columnOrder = isset($_POST['columnOrder'])? unserialize($_POST['columnOrder']) : null;
            $columnText = isset($_POST['columnText'])? unserialize($_POST['columnText']) : null;
        } else {
            $columnOrder = $_POST['columnOrder'] ?? null;
            $columnText = $_POST['columnText'] ?? null;
        }

        $fieldDelimiter = isset($_POST['fieldDelimiter'])? urldecode($_POST['fieldDelimiter']) : null;
        $stringEnclosure = isset($_POST['stringEnclosure'])? urldecode($_POST['stringEnclosure']) : null;

        $ignoreErrors = $_POST['ignoreErrors'] ?? false;

        if (empty($csvData) || empty($columnOrder)) {
            echo "<div class='error'>";
            echo __("Your request failed because your inputs were invalid.") ;
            echo "<br/></div>";
            return;
        } elseif ($mode != "sync" and $mode != "insert" and $mode != "update") {
            echo "<div class='error'>";
            echo __('Import cannot proceed, as the "Mode" field has been left blank.');
            echo "<br/></div>";
        } elseif (($mode == 'sync' || $mode == 'update') && (!empty($syncField) && $syncColumn < 0)) {
            echo "<div class='error'>";
            echo __("Your request failed because your inputs were invalid.") ;
            echo "<br/></div>";
            return;
        } elseif (empty($fieldDelimiter) or empty($stringEnclosure)) {
            echo "<div class='error'>";
            echo __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.');
            echo "<br/></div>";
        } else {
            $importer->mode = $mode;
            $importer->syncField = ($syncField == 'Y');
            $importer->syncColumn = $syncColumn;
            $importer->fieldDelimiter = (!empty($fieldDelimiter))? stripslashes($fieldDelimiter) : ',';
            $importer->stringEnclosure = (!empty($stringEnclosure))? stripslashes($stringEnclosure) : '"';

            // Load the CSV Data

            // Loop through and validate

            // If sync, check for how many updates
            // If import, check how many inserts

            // Check for duplicates within current data set
            // Check for database duplicates using unique keys

            $importSuccess = $buildSuccess = $databaseSuccess = false;
            $importSuccess = $importer->readCSVString($csvData);

            if ($importSuccess || $ignoreErrors) {
                $buildSuccess = $importer->buildTableData($importType, $columnOrder, $columnText);
            }

            if ($buildSuccess || $ignoreErrors) {
                $databaseSuccess = $importer->importIntoDatabase($importType, ($step == 4));
            }

            $overallSuccess = ($importSuccess && $buildSuccess && $databaseSuccess);

            if ($overallSuccess) {
                if ($step == 3) {
                    echo "<div class='message'>";
                    echo __('The data was successfully validated. This is a <b>DRY RUN!</b> No changes have been made to the database. If everything looks good here, you can click submit to complete this import.');
                    echo "</div>";
                } else {
                    echo "<div class='success'>";
                    echo __('The import completed successfully and all relevant database fields have been created and/or updated.');
                    echo "</div>";
                }
            } elseif ($ignoreErrors) {
                echo "<div class='warning'>";
                echo __('The import completed successfully, with the following errors ignored.');
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo $importer->getLastError();
                echo "</div>";
            }

            $logs = $importer->getLogs();

            if (count($logs) > 0) {
                $table = DataTable::create('logs');
                $table->modifyRows(function ($log, $row) {
                    return $row->addClass($log['type'] ?? '');
                });

                $table->addColumn('row', __('Row'));
                $table->addColumn('field', __('Field'))
                    ->format(function ($log) {
                        return $log['field_name'].($log['field'] >= 0 ? ' ('. $log['field'] .')' : '');
                    });
                $table->addColumn('info', __('Message'));

                echo $table->render(new DataSet($logs));
                echo '<br/>';
            }

            $executionTime = mb_substr(microtime(true) - $timeStart, 0, 6).' sec';
            $memoryUsage = readableFileSize(max(0, memory_get_usage() - $memoryStart)); 
            
            $results = array(
                'importSuccess'		=> $importSuccess,
                'buildSuccess'		=> $buildSuccess,
                'databaseSuccess'	=> $databaseSuccess,
                'rows'				=> $importer->getRowCount(),
                'rowerrors'			=> $importer->getErrorRowCount(),
                'errors'			=> $importer->getErrorCount(),
                'warnings'			=> $importer->getWarningCount(),
                'inserts'			=> $importer->getDatabaseResult('inserts'),
                'inserts_skipped'	=> $importer->getDatabaseResult('inserts_skipped'),
                'updates'			=> $importer->getDatabaseResult('updates'),
                'updates_skipped'	=> $importer->getDatabaseResult('updates_skipped'),
                'executionTime'		=> $executionTime,
                'memoryUsage'		=> $memoryUsage,
                'ignoreErrors'		=> $ignoreErrors,
            );

            echo $page->fetchFromTemplate('importer.twig.html', $results);
            
            if ($step==3) {
                $form = Form::create('importStep2', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=4');
                $form->getRenderer()->setWrapper('form', 'div')->setWrapper('row', 'div')->setWrapper('cell', 'div');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('mode', $mode);
                $form->addHiddenValue('syncField', $syncField);
                $form->addHiddenValue('syncColumn', $syncColumn);
                $form->addHiddenValue('columnOrder', serialize($columnOrder));
                $form->addHiddenValue('columnText', serialize($columnText));
                $form->addHiddenValue('fieldDelimiter', urlencode($fieldDelimiter));
                $form->addHiddenValue('stringEnclosure', urlencode($stringEnclosure));

                // CSV PREVIEW
                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

                $row = $table->addRow();
                $row->addLabel('csvData', __('Data'));
                $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

                $row = $table->addRow();
                $row->onlyIf(!$overallSuccess)->addCheckbox('ignoreErrors')->description(__('Ignore Errors? (Expert Only!)'))->setValue($ignoreErrors)->setClass('');
                $row->onlyIf($overallSuccess)->addContent('');
                
                if (!$overallSuccess && !$ignoreErrors) {
                    $row->addButton(__('Cannot Continue'))->setID('submitStep3')->isDisabled()->addClass('right');
                } else {
                    $row->addSubmit()->setID('submitStep3');
                }
                    
                echo $form->getOutput();
            }

            if ($step==4) {

                // Output passwords if generated
                if (!empty($importer->outputData['passwords'])) {
                    echo '<h4>';
                    echo __('Generated Passwords');
                    echo '</h4>';
                    echo '<p>';
                    echo __('These passwords have been generated by the import process. They have <b>NOT</b> been recorded anywhere: please copy & save them now if you wish to record them.');
                    echo '</p>';

                    $table = DataTable::create('output');

                    $table->addColumn('username', __('Username'));
                    $table->addColumn('password', __('Password'));

                    echo $table->render(new DataSet($importer->outputData['passwords']));
                }

                $columnOrder['syncField'] =  $syncField;
                $columnOrder['syncColumn'] =  $syncColumn;

                

                $importer->createImportLog($_SESSION[$guid]['gibbonPersonID'], $type, $results, $columnOrder);
            }
        }
    }
}
