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

namespace DataAdmin;


use Library\Yaml\Yaml ;


/**
 * Reads and holds the config info for a custom Import Type
 *
 * @version 25th April 2016
 * @since   25th April 2016
 * @author  Sandra Kuipers
 */
class importType
{
    /**
     * Information about the overall Import Type
     */
    protected $details = array();

    /**
     * Permission information for user access
     */
    protected $access = array();

    /**
     * Values that can be used for sync & updates
     */
    protected $primaryKey;
    protected $uniqueKeys = array();
    protected $keyFields = array();

    /**
     * Holds the table fields and information for each field
     */
    protected $table = array();
    protected $tablesUsed = array();

    /**
     * Has the structure been checked against the database?
     */
    protected $validated = false;

    /**
     * Relational data: System-wide (for filters)
     * @var array
     */
    protected $useYearGroups = false;
    protected $yearGroups = array();

    protected $useLanguages = false;
    protected $languages = array();

    protected $useCountries = false;
    protected $countries = array();

    protected $usePhoneCodes = false;
    protected $phoneCodes = array();

    protected $useCustomFields = false;
    protected $customFields = array();

    /**
     * Constructor
     *
     * @version 26th April 2016
     * @since   26th April 2016
     * @param   array   importType information
     * @param   Object  PDO Connection
     */
    public function __construct( $data, $pdo = NULL, $validateStructure = true )
    {
        if (isset($data['details'])) {
            $this->details = $data['details'];
        }

        if (isset($data['access'])) {
            $this->access = $data['access'];
        }

        if (isset($data['primaryKey'])) {
            $this->primaryKey = $data['primaryKey'];
        }

        if (isset($data['uniqueKeys'])) {
            $this->uniqueKeys = $data['uniqueKeys'];

            //Grab the unique fields used in all keys
            foreach ($this->uniqueKeys as $key) {
                if (is_array($key) && count($key) > 1) {
                    foreach ($key as $keyName) {
                        if (!in_array($keyName, $this->keyFields)) $this->keyFields[] = $keyName;
                    }
                } else {
                    if (!in_array($key, $this->keyFields)) $this->keyFields[] = $key;
                }
            }
        }

        if (isset($data['table'])) {
            $this->table = $data['table'];
            $this->tablesUsed[] = $this->details['table'];

            // Add relational tables to the tablesUsed array so they're locked
            foreach ($this->table as $fieldName => $field) {
                if ($this->isFieldRelational($fieldName)) {
                    $relationship = $this->getField($fieldName, 'relationship');
                    if (!in_array($relationship['table'], $this->tablesUsed)) {
                        $this->tablesUsed[] = $relationship['table'];
                    }
                }

                // Check the filters so we know if extra data is nessesary
                $filter = $this->getField($fieldName, 'filter');
                if ($filter == 'yearlist') $this->useYearGroups = true;
                if ($filter == 'language') $this->useLanguages = true;
                if ($filter == 'country') $this->useCountries = true;
                if ($filter == 'phonecode') $this->usePhoneCodes = true;
                if ($filter == 'customfield') $this->useCustomFields = true;
            }
        }

        if ($pdo != NULL) {

            if ($validateStructure == true) {
                $this->validated = $this->validateWithDatabase( $pdo );
                $this->loadRelationalData( $pdo );
            }

            $this->loadAccessData( $pdo );
        }

        if ( empty($this->primaryKey) || empty($this->uniqueKeys) || empty($this->details) || empty($this->table) ) {
            return NULL;
        }
    }

    public static function getBaseDir(\Gibbon\sqlConnection $pdo) {
        $absolutePath = getSettingByScope($pdo->getConnection(), 'System', 'absolutePath');
        return rtrim($absolutePath, '/ ');
    }

    public static function getImportTypeDir(\Gibbon\sqlConnection $pdo) {
        return self::getBaseDir($pdo) . "/modules/Data Admin/imports";
    }

    public static function getCustomImportTypeDir(\Gibbon\sqlConnection $pdo) {
        $customFolder = getSettingByScope($pdo->getConnection(), 'Data Admin', 'importCustomFolderLocation');

        return self::getBaseDir($pdo).'/uploads/'.trim($customFolder, '/ ');
    }

