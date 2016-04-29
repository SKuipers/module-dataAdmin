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

namespace ExtendedImport;

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

	public $fieldDelimiter = ',';
	public $stringEnclosure = '"';
	public $maxLineLength = 100000;

	public $mode;
	public $syncField;
	public $syncColumn;

	/**
	 * File handler for line-by-line CSV read
	 */
	private $csvFileHandler;

	/**
	 * Array of header names from first CSV line
	 */
	private $importHeaders;

	/**
	 * Array of raw parsed CSV records
	 */
	private $importData;

	/**
	 * Array of validated, database-friendly records
	 */
	private $tableData;

	private $rowErrors = array();
	private $importErrors = array();
	private $importWarnings = array();

	/**
	 * ID of the last error message
	 */
	private $errorID = 0;

	/**
	 * Current counts for database operations
	 */
	private $databaseResults = array(
		'inserts' => 0,
		'inserts_skipped' => 0,
		'updates' => 0,
		'updates_skipped' => 0,
		'duplicates' => 0
	);

	/**
	 * Valid import MIME types
	 */
	private $csvMimeTypes = array(
		"text/csv","text/comma-separated-values","text/x-comma-separated-values","application/vnd.ms-excel","application/csv"
	);
	
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
    public function __construct(\Gibbon\session $session = NULL, \Gibbon\config $config = NULL, \Gibbon\sqlConnection $pdo = NULL)
    {
        if ($session === NULL)
            $this->session = new \Gibbon\session();
        else
            $this->session = $session ;

        if ($config === NULL)
            $this->config = new \Gibbon\config();
        else
            $this->config = $config ;

        if ($pdo === NULL)
            $this->pdo = new \Gibbon\sqlConnection();
        else
            $this->pdo = $pdo ;
    }

    /**
     * Is Valid Mime Type
     * Validates the supplied MIME Type with a list of valid types
     *
     * @access  public
     * @version	25th April 2016
     * @since	25th April 2016
     * @param	string	MIME Type
     *
     * @return	bool
     */
    public function isValidMimeType( $fileMimeType ) {
    	return in_array( $fileMimeType, $this->csvMimeTypes );
    }

    /**
     * Open CSV File
     *
     * @access  public
     * @version	27th April 2016
     * @since	27th April 2016
     * @param	string	Full File Path
     *
     * @return	bool	true on success
     */
    public function openCSVFile( $csvFile ) {

    	ini_set("auto_detect_line_endings", true);
		$this->csvFileHandler=fopen($csvFile, "r");
		return ($this->csvFileHandler !== FALSE);
    }

    /**
     * Close CSV File
     *
     * @access  public
     * @version	27th April 2016
     * @since	27th April 2016
     */
    public function closeCSVFile() {
    	fclose($this->csvFileHandler);
    }

    /**
     * Get CSV Line
     *
     * @access  public
     * @version	27th April 2016
     * @since	27th April 2016
     *
     * @return	array	Next parsed CSV line, based on current handler
     */
    public function getCSVLine() {
    	return fgetcsv($this->csvFileHandler, $this->maxLineLength, $this->fieldDelimiter, $this->stringEnclosure);
    }

    /**
     * Read CSV String
     *
     * @access  public
     * @version	27th April 2016
     * @since	27th April 2016
     * @param	string	CSV Data
     *
     * @return	bool	true on successful CSV parse
     */
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

    /**
     * Build Table Data
     * Iterate over the imported records, validating and building table data for each one
     *
     * @access  public
     * @version	28th April 2016
     * @since	28th April 2016
     * @param	Object	Import Type
     * @param	array	Column Order
     * @param	array	Custom user-provided values
     *
     * @return	bool	true if build succeeded
     */
    public function buildTableData( $importType, $columnOrder, $customValues = array() ) {

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
				if ($columnIndex == importer::COLUMN_DATA_SKIP) {
					$fieldCount++;
					continue;
				}
				// Get the custom text value provided by the user (from Step 2)
				else if ($columnIndex == importer::COLUMN_DATA_CUSTOM) {
					
					$value = (isset($customValues[ $fieldCount ]))? $customValues[ $fieldCount ] : '';
				}
				// Run a user_func based on the function name defined for that field
				else if ($columnIndex == importer::COLUMN_DATA_FUNCTION) {
					
					$value = $importType->doImportFunction( $fieldName );
				}
				// Use the column index to grab to associated CSV value
				else {
					// Get the associative key from the CSV headers using the current index
					$columnKey = (isset($this->importHeaders[$columnIndex]))? $this->importHeaders[$columnIndex] : -1;
					$value = (isset($row[ $columnKey ]))? $row[ $columnKey ] : NULL;
				}
				
				// Filter & validate the value
				$validate = $importType->validateFieldValue( $fieldName, $value );
				if ($validate == false ) {
					$this->logError( $rowNum, importer::ERROR_INVALID_FIELD_VALUE, $fieldName, $fieldCount,
						array($importType->getField($fieldName, 'type'), $importType->getField($fieldName, 'length')) );

					$partialFail = TRUE;
				}

				if ( empty($value) && $importType->isFieldRequired($fieldName) ) {
					$this->logError( $rowNum, importer::ERROR_REQUIRED_FIELD_MISSING, $fieldName, $fieldCount);
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

    /**
     * Import Into Database
     * Iterate over the table data and INSERT or UPDATE the database, checking for existing records
     *
     * @access  public
     * @version	28th April 2016
     * @since	28th April 2016
     * @param	Object	Import Type
     * @param	bool	Update the database?
     *
     * @return	bool	true if import succeeded
     */
    public function importIntoDatabase( $importType, $liveRun = TRUE ) {

        if ($liveRun) {
        	if ( $this->lockTables( $importType->getTables() ) == false) {
        		$this->errorID = importer::ERROR_LOCKING_DATABASE;
        		return false;
        	}
        }

		if (empty($this->tableData)) {
			return false;
		}

		$tableName = $importType->getDetail('table');
		$primaryKey = $importType->getDetail('primary');
		$selectionKey = $importType->getDetail('select');

		$partialFail = FALSE;
		foreach ($this->tableData as $rowNum => $row) {

			// Ensure we have a valid key
			if (!isset($row[$selectionKey])) {
				$this->logError( $rowNum, importer::ERROR_KEY_MISSING, $selectionKey );
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
				$this->logError( $rowNum, importer::ERROR_DATABASE_GENERIC, $selectionKey );
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
					$this->logError( $rowNum, importer::WARNING_DUPLICATE_KEY, $selectionKey, $primaryKeyValue );
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
					$this->logError( $rowNum, importer::ERROR_DATABASE_FAILED_UPDATE, $e->getMessage() );
					$partialFail = TRUE;
					continue;
				}

			}

			// Handle New Records
			else if ($result->rowCount() == 0) {

				// Dont add records on UPDATE ONLY mode
				if ($this->mode == 'update') {
					$this->logError( $rowNum, importer::WARNING_RECORD_NOT_FOUND, $selectionKey, $row[ $selectionKey ] );
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
					$this->logError( $rowNum, importer::ERROR_DATABASE_FAILED_INSERT, $e->getMessage() );
					$partialFail = TRUE;
					continue;
				}
				
			}
			else {
				$this->logError( $rowNum, importer::ERROR_DATABASE_GENERIC, $selectionKey, $primaryKeyValue );
				$partialFail = TRUE;
			}

		}

        if ($liveRun) {
        	$partialFail = ($this->unlockTables() == false);
        }

		return (!$partialFail);
    }

    /**
     * Get Row Count
     *
     * @access  public
     * @version	27th April 2016
     * @since	27th April 2016
     *
     * @return	int		Count of rows imported from file
     */
    public function getRowCount() {
    	return count($this->importData);
    }

    /**
     * Get Database Results
     *
     * @access  public
     * @since	28th April 2016
     * @return	int		Current count of a database operation
     */
    public function getDatabaseResult( $key ) {
    	return (isset($this->databaseResults[$key]))? $this->databaseResults[$key] : 'unknown';
    }

    /**
     * Get Errors
     *
     * @access  public
     * @since	28th April 2016
     * @return	array	Errors logged with logError
     */
    public function getErrors() {
    	return $this->importErrors;
    }

    /**
     * Get Warnings
     *
     * @access  public
     * @since	28th April 2016
     * @return	array	Warnings logged with logError
     */
    public function getWarnings() {
    	return $this->importWarnings;
    }

    /**
     * Get Error Count
     *
     * @access  public
     * @since	28th April 2016
     * @return	int		Error count
     */
    public function getErrorCount() {
    	return count($this->importErrors);
    }

    /**
     * Get Error Row Count
     *
     * @access  public
     * @since	28th April 2016
     * @return	int		Count of rows with errors
     */
    public function getErrorRowCount() {
    	return count($this->rowErrors);
    }

    /**
     * Get Warning Count
     *
     * @access  public
     * @since	28th April 2016
     * @return	int		Warning count
     */
    public function getWarningCount() {
    	return count($this->importWarnings);
    }

    /**
     * Get Last Error
     *
     * @access  public
     * @since	28th April 2016
     * @return	string	Translated error message
     */
    public function getLastError() {
    	return $this->errorMessage( $this->errorID );
    }

    /**
     * Log Error
     *
     * @access  public
     * @version	28th April 2016
     * @since	28th April 2016
     * @param	int		Row Number
     * @param	int		Error ID
     * @param	string	Field Name
     * @param	string	Field Index
     * @param	array	Values to pass to String Format
     */
    public function logError( $rowNum, $errorID, $fieldName, $fieldNum = -1, $args = array() ) {

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

    /**
     * Error Message
     *
     * @access  public
     * @version	28th April 2016
     * @since	28th April 2016
     * @param	int		Error ID
     *
     * @return	string	Translated error message
     */
    public function errorMessage( $errorID ) {

    	switch ($errorID) {
    		// ERRORS
    		case importer::ERROR_REQUIRED_FIELD_MISSING: 
    			return __( $this->config->get('guid'), "Missing value for required field."); break;
    		case importer::ERROR_INVALID_FIELD_VALUE: 
    			return __( $this->config->get('guid'), "Invalid value type for field: %s Expected: %s(%s)"); break;
    		case importer::ERROR_INVALID_INPUTS:
    			return __( $this->config->get('guid'), "Your request failed because your inputs were invalid."); break;
    		case importer::ERROR_LOCKING_DATABASE:
    			return __( $this->config->get('guid'), "The database could not be locked/unlocked for use."); break;
    		case importer::ERROR_KEY_MISSING:
    			return __($this->config->get('guid'), "Missing value for primary key."); break;
    		case importer::ERROR_DATABASE_GENERIC:
    			return __($this->config->get('guid'), "There was an error accessing the database."); break;
    		case importer::ERROR_DATABASE_FAILED_INSERT:
    			return __($this->config->get('guid'), "Failed to insert record into database."); break;
    		case importer::ERROR_DATABASE_FAILED_UPDATE:
    			return __($this->config->get('guid'), "Failed to update database record."); break;
    		// WARNINGS
    		case importer::WARNING_DUPLICATE_KEY:
    			return __($this->config->get('guid'), "A duplicate entry already exists for this record. Record skipped."); break;
    		case importer::WARNING_RECORD_NOT_FOUND:
    			return __($this->config->get('guid'), "A database entry for this record could not be found. Record skipped."); break;
    		default:
    			return __( $this->config->get('guid'), "An unknown error occured, so the import will be aborted."); break;

    		
    	}
    }

    /**
     * Create Import Log
     * Inserts a record of an import into the database
     *
     * @access  public
     * @version	25th April 2016
     * @since	25th April 2016
     * @param	string	gibbonPersonID
     * @param	string	Import Type name
     * @param	array	Results of the import
     * @param	array	Column order used
     *
     * @return	bool
     */
    public function createImportLog( $gibbonPersonID, $type, $results = array(), $columnOrder = array() ) {
    	
    	$success = ( $results['importSuccess'] && $results['buildSuccess'] && $results['databaseSuccess'] );

		$data=array("gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "success"=>$success, "importResults"=>serialize($results), "columnOrder"=>serialize($columnOrder) ); 

		$sql="INSERT INTO extendedImportLog SET gibbonPersonID=:gibbonPersonID, type=:type, success=:success, importResults=:importResults, columnOrder=:columnOrder" ;
		$result=$this->pdo->executeQuery($data, $sql);
	
		return $this->pdo->getQuerySuccess();
    }

    /**
     * Lock Tables
     *
     * @access  private
     * @version	28th April 2016
     * @since	28th April 2016
     * @param	array	Tables to be locked
     *
     * @return	bool	true if database is now locked
     */
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

    /**
     * Unlock Tables
     *
     * @access  private
     * @version	28th April 2016
     * @since	28th April 2016
     *
     * @return	bool	true if database is now unlocked
     */
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

}

?>