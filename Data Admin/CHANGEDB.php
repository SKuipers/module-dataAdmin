<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql=array() ;
$count=0 ;

//v0.0.00
$sql[$count][0]="0.0.00" ;
$sql[$count][1]="-- First version, nothing to update" ;


//v1.0.00
$count++;
$sql[$count][0]="1.0.00" ;
$sql[$count][1]="" ;

//v1.1.00
$count++;
$sql[$count][0]="1.1.00" ;
$sql[$count][1]="
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin'), 'Manage Records', '0', 'Actions', 'Allows users to view database table information.', 'records_manage.php,records_orphaned.php,records_duplicates.php', 'records_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Data Admin' AND gibbonAction.name='Manage Records'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin'), 'Data Admin Settings', '0', 'Settings', 'Allows adminitrators to configure import settings.', 'settings.php,settingsProcess.php', 'settings.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Data Admin' AND gibbonAction.name='Data Admin Settings'));end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'enableUserLevelPermissions', 'Enable User-level Permissions', 'Restrict user imports and exports based on their role. Otherwise only the Import & Export permission is checked.', 'Y');end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'importCustomFolderLocation', 'Custom Imports Folder', 'Path to custom import types folder, relative to uploads.', '/imports');end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'exportSnapshotsFolderLocation', 'Snapshots Folder', 'Path to database snapshots folder, relative to uploads.', '/snapshots');end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`)VALUES ('Data Admin', 'exportDefaultFileType', 'Default Export File Type', '', 'Excel2007');end
" ;

//v1.2.00
$count++;
$sql[$count][0]="1.2.00" ;
$sql[$count][1]="";

?>
