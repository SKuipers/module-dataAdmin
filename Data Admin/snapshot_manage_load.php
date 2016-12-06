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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/snapshot_manage_load.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {

	print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Data Admin/snapshot_manage.php'>" . __($guid, 'Manage Snapshots') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Load Snapshot') . "</div>" ;
	print "</div>" ;

	print "<h3>" ;
	print __($guid, "Load Snapshot") ;
	print "</h3>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

	print "<div class='warning'>" ;
	print __($guid, 'Loading a snapshot is a HIGHLY DESTRUCTIVE operation. It will overwrite all data in Gibbon. Do not proceed unless you are absolutly certain you know what you\'re doing.');
	print "</div>" ;
	
	//Check if file exists
	$filename=(isset($_GET["file"]))? $_GET["file"] : '' ;
	if ($filename=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {

		$snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
		$snapshotFolder = '/'.trim($snapshotFolder, '/ ');

		$snapshotFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads'.$snapshotFolder;
		$filepath = $snapshotFolderPath.'/'.$filename;

		if ( !file_exists( $filepath ) ) {
			print "<div class='error'>" ;
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/snapshot_manage_loadProcess.php?file=$filename" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php print __($guid, 'Are you sure you want to load this snapshot? It will replace all data in Gibbon with the selected SQL file.') ; ?></b><br/>&nbsp;<br/>
							<span style="font-size: 120%; color: #cc0000"><i><?php print __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></span>
						</td>
						<td class="right">
							
						</td>
					</tr>
					<tr>
						<td> 
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, 'Yes') ; ?>">
						</td>
						<td class="right">
							
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}	
?>