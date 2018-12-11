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

namespace Gibbon\Module\DataAdmin;

use Gibbon\Contracts\Database\Connection;

/**
 * Extended Import class
 *
 * @version	25th April 2016
 * @since	25th April 2016
 * @author	Sandra Kuipers
 */
class Importer
{
    const COLUMN_DATA_SKIP = -1;
    const COLUMN_DATA_CUSTOM = -2;
    const COLUMN_DATA_FUNCTION = -3;
    const COLUMN_DATA_LINKED = -4;
    const COLUMN_DATA_HIDDEN = -5;

    const ERROR_IMPORT_FILE = 200;
    const ERROR_INVALID_INPUTS = 201;
    const ERROR_REQUIRED_FIELD_MISSING = 205;
    const ERROR_INVALID_FIELD_VALUE = 206;
    const ERROR_LOCKING_DATABASE = 207;
    const ERROR_DATABASE_GENERIC = 208;
    const ERROR_DATABASE_FAILED_INSERT = 209;
    const ERROR_DATABASE_FAILED_UPDATE = 210;
    const ERROR_KEY_MISSING = 211;
    const ERROR_NON_UNIQUE_KEY =212;
    const ERROR_RELATIONAL_FIELD_MISMATCH = 213;
    const ERROR_INVALID_HAS_SPACES = 214;

    const WARNING_DUPLICATE_KEY = 101;
    const WARNING_RECORD_NOT_FOUND = 102;

    const MESSAGE_GENERATED_PASSWORD = 10;

    public $fieldDelimiter = ',';
    public $stringEnclosure = '"';
    public $maxLineLength = 100000;

    public $mode;
    public $syncField;
    public $syncColumn;
    
    public $outputData = array();

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
    private $tableData = array();
    private $tableFields = array();

    private $serializeData = array();

