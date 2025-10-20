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
use Gibbon\Domain\System\SettingGateway;

// Gibbon Bootstrap
include __DIR__ . '/../../gibbon.php';

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? [];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Data Admin/tools_fileExport.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/Data Admin/tools_fileExport.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonSchoolYearID) || empty($gibbonYearGroupIDList)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    
    $absolutePath = $session->get('absolutePath');
    $exportPath = $absolutePath .'/../archive/export/';
    if (is_dir($exportPath) === false) {
        mkdir($exportPath, 0755, true);
    }
    $exportPath = realpath($exportPath);

    $gibbonYearGroupIDList = is_array($gibbonYearGroupIDList) ? implode(',', $gibbonYearGroupIDList) : $gibbonYearGroupIDList;

    $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList];
    $sql = "SELECT gibbonPerson.studentID as groupBy, gibbonPerson.gibbonPersonID, username, surname, preferredName, studentID, image_240, gibbonPerson.fields, gibbonFormGroup.nameShort as formGroup 
    FROM gibbonPerson
        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
        JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
        JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
        AND gibbonPerson.status = 'Full'
        AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
    ORDER BY surname, preferredName";

    $students = $pdo->select($sql, $data)->fetchGroupedUnique();

    $counts = [
        'photoCount' => 0,
        'reportCount' => 0,
        'documentCount' => 0,
    ];

    foreach ($students as $studentID => $student) {
        $exportData = [];

        $exportPathFormGroup = $exportPath . '/'. $student['formGroup'];
        $exportPathStudent = $exportPathFormGroup . '/'. $studentID;

        $photoName = $student['username'] . '_'.$student['surname'].', '. $student['preferredName'] . '.jpg';
        $photoPath = $absolutePath .'/'. $student['image_240'];
        $destinationPath = $exportPathStudent . '/'. $photoName;

        if (is_file($photoPath)) {
            $exportData[] = [
                'key'      => 'photoCount',
                'copyFrom' => $photoPath,
                'copyTo'   => $destinationPath,
                'data' => [
                    'gibbonPersonID' => $student['gibbonPersonID'],
                    'type'           => 'Student Photo',
                    'description'    => $student['surname'].', '. $student['preferredName'],
                    'fileName'       => $photoName,
                    'filePath'       => str_replace($exportPath, '', $destinationPath),
                    'success'        => 1,
                ]
            ];
        }

        // REPORTS
        $data = ['gibbonPersonID' => $student['gibbonPersonID']];
        $sql = "SELECT gibbonReportArchiveEntry.gibbonReportArchiveEntryID, 
            gibbonSchoolYear.name as schoolYear, 
            gibbonReportArchive.path as archivePath,
            gibbonReportArchiveEntry.filePath, 
            gibbonReportArchiveEntry.timestampModified, 
            gibbonReportArchiveEntry.reportIdentifier 
            FROM gibbonReportArchiveEntry
            INNER JOIN gibbonReportArchive ON (gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID)
            INNER JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonReportArchiveEntry.gibbonSchoolYearID)
            INNER JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonReportArchiveEntry.gibbonPersonID)
            WHERE gibbonReportArchiveEntry.type='Single'
            AND gibbonReportArchiveEntry.gibbonPersonID=:gibbonPersonID";

        $reports = $pdo->select($sql, $data)->fetchAll();

        foreach ($reports as $report) {
            $reportName = $student['username'] . '_'.$report['schoolYear'] . ' - '.$report['reportIdentifier'].'.pdf';
            $reportPath = $absolutePath . $report['archivePath'] .'/'. $report['filePath'];
            $destinationPath = $exportPathStudent . '/'. $reportName;

            if (is_file($reportPath)) {
                $exportData[] = [
                    'key'      => 'reportCount',
                    'copyFrom' => $reportPath,
                    'copyTo'   => $destinationPath,
                    'data' => [
                        'gibbonPersonID' => $student['gibbonPersonID'],
                        'type'           => 'Report',
                        'description'    => $report['schoolYear'] . ' - '.$report['reportIdentifier'],
                        'fileName'       => $reportName,
                        'filePath'       => str_replace($exportPath, '', $destinationPath),
                        'success'        => 1,
                    ]
                ];
            }
        }


        // PERSONAL DOCUMENTS
        $data = ['gibbonPersonID' => $student['gibbonPersonID']];
        $sql = "SELECT gibbonPersonalDocument.gibbonPersonalDocumentID, 
            gibbonPersonalDocument.filePath,
            gibbonPersonalDocumentType.name as description,
            gibbonPersonalDocument.document as type
            FROM gibbonPersonalDocument
            INNER JOIN gibbonPersonalDocumentType ON (gibbonPersonalDocument.gibbonPersonalDocumentTypeID=gibbonPersonalDocumentType.gibbonPersonalDocumentTypeID)
            INNER JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonPersonalDocument.foreignTableID)
            WHERE gibbonPersonalDocument.filePath IS NOT NULL
            AND gibbonPersonalDocument.foreignTable='gibbonPerson'
            AND gibbonPersonalDocument.foreignTableID=:gibbonPersonID";

        $documents = $pdo->select($sql, $data)->fetchAll();

        foreach ($documents as $document) {
            $documentName = $student['username'] . '_'.$document['description'].'.pdf';
            $documentPath = $absolutePath .'/'. $document['filePath'];
            $destinationPath = $exportPathStudent . '/'. $documentName;

            if (is_file($documentPath)) {
                $exportData[] = [
                    'key'      => 'documentCount',
                    'copyFrom' => $documentPath,
                    'copyTo'   => $destinationPath,
                    'data' => [
                        'gibbonPersonID' => $student['gibbonPersonID'],
                        'type'           => $document['type'],
                        'description'    => $document['description'],
                        'fileName'       => $documentName,
                        'filePath'       => str_replace($exportPath, '', $destinationPath),
                        'success'        => 1,
                    ]
                ];
            }
        }

        if (empty($exportData)) continue;

        if (is_dir($exportPathFormGroup) === false) {
            mkdir($exportPathFormGroup , 0775, true);
        }

        if (is_dir($exportPathStudent) === false) {
            mkdir($exportPathStudent , 0775, true);
        }

        foreach ($exportData as $export) {
            if (copy($export['copyFrom'], $export['copyTo'])) {
                $counts[$export['key']]++;

                $sql = "INSERT INTO dataAdminExportLog SET gibbonPersonID=:gibbonPersonID, type=:type, description=:description, fileName=:fileName, filePath=:filePath, success=:success ON DUPLICATE KEY UPDATE type=:type, description=:description, fileName=:fileName, filePath=:filePath, timestamp=CURRENT_TIME";
                $pdo->statement($sql, $export['data']);
            }
        }
        
    }

    $URL .= "&return=success0&photoCount={$counts['photoCount']}&reportCount={$counts['reportCount']}&documentCount={$counts['documentCount']}";
    header("Location: {$URL}");
    exit;
}
