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
$sql[$count][1]="INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `entrySidebar`, `menuShow`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Data Admin'), 'Manage Records', '0', 'Actions', 'Allows users to view databse table information.', 'records_manage.php,records_orphaned.php,records_duplicates.php', 'records_manage.php', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N');end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Data Admin' AND gibbonAction.name='Manage Records'));end
" ;

?>