    /**
     * Load Import Type List
     * Loads all YAML files from a folder and creates an importType object for each
     *
     * @access  public
     * @version 29th April 2016
     * @since   29th April 2016
     * @param   Object  PDO Connection
     *
     * @return  array   2D array of importType objects
     */
    public static function loadImportTypeList( \Gibbon\sqlConnection $pdo = NULL, $validateStructure = false ) {

        $yaml = new Yaml();
        $importTypes = array();

        // Get the built-in import definitions
        $defaultFiles = glob( self::getImportTypeDir($pdo) . "/*.yml" );

        // Create importType objects for each file
        foreach ( $defaultFiles as $file) {
            $fileData = $yaml::parse( file_get_contents( $file ) );

            if (isset($fileData['details']) && isset($fileData['details']['type']) ) {
                $fileData['details']['grouping'] = (isset($fileData['access']['module']))? $fileData['access']['module'] : 'General';
                $importTypes[ $fileData['details']['type'] ] = new importType( $fileData, $pdo, $validateStructure );
            }
        }

        // Get the user-defined custom definitions
        $customFiles = glob( self::getCustomImportTypeDir($pdo) . "/*.yml" );

        if (is_dir(self::getCustomImportTypeDir($pdo))==FALSE) {
            mkdir(self::getCustomImportTypeDir($pdo), 0755, TRUE) ;
        }

        foreach ( $customFiles as $file) {
            $fileData = $yaml::parse( file_get_contents( $file ) );

            if (isset($fileData['details']) && isset($fileData['details']['type']) ) {
                $fileData['details']['grouping'] = '* Custom Imports';
                $fileData['details']['custom'] = true;
                $importTypes[ $fileData['details']['type'] ] = new importType( $fileData, $pdo, $validateStructure );
            }
        }

        uasort($importTypes, array('self', 'sortImportTypes'));

        return $importTypes;
    }

    protected static function sortImportTypes($a, $b) {
        if ($a->getDetail('grouping') < $b->getDetail('grouping'))
            return -1;
        else if ($a->getDetail('grouping') > $b->getDetail('grouping'))
            return 1;

        if ($a->getDetail('category') < $b->getDetail('category'))
            return -1;
        else if ($a->getDetail('category') > $b->getDetail('category'))
            return 1;

        if ($a->getDetail('name') < $b->getDetail('name'))
            return -1;
        else if ($a->getDetail('name') > $b->getDetail('name'))
            return 1;

        return 0;
    }

    /**
     * Load Import Type
     * Loads a YAML file and creates an importType object
     *
     * @access  public
     * @version 29th April 2016
     * @since   29th April 2016
     * @param   string  Filename of the Import Type
     * @param   Object  PDO Conenction
     *
     * @return  [importType]
     */
    public static function loadImportType( $importTypeName, \Gibbon\sqlConnection $pdo = NULL ) {

        // Check custom first, this allows for local overrides
        $path = self::getCustomImportTypeDir($pdo).'/'.$importTypeName.'.yml';
        if (!file_exists($path)) {
            // Next check the built-in import types folder
            $path = self::getImportTypeDir($pdo).'/'.$importTypeName.'.yml';

            // Finally fail if nothing is found
            if (!file_exists($path)) return NULL;
        }

        $yaml = new Yaml();
        $fileData = $yaml::parse( file_get_contents($path) );

        return new importType( $fileData, $pdo );
    }

    /**
     * Is Import Accessible
     *
     * @access  public
     * @version 29th April 2016
     * @since   29th April 2016
     * @param   string  guid
     * @param   Object  PDO Conenction
     *
     * @return  bool
     */
    public function isImportAccessible( $guid, $connection2 ) {

        if ($this->getAccessDetail('protected') == false) return true;
        if ($connection2 == null) return false;

        return isActionAccessible($guid, $connection2, '/modules/' . $this->getAccessDetail('module').'/'.$this->getAccessDetail('entryURL') );
    }

    /**
     * Validate With Database
     * Compares the importType structure with the database table to ensure imports will succeed
     *
     * @access  protected
     * @version 29th April 2016
     * @since   29th April 2016
     * @param   Object  PDO Conenction
     *
     * @return  bool    true if all fields match existing table columns
     */
    protected function validateWithDatabase( \Gibbon\sqlConnection $pdo ) {

        try {
            $sql="SHOW COLUMNS FROM " . $this->getDetail('table');
            $result = $pdo->executeQuery(array(), $sql);
        }
        catch(PDOException $e) {
            return false;
        }

        $columns = $result->fetchAll( \PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE );

        $validatedFields = 0;
        foreach ($this->table as $fieldName => $field) {
            if ($this->isFieldReadOnly($fieldName)) {
                $validatedFields++;
                continue;
            }

            if ( isset($columns[$fieldName]) ) {
                foreach ($columns[$fieldName] as $columnName => $columnField) {

                    if ($columnName == 'Type') {
                        $this->parseTableValueType($fieldName, $columnField);
                    } else {
                        $this->setField($fieldName, mb_strtolower($columnName), $columnField);
                    }
                }
                $validatedFields++;
            } else {
                echo '<div class="error">Invalid field '. $fieldName .'</div>';
            }
        }

        return ($validatedFields == count($this->table));
    }