    /**
     * Errors
     */
    private $importLog = array( 'error' => array(), 'warning' => array(), 'message' => array() );
    private $rowErrors = array();

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
        'text/csv', 'text/xml', 'text/comma-separated-values', 'text/x-comma-separated-values', 'application/vnd.ms-excel', 'application/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/vnd.oasis.opendocument.spreadsheet', 'application/octet-stream',
    );

    /**
     * Gibbon\Contracts\Database\Connection
     */
    private $pdo ;

    /**
     * Gibbon\session
     */
    private $gibbon ;

    private $headerRow;
    private $firstRow;

    /**
     * Constructor
     *
     * @version  25th April 2016
     * @since    25th April 2016
     * @param    Gibbon\session
     * @param    Gibbon\config
     * @param    Gibbon\Contracts\Database\Connection
     * @return    void
     */
    public function __construct(\Gibbon\Core $gibbon, Connection $pdo)
    {
        $this->gibbon = $gibbon;
        $this->pdo = $pdo;
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        throw new \Exception('Trying to access a read-only property.');
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

        $csv = new ParseCSV();
        $csv->heading = true;
        $csv->delimiter = $this->fieldDelimiter;
        $csv->enclosure = $this->stringEnclosure;

        $csv->parse( $csvString );

        $this->importHeaders = $csv->titles ?? [];
        $this->importData = $csv->data ?? [];

        $this->importLog['error'] = $csv->error_info;
        unset($csv);

        foreach ($this->importLog['error'] as $error) {
            $this->rowErrors[ $error['row'] ] = 1;
        }

        return (!empty($this->importHeaders) && count($this->importData) > 0 && count($this->rowErrors) == 0 );
    }


    public function readFileIntoCSV() {

        $data = '';

        $fileType = mb_substr($_FILES['file']['name'], mb_strpos($_FILES['file']['name'], '.')+1);
        $fileType = mb_strtolower($fileType);
        $mimeType = $_FILES['file']['type'];

        if ($fileType == 'csv') {

            $opts = array('http' => array('header' => "Accept-Charset: utf-8;q=0.7,*;q=0.7\r\n"."Content-Type: text/html; charset =utf-8\r\n"));
            $context = stream_context_create($opts);

            $data = file_get_contents($_FILES['file']['tmp_name'], false, $context);
            if ( mb_check_encoding($data, 'UTF-8') == false ) {
                $data = mb_convert_encoding($data,'UTF-8');
            }

            // Grab the header & first row for Step 1
            if ($this->openCSVFile( $_FILES['file']['tmp_name'] )) {
                $this->headerRow = $this->getCSVLine();
                $this->firstRow = $this->getCSVLine();
                $this->closeCSVFile();
            }

        }
        else if ($fileType == 'xlsx' || $fileType == 'xls' || $fileType == 'xml' || $fileType == 'ods') {

            $filePath = $_FILES['file']['tmp_name'];

            // Try to use the best reader if available, otherwise catch any read errors
            try {
                if ($fileType == 'xml') {
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2003XML');
                    $objPHPExcel = $objReader->load( $filePath );
                } else {
                    $objPHPExcel = \PHPExcel_IOFactory::load( $filePath );
                }
            } catch(\PHPExcel_Reader_Exception $e) {
                $this->errorID = Importer::ERROR_IMPORT_FILE;
                return false;
            }

            $objWorksheet = $objPHPExcel->getActiveSheet();
            $lastColumn = $objWorksheet->getHighestColumn();

            // Grab the header & first row for Step 1
            foreach( $objWorksheet->getRowIterator(0, 2) as $rowIndex => $row ){
                $array = $objWorksheet->rangeToArray('A'.$rowIndex.':'.$lastColumn.$rowIndex, null, true, true, false);

                if ($rowIndex == 1) $this->headerRow = $array[0];
                else if ($rowIndex == 2) $this->firstRow = $array[0];
            }

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');

            // Export back to CSV
            ob_start();
            $objWriter->save('php://output');
            $data = ob_get_clean();
        }

        return $data;
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

        if ( empty($this->importData) ) return false;

        $this->tableData = array();

        foreach ( $this->importData as $rowNum => $row ) {

            $fields = array();
            $fieldCount = 0;
            $partialFail = FALSE;
            foreach ($importType->getTableFields() as $fieldName) {
                $columnIndex = $columnOrder[ $fieldCount ];

                $value = NULL;
                // Skip marked columns
                if ($columnIndex == Importer::COLUMN_DATA_SKIP) {
                    $fieldCount++;
                    continue;
                }
                // Get the custom text value provided by the user (from Step 2)
                else if ($columnIndex == Importer::COLUMN_DATA_CUSTOM) {

                    $value = (isset($customValues[ $fieldCount ]))? $customValues[ $fieldCount ] : '';
                }
                // Run a user_func based on the function name defined for that field
                else if ($columnIndex == Importer::COLUMN_DATA_FUNCTION) {

                    $value = $importType->doImportFunction( $fieldName );
                }
                // Grab another field value for linked fields. Fields with values must always preceed the linked field.
                else if ($columnIndex == Importer::COLUMN_DATA_LINKED) {

                    if ($importType->isFieldLinked($fieldName)) {
                        $linkedFieldName = $importType->getField($fieldName, 'linked');
                        $value = (isset($fields[ $linkedFieldName ]))? $fields[ $linkedFieldName ] : null;
                    }
                }
                // Use the column index to grab to associated CSV value
                else if ($columnIndex >= 0) {
                    // Get the associative key from the CSV headers using the current index
                    $columnKey = (isset($this->importHeaders[$columnIndex]))? $this->importHeaders[$columnIndex] : -1;
                    $value = (isset($row[ $columnKey ]))? $row[ $columnKey ] : NULL;
                }

                // Filter
                $value = $importType->filterFieldValue( $fieldName, $value );
                $filter = $importType->getField($fieldName, 'filter');

                // Validate the value
                if ( $importType->validateFieldValue( $fieldName, $value ) === false ) {
                    $type = $importType->getField($fieldName, 'type');

                    if ($filter == 'nospaces') {
                        $this->log( $rowNum, Importer::ERROR_INVALID_HAS_SPACES, $fieldName, $fieldCount, array($value) );
                    } else {
                        $expectation = (!empty($type))? $importType->readableFieldType($fieldName) : $filter;
                        $this->log( $rowNum, Importer::ERROR_INVALID_FIELD_VALUE, $fieldName, $fieldCount, array($value, $expectation) );
                    }

                    $partialFail = TRUE;
                }

                // Handle relational table data
                // Moved from Insert/Update queries so we can confirm on the dry run (and multi-key relationships)
                if ( $importType->isFieldRelational($fieldName) ) {
                    $join = ''; $on = '';
                    extract( $importType->getField($fieldName, 'relationship') );

                    $table = $this->escapeIdentifier($table);
                    $join = $this->escapeIdentifier($join);

                    // Handle table joins
                    $tableJoin = '';
                    if (!empty($join) && !empty($on)) {
                        if (is_array($on) && count($on) == 2) {
                            $tableJoin = "JOIN {$join} ON ({$join}.{$on[0]}={$table}.{$on[1]})";
                        }
                    }

                    // Handle relational fields with CSV data
                    $values = $filter == 'csv' ? array_map('trim', explode(',', $value)) : [$value];
                    $relationalValue = [];

                    foreach ($values as $value) {
                        // Muli-key relationships
                        if (is_array($field) && count($field) > 0 ) {
                            $relationalField = $this->escapeIdentifier($field[0]);
                            $relationalData = array( $fieldName => $value );
                            $relationalSQL = "SELECT {$table}.{$key} FROM {$table} {$tableJoin} WHERE {$relationalField}=:{$fieldName}";

                            for ($i=1; $i<count($field); $i++) {
                                // Relational field from within current import data
                                $relationalField = $field[$i];
                                if (isset($fields[ $relationalField ])) {
                                    $relationalData[ $relationalField ] = $fields[ $relationalField ];
                                    $relationalSQL .= " AND ".$this->escapeIdentifier($relationalField)."=:{$relationalField}";
                                }
                            }
                        // Single key/value relationship
                        } else {
                            $relationalField = $this->escapeIdentifier($field);
                            $relationalData = array( $fieldName => $value );
                            $relationalSQL = "SELECT {$table}.{$key} FROM {$table} {$tableJoin} WHERE {$table}.{$relationalField}=:{$fieldName}";
                        }

                        $result = $this->pdo->executeQuery($relationalData, $relationalSQL);
                        if ($result->rowCount() > 0) {
                            $relationalValue[] = $result->fetchColumn(0);
                        } else {
                            // Missing relation for required field? Or missing a relation when value is provided?
                            if (!empty($value) || $importType->isFieldRequired($fieldName)) {
                                $field = (is_array($field))? implode(', ', $field) : $field;
                                $this->log( $rowNum, Importer::ERROR_RELATIONAL_FIELD_MISMATCH, $fieldName, $fieldCount,
                                    array($importType->getField($fieldName, 'name'), $value, $field, $table) );
                                $partialFail = TRUE;
                            }
                        }
                    }

                    $value = implode(',', $relationalValue);
                }

                // Required field is empty?
                if ( (!isset($value) || $value === NULL) && $importType->isFieldRequired($fieldName) ) {
                    $this->log( $rowNum, Importer::ERROR_REQUIRED_FIELD_MISSING, $fieldName, $fieldCount);
                    $partialFail = TRUE;
                }

                // Do we serialize this data?
                $serialize = $importType->getField($fieldName, 'serialize');
                if ( !empty($serialize) ) {

                    // Is this the field we're serializing? Grab the array
                    if ($serialize == $fieldName) {
                        $value = serialize( $this->serializeData[ $serialize ] );
                        $fields[ $fieldName ] = $value;
                    }
                    // Otherwise collect values in an array
                    else {
                        $customField = $importType->getField($fieldName, 'customField');
                        $this->serializeData[ $serialize ][ $customField ] = $value;
                    }


                }
                // Add the field to the field set for this row
                else {

                    $fields[ $fieldName ] = $value;
                }

                $fieldCount++;
            }

            // Add the primary key if we're syncing with a databse ID
            if ($this->syncField == true) {

                if (isset($row[ $this->syncColumn ]) && !empty($row[ $this->syncColumn ])) {
                    $fields[ $importType->getPrimaryKey() ] = $row[ $this->syncColumn ];
                } else {
                    $this->log( $rowNum, Importer::ERROR_REQUIRED_FIELD_MISSING, $importType->getPrimaryKey(), $this->syncColumn);
                    $partialFail = TRUE;
                }
            }

            // Salt & hash passwords
            if ( isset($fields['password'] ) ) {
                if (!isset($this->outputData['passwords'])) $this->outputData['passwords'] = [];
                $this->outputData['passwords'][] = ['username' => $fields['username'], 'password' => $fields['password']];

                $salt=getSalt() ;
                $value=$fields['password'];
                $fields[ 'passwordStrong' ] = hash("sha256", $salt.$value);
                $fields[ 'passwordStrongSalt' ] = $salt;
                $fields[ 'password' ] = '';

            }

            if (!empty($fields) && $partialFail == FALSE) {
                $this->tableData[] = $fields;
            }
        }

        if ( count($this->tableData) > 0 && isset($this->tableData[0]) ) {
            $this->tableFields = array_keys($this->tableData[0]);
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

        if (empty($this->tableData) || count($this->tableData) < 1) {
            return false;
        }

        $tableName = $this->escapeIdentifier( $importType->getDetail('table') );
        $primaryKey = $importType->getPrimaryKey();

        // Setup the query string for keys
        $sqlKeyQueryString = $this->getKeyQueryString( $importType );

        $partialFail = FALSE;
        foreach ($this->tableData as $rowNum => $row) {

            // Ensure we have valid key(s)
            if ( !empty($importType->getUniqueKeyFields()) && array_diff($importType->getUniqueKeyFields(), array_keys($row) ) != false ) {
                $this->log( $rowNum, Importer::ERROR_KEY_MISSING );
                $partialFail = TRUE;
                continue;
            }

            // Find existing record(s)
            try {
                $data = array();
                // Add the unique keys
                foreach ($importType->getUniqueKeyFields() as $keyField) {
                    $data[ $keyField ] = $row[ $keyField ];
                }
                // Add the primary key if database IDs is enabled
                if ($this->syncField == true) {
                    $data[ $primaryKey ] = $row[ $primaryKey ];
                }

                //print_r($data);
                //echo '<br/>'.$sqlKeyQueryString.'<br/>';
                //print_r( array_keys($row) );

                $result = $this->pdo->executeQuery($data, $sqlKeyQueryString);
                $keyRow = $result->fetch();
            }

            catch(PDOException $e) {
                $this->log( $rowNum, Importer::ERROR_DATABASE_GENERIC );
                $partialFail = TRUE;
                continue;
            }

            // Build the data and query field=:value associations
            $sqlFields = array();
            $sqlData = array();

            foreach ($row as $fieldName => $fieldData ) {

                if ( $importType->isFieldReadOnly($fieldName) || ($this->mode == 'update' && $fieldName == $primaryKey) ) {
                    continue;
                } else {
                    $sqlFields[] = $this->escapeIdentifier($fieldName) . "=:" . $fieldName;
                    $sqlData[ $fieldName ] = $fieldData;
                }

                // Handle merging existing custom field data with partial custom field imports
                if ($importType->isUsingCustomFields() && $fieldName == 'fields') {
                    if (isset($keyRow['fields']) && !empty($keyRow['fields'])) {
                        $sqlData['fields'] = array_merge( unserialize($keyRow['fields']) , unserialize($fieldData) );
                        $sqlData['fields'] = serialize($sqlData['fields']);
                    }
                }
            }

            $sqlFieldString = implode(", ", $sqlFields );


            // Handle Existing Records
            if ($result->rowCount() == 1) {

                $primaryKeyValue = $keyRow[ $primaryKey ];

                // Dont update records on INSERT ONLY mode
                if ($this->mode == 'insert') {
                    $this->log( $rowNum, Importer::WARNING_DUPLICATE_KEY, $primaryKey, $primaryKeyValue );
                    $this->databaseResults['updates_skipped'] += 1;
                    continue;
                }

                // If these IDs don't match, then one of the unique keys matched (eg: non-unique value with different database ID)
                if ($this->syncField == true && $primaryKeyValue != $row[ $primaryKey ] ) {
                    $this->log( $rowNum, Importer::ERROR_NON_UNIQUE_KEY, $primaryKey, $row[ $primaryKey ], array( $primaryKey, intval($primaryKeyValue) ) );
                    $this->databaseResults['updates_skipped'] += 1;
                    continue;
                }

                $this->databaseResults['updates'] += 1;

                // Skip now so we dont change the database
                if (!$liveRun) continue;

                try {
                    $sqlData[ $primaryKey ] = $primaryKeyValue;
                    $sql="UPDATE {$tableName} SET " . $sqlFieldString . " WHERE ".$this->escapeIdentifier($primaryKey)."=:{$primaryKey}" ;
                    $this->pdo->executeQuery($sqlData, $sql);
                }
                catch(PDOException $e) {
                    $this->log( $rowNum, Importer::ERROR_DATABASE_FAILED_UPDATE, $e->getMessage() );
                    $partialFail = TRUE;
                    continue;
                }

            }

            // Handle New Records
            else if ($result->rowCount() == 0) {

                // Dont add records on UPDATE ONLY mode
                if ($this->mode == 'update') {
                    $this->log( $rowNum, Importer::WARNING_RECORD_NOT_FOUND );
                    $this->databaseResults['inserts_skipped'] += 1;
                    continue;
                }

                $this->databaseResults['inserts'] += 1;

                // Skip now so we dont change the database
                if (!$liveRun) continue;

                try {
                    $sql="INSERT INTO {$tableName} SET ".$sqlFieldString;
                    $this->pdo->executeQuery($sqlData, $sql);
                }
                catch(PDOException $e) {
                    $this->log( $rowNum, Importer::ERROR_DATABASE_FAILED_INSERT, $e->getMessage() );
                    $partialFail = TRUE;
                    continue;
                }

            }
            else {

                $primaryKeyValues = $result->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE, 0);
                $this->log( $rowNum, Importer::ERROR_NON_UNIQUE_KEY, $primaryKey, -1, array( $primaryKey, implode(', ',$primaryKeyValues) ) );
                $partialFail = TRUE;
            }
        }

        return (!$partialFail);
    }

    protected function getKeyQueryString( $importType ) {

        $tableName = $this->escapeIdentifier( $importType->getDetail('table') );
        $primaryKey = $importType->getPrimaryKey();
        $primaryKeyField = $this->escapeIdentifier($primaryKey);

        $sqlKeys = array();
        foreach ( $importType->getUniqueKeys() as $uniqueKey ) {

            // Handle multi-part unique keys (eg: school year AND course short name)
            if ( is_array($uniqueKey) && count($uniqueKey) > 1 ) {

                $sqlKeysFields = array();
                foreach ($uniqueKey as $fieldName) {
                    if (!in_array($fieldName, $this->tableFields) ) continue;

                    $fieldNameField = $this->escapeIdentifier($fieldName);
                    $sqlKeysFields[] = "({$fieldNameField}=:{$fieldName} AND {$fieldNameField} IS NOT NULL)";
                }
                $sqlKeys[] = '('. implode(' AND ', $sqlKeysFields ) .')';
            } else {
                // Skip key fields which dont exist in our imported data set
                if (!in_array($uniqueKey, $this->tableFields) ) continue;

                $uniqueKeyField = $this->escapeIdentifier($uniqueKey);
                $sqlKeys[] = "({$uniqueKeyField}=:{$uniqueKey} AND {$uniqueKeyField} IS NOT NULL)";
            }

        }

        // Add the primary key if database IDs is enabled
        if ($this->syncField == true) {
            $sqlKeys[] = $primaryKeyField.'=:'.$primaryKey;
        }

        $sqlKeyString = implode(' OR ', $sqlKeys );

        if (empty($sqlKeyString)) $sqlKeyString = "FALSE";

        if ($importType->isUsingCustomFields()) {
            $primaryKeyField = $primaryKeyField.", fields";
        }

        return "SELECT {$tableName}.{$primaryKeyField} FROM {$tableName} WHERE ". $sqlKeyString ;
    }

    protected function escapeIdentifier($text) {
        return "`".str_replace("`","``",$text)."`";
    }

    /**
     * Get Header Row
     *
     * @access  public
     * @version 1st December 2016
     * @since   1st December 2016
     *
     * @return  array     Row data
     */
    public function getHeaderRow() {
        return $this->headerRow;
    }

    /**
     * Get First Row
     *
     * @access  public
     * @version 1st December 2016
     * @since   1st December 2016
     *
     * @return  array     Row data
     */
    public function getFirstRow() {
        return $this->firstRow;
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
     * Get Logs
     *
     * @access  public
     * @since	28th April 2016
     * @return	array	Errors logged with logError
     */
    public function getLogs() {
        return array_merge($this->importLog['message'], $this->importLog['warning'], $this->importLog['error']);
    }

    /**
     * Get Warning Count
     *
     * @access  public
     * @since   28th April 2016
     * @return  int     Warning count
     */
    public function getWarningCount() {
        return count($this->importLog['warning']);
    }

    /**
     * Get Error Count
     *
     * @access  public
     * @since	28th April 2016
     * @return	int		Error count
     */
    public function getErrorCount() {
        return count($this->importLog['error']);
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
     * Get Last Error
     *
     * @access  public
     * @since	28th April 2016
     * @return	string	Translated error message
     */
    public function getLastError() {
        return $this->translateMessage( $this->errorID );
    }

    /**
     * Log
     *
     * @access  protected
     * @version	27th May 2016
     * @since	28th April 2016
     * @param	int		Row Number
     * @param	int		Error ID
     * @param	string	Field Name
     * @param	string	Field Index
     * @param	array	Values to pass to String Format
     */
    protected function log( $rowNum, $messageID, $fieldName = '', $fieldNum = -1, $args = array() ) {

        if ($messageID > 200 ) {
            $type = 'error';
        } else if ($messageID > 100 ) {
            $type = 'warning';
        } else {
            $type = 'message';
        }

        $this->importLog[ $type ][] = array(
            'index' => $rowNum,
            'row' => $rowNum+2,
            'info' => vsprintf( $this->translateMessage($messageID), $args ),
            'field_name' => $fieldName,
            'field' => $fieldNum,
            'type' => $type
        );

        if ( $type == 'error' ) {
            $this->rowErrors[ $rowNum ] = 1;
        }
    }

    /**
     * Error Message
     *
     * @access  protected
     * @version	27th May 2016
     * @since	28th April 2016
     * @param	int		Error ID
     *
     * @return	string	Translated error message
     */
    protected function translateMessage( $errorID ) {

        switch ($errorID) {
            // ERRORS
            case Importer::ERROR_IMPORT_FILE:
                return __("There was an error reading the import file type %s", 'Data Admin'); break;
            case Importer::ERROR_REQUIRED_FIELD_MISSING:
                return __("Missing value for required field.", 'Data Admin'); break;
            case Importer::ERROR_INVALID_FIELD_VALUE:
                return __("Invalid value: \"%s\".  Expected: %s", 'Data Admin'); break;
            case Importer::ERROR_INVALID_HAS_SPACES:
                return __("Invalid value: \"%s\".  This field type cannot contain spaces.", 'Data Admin'); break;
            case Importer::ERROR_INVALID_INPUTS:
                return __("Your request failed because your inputs were invalid.", 'Data Admin'); break;
            case Importer::ERROR_LOCKING_DATABASE:
                return __("The database could not be locked/unlocked for use.", 'Data Admin'); break;
            case Importer::ERROR_KEY_MISSING:
                return __("Missing value for primary key or unique key set.", 'Data Admin'); break;
            case Importer::ERROR_NON_UNIQUE_KEY:
                return __("Encountered non-unique values used by %s: %s", 'Data Admin'); break;
            case Importer::ERROR_DATABASE_GENERIC:
                return __("There was an error accessing the database.", 'Data Admin'); break;
            case Importer::ERROR_DATABASE_FAILED_INSERT:
                return __("Failed to insert record into database.", 'Data Admin'); break;
            case Importer::ERROR_DATABASE_FAILED_UPDATE:
                return __("Failed to update database record.", 'Data Admin'); break;
            case Importer::ERROR_RELATIONAL_FIELD_MISMATCH:
                return __("%s: %s does not match an existing %s in %s", 'Data Admin'); break;
            // WARNINGS
            case Importer::WARNING_DUPLICATE_KEY:
                return __("A duplicate entry already exists for this record. Record skipped.", 'Data Admin'); break;
            case Importer::WARNING_RECORD_NOT_FOUND:
                return __("A database entry for this record could not be found. Record skipped.", 'Data Admin'); break;
            // MESSAGES
            case Importer::MESSAGE_GENERATED_PASSWORD:
                return __("Password generated for user %s: %s", 'Data Admin'); break;
            default:
                return __("An error occured, the import was aborted.", 'Data Admin'); break;
        }
    }

    /**
     * Inserts a record of an import into the database
     *
     * @param	string	gibbonPersonID
     * @param	string	Import Type name
     * @param	array	Results of the import
     * @param	array	Column order used
     * @return	bool
     */
    public function createImportLog($gibbonPersonID, $type, $results = [], $columnOrder = []) {

        $success = (( $results['importSuccess'] && $results['buildSuccess'] && $results['databaseSuccess'] ) || $results['ignoreErrors']);

        $log = [
            'type'        => $type,
            'success'     => $success,
            'results'     => $results,
            'columnOrder' => $columnOrder,
        ];

        $data = [
            'gibbonPersonID'  => $gibbonPersonID,
            'title'           => 'Import - '.$type,
            'serialisedArray' => serialize($log),
            'ip'              => getIPAddress(),
        ];

        $sql = "INSERT INTO gibbonLog SET gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current'), gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='System Admin'), gibbonPersonID=:gibbonPersonID, title=:title, serialisedArray=:serialisedArray, ip=:ip";

        return $this->pdo->insert($sql, $data);
    }
}
