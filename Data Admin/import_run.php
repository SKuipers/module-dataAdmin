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

use Modules\DataAdmin\Importer;
use Modules\DataAdmin\ImportType;
use Modules\DataAdmin\ParseCSV;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_run.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	// Some script performace tracking
	$memoryStart = memory_get_usage();
	$resourceStart = getrusage();
	$timeStart = microtime(true);

	// Include PHPExcel
	require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPExcel/Classes/PHPExcel.php';

	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Import From File', 'Data Admin') . "</div>" ;
	print "</div>" ;

	$importer = new Importer( $gibbon, $pdo );

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';
	$importType = ImportType::loadImportType( $type, $pdo );

	$checkUserPermissions = getSettingByScope($connection2, 'Data Admin', 'enableUserLevelPermissions');

	if ($checkUserPermissions == 'Y' && $importType->isImportAccessible($guid, $connection2) == false) {
		print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
		print "</div>" ;
		return;
	} else if ( empty($importType)  ) {
		print "<div class='error'>" ;
		print __("Your request failed because your inputs were invalid.") ;
		print "</div>" ;
		return;
	} else if ( !$importType->isValid() ) {
		print "<div class='error'>";
		printf( __('Import cannot proceed, as the selected Import Type "%s" did not validate with the database.', 'Data Admin'), $type) ;
		print "<br/></div>";
		return;
	}

	$step = (isset($_GET["step"]))? min( max(1, $_GET["step"]), 4) : 1;

	print "<ul id='progressbar'>";
		printf("<li class='%s'>%s</li>", ($step >= 1)? "active" : "", __("Select CSV", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 2)? "active" : "", __("Confirm Data", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 3)? "active" : "", __("Dry Run", 'Data Admin') );
		printf("<li class='%s'>%s</li>", ($step >= 4)? "active" : "", __("Live Run", 'Data Admin') );
	print "</ul>";


	//STEP 1, SELECT TERM -----------------------------------------------------------------------------------
	if ($step==1) {

		try {
			$data=array( 'type' => $type, 'success' => '1' );
			$sql="SELECT importLogID FROM dataAdminImportLog as importLog WHERE type=:type AND success=:success ORDER BY timestamp DESC LIMIT 1" ;
			$result = $pdo->executeQuery($data, $sql);
		}
		catch(PDOException $e) {
			print "<div class='error'>" . $e->getMessage() . "</div>" ;
		}
	?>
		<h2>
			<?php print __('Step 1 - Select CSV Files', 'Data Admin') ?>
		</h2>

		<div class='message'>
			<?php print __('Always backup your database before performing any imports. You will have the opportunity to review the data on the next step, however there\'s no guaruntee the import won\'t change or overwrite important data.', 'Data Admin'); ?>
		</div>
		<p>
		</p>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=$type&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td>
						<b><?php print __("Mode", 'Data Admin'); ?> *</b><br/>
						<span class="emphasis small"><?php print __("Options available depend on the import type.", 'Data Admin'); ?></span>
					</td>
					<td class="right">
						<select name="mode" id="mode" class="standardWidth">
							<?php
								$modes = $importType->getDetail('modes');

								if ((isset($modes['update']) && $modes['update'] == true) && (isset($modes['insert']) && $modes['insert'] == true)) {
									print "<option value='sync'>". __('UPDATE & INSERT', 'Data Admin'). "</option>";
								}

								if ( isset($modes['update']) && $modes['update'] == true ) {
									print "<option value='update'>". __('UPDATE only', 'Data Admin') . "</option>";
								}

								if ( isset($modes['insert']) && $modes['insert'] == true ) {
									print "<option value='insert'>". __('INSERT only', 'Data Admin'). "</option>";
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php print __("Column Order", 'Data Admin'); ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="columnOrder" id="columnOrder" class="standardWidth">
							<option value="guess"><?php print __('Best Guess', 'Data Admin') ?></option>
							<?php if ($result->rowCount() > 0) : ?>
								<option value="last" selected><?php print __('From Last Import', 'Data Admin') ?></option>
							<?php endif; ?>
							<option value="linearplus"><?php print __('From Export Data', 'Data Admin') ?></option>
							<option value="linear"><?php print __('Same as Below', 'Data Admin') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'>
						<b><?php print __('CSV File', 'Data Admin') ?> *</b><br/>
						<span class="emphasis small"><?php print __('See Notes below for specification.', 'Data Admin') ?></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars" accept=".csv,.xls,.xlsx,.xml,.ods">
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php print __('Field Delimiter', 'Data Admin') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php print __('String Enclosure', 'Data Admin') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __("denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input id="submitStep1" type="submit" value="<?php print __("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>


		<h4>
			<?php print __('Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php print __('Always include a header row in the CSV file.', 'Data Admin') ?></li>
			<li><?php print __('You may only submit CSV files.') ?></li>
			<li><?php print __('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
		</ol>
	<?php

	if ( isActionAccessible($guid, $connection2, "/modules/Data Admin/export_run.php") ) {
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/export_run.php?type=$type'>" .  __('Export Structure', 'Data Admin') . "<img style='margin-left: 5px' title='" . __('Export Structure', 'Data Admin'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
		print "</div>" ;
	}

	print "<table class='smallIntBorder fullWidth colorOddEven' cellspacing='0'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 20px;'>" ;
			print "</th>" ;
			print "<th style='width: 180px;'>" ;
				print __("Name") ;
			print "</th>" ;
			print "<th >" ;
				print __("Description") ;
			print "</th>" ;
			print "<th style='width: 100px;'>" ;
				print __("Type") ;
			print "</th>" ;
		print "</tr>" ;


		if ( !empty($importType->getTableFields()) ) {

			$count = 1;
			foreach ($importType->getTableFields() as $fieldName ) {

				if ( $importType->isFieldHidden($fieldName) ) {
					$count++;
					continue;
				}

				print "<tr>" ;
					print "<td>" . $count. "</td>" ;
					print "<td>";
						 print $importType->getField($fieldName, 'name');
						 if ( $importType->isFieldRequired($fieldName) == true ) {
						 	print " <strong class='highlight'>*</strong>";
						 }
					print "</td>" ;
					print "<td><em>" . $importType->getField($fieldName, 'desc'). "</em></td>" ;
					print "<td>";
						print $importType->readableFieldType($fieldName);
					print "</td>" ;
				print "</tr>" ;
				$count++;
			}

		}
	print "</table><br/>" ;
	}

	//STEP 2, CONFIG -----------------------------------------------------------------------------------
	else if ($step==2) {

		// print "<pre>";
		// print_r($_POST);
		// print "</pre>";


		print "<h2>";
		print __('Step 2 - Data Check & Confirm', 'Data Admin');
		print "</h2>";

		$mode = (isset($_POST['mode']))? $_POST['mode'] : NULL;

		//Check file type
		if ($importer->isValidMimeType($_FILES['file']['type']) == false) {
			print "<div class='error'>";
			printf(__('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.', 'Data Admin'), $_FILES['file']['type']);
			print "<br/></div>";
		}
		else if ( empty($_POST["fieldDelimiter"]) OR empty($_POST["stringEnclosure"])) {
			print "<div class='error'>";
			print __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.', 'Data Admin');
			print "<br/></div>";
		}
		else if ($mode != "sync" AND $mode != "insert" AND $mode != "update") {
			print "<div class='error'>";
			print __('Import cannot proceed, as the "Mode" field has been left blank.', 'Data Admin');
			print "<br/></div>";
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
					print "<div class='error'>" . $e->getMessage() . "</div>" ;
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
				print "<div class='error'>";
				print __('Import cannot proceed, the file type cannot be read.', 'Data Admin');
				print "<br/></div>";
				return;
			}


			print "<script>";
			print "var csvFirstLine = " . json_encode($firstLine) .";";
			print "var columnDataSkip = " . Importer::COLUMN_DATA_SKIP .";";
			print "var columnDataCustom = " . Importer::COLUMN_DATA_CUSTOM .";";
			print "var columnDataFunction = " . Importer::COLUMN_DATA_FUNCTION .";";
			print "</script>";

			print "<form method='post' action='". $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=$type&step=3' enctype='multipart/form-data'>";

			if ($mode == "sync" || $mode == "update") {


				$lastFieldValue = ($columnOrder == 'last' && isset($columnOrderLast['syncField']))? $columnOrderLast['syncField'] : 'N';
				$lastColumnValue = ($columnOrder == 'last' && isset($columnOrderLast['syncColumn']))? $columnOrderLast['syncColumn'] : '';

				if ($columnOrder == 'linearplus') {
					$lastFieldValue = 'Y';
					$lastColumnValue = $importType->getPrimaryKey();
				}

				print "<table class='smallIntBorder fullWidth' cellspacing='0'>" ;

				print "<tr>" ;
					print "<td>";
						print "<strong>";
						print __("Use database ID field?", 'Data Admin');
						print "</strong><br/>";

						print "<span class='emphasis small'>";
						print __("Only entries with a matching database ID will be updated.", 'Data Admin');
						print "</span>";
					print "</td>";
					print "<td class='right standardWidth'>";

					print "<input type='radio' name='syncField' value='Y' ". (($lastFieldValue == 'Y')? 'checked' : '') ."> " . __('Yes') .' ';
					print "<input type='radio' name='syncField' value='N' ". (($lastFieldValue == 'N')? 'checked' : '') ."> " . __('No');

					print "</td>";
				print "</tr>" ;

				print "<tr class='syncDetails'>" ;
					print "<td>";
					printf( __("Sync field %s with CSV column:", 'Data Admin'), '<code>'.$importType->getPrimaryKey().'</code>' );
					print "</td>";

					print "<td>";
						print "<select name='syncColumn' class='standardWidth'>";
						print "<option value='0' ". (($lastColumnValue == 0)? 'selected' : '') ."> </option>";

						foreach ($headings as $i => $name) {
							printf("<option value='%s' %s>%s</option>", $name, ($lastColumnValue == $name)? 'selected' : '', $name);
						}
						print "</select>" ;
					print "</td>";
				print "</tr>" ;
				print "</table><br/>" ;
				?>
				<script type="text/javascript">
					$(document).ready(function(){

						if ($('input[name=syncField]:checked').val() != "Y" ) {
							$(".syncDetails").css("display","none");
						}

						$("input[name=syncField]").click(function(){
							if ($('input[name=syncField]:checked').val()=="Y" ) {
								$(".syncDetails").slideDown("slow", $(".syncDetails").css("display","table-row"));

							} else {
								$(".syncDetails").css("display","none");
							}
						});
					});
				</script>
				<?php
			}

			// Import Restrictions & Unique Key Info
			$importRestrictions = array();

			if (!empty($importType->getUniqueKeys())) {
				foreach ($importType->getUniqueKeys() as $key) {

					if (is_array($key)) {
						$keyNames = array();
						foreach(array_reverse($key) as $keyName) {
							$keyNames[] = $importType->getField($keyName, 'name');
						}
						$importRestrictions[] = __('Unique', 'Data Admin').' '. implode(__(' for each ', 'Data Admin'), $keyNames);
					} else {
						$importRestrictions[] = $importType->getField($key, 'name') .' ' . __('must be unique', 'Data Admin');
					}
				}
			}

			foreach ($importType->getTableFields() as $fieldName) {

				if ( $importType->isFieldHidden($fieldName) ) continue; // Skip hidden fields

				if ($importType->isFieldRelational($fieldName)) {

					extract( $importType->getField($fieldName, 'relationship') );
					$field = (is_array($field))? implode(', ', $field) : $field;

					$importRestrictions[] = sprintf( __('Each %s should match the %s of a %s', 'Data Admin'),
						$importType->getField($fieldName, 'name'), $field, $table
					);
				}

				if ($importType->getField($fieldName, 'type') == 'enum') {

					$importRestrictions[] = sprintf( __('%s must be one of: %s', 'Data Admin'),
						$importType->getField($fieldName, 'name'),
						implode(', ', $importType->getField($fieldName, 'elements'))
					);

				}

				if ($importType->getField($fieldName, 'filter') == 'email') {
					$importRestrictions[] = sprintf( __('%s must be a valid email address'), $importType->getField($fieldName, 'name'), $_SESSION[$guid]['module'] );
				}

				if ($importType->getField($fieldName, 'filter') == 'url') {
					$importRestrictions[] = sprintf( __('%s must be a valid url'), $importType->getField($fieldName, 'name'), $_SESSION[$guid]['module'] );
				}
			}


			if (!empty($importRestrictions)) {

				print "<table class='smallIntBorder fullWidth' cellspacing='0'>" ;

				print "<tr>" ;
					print "<th colspan=2>";
						print "<strong>";
						print __("Import Restrictions", 'Data Admin');
						print "</strong>";
					print "</th>" ;
				print "</tr>" ;

				foreach ($importRestrictions as $count => $restriction) {
					print "<tr>" ;
						print "<td style='width: 20px'>". ($count + 1) .".</td>";
						print "<td>";
						print $restriction;
						print "</td>" ;
					print "</tr>" ;
				}

				print "</table><br/>" ;
			}

			print "<table class='fullWidth colorOddEven' cellspacing='0'>" ;
			print "<tr class='head'>" ;
				print "<th >" ;
					print __("Field Name", 'Data Admin') ;
				print "</th>" ;
				print "<th style='width: 120px;'>" ;
					print __("Type", 'Data Admin') ;
				print "</th>" ;
				print "<th style='width:215px;'>" ;
					print __("Column", 'Data Admin') ;
				print "</th>" ;
				print "<th style='width:185px;'>" ;
					print __("Sample", 'Data Admin') ;
				print "</th>" ;

			print "</tr>" ;

			if ( !empty($importType->getTableFields()) ) {

				$count = 0;
				foreach ($importType->getTableFields() as $fieldName ) {

					if ( $importType->isFieldHidden($fieldName) ) {
						$columnIndex = Importer::COLUMN_DATA_HIDDEN;

						if ($importType->isFieldLinked($fieldName)) $columnIndex = Importer::COLUMN_DATA_LINKED;
						if (!empty($importType->getField($fieldName, 'function'))) $columnIndex = Importer::COLUMN_DATA_FUNCTION;

						print "<input type='hidden' id='col[$count]' name='columnOrder[$count]' value='".$columnIndex."'>";
						$count++;
						continue;
					}

					print "<tr>" ;
						print "<td>";
							 printf("<span title='%s'>%s</span>", $importType->getField($fieldName, 'desc'), $importType->getField($fieldName, 'name') );
							 if ( $importType->isFieldRequired($fieldName) ) {
							 	print " <strong class='highlight'>*</strong>";
							 }

							 if ( $importType->isFieldUniqueKey($fieldName) ) {
							 	print "<img title='" . __('Unique Key', 'Data Admin') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/target.png'/ style='float: right; width:12px; height:12px;margin-left:4px;'>";
							 }

							 if ( $importType->isFieldRelational($fieldName) ) {
							 	print "<img title='" . __('Relational', 'Data Admin') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/refresh.png'/ style='float: right; width:10px; height:10px;margin-left:4px;'>";
							 }
						print "</td>" ;

						print "<td>";
							print $importType->readableFieldType($fieldName);
						print "</td>" ;

						print "<td colspan='2'>";
							print "<select id='col[$count]' class='columnOrder' name='columnOrder[$count]' style='width:190px;float:left;'>";
							print "<option value=''></option>";
							$lastImportValue = ($columnOrder == 'last' && isset($columnOrderLast[$count]))? $columnOrderLast[$count] : '';
							// Allow users to skip non-required columns
							if ( $importType->isFieldRequired($fieldName) == false ) {
								$selectThis = ($lastImportValue == Importer::COLUMN_DATA_SKIP)? 'selected' : '';
								print "<option value='".Importer::COLUMN_DATA_SKIP."' $selectThis>[ Skip this Column ]</option>";
							}

							// Allow users to enter a value manually
							if ( $importType->getField($fieldName, 'custom')) {
								$selectThis = ($lastImportValue == Importer::COLUMN_DATA_CUSTOM)? 'selected' : '';
								print "<option value='".Importer::COLUMN_DATA_CUSTOM."' $selectThis>[ Custom Value ]</option>";
							}

							// Allow users to enter a value manually
							if ( $importType->getField($fieldName, 'function') ) {
								$selectThis = ($lastImportValue == Importer::COLUMN_DATA_FUNCTION)? 'selected' : '';
								print "<option value='".Importer::COLUMN_DATA_FUNCTION."' data-function='". $importType->getField($fieldName, 'function') ."' $selectThis>[ Generate Value ]</option>";
							}

							$selectCount = 0;

							foreach ($headings as $i => $columnName) {

								if ($columnOrder == 'linear' || $columnOrder == 'linearplus') {
									$selected = ($columnOrder == 'linearplus')? ($i == $count+1) : ($i == $count);
								}
								else if ($columnOrder == 'guess' ) {
									$selected = ($columnName == $fieldName) || ($columnName == $importType->getField($fieldName, 'name') ) || (mb_strtolower($columnName) == mb_strtolower($fieldName) ) || (mb_strtolower($columnName) == mb_strtolower($importType->getField($fieldName, 'name')) );
									// if (!$selected) {
									// 	similar_text( mb_strtoupper( $importType->getField($fieldName, 'name') ), mb_strtoupper($columnName), $similarity);
									// 	$selected = ceil($similarity) > 85;
									// }
								}
								else if ($columnOrder == 'last' && isset($columnOrderLast[$count])) {
									$selected = ($i == $columnOrderLast[$count]);
								}

								if ($selected) $selectCount++;


								printf("<option value='%s' %s>%s</option>", $i, ($selected && $selectCount <= 1)? 'selected' : '', $columnName);
							}

							print "</select>" ;

							print "<input type='text' class='columnText' name='columnText[$count]' readonly disabled/>";

							print "<script type='text/javascript'>";
							print "var col$count = new LiveValidation('col[$count]'); col$count.add(Validate.Presence);";
							print "</script>";
						print "</td>" ;

					print "</tr>" ;
					$count++;
				}

			}
		print "</table><br/>" ;

		?>
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr>
					<td colspan="2">
						<?php print __("CSV Data:", 'Data Admin') ; ?><br/>
						<textarea name="csvData" cols="92" rows="5" readonly><?php print $csvData; ?></textarea>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __("denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="mode" id="mode" value="<?php print $mode; ?>" type="hidden">
						<input name="fieldDelimiter" id="fieldDelimiter" value="<?php print urlencode($_POST["fieldDelimiter"]); ?>" type="hidden">
						<input name="stringEnclosure" id="stringEnclosure" value="<?php print urlencode($_POST["stringEnclosure"]); ?>" type="hidden">
						<input id="ignoreErrors" name="ignoreErrors" value="0" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input id="submitStep2" type="submit" value="<?php print __("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<br/>

		<?php
		}
	}

	//STEP 3 & 4, DRY & LIVE RUN  -----------------------------------------------------------------------------------
	else if ($step==3 || $step==4) {

		// print "<pre>";
		// print_r($_POST);
		// print "</pre>";

		print "<h2>";
		print ($step==3)? __('Step 3 - Dry Run', 'Data Admin') : __('Step 4 - Live Run', 'Data Admin');
		print "</h2>";

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
			print "<div class='error'>";
			print __("Your request failed because your inputs were invalid.") ;
			print "<br/></div>";
			return;
		}
		else if ($mode != "sync" AND $mode != "insert" AND $mode != "update") {
			print "<div class='error'>";
			print __('Import cannot proceed, as the "Mode" field has been left blank.', 'Data Admin');
			print "<br/></div>";
		}
		else if ( ($mode == 'sync' || $mode == 'update') && (!empty($syncField) && $syncColumn < 0 ) ) {
			print "<div class='error'>";
			print __("Your request failed because your inputs were invalid.") ;
			print "<br/></div>";
			return;
		}
		else if ( empty($fieldDelimiter) OR empty($stringEnclosure) ) {
			print "<div class='error'>";
			print __('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.', 'Data Admin');
			print "<br/></div>";
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

				print "<div class='success'>";
				if ($step == 3) {
					print __('The data was successfully imported and validated. No changes have been made to the database.', 'Data Admin');
				} else {
					print __('The import completed successfully and all relevant database fields have been created and/or updated.', 'Data Admin');
				}
				print "</div>";

			} else {

				print "<div class='error'>";
					print $importer->getLastError();
				print "</div>";
			}

			$logs = $importer->getLogs();

			if (count($logs) > 0) {
				print "<table class='smallIntBorder fullWidth colorOddEven' cellspacing='0'>" ;
					print "<tr class='head'>" ;
						print "<th style='width: 40px;'>" ;
							print __("Row", 'Data Admin') ;
						print "</th>" ;
						print "<th style='width: 200px;'>" ;
							print __("Field", 'Data Admin') ;
						print "</th>" ;
						print "<th >" ;
							print __("Message", 'Data Admin') ;
						print "</th>" ;
					print "</tr>" ;

					foreach ($logs as $log ) {
						print "<tr class='".$log['type']."'>" ;
							print "<td>" . $log['row'] . "</td>";
							print "<td>";
								print $log['field_name'];
								print ($log['field'] >= 0)? " (". $log['field'] .")" : "";
							print "</td>";
							print "<td>" . $log['info'] . "</td>";
						print "</tr>" ;
					}

				print "</table><br/>" ;
			}

			$executionTime = mb_substr( microtime(true) - $timeStart, 0, 6 ).' sec';
    		$memoryUsage = readableFileSize(  max( 0, memory_get_usage() - $memoryStart )  );

			?>

			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php print "class='". ( ($importSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right"  width="50%">
						<?php print __("Reading CSV file", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print ($importSuccess)? __("Success") : __("Failed"); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Execution time", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print $executionTime; ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Memory usage", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php

						print $memoryUsage;
						?>
					</td>
				</tr>
			</table><br/>
			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php print "class='". ( ($buildSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right" width="50%">
						<?php print __("Validating data", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print ($buildSuccess)? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Rows processed", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print $importer->getRowCount(); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Rows with errors", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print $importer->getErrorRowCount(); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Total errors", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print $importer->getErrorCount(); ?>
					</td>
				</tr>
				<?php if ($importer->getWarningCount() > 0) : ?>
				<tr>
					<td class="right">
						<?php print __("Total warnings", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print $importer->getWarningCount(); ?>
					</td>
				</tr>
				<?php endif; ?>
			</table><br/>

			<table class='smallIntBorder' cellspacing='0' style="margin: 0 auto; width: 60%;">
				<tr <?php print "class='". ( ($databaseSuccess)? 'current' : 'error' ) ."'"; ?>>
					<td class="right" width="50%">
						<?php print __("Querying database", 'Data Admin').": "; ?>
					</td>
					<td>
						<?php print ($databaseSuccess)? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
					</td>
				</tr>
				<tr>
					<td class="right">
						<?php print __("Database Inserts", 'Data Admin').": ";?>
					</td>
					<td>
					<?php
						print $importer->getDatabaseResult('inserts');
						if ($importer->getDatabaseResult('inserts_skipped') > 0) {
							print " (". $importer->getDatabaseResult('inserts_skipped') ." ". __("skipped", 'Data Admin') .")";
						}
					?>
					</td>
				</tr>

				<tr>
					<td class="right">
						<?php print __("Database Updates").": "; ?>
					</td>
					<td>
					<?php print $importer->getDatabaseResult('updates');
						if ($importer->getDatabaseResult('updates_skipped') > 0) {
							print " (". $importer->getDatabaseResult('updates_skipped') ." ". __("skipped", 'Data Admin') .")";
						}
					?>
					</td>
				</tr>


			</table><br/>

			<?php if ($step==3) : ?>

			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_run.php&type=$type&step=4" ?>" enctype="multipart/form-data">

				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td colspan=2>
							<?php print __("CSV Data:", 'Data Admin') ; ?><br/>
							<textarea name="csvData" cols="92" rows="5" readonly><?php print $csvData; ?></textarea>
						</td>
					</tr>
					<tr>
						<?php if (!$overallSuccess) : ?>
							<td>
								<input type="checkbox" id="ignoreErrors" name="ignoreErrors" value="<?php echo $ignoreErrors; ?>">
								<span class="emphasis small"><?php echo __('Ignore Errors? (Expert Only!)', 'Data Admin'); ?><span>
							</td>
						<?php endif; ?>

						<td class="right">
							<input name="mode" id="mode" value="<?php print $mode; ?>" type="hidden">
							<input name="syncField" id="syncField" value="<?php print $syncField; ?>" type="hidden">
							<input name="syncColumn" id="syncColumn" value="<?php print $syncColumn; ?>" type="hidden">
							<input name="columnOrder" id="columnOrder" value="<?php print htmlentities(serialize($columnOrder)); ?>" type="hidden">
							<input name="columnText" id="columnText" value="<?php print htmlentities(serialize($columnText)); ?>" type="hidden">
							<input name="fieldDelimiter" id="fieldDelimiter" value="<?php print urlencode($fieldDelimiter); ?>" type="hidden">
							<input name="stringEnclosure" id="stringEnclosure" value="<?php print urlencode($stringEnclosure); ?>" type="hidden">
							<input name="address" type="hidden" value="<?php print $_SESSION[$guid]["address"] ?>">
							<?php if (!$overallSuccess && !$ignoreErrors) : ?>
								<input id="submitStep3" type="submit" value="<?php print __("Cannot Continue", 'Data Admin') ; ?>" disabled>
							<?php else : ?>
								<input id="submitStep3" type="submit" value="<?php print __("Submit") ; ?>"  >
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</form>
			<?php endif; ?>


			<?php

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
?>
