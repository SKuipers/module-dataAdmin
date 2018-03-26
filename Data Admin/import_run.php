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
use Modules\DataAdmin\Importer;
use Modules\DataAdmin\ImportType;
use Modules\DataAdmin\ParseCSV;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_run.php")==FALSE) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __("You do not have access to this action.") ;
	echo "</div>" ;
}
else {
	// Some script performace tracking
	$memoryStart = memory_get_usage();
	$resourceStart = getrusage();
	$timeStart = microtime(true);

	// Include PHPExcel
	require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPExcel/Classes/PHPExcel.php';

	echo "<div class='trail'>" ;
	echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Import From File', 'Data Admin') . "</div>" ;
	echo "</div>" ;

	$importer = new Importer( $gibbon, $pdo );

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';
	$importType = ImportType::loadImportType( $type, $pdo );

	$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

	if ($checkUserPermissions == 'Y' && $importType->isImportAccessible($guid, $connection2) == false) {
		echo "<div class='error'>" ;
		echo __("You do not have access to this action.") ;
		echo "</div>" ;
		return;
	} else if ( empty($importType)  ) {
		echo "<div class='error'>" ;
		echo __("Your request failed because your inputs were invalid.") ;
		echo "</div>" ;
		return;
	} else if ( !$importType->isValid() ) {
		echo "<div class='error'>";
		printf( __('Import cannot proceed, as the selected Import Type "%s" did not validate with the database.', 'Data Admin'), $type) ;
		echo "<br/></div>";
		return;
	}

	$step = (isset($_GET["step"]))? min( max(1, $_GET["step"]), 4) : 1;

	echo "<ul id='progressbar'>";
		printf("<li class='%s'>%s</li>", ($step >= 1)? "active" : "", __("Select CSV", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 2)? "active" : "", __("Confirm Data", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 3)? "active" : "", __("Dry Run", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 4)? "active" : "", __("Live Run", 'Data Admin') );
	echo "</ul>";


	//STEP 1, SELECT TERM -----------------------------------------------------------------------------------
	if ($step==1) {

		try {
			$data=array( 'type' => $type, 'success' => '1' );
			$sql="SELECT importLogID FROM dataAdminImportLog as importLog WHERE type=:type AND success=:success ORDER BY timestamp DESC LIMIT 1" ;
			$result = $pdo->executeQuery($data, $sql);
		}
		catch(PDOException $e) {
			echo "<div class='error'>" . $e->getMessage() . "</div>" ;
        }
        
        echo '<h2>';
		echo __('Step 1 - Select CSV Files', 'Data Admin');
        echo '</h2>';

        echo '<div class="message">';
		echo __('Always backup your database before performing any imports. You will have the opportunity to review the data on the next step, however there\'s no guaruntee the import won\'t change or overwrite important data.', 'Data Admin');
        echo '</div>';

        $form = Form::create('importStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $availableModes = array();
        $modes = $importType->getDetail('modes');
        if (!empty($modes['update']) && !empty($modes['insert'])) $availableModes['sync'] = __('UPDATE & INSERT', 'Data Admin');
        if (!empty($modes['update'])) $availableModes['update'] = __('UPDATE only', 'Data Admin');
        if (!empty($modes['insert'])) $availableModes['insert'] = __('INSERT only', 'Data Admin');

        $row = $form->addRow();
            $row->addLabel('mode', __('Mode'))->description(__('Options available depend on the import type.', 'Data Admin'));
            $row->addSelect('mode')->fromArray($availableModes)->isRequired();

        $columnOrders = array(
            'guess'      => __('Best Guess', 'Data Admin'),
            'last'       => __('From Last Import', 'Data Admin'),
            'linearplus' => __('From Export Data', 'Data Admin'),
            'linear'     => __('Same as Below', 'Data Admin'),
        );
        $selectedOrder = ($result->rowCount() > 0)? 'last' : 'guess';
        $row = $form->addRow();
            $row->addLabel('columnOrder', __('Column Order'));
            $row->addSelect('columnOrder')->fromArray($columnOrders)->isRequired()->selected($selectedOrder);

        $row = $form->addRow();
            $row->addLabel('file', __('CSV File'))->description(__('See Notes below for specification.'));
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
		echo '<li style="color: #c00; font-weight: bold">'.__('Always include a header row in the uploaded file.', 'Data Admin').'</li>';
		echo '<li>'.__('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).').'</li>';
        echo '</ol>';

        if ( isActionAccessible($guid, $connection2, "/modules/Data Admin/export_run.php") ) {
            echo "<div class='linkTop'>" ;
            echo "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=$type'>" .  __('Export Structure', 'Data Admin') . "<img style='margin-left: 5px' title='" . __('Export Structure', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
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

            if ( !empty($importType->getTableFields()) ) {

                $count = 0;
                foreach ($importType->getTableFields() as $fieldName ) {
                    $count++;

                    if ( $importType->isFieldHidden($fieldName) ) continue;

                    echo "<tr>" ;
                        echo "<td>" . $count. "</td>" ;
                        echo "<td>";
                            echo $importType->getField($fieldName, 'name');
                            if ( $importType->isFieldRequired($fieldName) == true ) {
                                echo " <strong class='highlight'>*</strong>";
                            }
                        echo "</td>" ;
                        echo "<td><em>" . $importType->getField($fieldName, 'desc'). "</em></td>" ;
                        echo "<td>";
                            echo $importType->readableFieldType($fieldName);
                        echo "</td>" ;
                    echo "</tr>" ;
                }

            }
        echo "</table><br/>" ;
    }

	//STEP 2, CONFIG -----------------------------------------------------------------------------------
	else if ($step==2) {

		echo '<h2>';
		echo __('Step 2 - Data Check & Confirm', 'Data Admin');
		echo '</h2>';

		$mode = (isset($_POST['mode']))? $_POST['mode'] : NULL;

		//Check file type
		if ($importer->isValidMimeType($_FILES['file']['type']) == false) {
			echo "<div class='error'>";
			printf(__('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.', 'Data Admin'), $_FILES['file']['type']);
			echo "<br/></div>";
		}
		else if ( empty($_POST["fieldDelimiter"]) OR empty($_POST["stringEnclosure"])) {
			echo "<div class='error'>";
			echo __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.', 'Data Admin');
			echo "<br/></div>";
		}
		else if ($mode != "sync" AND $mode != "insert" AND $mode != "update") {
			echo "<div class='error'>";
			echo __('Import cannot proceed, as the "Mode" field has been left blank.', 'Data Admin');
			echo "<br/></div>";
		}
		else {
			$proceed=true ;
			$columnOrder=(isset($_POST["columnOrder"]))? $_POST["columnOrder"] : 'guess';

			if ($columnOrder == 'last') {
				try {
					$data=array( 'type' => $type, 'success' => '1' );
					$sql="SELECT columnOrder FROM dataAdminImportLog WHERE type=:type AND success=:success ORDER BY timestamp DESC LIMIT 1" ;
					$columnResult = $pdo->executeQuery($data, $sql);
				}
				catch(PDOException $e) {
					echo "<div class='error'>" . $e->getMessage() . "</div>" ;
				}

				$columnOrderLast = $columnResult->fetch();
				$columnOrderLast = unserialize( $columnOrderLast['columnOrder'] );
			}

			$importer->fieldDelimiter = (!empty($_POST["fieldDelimiter"]))? stripslashes($_POST["fieldDelimiter"]) : ',';
    		$importer->stringEnclosure = (!empty($_POST["stringEnclosure"]))? stripslashes($_POST["stringEnclosure"]) : '"';

			// Load the CSV or Excel data from the uploaded file
			$csvData = $importer->readFileIntoCSV();

			$headings = $importer->getHeaderRow();
			$firstLine = $importer->getFirstRow();

			if ( empty($csvData) || empty($headings) || empty($firstLine) ) {
				echo "<div class='error'>";
				echo __('Import cannot proceed, the file type cannot be read.', 'Data Admin');
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
                    $row->addLabel('syncField', __('Use database ID field?', 'Data Admin'))->description(__('Only entries with a matching database ID will be updated.', 'Data Admin'));
                    $row->addYesNoRadio('syncField')->checked($lastFieldValue);

                $form->toggleVisibilityByClass('syncDetails')->onRadio('syncField')->when('Y');
                $row = $table->addRow()->addClass('syncDetails');
                    $row->addLabel('syncColumn', __('Primary Key ID'))->description(sprintf(__("Sync field %s with CSV column:", 'Data Admin'), '<code>'.$importType->getPrimaryKey().'</code>'));
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
            if ( !empty($importType->getTableFields()) ) {
                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                    $header->addContent(__('Field Name', 'Data Admin'));
                    $header->addContent(__('Type', 'Data Admin'));
                    $header->addContent(__('Column', 'Data Admin'));
                    $header->addContent(__('Sample', 'Data Admin'));

                $count = 0;

                $columns = array_reduce(range(0, count($headings)-1), function($group, $index) use (&$headings) {
                    $group[strval($index)." "] = $headings[$index];
                    return $group;
                }, array());

                $columnIndicators = function($fieldName) use (&$importType) {
                    $output = '';
                    if ( $importType->isFieldRequired($fieldName) ) {
                        $output .= " <strong class='highlight'>*</strong>";
                    }
                    if ( $importType->isFieldUniqueKey($fieldName) ) {
                        $output .= "<img title='" . __('Unique Key', 'Data Admin') . "' src='./themes/Default/img/target.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    if ( $importType->isFieldRelational($fieldName) ) {
                        $output .= "<img title='" . __('Relational', 'Data Admin') . "' src='./themes/Default/img/refresh.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    return $output;
                };

                foreach ($importType->getTableFields() as $fieldName ) {
                    $selectedColumn = '';
                    if ($columnOrder == 'linear' || $columnOrder == 'linearplus') {
                        $selectedColumn = ($columnOrder == 'linearplus')? $count+1 : $count;
                    } else if ($columnOrder == 'last') {
                        $selectedColumn = isset($columnOrderLast[$count])? $columnOrderLast[$count] : '';
                    } else if ($columnOrder == 'guess') {
                        foreach ($headings as $index => $columnName) {
                            if (mb_strtolower($columnName) == mb_strtolower($fieldName) || mb_strtolower($columnName) == mb_strtolower($importType->getField($fieldName, 'name'))) {
                                $selectedColumn = $index;
                                break;
                            }
                        }
                    }

                    $row = $table->addRow();
                        $row->addContent($importType->getField($fieldName, 'name'))
                            ->wrap('<span class="'.$importType->getField($fieldName, 'desc').'">', '</span>')
                            ->append($columnIndicators($fieldName));
                        $row->addContent($importType->readableFieldType($fieldName));
                        $row->addSelect('columnOrder['.$count.']')
                            ->setID('columnOrder'.$count)
                            ->fromArray($columns)
                            ->setRequired($importType->isFieldRequired($fieldName))
                            ->setClass('columnOrder floatLeft mediumWidth')
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
                $row->addLabel('csvData', __('CSV Data:', 'Data Admin'));
                $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

            $row = $table->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
		}
	}

	//STEP 3 & 4, DRY & LIVE RUN  -----------------------------------------------------------------------------------
	else if ($step==3 || $step==4) {

		echo "<h2>";
		echo ($step==3)? __('Step 3 - Dry Run', 'Data Admin') : __('Step 4 - Live Run', 'Data Admin');
		echo "</h2>";

		// Gather our data
		$mode = (isset($_POST['mode']))? $_POST['mode'] : NULL;
		$syncField = (isset($_POST['syncField']))? $_POST['syncField'] : NULL;
		$syncColumn = (isset($_POST['syncColumn']))? $_POST['syncColumn'] : NULL;

		$csvData = (isset($_POST['csvData']))? $_POST['csvData'] : NULL;
		if ($step==4) {
			$columnOrder = (isset($_POST['columnOrder']))? unserialize($_POST['columnOrder']) : NULL;
			$columnText = (isset($_POST['columnText']))? unserialize($_POST['columnText']) : NULL;
		} else {
			$columnOrder = (isset($_POST['columnOrder']))? $_POST['columnOrder'] : NULL;
			$columnText = (isset($_POST['columnText']))? $_POST['columnText'] : NULL;
		}

		$fieldDelimiter = (isset($_POST['fieldDelimiter']))? urldecode($_POST['fieldDelimiter']) : NULL;
		$stringEnclosure = (isset($_POST['stringEnclosure']))? urldecode($_POST['stringEnclosure']) : NULL;

		$ignoreErrors = (isset($_POST['ignoreErrors']))? $_POST['ignoreErrors'] : false;

		if ( empty($csvData) || empty($columnOrder) ) {
			echo "<div class='error'>";
			echo __("Your request failed because your inputs were invalid.") ;
			echo "<br/></div>";
			return;
		}
		else if ($mode != "sync" AND $mode != "insert" AND $mode != "update") {
			echo "<div class='error'>";
			echo __('Import cannot proceed, as the "Mode" field has been left blank.', 'Data Admin');
			echo "<br/></div>";
		}
		else if ( ($mode == 'sync' || $mode == 'update') && (!empty($syncField) && $syncColumn < 0 ) ) {
			echo "<div class='error'>";
			echo __("Your request failed because your inputs were invalid.") ;
			echo "<br/></div>";
			return;
		}
		else if ( empty($fieldDelimiter) OR empty($stringEnclosure) ) {
			echo "<div class='error'>";
			echo __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.', 'Data Admin');
			echo "<br/></div>";
		}
		else {

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
			$importSuccess = $importer->readCSVString( $csvData );

			if ($importSuccess || $ignoreErrors) {
				$buildSuccess = $importer->buildTableData( $importType, $columnOrder, $columnText );
			}

			if ($buildSuccess || $ignoreErrors) {
				$databaseSuccess = $importer->importIntoDatabase( $importType, ($step == 4) );
			}

			$overallSuccess = ($importSuccess && $buildSuccess && $databaseSuccess);

			if ($overallSuccess) {

				echo "<div class='success'>";
				if ($step == 3) {
					echo __('The data was successfully imported and validated. No changes have been made to the database.', 'Data Admin');
				} else {
					echo __('The import completed successfully and all relevant database fields have been created and/or updated.', 'Data Admin');
				}
				echo "</div>";

			} else {

				echo "<div class='error'>";
					echo $importer->getLastError();
				echo "</div>";
			}

			$logs = $importer->getLogs();

			if (count($logs) > 0) {
				echo "<table class='smallIntBorder fullWidth colorOddEven' cellspacing='0'>" ;
					echo "<tr class='head'>" ;
						echo "<th style='width: 40px;'>" ;
							echo __("Row", 'Data Admin') ;
						echo "</th>" ;
						echo "<th style='width: 200px;'>" ;
							echo __("Field", 'Data Admin') ;
						echo "</th>" ;
						echo "<th >" ;
							echo __("Message", 'Data Admin') ;
						echo "</th>" ;
					echo "</tr>" ;

					foreach ($logs as $log ) {
						echo "<tr class='".$log['type']."'>" ;
							echo "<td>" . $log['row'] . "</td>";
							echo "<td>";
								echo $log['field_name'];
								echo ($log['field'] >= 0)? " (". $log['field'] .")" : "";
							echo "</td>";
							echo "<td>" . $log['info'] . "</td>";
						echo "</tr>" ;
					}

				echo "</table><br/>" ;
			}

			$executionTime = mb_substr( microtime(true) - $timeStart, 0, 6 ).' sec';
    		$memoryUsage = readableFileSize(  max( 0, memory_get_usage() - $memoryStart )  );

			?>

			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php echo "class='". ( ($importSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right"  width="50%">
						<?php echo __("Reading CSV file", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo ($importSuccess)? __("Success") : __("Failed"); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Execution time", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo $executionTime; ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Memory usage", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php

						echo $memoryUsage;
						?>
					</td>
				</tr>
			</table><br/>
			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php echo "class='". ( ($buildSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right" width="50%">
						<?php echo __("Validating data", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo ($buildSuccess)? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Rows processed", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo $importer->getRowCount(); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Rows with errors", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo $importer->getErrorRowCount(); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Total errors", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo $importer->getErrorCount(); ?>
					</td>
				</tr>
				<?php if ($importer->getWarningCount() > 0) : ?>
				<tr>
					<td class="right">
						<?php echo __("Total warnings", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo $importer->getWarningCount(); ?>
					</td>
				</tr>
				<?php endif; ?>
			</table><br/>

			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php echo "class='". ( ($databaseSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right" width="50%">
						<?php echo __("Querying database", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php echo ($databaseSuccess)? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php echo __("Database Inserts", 'Data Admin').": ";?>
					</td>
					<td>
					<?php
						echo $importer->getDatabaseResult('inserts');
						if ($importer->getDatabaseResult('inserts_skipped') > 0) {
							echo " (". $importer->getDatabaseResult('inserts_skipped') ." ". __("skipped", 'Data Admin') .")";
						}
					?>
					</td>
				</tr>

				<tr>
					<td class="right">
						<?php echo __("Database Updates").": "; ?>
					</td>
					<td>
					<?php echo $importer->getDatabaseResult('updates');
						if ($importer->getDatabaseResult('updates_skipped') > 0) {
							echo " (". $importer->getDatabaseResult('updates_skipped') ." ". __("skipped", 'Data Admin') .")";
						}
					?>
					</td>
				</tr>


			</table><br/>

            <?php if ($step==3) {

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
                    $row->addLabel('csvData', __('CSV Data:', 'Data Admin'));
                    $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

                $row = $table->addRow();
                    $row->onlyIf(!$overallSuccess)->addCheckbox('ignoreErrors')->description(__('Ignore Errors? (Expert Only!)', 'Data Admin'))->setValue($ignoreErrors)->setClass('');
                    $row->onlyIf($overallSuccess)->addContent('');
                
                if (!$overallSuccess && !$ignoreErrors) {
                    $row->addButton(__('Cannot Continue', 'Data Admin'))->setID('submitStep3')->isDisabled()->addClass('right');
                } else {
                    $row->addSubmit()->setID('submitStep3');
                }
                    
                echo $form->getOutput();
            }

			if ($step==4) {
				$columnOrder['syncField'] =  $syncField;
				$columnOrder['syncColumn'] =  $syncColumn;

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

				$importer->createImportLog( $_SESSION[$guid]['gibbonPersonID'], $type, $results, $columnOrder );
			}
		}

	}

}
