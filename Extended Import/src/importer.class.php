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

namespace Gibbon;

/**
 * Extended Import class
 *
 * @version	25th April 2016
 * @since	25th April 2016
 * @author	Sandra Kuipers
 */
class importer
{
	const COLUMN_DATA_SKIP = -1;
	const COLUMN_DATA_CUSTOM = -2;
	const COLUMN_DATA_FUNCTION = -3;

	const ERROR_INVALID_INPUTS = 1;
	const ERROR_REQUIRED_FIELD_MISSING = 5;
	const ERROR_INVALID_FIELD_VALUE = 6;
	const ERROR_LOCKING_DATABASE = 7;
	const ERROR_DATABASE_GENERIC = 8;
	const ERROR_DATABASE_FAILED_INSERT = 9;
	const ERROR_DATABASE_FAILED_UPDATE = 10;
	const ERROR_KEY_MISSING = 11;

	const WARNING_DUPLICATE_KEY = 101;
	const WARNING_RECORD_NOT_FOUND = 102;

	
	/**
	 * Gibbon\sqlConnection
	 */
	private $pdo ;
	
	/**
	 * Gibbon\session
	 */
	private $session ;
	
	/**
	 * Gibbon\config
	 */
	private $config ;

	public $fieldDelimiter = ',';
	public $stringEnclosure = '"';
	public $maxLineLength = 100000;

	public $mode;
	public $syncField;
	public $syncColumn;

	private $csvFileHandler;

	private $importHeaders;
	private $importData;

	private $tableData;

	private $rowErrors = array();
	private $importErrors = array();
	private $importWarnings = array();

	private $errorID = 0;

	private $databaseResults = array(
		'inserts' => 0,
		'inserts_skipped' => 0,
		'updates' => 0,
		'updates_skipped' => 0,
		'duplicates' => 0
	);

	private $csvMimeTypes = array(
		"text/csv","text/comma-separated-values","text/x-comma-separated-values","application/vnd.ms-excel","application/csv"
	);
	
	/**
     * Constructor
     *
     * @version  25th April 2016
     * @since    25th April 2016
     * @param    Gibbon\session
     * @param    Gibbon\config
     * @param    Gibbon\sqlConnection
     * @return    void
     */
    public function __construct(session $session = NULL, config $config = NULL, sqlConnection $pdo = NULL)
    {
        if ($session === NULL)
            $this->session = new session();
        else
            $this->session = $session ;

        if ($config === NULL)
            $this->config = new config();
        else
            $this->config = $config ;

        if ($pdo === NULL)
            $this->pdo = new sqlConnection();
        else
            $this->pdo = $pdo ;
    }

    public function openCSVFile( $csvFile ) {

    	ini_set("auto_detect_line_endings", true);
		$this->csvFileHandler=fopen($csvFile, "r");
		return ($this->csvFileHandler !== FALSE);
    }

    public function closeCSVFile() {
    	fclose($this->csvFileHandler);
    }

    public function getCSVLine() {
    	return fgetcsv($this->csvFileHandler, $this->maxLineLength, $this->fieldDelimiter, $this->stringEnclosure);
    }

    public function readCSVFile() {

		if ( $this->openCSVFile() ) {
			$count = 0;
			while ($data = $this->readCSVLine() ) {
				if ($count == 0) {
					$this->importHeaders = $data;
				} else {
					// Skips CSV lines where all values in the line are empty
					if ( !empty(array_filter($data)) ) {
						$this->importData[] = $data;
					}
				}
				$count++;
			}

			return (!empty($this->importHeaders) && count($this->importData) > 0);
		} else {
			return false;
		}

		fclose($this->csvFileHandler);
    }

    public function readCSVString( $csvString ) {

		$csv = new \parseCSV();
		$csv->heading = true;
		$csv->delimiter = $this->fieldDelimiter;
		$csv->enclosure = $this->stringEnclosure;

		$csv->parse( $csvString );

		$this->importHeaders = $csv->titles;
		$this->importData = $csv->data;

		$this->importErrors = $csv->error_info;
		unset($csv);

		foreach ($this->importErrors as $error) {
			$this->rowErrors[ $error['row'] ] = 1;
		}

		return (!empty($this->importHeaders) && count($this->importData) > 0 && count($this->importErrors) == 0 );
    }	

    public function getHeaders() {
    	return $this->importHeaders;
    }

    public function getHeaderKey( $index ) {
    	return (isset($this->importHeaders[$index]))? $this->importHeaders[$index] : 0;
    }

    public function getRowCount() {
    	return count($this->importData);
    }

    public function getRow( $row ) {
    	return (isset($this->importData[$row]))? $this->importData[$row] : FALSE;
    }

