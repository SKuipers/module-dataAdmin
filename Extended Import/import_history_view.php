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

@session_start() ;

//Module includes
require_once "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Extended Import/import_history_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//New PDO DB connection
	$pdo = new Gibbon\sqlConnection();
	$connection2 = $pdo->getConnection();

	//Class includes
	require_once "./modules/" . $_SESSION[$guid]["module"] . "/src/importType.class.php" ;

	$importLogID = (isset($_GET['importLogID']))? $_GET['importLogID'] : -1;

	$data = array( 'importLogID' => $importLogID );
	$sql="SELECT importResults, type, success, timestamp, UNIX_TIMESTAMP(timestamp) as unixtime, username, surname, preferredName FROM extendedImportLog, gibbonPerson WHERE gibbonPerson.gibbonPersonID=importLog.gibbonPersonID AND importLogID=:importLogID";
	$result=$pdo->executeQuery($data, $sql);

	if ( $result->rowCount() < 1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;

	} else {
		$importLog = $result->fetch();
		$importResults = (isset($importLog['importResults']))? unserialize($importLog['importResults']) : array();

		if (empty($importResults) || !isset($importLog['type'])) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
			return;
		}

		$importType = ExtendedImport\importType::loadImportType( $importLog['type'] );
	?>
		<h1>
			<?php print __($guid, 'Import History'); ?>
		</h1>

		<table class='blank fullWidth' cellspacing='0'>	
			<tr>
				<td width="50%">
					<?php print __($guid, "Import Type").": "; ?><br/>
					<?php print $importType->getDetail('name'); ?>
				</td>
				<td width="50%">
					<?php print __($guid, "Date").": "; ?><br/>
					<?php printf( "<span title='%s'>%s</span>", $importLog['timestamp'], date('F j, Y, g:i a', $importLog['unixtime']) ); ?>
				</td>
			</tr>
			<tr>
				<td width="50%">
					<?php print __($guid, "Details").": "; ?><br/>
					<?php print ($importLog['success'])? __($guid, "Success") : __($guid, "Failed"); ?>
				</td>
				<td width="50%">
					<?php print __($guid, "User").": "; ?><br/>
					<?php printf( "<span title='%s'>%s %s</span>", $importLog['username'], $importLog['preferredName'], $importLog['surname'] ); ?>
				</td>
			</tr>
		</table>
		<br/>

		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php print "class='". ( ($importResults['importSuccess'])? 'current' : 'error' ) ."'"; ?>>
				<td class="right"  width="50%">
					<?php print __($guid, "Reading CSV file").": "; ?>
				</td>
				<td>
					<?php print ($importResults['importSuccess'])? __($guid, "Success") : __($guid, "Failed"); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Execution time").": "; ?>
				</td>
				<td>
					<?php print $importResults['executionTime']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Memory usage").": "; ?>
				</td>
				<td>
					<?php print $importResults['memoryUsage']; ?>
				</td>
			</tr>
		</table><br/>
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php print "class='". ( ($importResults['buildSuccess'])? 'current' : 'error' ) ."'"; ?>>
				<td class="right" width="50%">
					<?php print __($guid, "Validating data").": "; ?>
				</td>
				<td>
					<?php print ($importResults['buildSuccess'])? __($guid, "Success") : __($guid, "Failed"); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Rows processed").": "; ?>
				</td>
				<td>
					<?php print $importResults['rows']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Rows with errors").": "; ?>
				</td>
				<td>
					<?php print $importResults['rowerrors']; ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Total errors").": "; ?>
				</td>
				<td>
					<?php print $importResults['errors']; ?>
				</td>
			</tr>
			<?php if ($importResults['warnings'] > 0) : ?>
			<tr>
				<td class="right">
					<?php print __($guid, "Total warnings").": "; ?>
				</td>
				<td>
					<?php print $importResults['warnings']; ?>
				</td>
			</tr>
			<?php endif; ?>
		</table><br/>

		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr <?php print "class='". ( ($importResults['databaseSuccess'])? 'current' : 'error' ) ."'"; ?>>
				<td class="right" width="50%">
					<?php print __($guid, "Querying database").": "; ?>
				</td>
				<td>
					<?php print ($importResults['databaseSuccess'])? __($guid, "Success") : __($guid, "Failed"); ?>
				</td>
			</tr>
			<tr>
				<td class="right">
					<?php print __($guid, "Database Inserts").": ";?>
				</td>
				<td>
				<?php 
					print $importResults['inserts'];
					if ($importResults['inserts_skipped'] > 0) {
						print " (". $importResults['inserts_skipped'] ." ". __($guid, "skipped") .")";
					}
				?>
				</td>
			</tr>

			<tr>
				<td class="right">
					<?php print __($guid, "Database Updates").": "; ?>
				</td>
				<td>
				<?php 
					print $importResults['updates'];
					if ($importResults['updates_skipped'] > 0) {
						print " (". $importResults['updates_skipped'] ." ". __($guid, "skipped") .")";
					}
				?>
				</td>
			</tr>

			
		</table><br/>


	<?php
	}

}	
?>