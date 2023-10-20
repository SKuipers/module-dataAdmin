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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name="Data Admin" ; //The name of the variable as it appears to users. Needs to be unique to installation. Also the name of the folder that holds the unit.
$description="Provides additional import functionality for migrating existing data." ; //Short text description
$entryURL="import_manage.php" ; //The landing page for the unit, used in the main menu
$type="Additional" ; //Do not change.
$category="Admin" ; //The main menu area to place the module in
$version="1.7.02" ; //Version number
$author="Sandra Kuipers" ; //Your name
$url="https://github.com/SKuipers/" ; //Your URL

//Module tables
 $moduleTables[0]="CREATE TABLE `dataAdminImportLog` (
  `importLogID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonPersonID` int(10) unsigned zerofill NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` varchar(30) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `importResults` text NOT NULL,
  `columnOrder` text NOT NULL,
  PRIMARY KEY (`importLogID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;" ;

//gibbonSettings entries
$gibbonSetting[0]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'enableUserLevelPermissions', 'Enable User-level Permissions', 'Restrict user import and export based on their user role permissions.', 'Y');";
$gibbonSetting[1]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'importCustomFolderLocation', 'Custom Imports Folder', 'Path to custom import types folder, relative to uploads.', '/imports');";
$gibbonSetting[2]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'exportSnapshotsFolderLocation', 'Snapshots Folder', 'Path to database snapshots folder, relative to uploads.', '/snapshots');";
$gibbonSetting[3]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'exportDefaultFileType', 'Default Export File Type', '', 'Excel2007');";

//Action rows

$actionRows[0]["name"]="Manage Snapshots" ;
$actionRows[0]["precedence"]="0";
$actionRows[0]["category"]="Database" ;
$actionRows[0]["description"]="Create and restore a mysqldump file." ;
$actionRows[0]["URLList"]="snapshot_manage.php,snapshot_manage_add.php,snapshot_manage_delete.php,snapshot_manage_load.php" ;
$actionRows[0]["entryURL"]="snapshot_manage.php" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ;
$actionRows[0]["defaultPermissionTeacher"]="N" ;
$actionRows[0]["defaultPermissionStudent"]="N" ;
$actionRows[0]["defaultPermissionParent"]="N" ;
$actionRows[0]["defaultPermissionSupport"]="N" ;
$actionRows[0]["categoryPermissionStaff"]="Y" ;
$actionRows[0]["categoryPermissionStudent"]="N" ;
$actionRows[0]["categoryPermissionParent"]="N" ;
$actionRows[0]["categoryPermissionOther"]="N" ;

$actionRows[1]["name"]="Manage Records" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="Database" ;
$actionRows[1]["description"]="Allows users to view database table information." ;
$actionRows[1]["URLList"]="records_manage.php,records_orphaned.php,records_duplicates.php,export_run.php" ;
$actionRows[1]["entryURL"]="records_manage.php" ;
$actionRows[1]["defaultPermissionAdmin"]="Y" ;
$actionRows[1]["defaultPermissionTeacher"]="N" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="Data Admin Settings" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="Settings" ;
$actionRows[2]["description"]="Allows adminitrators to configure import settings." ;
$actionRows[2]["URLList"]="settings.php,settingsProcess.php" ;
$actionRows[2]["entryURL"]="settings.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="N" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="N" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$actionRows[3]["name"]="Combine Similar Fields" ;
$actionRows[3]["precedence"]="0";
$actionRows[3]["category"]="Data Tools" ;
$actionRows[3]["description"]="Reduce duplication in fields with similar types of data." ;
$actionRows[3]["URLList"]="duplication_combine.php,duplication_combineConfirm.php" ;
$actionRows[3]["entryURL"]="duplication_combine.php" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="N" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="N" ;
$actionRows[3]["categoryPermissionStaff"]="Y" ;
$actionRows[3]["categoryPermissionStudent"]="N" ;
$actionRows[3]["categoryPermissionParent"]="N" ;
$actionRows[3]["categoryPermissionOther"]="N" ;

$actionRows[4]["name"]="Find Usernames" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="Data Tools" ;
$actionRows[4]["description"]="Helps find usernames from a spreadsheet with only names.";
$actionRows[4]["URLList"]="tools_findUsernames.php" ;
$actionRows[4]["entryURL"]="tools_findUsernames.php" ;
$actionRows[4]["defaultPermissionAdmin"]="Y" ;
$actionRows[4]["defaultPermissionTeacher"]="N" ;
$actionRows[4]["defaultPermissionStudent"]="N" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="N" ;
$actionRows[4]["categoryPermissionStaff"]="Y" ;
$actionRows[4]["categoryPermissionStudent"]="N" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;
//Hooks
// $hooks[0]="" ; //Serialised array to create hook and set options. See Hooks documentation online.
?>