    public function buildTableData( $importType, $columnOrder, $customValues ) {

    	$importTypeFields = $importType->getTableFields();

		$this->tableData = array();

		foreach ( $this->importData as $rowNum => $row ) {

			$fields = array();
			$fieldCount = 0;
			$partialFail = FALSE;
			foreach ($importTypeFields as $fieldName) {
				$columnIndex = $columnOrder[ $fieldCount ];

				$value = '';
				// Skip marked columns
				if ($columnIndex == ExtendedImporter::COLUMN_DATA_SKIP) {
					$fieldCount++;
					continue;
				}
				// Get the custom text value provided by the user (from Step 2)
				else if ($columnIndex == ExtendedImporter::COLUMN_DATA_CUSTOM) {
					
					$value = (isset($customValues[ $fieldCount ]))? $customValues[ $fieldCount ] : '';
				}
				// Run a user_func based on the function name defined for that field
				else if ($columnIndex == ExtendedImporter::COLUMN_DATA_FUNCTION) {
					
					$value = $importType->doImportFunction( $fieldName );
				}
				// Use the column index to grab to associated CSV value
				else {
					$value = $row[ $this->getHeaderKey($columnIndex) ];
				}
				
				// Filter & validate the value
				$validate = $importType->validateFieldValue( $fieldName, $value );
				if ($validate == false ) {
					$this->logError( $rowNum, ExtendedImporter::ERROR_INVALID_FIELD_VALUE, $fieldName, $fieldCount,
						array($importType->getField($fieldName, 'type'), $importType->getField($fieldName, 'length')) );

					$partialFail = TRUE;
				}

				if ( empty($value) && $importType->isFieldRequired($fieldName) ) {
					$this->logError( $rowNum, ExtendedImporter::ERROR_REQUIRED_FIELD_MISSING, $fieldName, $fieldCount);
					$partialFail = TRUE;
				} else {
					$fields[ $fieldName ] = $value;
				}

				$fieldCount++;
			}

			if (!empty($fields) && $partialFail == FALSE) {
				$this->tableData[] = $fields;
			}
		}

		return ( !empty($this->tableData) && $this->getErrorCount() == 0 );
    }

    public function importIntoDatabase( $importType, $liveRun = TRUE ) {

    	if ($liveRun) {
	    	if ( $this->lockTables( $importType->getTables() ) == false) {
	    		$this->errorID = ExtendedImporter::ERROR_LOCKING_DATABASE;
				return false;
			}
		}

		$tableName = $importType->getDetail('table');
		$primaryKey = $importType->getDetail('primary');
		$selectionKey = $importType->getDetail('select');

		$partialFail = FALSE;
		foreach ($this->tableData as $rowNum => $row) {

			// Ensure we have a valid key
			if (!isset($row[$selectionKey])) {
				$this->logError( $rowNum, ExtendedImporter::ERROR_KEY_MISSING, $selectionKey );
				$partialFail = TRUE;
				continue;
			}

			// Find existing record(s)
			try {
				$data=array( $selectionKey => $row[ $selectionKey ] ); 
				$sql="SELECT $primaryKey FROM $tableName WHERE $selectionKey=:$selectionKey" ;
				$result = $this->pdo->executeQuery($data, $sql);
			}
			catch(PDOException $e) { 
				$this->logError( $rowNum, ExtendedImporter::ERROR_DATABASE_GENERIC, $selectionKey );
				$partialFail = TRUE;
				continue;
			}

			$primaryKeyValue = $result->fetchColumn(0);
			$sqlFields = array();
			foreach (array_keys($row) as $field) {
				$sqlFields[] = $field."=:".$field;
			}
			$sqlFields = implode(", ", $sqlFields );

			// Handle Existing Records
			if ($result->rowCount() == 1) {

				// Dont update records on INSERT ONLY mode
				if ($this->mode == 'insert') {
					$this->logError( $rowNum, ExtendedImporter::WARNING_DUPLICATE_KEY, $selectionKey, $primaryKeyValue );
					$this->databaseResults['updates_skipped'] += 1;
					continue;
				}

				$this->databaseResults['updates'] += 1;

				// Skip now so we dont change the database
				if (!$liveRun) continue;

				try {
					$data[$selectionKey] = $row[ $selectionKey ];
					$sql="UPDATE $tableName SET " . $sqlFields . " WHERE $selectionKey=:$selectionKey" ;
					$this->pdo->executeQuery($row, $sql);
				}
				catch(PDOException $e) { 
					$this->logError( $rowNum, ExtendedImporter::ERROR_DATABASE_FAILED_UPDATE, $e->getMessage() );
					$partialFail = TRUE;
					continue;
				}

			}

			// Handle New Records
			else if ($result->rowCount() == 0) {

				// Dont add records on UPDATE ONLY mode
				if ($this->mode == 'update') {
					$this->logError( $rowNum, ExtendedImporter::WARNING_RECORD_NOT_FOUND, $selectionKey, $row[ $selectionKey ] );
					$this->databaseResults['inserts_skipped'] += 1;
					continue;
				}

				$this->databaseResults['inserts'] += 1;

				// Skip now so we dont change the database
				if (!$liveRun) continue;

				try {
					$sql="INSERT INTO $tableName SET ".$sqlFields;
					$this->pdo->executeQuery($row, $sql);
				}
				catch(PDOException $e) { 
					$this->logError( $rowNum, ExtendedImporter::ERROR_DATABASE_FAILED_INSERT, $e->getMessage() );
					$partialFail = TRUE;
					continue;
				}
				
			}
			else {
				$this->logError( $rowNum, ExtendedImporter::ERROR_DATABASE_GENERIC, $selectionKey, $primaryKeyValue );
				$partialFail = TRUE;
			}

		}

		if ($liveRun) {
			$this->unlockTables();
		}

		return true;
    }

