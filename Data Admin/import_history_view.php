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

use Modules\DataAdmin\ImportType;

// Module Bootstrap
require __DIR__ . '/module.php';

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/import_history_view.php")==false) {
    //Acess denied
    echo "<div class='error'>" ;
    echo __("You do not have access to this action.") ;
    echo "</div>" ;
} else {
    $gibbonLogID = (isset($_GET['gibbonLogID']))? $_GET['gibbonLogID'] : -1;

    $data = array('gibbonLogID' => $gibbonLogID);
    $sql = "SELECT gibbonLog.*, gibbonPerson.username, gibbonPerson.surname, gibbonPerson.preferredName 
            FROM gibbonLog
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonLog.gibbonPersonID) 
            WHERE gibbonLog.gibbonLogID=:gibbonLogID";
    $result=$pdo->executeQuery($data, $sql);

    if ($result->rowCount() < 1) {
        echo "<div class='error'>" ;
        echo __("There are no records to display.") ;
        echo "</div>" ;
    } else {
        $importLog = $result->fetch();
        $importData = isset($importLog['serialisedArray'])? unserialize($importLog['serialisedArray']) : [];
        $importResults = $importData['results'] ?? [];

        if (empty($importResults) || !isset($importData['type'])) {
            echo "<div class='error'>" ;
            echo __("There are no records to display.") ;
            echo "</div>" ;
            return;
        }

        $importType = ImportType::loadImportType($importData['type'], $pdo); ?>
		<h1>
			<?php echo __('Import History', 'Data Admin'); ?>
		</h1>

		<?php if (!empty($importResults['ignoreErrors'])) : ?>
			<div class="warning">
				<?php echo __("Imported with errors ignored."); ?>
			</div>
		<?php endif; ?>

		<table class='blank fullWidth' cellspacing='0'>	
			<tr>
				<td width="50%">
					<?php echo __("Import Type", 'Data Admin').": "; ?><br/>
					<?php echo $importType->getDetail('name'); ?>
				</td>
				<td width="50%">
					<?php echo __("Date").": "; ?><br/>
					<?php printf("<span title='%s'>%s</span>", $importLog['timestamp'], date('F j, Y, g:i a', strtotime($importLog['timestamp']))); ?>
				</td>
			</tr>
			<tr>
				<td width="50%">
					<?php echo __("Details").": "; ?><br/>
					<?php echo ($importData['success'])? __("Success") : __("Failed"); ?>
				</td>
				<td width="50%">
					<?php echo __("User").": "; ?><br/>
					<?php printf("<span title='%s'>%s %s</span>", $importLog['username'], $importLog['preferredName'], $importLog['surname']); ?>
				</td>
			</tr>
		</table>
		<br/>

		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php echo "class='". (($importResults['importSuccess'])? 'current' : 'error') ."'"; ?>>
				<td class="right"  width="50%">
					<?php echo __("Reading Spreadsheet", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo ($importResults['importSuccess'])? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Execution time", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['executionTime']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Memory usage", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['memoryUsage']; ?>
				</td>
			</tr>
		</table><br/>
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php echo "class='". (($importResults['buildSuccess'])? 'current' : 'error') ."'"; ?>>
				<td class="right" width="50%">
					<?php echo __("Validating data", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo ($importResults['buildSuccess'])? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Rows processed", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['rows']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Rows with errors", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['rowerrors']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Total errors", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['errors']; ?>
				</td>
			</tr>
			<?php if ($importResults['warnings'] > 0) : ?>
			<tr>
				<td class="right">
					<?php echo __("Total warnings", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo $importResults['warnings']; ?>
				</td>
			</tr>
			<?php endif; ?>
		</table><br/>

		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php echo "class='". (($importResults['databaseSuccess'])? 'current' : 'error') ."'"; ?>>
				<td class="right" width="50%">
					<?php echo __("Querying database", 'Data Admin').": "; ?>
				</td>
				<td>
					<?php echo ($importResults['databaseSuccess'])? __("Success", 'Data Admin') : __("Failed", 'Data Admin'); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php echo __("Database Inserts", 'Data Admin').": "; ?>
				</td>
				<td>
				<?php
                    echo $importResults['inserts'];
        if ($importResults['inserts_skipped'] > 0) {
            echo " (". $importResults['inserts_skipped'] ." ". __("skipped", 'Data Admin') .")";
        } ?>
				</td>
			</tr>

			<tr>
				<td class="right">
					<?php echo __("Database Updates", 'Data Admin').": "; ?>
				</td>
				<td>
				<?php
                    echo $importResults['updates'];
        if ($importResults['updates_skipped'] > 0) {
            echo " (". $importResults['updates_skipped'] ." ". __("skipped", 'Data Admin') .")";
        } ?>
				</td>
			</tr>

			
		</table><br/>


	<?php
    }
}
?>