    /**
     * Load Access Data - for user permission checking, and category names
     * @version 2016
     * @since   2016
     * @param   \Gibbon\sqlConnection $pdo
     */
    protected function loadAccessData( \Gibbon\sqlConnection $pdo ) {

        if ( empty($this->access['module']) || empty($this->access['action']) ) {
            $this->access['protected'] = false;
            $this->details['category'] = 'Gibbon';
            return;
        }

        try {
            $data = array('module' => $this->access['module'], 'action' => $this->access['action'] );
            $sql = "SELECT gibbonAction.category, gibbonAction.entryURL
                    FROM gibbonAction
                    JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                    WHERE gibbonModule.name=:module
                    AND gibbonAction.name=:action
                    ORDER BY gibbonAction.precedence ASC
                    LIMIT 1";
            $result = $pdo->executeQuery($data, $sql);
        } catch(PDOException $e) {}

        if ($result->rowCount() > 0) {
            $action = $result->fetch();

            $this->access['protected'] = true;
            $this->access['entryURL'] = $action['entryURL'];

            if (empty($this->details['category'])) {
                $this->details['category'] = $action['category'];
            }
        }
    }

    /**
     * Load Relational Data
     * @version 2016
     * @since   2016
     * @param   \Gibbon\sqlConnection $pdo
     */
    protected function loadRelationalData( \Gibbon\sqlConnection $pdo ) {

        // Grab the year groups so we can translate Year Group Lists without a million queries
        if ($this->useYearGroups) {
            try {
                $sql="SELECT gibbonYearGroupID, nameShort FROM gibbonYearGroup ORDER BY sequenceNumber";
                $resultYearGroups = $pdo->executeQuery(array(), $sql);
            } catch(PDOException $e) {}

            if ($resultYearGroups->rowCount() > 0) {
                while ($yearGroup = $resultYearGroups->fetch() ) {
                    $this->yearGroups[ $yearGroup['nameShort'] ] = $yearGroup['gibbonYearGroupID'];
                }
            }
        }

        // Grab the Languages for system-wide relational data (filters)
        if ($this->useLanguages) {
            try {
                $sql="SELECT name FROM gibbonLanguage";
                $resultLanguages = $pdo->executeQuery(array(), $sql);
            } catch(PDOException $e) {}

            if ($resultLanguages->rowCount() > 0) {
                while ($languages = $resultLanguages->fetch() ) {
                    $this->languages[ $languages['name'] ] = $languages['name'];
                }
            }
        }

        // Grab the Countries for system-wide relational data (filters)
        if ($this->useCountries || $this->usePhoneCodes) {
            try {
                $sql="SELECT printable_name, iddCountryCode FROM gibbonCountry";
                $resultCountries = $pdo->executeQuery(array(), $sql);
            } catch(PDOException $e) {}

            if ($resultCountries->rowCount() > 0) {
                while ($countries = $resultCountries->fetch() ) {
                    if ($this->useCountries) $this->countries[ $countries['printable_name'] ] = $countries['printable_name'];
                    if ($this->usePhoneCodes) $this->phoneCodes[ $countries['iddCountryCode'] ] = $countries['iddCountryCode'];
                }
            }
        }

        // Grab the user-defined Custom Fields
        if ($this->useCustomFields) {
            try {
                $sql="SELECT gibbonPersonFieldID, name, type, options, required FROM gibbonPersonField where active = 'Y'";
                $resultCustomFields = $pdo->executeQuery(array(), $sql);
            } catch(PDOException $e) {}

            if ($resultCustomFields->rowCount() > 0) {
                while ($fields = $resultCustomFields->fetch() ) {
                    $this->customFields[ $fields['name'] ] = $fields;
                }

                foreach ($this->table as $fieldName => $field) {
                    $customFieldName = $this->getField($fieldName, 'name');
                    if ( !isset($this->customFields[$customFieldName]) ) continue;

                    $type = $this->customFields[ $customFieldName ]['type'];
                    if ($type == 'varchar') {
                        $this->setField( $fieldName, 'kind', 'char');
                        $this->setField( $fieldName, 'type', 'varchar');
                        $this->setField( $fieldName, 'length', $this->customFields[ $customFieldName ]['options'] );
                    } else if ($type == 'select') {
                        $this->setField( $fieldName, 'kind', 'enum');
                        $this->setField( $fieldName, 'type', 'enum');
                        $elements = explode(',', $this->customFields[ $customFieldName ]['options']);
                        $this->setField( $fieldName, 'elements', $elements );
                        $this->setField( $fieldName, 'length', count($elements) );
                    } else if ($type == 'text' || $type == 'date') {
                        $this->setField( $fieldName, 'kind', $type);
                        $this->setField( $fieldName, 'type', $type);
                    }

                    $this->setField( $fieldName, 'customField', $this->customFields[ $customFieldName ]['gibbonPersonFieldID'] );

                    $args = $this->getField( $fieldName, 'args');
                    $args['required'] = ($this->customFields[ $customFieldName ]['required'] == 'Y');
                    $this->setField( $fieldName, 'args', $args);
                }
            }
        }
    }

