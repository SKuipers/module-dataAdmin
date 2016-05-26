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
 * @version	25th April 2016
 * @since	25th April 2016
 * @author	Sandra Kuipers
 */
class importType
{
    /**
     * Information about the overall Import Type
     */
	protected $details = array();

    /**
     * Values that can be used for sync & updates
     */
	protected $keys = array();

    /**
     * Holds the table fields and information for each field
     */
	protected $table = array();

    /**
     * Has the structure been checked against the database?
     */
	protected $validated = false;


	/**
     * Constructor
     *
     * @version 26th April 2016
     * @since   26th April 2016
     * @param   array   importType information
     * @param   Object  PDO Connection
     */
    public function __construct( $data, $pdo = NULL )
    {
    	if (isset($data['details'])) {
    		$this->details = $data['details'];
    	}

    	if (isset($data['keys'])) {
    		$this->keys = $data['keys'];
    	}

    	if (isset($data['table'])) {
    		$this->table = $data['table'];
    	}

    	if ($pdo != NULL) {
    		$this->validated = $this->validateWithDatabase( $pdo );
    	}

    	if ( empty($this->details) || empty($this->details) || empty($this->table) ) {
    		return NULL;
    	}
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
    public static function loadImportTypeList( \Gibbon\sqlConnection $pdo = NULL ) {

    	$dir = glob( GIBBON_ROOT . "modules/Data Admin/imports/*.yml" );

    	$yaml = new Yaml();
    	$importTypes = array();
    	
    	foreach ($dir as $file) {
    		if (!file_exists($file)) continue;
			$fileData = $yaml::parse( file_get_contents( $file ) );
			if (isset($fileData['details']) && isset($fileData['details']['type']) ) {
				$importTypes[ $fileData['details']['type'] ] = new importType( $fileData, $pdo );
			}
    	}

    	return $importTypes;
    }

    /**
     * Load Import Type
     * Loads a YAML file and creates an importType object
     *
     * @access  public
     * @version 29th April 2016
     * @since 	29th April 2016
     * @param   string  Filename of the Import Type
     * @param   Object  PDO Conenction
     *
     * @return 	[importType]
     */
    public static function loadImportType( $importTypeName, \Gibbon\sqlConnection $pdo = NULL ) {
    	$path = GIBBON_ROOT . "modules/Data Admin/imports/" . $importTypeName .".yml";
    	if (!file_exists($path)) return NULL;

    	$yaml = new Yaml();
    	$fileData = $yaml::parse( file_get_contents($path) );

    	return new importType( $fileData, $pdo );
    }

    /**
     * Validate With Database
     * Compares the importType structure with the database table to ensure imports will succeed
     *
     * @access  public
     * @version 29th April 2016
     * @since 	29th April 2016
     * @param   Object  PDO Conenction
     *
     * @return  bool    true if all fields match existing table columns
     */
    public function validateWithDatabase( \Gibbon\sqlConnection $pdo ) {

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
			if ( isset($columns[$fieldName]) ) {
				foreach ($columns[$fieldName] as $columnName => $columnField) {
                    if ($columnName == 'Type') {
                        $this->parseTableValueType($fieldName, $columnField);
                    } else {
                        $this->setField($fieldName, strtolower($columnName), $columnField);
                    }
				}
				$validatedFields++;
			}
		}

    	return ($validatedFields == count($this->table));
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

        // Split the info from inside the brackets, eg int(3)
        $info = preg_split("/[()]+/", $columnField );

        // Cancel out if the type is not valid
        if (!isset($info[0])) return;

        $this->setField( $fieldName, 'type', $info[0] );

        if ($info[0] == 'varchar' || $info[0] == 'character') {
            $this->setField( $fieldName, 'kind', 'char' );
            $this->setField( $fieldName, 'length', $info[1] );
        }
        else if ($info[0] == 'text' || $info[0] == 'mediumtext' || $info[0] == 'longtext' || $info[0] == 'blob') {
            $this->setField( $fieldName, 'kind', 'text' );
        }
        else if ($info[0] == 'integer' || $info[0] == 'int' || $info[0] == 'tinyint' || $info[0] == 'smallint' || $info[0] == 'mediumint' || $info[0] == 'bigint') {
            $this->setField( $fieldName, 'kind', 'number' );
            $this->setField( $fieldName, 'length', $info[1] );
        }
        else if ($info[0] == 'decimal' || $info[0] == 'numeric' || $info[0] == 'float' || $info[0] == 'real') {
            $this->setField( $fieldName, 'kind', 'decimal' );
            $this->setField( $fieldName, 'length', $info[1] - $info[2] );
            $this->setField( $fieldName, 'precision', $info[1] );
            $this->setField( $fieldName, 'scale', $info[2] );
        }
        else if ($info[0] == 'enum') {
            // Grab the CSV enum elements as an array
            $elements = explode(',', str_replace("'", "", $info[1]) );
            $this->setField( $fieldName, 'elements', $elements );
            $this->setField( $fieldName, 'kind', 'enum' );

            if ( empty($this->getField($fieldName, 'desc')) ) {
                $this->setField( $fieldName, 'desc', implode(', ', $elements) );
            }
        }
        else {
            $this->setField( $fieldName, 'kind', $info[0] );
        }
       
    }

