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

//v1.2.01
$count++;
$sql[$count][0]="1.2.01" ;
$sql[$count][1]="
UPDATE `gibbonAction` SET `name`='Import From File', `category`='Data Import' WHERE name='Import & Export' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin');end
UPDATE `gibbonAction` SET `category`='Data Import' WHERE name='View Import History' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin');end
UPDATE `gibbonAction` SET `category`='Database' WHERE name='Manage Records' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin');end
UPDATE `gibbonAction` SET `category`='Database' WHERE name='Manage Snapshots' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin');end
";

//v1.2.02
$count++;
$sql[$count][0]="1.2.02" ;
$sql[$count][1]="
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin'), 'Combine Similar Fields', 0, 'Data Tools', '', 'duplication_combine.php,duplication_combineConfirm.php', 'duplication_combine.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Data Admin' AND gibbonAction.name='Combine Similar Fields'));end
";

//v1.2.03
$count++;
$sql[$count][0]="1.2.03" ;
$sql[$count][1]="";

//v1.3.00
$count++;
$sql[$count][0]="1.3.00" ;
$sql[$count][1]="";

//v1.3.01
$count++;
$sql[$count][0]="1.3.01" ;
$sql[$count][1]="";

//v1.3.02
$count++;
$sql[$count][0]="1.3.02" ;
$sql[$count][1]="";

//v1.3.03
$count++;
$sql[$count][0]="1.3.03" ;
$sql[$count][1]="";

//v1.3.04
$count++;
$sql[$count][0]="1.3.04" ;
$sql[$count][1]="";

//v1.3.05
$count++;
$sql[$count][0]="1.3.05" ;
$sql[$count][1]="
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin'), 'Find Usernames', 0, 'Data Tools', '', 'tools_findUsernames.php', 'tools_findUsernames.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Data Admin' AND gibbonAction.name='Find Usernames'));end
";

//v1.3.06
$count++;
$sql[$count][0]="1.3.06" ;
$sql[$count][1]="";

//v1.4.00
$count++;
$sql[$count][0]="1.4.00" ;
$sql[$count][1]="";