    /**
     * Parse Table Value Type
     * Split the SQL type eg: int(3) into a type name and length, etc.
     *
     * @access  protected
     * @version 25th May 2016
     * @since   29th April 2016
     * @param   string $fieldName
     * @param   string $columnField
     */
    protected function parseTableValueType( $fieldName, $columnField ) {

        // Split the info from inside the outer brackets, eg int(3)
        $firstBracket = mb_strpos($columnField, '(');
        $lastBracket = mb_strrpos($columnField, ')');

        $type = ($firstBracket !== false)? mb_substr($columnField, 0, $firstBracket) : $columnField;
        $details = ($firstBracket !== false)? mb_substr($columnField, $firstBracket+1, $lastBracket-$firstBracket-1 ) : '';

        // Cancel out if the type is not valid
        if (!isset($type)) return;

        $this->setField( $fieldName, 'type', $type );

        if ($type == 'varchar' || $type == 'character') {
            $this->setField( $fieldName, 'kind', 'char' );
            $this->setField( $fieldName, 'length', $details );
        }
        else if ($type == 'text' || $type == 'mediumtext' || $type == 'longtext' || $type == 'blob') {
            $this->setField( $fieldName, 'kind', 'text' );
        }
        else if ($type == 'integer' || $type == 'int' || $type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'bigint') {
            $this->setField( $fieldName, 'kind', 'integer' );
            $this->setField( $fieldName, 'length', $details );
        }
        else if ($type == 'decimal' || $type == 'numeric' || $type == 'float' || $type == 'real') {
            $this->setField( $fieldName, 'kind', 'decimal' );
            $decimalParts = explode(',', $details);
            $this->setField( $fieldName, 'length', $decimalParts[0] - $decimalParts[1] );
            $this->setField( $fieldName, 'precision', $decimalParts[0] );
            $this->setField( $fieldName, 'scale', $decimalParts[1] );
        }
        else if ($type == 'enum') {

            // Grab the CSV enum elements as an array
            $elements = explode(',', str_replace("'", "", $details) );
            $this->setField( $fieldName, 'elements', $elements );
            $this->setField( $fieldName, 'length', count($elements) );

            if ($details == "'Y','N'" || $details == "'N','Y'") {
                $this->setField( $fieldName, 'kind', 'yesno' );
            } else {
                $this->setField( $fieldName, 'kind', 'enum' );
            }

            if ( empty($this->getField($fieldName, 'desc')) ) {
                $this->setField( $fieldName, 'desc', implode(', ', $elements) );
            }
        }
        else {
            $this->setField( $fieldName, 'kind', $type );
        }

        if ( $this->isFieldRelational($fieldName) ) {
            $this->setField( $fieldName, 'kind', 'char' );
            $this->setField( $fieldName, 'length', 50 );
        }
    }

    /**
     * Get Detail
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  key - name of the detail to retrieve
     * @param   string  default - an optional value to return if key doesn't exist
     *
     * @return  var
     */
    public function getDetail($key, $default = "") {
        return ( isset($this->details[$key]) )? $this->details[$key] : $default;
    }

