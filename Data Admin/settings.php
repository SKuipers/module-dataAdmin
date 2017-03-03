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

if (isActionAccessible($guid, $connection2, "/modules/Data Admin/settings.php") == FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {

	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Import Settings', 'Data Admin') . "</div>" ;
	print "</div>" ;

	if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

	$trueIcon = "<img title='" . __($guid, 'Yes'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png' width=16 height=16 />";
	$falseIcon = "<img title='" . __($guid, 'No'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png' width=16 height=16 />";

	// Include the module version info, with required versions
	include $_SESSION[$guid]['absolutePath'].'/modules/'.$_SESSION[$guid]['module'].'/version.php';
	?>

	<table class='smallIntBorder' cellspacing='0' style='width:60%;margin:0 auto;'>
		<tr class="break" style="line-height:20px;">
			<td>
				<?php echo __($guid, 'Compatability Check', 'Data Admin'); ?>
			</td>
			<td class="right">
				<?php echo $_SESSION[$guid]['module'].' '.$moduleVersion; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, '%s version %s or higher', 'Data Admin'), 'Gibbon', mb_strstr($gibbonVersionRequired, '.', true) ); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					echo '<span style="margin-right:20px;">Gibbon '.$version.'</span>';
					echo (version_compare($version, $gibbonVersionRequired, '>='))? $trueIcon : $falseIcon;
				?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, '%s version %s or higher', 'Data Admin'), 'PHP', $phpVersionRequired); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$phpVersion = phpversion();

					echo '<span style="margin-right:20px;">PHP '.$phpVersion.'</span>';
					echo (version_compare($phpVersion, $phpVersionRequired, '>='))? $trueIcon : $falseIcon;

				?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, '%s version %s or higher', 'Data Admin'), 'MySQL', $mysqlVersionRequired); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$mysqlVersion = $pdo->executeQuery(array(), 'select version()')->fetchColumn();

					echo '<span style="margin-right:20px;">MySQL '.$mysqlVersion.'</span>';
					echo (version_compare($mysqlVersion, $mysqlVersionRequired, '>='))? $trueIcon : $falseIcon;

				?>
			</td>
		</tr>

		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, 'Extension %s enabled', 'Data Admin'), 'php_zip'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('zip'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, 'Extension %s enabled', 'Data Admin'), 'php_xml'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('xml'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, 'Extension %s enabled', 'Data Admin'), 'php_gd'); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php echo (extension_loaded('gd'))? $trueIcon : $falseIcon; ?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, '%s is writeable', 'Data Admin'), __($guid,'Custom Imports Folder', 'Data Admin') ); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$importsFolder = getSettingByScope($connection2, 'Data Admin', 'importCustomFolderLocation');
					$importsFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads/'.trim($importsFolder, '/ ');

					echo (is_writable($importsFolderPath))? $trueIcon : $falseIcon;
				?>
			</td>
		</tr>
		<tr>
			<td style="width: 275px">
				<b><?php printf( __($guid, '%s is writeable', 'Data Admin'), __($guid,'Snapshots Folder', 'Data Admin') ); ?></b><br>
				<span class="emphasis small"></span>
			</td>
			<td class="right">
				<?php
					$snapshotFolder = getSettingByScope($connection2, 'Data Admin', 'exportSnapshotsFolderLocation');
					$snapshotFolderPath = $_SESSION[$guid]["absolutePath"].'/uploads/'.trim($snapshotFolder, '/ ');

					echo (is_writable($snapshotFolderPath))? $trueIcon : $falseIcon;
				?>
			</td>
		</tr>

	</table></br>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/settingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Data Admin' AND name='exportDefaultFileType'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay'], 'Data Admin') ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Data Admin');}?></span>
				</td>
				<td class="right">
                    <select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                    	<option <?php if ($row['value'] == 'Excel2007') { echo 'selected '; } ?>value="Excel2007"><?php echo __($guid, 'Excel 2007 and above (.xlsx)', 'Data Admin') ?></option>
                        <option <?php if ($row['value'] == 'Excel5') { echo 'selected '; } ?>value="Excel5"><?php echo __($guid, 'Excel 95 and above (.xls)', 'Data Admin') ?></option>
                        <option <?php if ($row['value'] == 'OpenDocument') { echo 'selected '; } ?>value="OpenDocument"><?php echo __($guid, 'OpenDocument (.ods)', 'Data Admin') ?></option>
                        <option <?php if ($row['value'] == 'CSV') { echo 'selected '; } ?>value="CSV"><?php echo __($guid, 'Comma Separated (.csv)', 'Data Admin') ?></option>
                    </select>
                </td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Data Admin' AND name='enableUserLevelPermissions'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay'], 'Data Admin') ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Data Admin');}?></span>
				</td>
				<td class="right">
                    <select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
                        <option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
                    </select>
                </td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Data Admin' AND name='importCustomFolderLocation'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay'], 'Data Admin') ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Data Admin');}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" value="<?php echo $row['value'] ?>" class="standardWidth">
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Data Admin' AND name='exportSnapshotsFolderLocation'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay'], 'Data Admin') ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description'], 'Data Admin');}?></span>
				</td>
				<td class="right">
					<input type='text' name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" value="<?php echo $row['value'] ?>" class="standardWidth">
					<script type="text/javascript">
						var <?php echo $row['name'] ?>=new LiveValidation('<?php echo $row['name'] ?>');
						<?php echo $row['name'] ?>.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>

	<?php
}
?>