    public function getDatabaseResults( $key ) {
    	return (isset($this->databaseResults[$key]))? $this->databaseResults[$key] : 'unknown';
    }

    public function getErrors() {
    	return $this->importErrors;
    }

    public function getWarnings() {
    	return $this->importWarnings;
    }

    public function getErrorCount() {
    	return count($this->importErrors);
    }

    public function getErrorRowCount() {
    	return count($this->rowErrors);
    }

    public function getWarningCount() {
    	return count($this->importWarnings);
    }

    public function getLastError() {
    	return $this->errorMessage( $this->errorID );
    }

    private function logError( $rowNum, $errorID, $fieldName, $fieldNum = -1, $args = array() ) {

    	$error = array( 
    		'index' => $rowNum,
    		'row' => $rowNum+2,
    		'info' => vsprintf( $this->errorMessage($errorID), $args ),
    		'field_name' => $fieldName,
    		'field' => $fieldNum,
    	);

    	if ( $errorID >= 100 ) {
    		$this->importWarnings[] = $error;
    	} else {
    		$this->importErrors[] = $error;
    		$this->rowErrors[ $rowNum ] = 1;
    	}
    }

    public function errorMessage( $errorID ) {

    	switch ($errorID) {
    		// ERRORS
    		case ExtendedImporter::ERROR_REQUIRED_FIELD_MISSING: 
    			return __( $this->config->get('guid'), "Missing value for required field."); break;
    		case ExtendedImporter::ERROR_INVALID_FIELD_VALUE: 
    			return __( $this->config->get('guid'), "Invalid value type for field: %s Expected: %s(%s)"); break;
    		case ExtendedImporter::ERROR_INVALID_INPUTS:
    			return __( $this->config->get('guid'), "Your request failed because your inputs were invalid."); break;
    		case ExtendedImporter::ERROR_LOCKING_DATABASE:
    			return __( $this->config->get('guid'), "The database could not be locked for use."); break;
    		case ExtendedImporter::ERROR_KEY_MISSING:
    			return __($this->config->get('guid'), "Missing value for primary key."); break;
    		case ExtendedImporter::ERROR_DATABASE_GENERIC:
    			return __($this->config->get('guid'), "There was an error accessing the database."); break;
    		case ExtendedImporter::ERROR_DATABASE_FAILED_INSERT:
    			return __($this->config->get('guid'), "Failed to insert record into database."); break;
    		case ExtendedImporter::ERROR_DATABASE_FAILED_UPDATE:
    			return __($this->config->get('guid'), "Failed to update database record."); break;
    		// WARNINGS
    		case ExtendedImporter::WARNING_DUPLICATE_KEY:
    			return __($this->config->get('guid'), "A duplicate entry already exists for this record. Record skipped."); break;
    		case ExtendedImporter::WARNING_RECORD_NOT_FOUND:
    			return __($this->config->get('guid'), "A database entry for this record could not be found. Record skipped."); break;
    		default:
    			return __( $this->config->get('guid'), "An unknown error occured, so the import will be aborted."); break;

    		
    	}
    }

    private function lockTables( $tables ) {

    	if (empty($tables)) return false;

    	$lockFail=false;
		try {
			$sql="LOCK TABLES " . implode(' WRITE, ', $tables) ." WRITE";
			$result = $this->pdo->executeQuery(array(), $sql);   
			return true;
		}
		catch(PDOException $e) {
			return false;
		}

    }

    private function unlockTables() {
    	try {
			$sql="UNLOCK TABLES" ;
			$result = $this->pdo->executeQuery(array(), $sql);   
			return true;
		}
		catch(PDOException $e) {
			return false;
		}	
    }

    public function createImportLog( $gibbonPersonID, $type, $results = array(), $columnOrder = array() ) {
    	
    	$success = ( $results['importSuccess'] && $results['buildSuccess'] && $results['databaseSuccess'] );

		$data=array("gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "success"=>$success, "importResults"=>serialize($results), "columnOrder"=>serialize($columnOrder) ); 

		$sql="INSERT INTO importLog SET gibbonPersonID=:gibbonPersonID, type=:type, success=:success, importResults=:importResults, columnOrder=:columnOrder" ;
		$result=$this->pdo->executeQuery($data, $sql);
	
		return $this->pdo->getQuerySuccess();
    }

    /**
     * Validates the supplied MIME Type with a list of valid types
     *
     * @since    25th April 2016
     * @param    {string} fileMimeType
     * @return   {boolean}
     */
    public function isValidMimeType( $fileMimeType ) {
    	return in_array( $fileMimeType, $this->csvMimeTypes );
    }

}

?>