    /**
     * Get Access Detail
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  key - name of the access key to retrieve
     * @param   string  default - an optional value to return if key doesn't exist
     *
     * @return  var
     */
    public function getAccessDetail($key, $default = "") {
        return ( isset($this->access[$key]) )? $this->access[$key] : $default;
    }

    /**
     * Get Primary Key
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     *
     * @return  array   2D array of available keys to sync with
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * Get Keys
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     *
     * @return  array   2D array of available keys to sync with
     */
    public function getUniqueKeys() {
        return $this->uniqueKeys;
    }

    /**
     * Get Key Fields
     *
     * @access  public
     * @version 25th May 2016
     * @since   25th May 2016
     *
     * @return  array   2D array of available key fields
     */
    public function getUniqueKeyFields() {
        return ( isset($this->keyFields) )? $this->keyFields : array();
    }

    /**
     * Get Tables
     * Get the tables used in this import. All tables used must be locked.
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     *
     * @return  array   2D array of table names used in this import
     */
    public function getTables() {
        return $this->tablesUsed;
    }

    /**
     * Get Table Fields
     *
     * @access  public
     * @version 28th April 2016
     * @since   28th April 2016
     *
     * @return  array   2D array of table field names used in this import
     */
    public function getTableFields() {
        return ( isset($this->table) )? array_keys($this->table) : array();
    }

    /**
     * Get Field Information by Key
     *
     * @access  public
     * @version 28th April 2016
     * @since   28th April 2016
     * @param   string  Field Name
     * @param   string  Key to retrieve
     * @param   string  Default value to return if key doesn't exist
     *
     * @return  var
     */
    public function getField( $fieldName, $key, $default = "" ) {

        if (isset($this->table[$fieldName][$key])) {
            return $this->table[$fieldName][$key];
        } else if (isset($this->table[$fieldName]['args'][$key])) {
            return $this->table[$fieldName]['args'][$key];
        } else {
            return $default;
        }
    }

    /**
     * Set Field Information by Key
     *
     * @access  protected
     * @version 25th May 2016
     * @since   25th May 2016
     * @param   string  Field Name
     * @param   string  Key to retrieve
     * @param   string  Value to set
     */
    protected function setField( $fieldName, $key, $value ) {

        if ( isset($this->table[$fieldName]) ) {
            $this->table[$fieldName][$key] = $value;
        } else {
            $this->table[$fieldName] = array( $key => $value );
        }
    }

