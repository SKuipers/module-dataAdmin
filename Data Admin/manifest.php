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
$version="1.3.05" ; //Verson number
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
//One array per action
$actionRows[0]["name"]="Import From File" ; //The name of the action (appears to user in the right hand side module menu)
$actionRows[0]["precedence"]="0"; //If it is a grouped action, the precedence controls which is highest action in group
$actionRows[0]["category"]="Data Import" ; //Optional: subgroups for the right hand side module menu
$actionRows[0]["description"]="View and run available import actions." ; //Text description
$actionRows[0]["URLList"]="import_manage.php,import_run.php,export_run.php" ; //List of pages included in this action
$actionRows[0]["entryURL"]="import_manage.php" ; //The landing action for the page.
$actionRows[0]["defaultPermissionAdmin"]="Y" ; //Default permission for built in role Admin
$actionRows[0]["defaultPermissionTeacher"]="N" ; //Default permission for built in role Teacher
$actionRows[0]["defaultPermissionStudent"]="N" ; //Default permission for built in role Student
$actionRows[0]["defaultPermissionParent"]="N" ; //Default permission for built in role Parent
$actionRows[0]["defaultPermissionSupport"]="N" ; //Default permission for built in role Support
$actionRows[0]["categoryPermissionStaff"]="Y" ; //Should this action be available to user roles in the Staff category?
$actionRows[0]["categoryPermissionStudent"]="N" ; //Should this action be available to user roles in the Student category?
$actionRows[0]["categoryPermissionParent"]="N" ; //Should this action be available to user roles in the Parent category?
$actionRows[0]["categoryPermissionOther"]="N" ; //Should this action be available to user roles in the Other category?

$actionRows[1]["name"]="View Import History" ;
$actionRows[1]["precedence"]="0";
$actionRows[1]["category"]="Data Import" ;
$actionRows[1]["description"]="View a log of import activity." ;
$actionRows[1]["URLList"]="import_history.php,import_history_view.php" ;
$actionRows[1]["entryURL"]="import_history.php" ;
$actionRows[1]["defaultPermissionAdmin"]="Y" ;
$actionRows[1]["defaultPermissionTeacher"]="N" ;
$actionRows[1]["defaultPermissionStudent"]="N" ;
$actionRows[1]["defaultPermissionParent"]="N" ;
$actionRows[1]["defaultPermissionSupport"]="N" ;
$actionRows[1]["categoryPermissionStaff"]="Y" ;
$actionRows[1]["categoryPermissionStudent"]="N" ;
$actionRows[1]["categoryPermissionParent"]="N" ;
$actionRows[1]["categoryPermissionOther"]="N" ;

$actionRows[2]["name"]="Manage Snapshots" ;
$actionRows[2]["precedence"]="0";
$actionRows[2]["category"]="Database" ;
$actionRows[2]["description"]="Create and restore a mysqldump file." ;
$actionRows[2]["URLList"]="snapshot_manage.php,snapshot_manage_add.php,snapshot_manage_delete.php,snapshot_manage_load.php" ;
$actionRows[2]["entryURL"]="snapshot_manage.php" ;
$actionRows[2]["defaultPermissionAdmin"]="Y" ;
$actionRows[2]["defaultPermissionTeacher"]="N" ;
$actionRows[2]["defaultPermissionStudent"]="N" ;
$actionRows[2]["defaultPermissionParent"]="N" ;
$actionRows[2]["defaultPermissionSupport"]="N" ;
$actionRows[2]["categoryPermissionStaff"]="Y" ;
$actionRows[2]["categoryPermissionStudent"]="N" ;
$actionRows[2]["categoryPermissionParent"]="N" ;
$actionRows[2]["categoryPermissionOther"]="N" ;

$actionRows[3]["name"]="Manage Records" ;
$actionRows[3]["precedence"]="0";
$actionRows[3]["category"]="Database" ;
$actionRows[3]["description"]="Allows users to view database table information." ;
$actionRows[3]["URLList"]="records_manage.php,records_orphaned.php,records_duplicates.php" ;
$actionRows[3]["entryURL"]="records_manage.php" ;
$actionRows[3]["defaultPermissionAdmin"]="Y" ;
$actionRows[3]["defaultPermissionTeacher"]="N" ;
$actionRows[3]["defaultPermissionStudent"]="N" ;
$actionRows[3]["defaultPermissionParent"]="N" ;
$actionRows[3]["defaultPermissionSupport"]="N" ;
$actionRows[3]["categoryPermissionStaff"]="Y" ;
$actionRows[3]["categoryPermissionStudent"]="N" ;
$actionRows[3]["categoryPermissionParent"]="N" ;
$actionRows[3]["categoryPermissionOther"]="N" ;

$actionRows[4]["name"]="Data Admin Settings" ;
$actionRows[4]["precedence"]="0";
$actionRows[4]["category"]="Settings" ;
$actionRows[4]["description"]="Allows adminitrators to configure import settings." ;
$actionRows[4]["URLList"]="settings.php,settingsProcess.php" ;
$actionRows[4]["entryURL"]="settings.php" ;
$actionRows[4]["defaultPermissionAdmin"]="Y" ;
$actionRows[4]["defaultPermissionTeacher"]="N" ;
$actionRows[4]["defaultPermissionStudent"]="N" ;
$actionRows[4]["defaultPermissionParent"]="N" ;
$actionRows[4]["defaultPermissionSupport"]="N" ;
$actionRows[4]["categoryPermissionStaff"]="Y" ;
$actionRows[4]["categoryPermissionStudent"]="N" ;
$actionRows[4]["categoryPermissionParent"]="N" ;
$actionRows[4]["categoryPermissionOther"]="N" ;

$actionRows[5]["name"]="Combine Similar Fields" ;
$actionRows[5]["precedence"]="0";
$actionRows[5]["category"]="Data Tools" ;
$actionRows[5]["description"]="Reduce duplication in fields with similar types of data." ;
$actionRows[5]["URLList"]="duplication_combine.php,duplication_combineConfirm.php" ;
$actionRows[5]["entryURL"]="duplication_combine.php" ;
$actionRows[5]["defaultPermissionAdmin"]="Y" ;
$actionRows[5]["defaultPermissionTeacher"]="N" ;
$actionRows[5]["defaultPermissionStudent"]="N" ;
$actionRows[5]["defaultPermissionParent"]="N" ;
$actionRows[5]["defaultPermissionSupport"]="N" ;
$actionRows[5]["categoryPermissionStaff"]="Y" ;
$actionRows[5]["categoryPermissionStudent"]="N" ;
$actionRows[5]["categoryPermissionParent"]="N" ;
$actionRows[5]["categoryPermissionOther"]="N" ;
//Hooks
// $hooks[0]="" ; //Serialised array to create hook and set options. See Hooks documentation online.
?>
