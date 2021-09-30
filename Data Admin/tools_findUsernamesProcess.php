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

use PhpOffice\PhpSpreadsheet\IOFactory;

// Gibbon Bootstrap
include __DIR__ . '/../../gibbon.php';

// Module Bootstrap
require __DIR__ . '/module.php';

$filePath = isset($_FILES['file']['tmp_name'])? $_FILES['file']['tmp_name'] : '';
$roleCategory = $_POST['roleCategory'] ?? '';
$columnType = $_POST['columnType'] ?? '';
$nameType = $_POST['nameType'] ?? '';
$nameFormat = $_POST['nameFormat'] ?? '';
$nameColumn = intval($_POST['nameColumn'] ?? 0);
$firstNameColumn = intval($_POST['firstNameColumn'] ?? 0);
$surnameColumn = intval($_POST['surnameColumn'] ?? 0);
$yearGroupColumn = intval($_POST['yearGroupColumn'] ?? 0);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Data Admin/tools_findUsernames.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Admin/tools_findUsernames.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($filePath)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    try {
        $objPHPExcel = IOFactory::load($filePath);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        $URL .= '&return=error4';
        header("Location: {$URL}");
        exit;
    }

    $objWorksheet = $objPHPExcel->getActiveSheet();
    $lastColumn = $objWorksheet->getHighestDataColumn();
    $lastColumn++;

    $objWorksheet->setCellValue($lastColumn.'1', 'Username');

    $studentCount = 0;
    $studentFoundCount = 0;

    // Grab the header & first row for Step 1
    foreach ($objWorksheet->getRowIterator(2) as $rowIndex => $row) {
        $array = $objWorksheet->rangeToArray('A'.$rowIndex.':'.$lastColumn.$rowIndex, null, true, true, false);

        $studentName = isset($array[0][$nameColumn])? $array[0][$nameColumn] : '';
        $yearGroup = isset($array[0][$yearGroupColumn])? $array[0][$yearGroupColumn] : '';

        if ($columnType == 'one') {
            // Parse the student name, then copy into variables based on the name format
            $matches = array();
            $preferredName = $firstName = $surname1 = $surname2 = '';

            switch ($nameFormat) {
                case 'firstLast':       // Handle names with spaces: Alpha Beta + Gamma as well as Alpha + Beta Gamma
                                        if (preg_match_all('/([\w\.\-\']+)/i', $studentName, $matches)) {
                                            $matches = $matches[0];
                                            $firstName = !empty($matches[0])? $matches[0] : '';
                                            $surname1 = !empty($matches[1])? implode(' ', array_slice($matches, 1)) : '';
                                            $preferredName = !empty($matches[2])? implode(' ', array_slice($matches, 0, 2)) : $firstName;
                                            $surname2 = !empty($matches[2])? implode(' ', array_slice($matches, 2)) : $surname1;
                                        }
                                        break;

                case 'lastFirst':       // Everything before the , is the surname, everything after is the first name
                                        if (preg_match('/([^,]+)[, ]+(.*)/i', $studentName, $matches)) {
                                            $surname1 = !empty($matches[1])? $matches[1] : '';
                                            $firstName = !empty($matches[2])? implode(' ', array_slice($matches, 2)) : '';
                                            $preferredName = $firstName;
                                            $surname2 = $surname1;
                                        }
                                        break;

                case 'lastFirstAlt':    // Split the surname before the , and the preferred name after, optionally grabbing a first name in ()
                                        if (preg_match('/([^,]+)[, ]+([\w\.\-\']+)[ \(\)]*([\w\.\-\' ]*)/i', $studentName, $matches)) {
                                            list($surname1, $preferredName, $firstName) = array_pad(array_slice($matches, 1), 3, '');
                                            if (empty($firstName)) {
                                                $firstName = $preferredName;
                                            }
                                            $surname2 = $surname1;
                                        }
                                        break;

            }
        } elseif ($columnType == 'multi') {
            $preferredName = isset($array[0][$nameColumn])? $array[0][$nameColumn] : '';
            $firstName = isset($array[0][$firstNameColumn])? $array[0][$firstNameColumn] : $preferredName;
            $surname1 = isset($array[0][$surnameColumn])? $array[0][$surnameColumn] : '';
            $surname2 = $surname1;
        }

        // echo "Matching: $firstName + $surname1 OR $preferredName + $surname2 </br>";

        if ($roleCategory == 'Student') {
            // Locate a student enrolment for the target year group with a matching student name
            $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'yearGroup' => $yearGroup, 'preferredName' => trim($preferredName), 'firstName' => trim($firstName), 'surname1' => trim($surname1), 'surname2' => trim($surname2) ];
            $sql = "SELECT gibbonPerson.username
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonStudentEnrolment.gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup)
                    AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                    AND (
                        (gibbonPerson.surname = :surname1 AND gibbonPerson.preferredName = :preferredName)
                        OR (gibbonPerson.surname = :surname2 AND gibbonPerson.firstName = :firstName)
                    )";
        } else {
            $data = ['preferredName' => trim($preferredName), 'firstName' => trim($firstName), 'surname1' => trim($surname1), 'surname2' => trim($surname2) ];
            $sql = "SELECT gibbonPerson.username
                    FROM gibbonPerson
                    WHERE (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                    AND (
                        (gibbonPerson.surname = :surname1 AND gibbonPerson.preferredName = :preferredName)
                        OR (gibbonPerson.surname = :surname2 AND gibbonPerson.firstName = :firstName)
                    )";
        }

        $result = $pdo->select($sql, $data);

        if ($result->rowCount() == 1) {
            $foundValue = $result->fetchColumn();
            $studentFoundCount++;
        } else {
            $foundValue = '';
        }

        // Write the ID to the last column
        $objWorksheet->setCellValue($lastColumn.$rowIndex, $foundValue);

        $studentCount++;
    }

    $filename = mb_substr($_FILES['file']['name'], 0, mb_strpos($_FILES['file']['name'], '.'));
    $filename .= '-matches';

    $exportFileType = getSettingByScope($connection2, 'Data Admin', 'exportDefaultFileType');
	if (empty($exportFileType)) $exportFileType = 'Excel2007';

	switch($exportFileType) {
		case 'Excel2007': 		$filename .= '.xlsx';
								$mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
		case 'Excel5': 			$filename .= '.xls';
								$mimetype = 'application/vnd.ms-excel'; break;
		case 'OpenDocument': 	$filename .= '.ods';
								$mimetype = 'application/vnd.oasis.opendocument.spreadsheet'; break;
		case 'CSV': 			$filename .= '.csv';
								$mimetype = 'text/csv'; break;
	}

    //FINALISE THE DOCUMENT SO IT IS READY FOR DOWNLOAD
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);

    // Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: '.$mimetype);
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter = IOFactory::createWriter($objPHPExcel, $exportFileType);
    $objWriter->save('php://output');
    exit;
}