    /**
     * Filter Field Value
     * Compares the value type, legth and properties with the expected values for the table column
     *
     * @access  public
     * @version 10th June 2016
     * @since   8th June 2016
     * @param   string  Field name
     * @param   var     Value to validate
     *
     * @return  bool    true if the value checks out
     */
    public function filterFieldValue( $fieldName, $value ) {

        $value = trim($value);

        $filter = $this->getField( $fieldName, 'filter' );
        $strvalue = mb_strtoupper($value);

        switch($filter) {

            case 'html':    // Filter valid tags? requres db connection, which we dont store :(
                            break;

            case 'url':     if (!empty($value)) $value = filter_var( $value, FILTER_SANITIZE_URL);
                            break;

            case 'email':   if (mb_strpos($value, ',') !== false || mb_strpos($value, '/') !== false || mb_strpos($value, ' ') !== false ) {
                                $emails = preg_split('/[\s,\/]*/u', $value);
                                $value = (isset($emails[0]))? $emails[0] : '';
                            }

                            if (!empty($value)) $value = filter_var( $value, FILTER_SANITIZE_EMAIL);
                            break;

            case 'yesno':   // Translate generic boolean values into Y or N, watch the === for TRUE/FALSE, otherwise it breaks!
                            if ($strvalue == 'TRUE' || $strvalue == 'YES' || $strvalue == 'Y') {
                                $value = 'Y';
                            } else if ($value === FALSE || $strvalue == 'FALSE' || $strvalue == 'NO' || $strvalue == 'N' || $strvalue == '') {
                                $value = 'N';
                            }
                            break;

            case 'date':    // Handle various date formats
                            if ( !empty($value) ) { // && preg_match('/(^\d{4}[-]\d{2}[-]\d{2}$)/u', $value) === false
                                $date = strtotime($value);
                                $value = date('Y-m-d', $date);
                            }
                            if ( empty($value) || $value == '0000-00-00' || preg_match('/(^\d{4}[-]\d{2}[-]\d{2}$)/u', $value) === false) {
                                $value = NULL;
                            }
                            break;

            case 'time':    // Handle various time formats
                            if ( !empty($value) ) { // && preg_match('/(^\d{2}[:]\d{2}$)/u', $value) === false
                                $time = strtotime($value);
                                $value = date('H:i:s', $time);
                            }
                            if (empty($value) || $value == '00:00:00' || preg_match('/(^\d{2}[:]\d{2}$)/u', $value) === false) {
                                $value = NULL;
                            }
                            break;

            case 'timestamp':
                            if ( !empty($value) ) {
                                $time = strtotime($value);
                                $value = date( 'Y-m-d H:i:s', $time );
                            }
                            if (empty($value) || $value == '0000-00-00 00:00:00' || preg_match('/(^\d{4}[-]\d{2}[-]\d{2}[ ]+\d{2}[:]\d{2}[:]\d{2}$)/u', $value) === false) {
                                $value = NULL;
                            }

                            break;

            case 'schoolyear':
                            // Change school years formated as 2015-16 to 2015-2016
                            if ( preg_match('/(^\d{4}[-]\d{2}$)/u', $value) > 0 ) {
                                $value = mb_substr($value, 0, 5) . mb_substr($value, 0, 2) . mb_substr($value, 5, 2);
                            }
                            break;

            case 'gender':  // Handle various gender formats
                            $strvalue = str_replace('.', '', $strvalue);
                            if ($strvalue == 'M' || $strvalue == 'MALE' || $strvalue == 'MR') {
                                $value = 'M';
                            } else if ($strvalue == 'F' || $strvalue == 'FEMALE' || $strvalue == 'MS' || $strvalue == 'MRS' || $strvalue == 'MISS') {
                                $value = 'F';
                            } else if (empty($value)) {
                                $value = 'Unspecified';
                            } else {
                                $value = 'Other';
                            }
                            break;

            case 'numeric': $value = preg_replace("/[^0-9]/u", '', $value);
                            break;

            case 'phone':   // Handle phone numbers - strip all non-numeric chars
                            $value = preg_replace("/[^0-9,\/]/u", '', $value);

                            if (mb_strpos($value, ',') !== false || mb_strpos($value, '/') !== false || mb_strpos($value, ' ') !== false ) {
                                //$value = preg_replace("/[^0-9,\/]/u", '', $value);
                                $numbers = preg_split("/[,\/]*/u", $value);
                                $value = (isset($numbers[0]))? $numbers[0] : '';
                            }
                            break;

            case 'phonecode': $value = preg_replace("/[^0-9]/u", '', $value);
                            break;

            case 'phonetype': // Handle TIS phone types
                            if (mb_stripos($value, 'Mobile') !== false || mb_stripos($value, 'Cellular') !== false ) {
                                $value = 'Mobile';
                            }
                            else if (mb_stripos($value, 'Home') !== false ) {
                                $value = 'Home';
                            }
                            else if (mb_stripos($value, 'Office') !== false || mb_stripos($value, 'Business') !== false ) {
                                $value = 'Work';
                            } else {
                                $value = 'Other';
                            }
                            break;

            case 'country': if ($strvalue == "MACAU") $value = 'Macao';
                            if ($strvalue == "HK") $value = 'Hong Kong';
                            if ($strvalue == "USA") $value = 'United States';
                            $value = ucfirst($value);
                            break;

            case 'language': // Translate a few languages to gibbon-specific use
                            if ($strvalue == "CANTONESE") $value = 'Chinese (Cantonese)';
                            if ($strvalue == "MANDARIN") $value = 'Chinese (Mandarin)';
                            if ($strvalue == "CHINESE") $value = 'Chinese (Mandarin)';
                            $value = ucfirst($value);
                            break;

            case 'ethnicity':
                            $value = ucfirst($value);
                            break;

            case 'relation': if ($strvalue == "MOTHER") $value = 'Parent';
                            else if ($strvalue == "FATHER") $value = 'Parent';
                            else if ($strvalue == "SISTER") $value = 'Other Relation';
                            else if ($strvalue == "BROTHER") $value = 'Other Relation';
                            else $value = 'Other';
                            break;

            case 'yearlist': // Handle incoming blackbaud Grade Level's Allowed, turn them into Year Group IDs

                            if (!empty($value)) {
                                $yearGroupIDs = array();
                                $yearGroupNames = explode(',', $value);

                                foreach ( $yearGroupNames as $gradeLevel ) {
                                    $gradeLevel = trim($gradeLevel);
                                    if (isset($this->yearGroups[$gradeLevel])) {
                                        $yearGroupIDs[] = $this->yearGroups[$gradeLevel];
                                    }
                                }

                                $value = implode(',', $yearGroupIDs);
                            }
                            break;

            case 'status':  // Transform positive values into Full and negative into Left
                            if ($strvalue == 'FULL' || $strvalue == 'YES' || $strvalue == 'Y' || $value === '1') {
                                $value = 'Full';
                            }
                            else if ($strvalue == 'LEFT' || $strvalue == 'NO' || $strvalue == 'N' || $value == '' || $value === '0') {
                                $value = 'Left';
                            }
                            else if ($strvalue == 'EXPECTED') {
                                $value = 'Expected';
                            }
                            else if ($strvalue == 'PENDING APPROVAL') {
                                $value = 'Pending Approval';
                            }
                            break;

            case 'customfield': break;

            case 'string':
            default:        $value = strip_tags($value);


        }

        $kind = $this->getField( $fieldName, 'kind' );

        switch($kind) {
            case 'integer': $value = intval($value); break;
            case 'decimal': $value = floatval($value); break;
            case 'boolean': $value = boolval($value); break;
        }

        if ($strvalue == 'NOT REQUIRED' || $value == 'N/A') {
            $value = '';
        }

        return $value;
    }

