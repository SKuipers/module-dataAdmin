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

//Increase max execution time, as this stuff gets big
ini_set('max_execution_time', 600);

include "../../config.php" ;
include "../../functions.php" ;
include "../../version.php" ;

$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);


//Module includes
include "./moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Extended Import/import_run_export.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {

	$dataExport = (isset($_GET['data']) && $_GET['data'] == true);

	//Class includes
	require_once "./src/import.php" ;

	$importer = new Gibbon\ExtendedImporter( NULL, NULL, $pdo );

	// Get the importType information
	$type = (isset($_GET['type']))? $_GET['type'] : '';
	$importType = $importer->getImportType( $type );

	if ( empty($importType)  ) {
		print "<div class='error'>" ;
		print __($guid, "Your request failed because your inputs were invalid.") ;
		print "</div>" ;
		return;
	} else if ( !$importType->isValid() ) {
		print "<div class='error'>";
		printf( __($guid, 'Import cannot proceed, as the selected Import Type "%s" did not validate with the database.'), $type) ;
		print "<br/></div>";
		return;
	}

	/** Include PHPExcel */
	require_once $_SESSION[$guid]["absolutePath"] . '/lib/PHPExcel/Classes/PHPExcel.php';

	// Create new PHPExcel object
	$excel = new PHPExcel();

	//Create border styles
	$style_head_fill=array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'eeeeee')),
							'borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '444444'),), 'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN,'color' => array('argb' => '444444'),)),
							) ;

	// Set document properties
	$excel->getProperties()->setCreator(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setLastModifiedBy(formatName("",$_SESSION[$guid]["preferredName"], $_SESSION[$guid]["surname"], "Staff"))
		 ->setTitle( __($guid, "Activity Attendance") )
		 ->setDescription(__($guid, 'This information is confidential. Generated by Gibbon (https://gibbonedu.org).')) ;

	$filename = ( ($dataExport)? __($guid, "DataExport") : __($guid, "DataStructure") ).'-'.$type;

	$excel->setActiveSheetIndex(0) ;

	$tableName = $importType->getDetail('table');
	$primaryKey = $importType->getDetail('primary');

	$tableFields = $importType->getTableFields();
	$tableFields[0] = $primaryKey;

	// Create the header row
	$count = 0;
	foreach ($tableFields as $fieldName ) {
		$excel->getActiveSheet()->getColumnDimension( num2alpha($count) )->setAutoSize(true);
		$excel->getActiveSheet()->setCellValue( num2alpha($count).'1', $importType->getField($fieldName, 'name', $fieldName ) );
		$excel->getActiveSheet()->getStyle( num2alpha($count).'1')->applyFromArray($style_head_fill);
		$count++;
	}

	// Get the data
	if ($dataExport) {
		
 		try {
			$data=array(); 
			$sql="SELECT ".implode(', ', $tableFields)." FROM $tableName ORDER BY $primaryKey ASC" ;
			$result = $pdo->executeQuery($data, $sql);
		}
		catch(PDOException $e) { print $e->getMessage(); }

		if ($result->rowCount() > 0) {
			$rowCount = 2;
			while ($row = $result->fetch()) {

				$fieldCount = 0;
				foreach ($tableFields as $fieldName ) {
					$excel->getActiveSheet()->setCellValue( num2alpha($fieldCount).$rowCount, $row[ $fieldName ] );
					$fieldCount++;
				}

				$rowCount++;
			}
		}

	}


	

	//FINALISE THE DOCUMENT SO IT IS READY FOR DOWNLOAD
	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$excel->setActiveSheetIndex(0);

	// Redirect output to a client’s web browser (Excel2007)
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
	header('Cache-Control: max-age=0');
	// If you're serving to IE 9, then the following may be needed
	header('Cache-Control: max-age=1');

	// If you're serving to IE over SSL, then the following may be needed
	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header ('Pragma: public'); // HTTP/1.0

	$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
	$objWriter->save('php://output');
	exit;
}	
?>