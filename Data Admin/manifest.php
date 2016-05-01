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
$version="0.0.01" ; //Verson number
$author="Sandra Kuipers" ; //Your name
$url="https://github.com/SKuipers/" ; //Your URL

//Module tables
 $moduleTables[0]="CREATE TABLE IF NOT EXISTS `dataAdminImportLog` (
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
// $gibbonSetting[0]=""; //One array entry for every gibbonSetting entry you need to create. The scope field for the setting should be your module name.
// $gibbonSetting[1]="";


//Action rows 
//One array per action
$actionRows[0]["name"]="Import & Export" ; //The name of the action (appears to user in the right hand side module menu)
$actionRows[0]["precedence"]="0"; //If it is a grouped action, the precedence controls which is highest action in group
$actionRows[0]["category"]="Actions" ; //Optional: subgroups for the right hand side module menu
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

$actionRows[1]["name"]="View Import History" ; //The name of the action (appears to user in the right hand side module menu)
$actionRows[1]["precedence"]="0"; //If it is a grouped action, the precedence controls which is highest action in group
$actionRows[1]["category"]="Reports" ; //Optional: subgroups for the right hand side module menu
$actionRows[1]["description"]="View a log of import activity." ; //Text description
$actionRows[1]["URLList"]="import_history.php,import_history_view.php" ; //List of pages included in this action
$actionRows[1]["entryURL"]="import_history.php" ; //The landing action for the page.
$actionRows[1]["defaultPermissionAdmin"]="Y" ; //Default permission for built in role Admin
$actionRows[1]["defaultPermissionTeacher"]="N" ; //Default permission for built in role Teacher
$actionRows[1]["defaultPermissionStudent"]="N" ; //Default permission for built in role Student
$actionRows[1]["defaultPermissionParent"]="N" ; //Default permission for built in role Parent
$actionRows[1]["defaultPermissionSupport"]="N" ; //Default permission for built in role Support
$actionRows[1]["categoryPermissionStaff"]="Y" ; //Should this action be available to user roles in the Staff category?
$actionRows[1]["categoryPermissionStudent"]="N" ; //Should this action be available to user roles in the Student category?
$actionRows[1]["categoryPermissionParent"]="N" ; //Should this action be available to user roles in the Parent category?
$actionRows[1]["categoryPermissionOther"]="N" ; //Should this action be available to user roles in the Other category?

$actionRows[2]["name"]="Manage Snapshots" ; //The name of the action (appears to user in the right hand side module menu)
$actionRows[2]["precedence"]="0"; //If it is a grouped action, the precedence controls which is highest action in group
$actionRows[2]["category"]="Actions" ; //Optional: subgroups for the right hand side module menu
$actionRows[2]["description"]="Create and restore a mysqldump file." ; //Text description
$actionRows[2]["URLList"]="snapshot_manage.php,snapshot_manage_add.php,snapshot_manage_delete.php,snapshot_manage_load.php" ; //List of pages included in this action
$actionRows[2]["entryURL"]="snapshot_manage.php" ; //The landing action for the page.
$actionRows[2]["defaultPermissionAdmin"]="Y" ; //Default permission for built in role Admin
$actionRows[2]["defaultPermissionTeacher"]="N" ; //Default permission for built in role Teacher
$actionRows[2]["defaultPermissionStudent"]="N" ; //Default permission for built in role Student
$actionRows[2]["defaultPermissionParent"]="N" ; //Default permission for built in role Parent
$actionRows[2]["defaultPermissionSupport"]="N" ; //Default permission for built in role Support
$actionRows[2]["categoryPermissionStaff"]="Y" ; //Should this action be available to user roles in the Staff category?
$actionRows[2]["categoryPermissionStudent"]="N" ; //Should this action be available to user roles in the Student category?
$actionRows[2]["categoryPermissionParent"]="N" ; //Should this action be available to user roles in the Parent category?
$actionRows[2]["categoryPermissionOther"]="N" ; //Should this action be available to user roles in the Other category?

//Hooks
// $hooks[0]="" ; //Serialised array to create hook and set options. See Hooks documentation online.
?>