    /**
     * Validate Field Value
     * Compares the value type, legth and properties with the expected values for the table column
     *
     * @access  public
     * @version 10th June 2016
     * @since   29th April 2016
     * @param   string  Field name
     * @param   var     Value to validate
     *
     * @return  bool    true if the value checks out
     */
    public function validateFieldValue( $fieldName, $value ) {

        if (!$this->validated) return false;

        if ( $this->isFieldRelational($fieldName) ) {
            return true;
        }

        // Validate based on filter type (from args)
        $filter = $this->getField( $fieldName, 'filter' );

        switch($filter) {

            case 'url':         if (!empty($value) && filter_var( $value, FILTER_VALIDATE_URL) === false) return false; break;
            case 'email':       //if (!empty($value) && filter_var( $value, FILTER_VALIDATE_EMAIL) === false) return false;
                                break;

            case 'country':     if ( !empty($value) && !isset($this->countries[ $value ]) ) return false; break;

            case 'language':    if ( !empty($value) && !isset($this->languages[ $value ]) ) return false; break;

            case 'phonecode':   if ( !empty($value) && !isset($this->phoneCodes[ $value ]) ) return false; break;

            case 'schoolyear':  if ( preg_match('/(^\d{4}[-]\d{4}$)/u', $value) > 1 ) return false; break;

            case 'nospaces':    if ( preg_match('/\s/u', $value) > 0 ) return false; break;

            default:            if (mb_substr($filter, 0, 1) == '/') {
                                    if ( preg_match($filter, $value) == false ) {
                                        return false;
                                    }
                                };
        }

        // Validate based on value type (from db)
        $kind = $this->getField( $fieldName, 'kind' );

        switch($kind) {
            case 'char':    $length = $this->getField( $fieldName, 'length' );
                            if ( mb_strlen($value) > $length ) return false;
                            break;

            case 'text':    break;

            case 'integer': $value = intval($value);
                            $length = $this->getField( $fieldName, 'length' );
                            if ( mb_strlen($value) > $length ) return false;
                            break;

            case 'decimal': $value = floatval($value);
                            $length = $this->getField( $fieldName, 'length' );

                            if (mb_strpos($value, '.') !== false) {
                                $number = mb_strstr($value, '.', true);
                                if ( mb_strlen($number) > $length ) return false;
                            } else {
                                if ( mb_strlen($value) > $length ) return false;
                            }
                            break;

            case 'yesno':   if ( $value != 'Y' && $value != 'N' ) return false;
                            break;

            case 'boolean': if ( !is_bool($value) ) return false;
                            break;

            case 'enum':    $elements = $this->getField( $fieldName, 'elements' );
                            if ( !in_array($value, $elements) ) return false;
                            break;
        }

        //echo $fieldName .'='. $value .'<br>';

        //TODO: More value validation
        //TODO: Handle relational table data
        //TODO: Sanitize

        return $value;
    }