    /**
     * Get Detail
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     * @param   string  key - name of the detail to retrieve
     * @param   string  default - an optional value to return if key doesn't exist
     *
     * @return  var
     */
    public function getDetail($key, $default = "") {
    	return ( isset($this->details[$key]) )? $this->details[$key] : $default;
    }

    /**
     * Get Keys
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     *
     * @return  array   2D array of available keys to sync with
     */
    public function getKeys() {
    	return ( isset($this->keys) )? $this->keys : array();
    }

    /**
     * Get Tables
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     *
     * @return  array   2D array of table names used in this import
     */
    public function getTables() {
    	return ( isset($this->details['table']) )? array( $this->details['table'] ) : array();
    }

    /**
     * Get Table Fields
     *
     * @access  public
     * @version 28th April 2016
     * @since 	28th April 2016
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
     * @since 	28th April 2016
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
     * Validate Field Value
     * Compares the value type, legth and properties with the expected values for the table column
     *
     * @access  public
     * @version 29th April 2016
     * @since 	29th April 2016
     * @param   string  Field name
     * @param   var     Value to validate
     *
     * @return  bool    true if the value checks out
     */
    public function validateFieldValue( $fieldName, $value ) {

    	if (!$this->validated) return false;


    	//TODO: More value validation
        //TODO: Handle relational table data
        //TODO: Sanitize
        
    	return true;
    }

    /**
     * Is Valid
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     *
     * @return  bool true if the importType has been successfully checked against the database
     */
    public function isValid() {

    	return $this->validated;
    }

    /**
     * Is Field Required
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     * @param   string	Field name
     *
     * @return  bool true if marked as a required field
     */
    public function isFieldRequired( $fieldName ) {
    	return (isset( $this->table[$fieldName]['args']['required']))? $this->table[$fieldName]['args']['required'] : false;
    }

    /**
     * Is Field Unique
     *
     * @access  public
     * @version 27th April 2016
     * @since   27th April 2016
     * @param   string  Field name
     *
     * @return  bool true if marked as a unique field
     */
    public function isFieldUnique( $fieldName ) {
        return (isset( $this->table[$fieldName]['args']['unique']))? $this->table[$fieldName]['args']['unique'] : false;
    }

    /**
     * Readable Field Type
     * Create a human friendly representation of the field value type
     *
     * @access  public
     * @version 27th April 2016
     * @since 	27th April 2016
     * @param   string  Field name
     *
     * @return  string 
     */
    public function readableFieldType( $fieldName ) {
    	$output = '';
    	$kind = $this->getField($fieldName, 'kind');

    	if (isset($kind)) {
    		$length = $this->getField($fieldName, 'length');

            switch($kind) {
                case 'char':    $output = "Text (" . $length . " chars)"; break;
                case 'text':    $output = "Text"; break;
                case 'number':  $output = "Number (" . $length . " digits)"; break;
                case 'decimal': $scale = $this->getField($fieldName, 'scale');
                                $output = "Decimal (" . str_repeat('0', $length) .".". str_repeat('0', $scale)." format)"; break;
                case 'boolean': $output = "True or False"; break;
                case 'enum':    $output = "Options"; break;
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
     * @since 	27th April 2016
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
     * @since 	27th April 2016
     *
     * @return  string  Random password, based on default Gibbon function
     */
    protected function userFunc_generatePassword() {
    	return randomPassword(8);
    }

}

?>