    /**
     * Is Valid
     * Has the ImportType been checked against the databate table for field validity?
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     *
     * @return  bool true if the importType has been validated
     */
    public function isValid() {

        return $this->validated;
    }

    /**
     * Is Using Custom Fields
     *
     * @access  public
     * @version 20th September 2016
     * @since   20th September 2016
     *
     * @return  bool
     */
    public function isUsingCustomFields() {

        return $this->useCustomFields;
    }

    /**
     * Is Field Relational
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a required field
     */
    public function isFieldRelational( $fieldName ) {
        return ( isset($this->table[$fieldName]['relationship']) && !empty($this->table[$fieldName]['relationship']) );
    }

    /**
     * Is Field Linked to another field (for relational reference)
     *
     * @access  public
     * @version 28th November 2016
     * @since   28th November 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a linked field
     */
    public function isFieldLinked( $fieldName ) {
        return (isset( $this->table[$fieldName]['args']['linked']))? $this->table[$fieldName]['args']['linked'] : false;
    }

    /**
     * Is Field Read Only (for relational reference)
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a read only field
     */
    public function isFieldReadOnly( $fieldName ) {
        return (isset( $this->table[$fieldName]['args']['readonly']))? $this->table[$fieldName]['args']['readonly'] : false;
    }

    /**
     * Is Field Hidden
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a hidden field (or is linked)
     */
    public function isFieldHidden( $fieldName ) {
        if ($this->isFieldLinked($fieldName)) return true;
        return (isset( $this->table[$fieldName]['args']['hidden']))? $this->table[$fieldName]['args']['hidden'] : false;
    }

    /**
     * Is Field Required
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a required field
     */
    public function isFieldRequired( $fieldName ) {
        return (isset( $this->table[$fieldName]['args']['required']))? $this->table[$fieldName]['args']['required'] : false;
    }

    /**
     * Is Field Required
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a required field
     */
    public function isFieldUniqueKey( $fieldName ) {
        return ( in_array($fieldName, $this->keyFields) ) ;
    }

    /**
     * Readable Field Type
     * Create a human friendly representation of the field value type
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  string
     */
    public function readableFieldType( $fieldName ) {
        $output = '';
        $kind = $this->getField($fieldName, 'kind');

        if ($this->isFieldRelational($fieldName)) {
            extract( $this->getField($fieldName, 'relationship') );
            return $table.' '.( (is_array($field))? implode(', ', $field) : $field );
        }

        if (isset($kind)) {
            $length = $this->getField($fieldName, 'length');

            switch($kind) {
                case 'char':    $output = "Text (" . $length . " chars)"; break;
                case 'text':    $output = "Text"; break;
                case 'integer': $output = "Number (" . $length . " digits)"; break;
                case 'decimal': $scale = $this->getField($fieldName, 'scale');
                                $output = "Decimal (" . str_repeat('0', $length) .".". str_repeat('0', $scale)." format)"; break;
                case 'yesno':   $output = "Y or N"; break;
                case 'boolean': $output = "True or False"; break;
                case 'enum':    $options = $this->getField($fieldName, 'elements');
                                $optionCount = $this->getField($fieldName, 'length');
                                $optionString = ($optionCount > 4)? mb_substr(implode(', ', $options), 0, 60).' ...' : implode(', ', $options);
                                $output = "Options (".$optionString.")"; break;
                default:        $output = ucfirst($kind);
            }
        }
        return $output;
    }

    /**
     * Do Import Function
     * Returns the value of a dynmaic function name supplied by the importType field
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  var|NULL
     */
    public function doImportFunction( $fieldName ) {

        $method = $this->getField($fieldName, 'function');

        if ( !empty($method) && method_exists($this, 'userFunc_'.$method)) {
            return call_user_func( array($this, 'userFunc_'.$method) );
        } else {
            return NULL;
        }
    }

    /**
     * Generate Password
     * Custom function for run-time generation of passwords on import
     *
     * @access  protected
     * @version 27th April 2016
     * @since   27th April 2016
     *
     * @return  string  Random password, based on default Gibbon function
     */
    protected function userFunc_generatePassword() {
        return randomPassword(8);
    }

    /**
     * Timestamp
     * Custom function for run-time generation of timestamps
     *
     * @access  protected
     * @version 1st December 2016
     * @since   1st December 2016
     *
     * @return  string  current timestamp
     */
    protected function userFunc_timestamp() {
        return date( 'Y-m-d H:i:s', time() );
    }

}
