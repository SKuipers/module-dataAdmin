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
 * Database Tools class
 *
 * @version	2nd December 2016
 * @since	2nd December 2016
 * @author	Sandra Kuipers
 */
class DatabaseTools
{
    /**
     * Gibbon\session
     */
    private $session ;

	/**
	 * Gibbon\Contracts\Database\Connection
	 */
	private $pdo ;
	

	/**
     * Constructor
     *
     * @version  2nd December 2016
     * @since    2nd December 2016
     * @param    Gibbon\session
     * @param    Gibbon\Contracts\Database\Connection
     */
    public function __construct(\Gibbon\Session $session = NULL, Connection $pdo = NULL)
    {
        $this->session = $session;
        $this->pdo = $pdo;
    }

    public function getRecordCount( ImportType $importType, $currentSchoolYear = false )
    {
        if (!$importType->isValid()) return;

        $table = $this->escapeIdentifier( $importType->getDetail('table') );

        try {
            $data = array();
            $sql = "SELECT COUNT(*) FROM $table";

            if ($currentSchoolYear == true ) {
                // Optionally limit the import to the current school year, if it applies to this type of import
                $gibbonSchoolYearID = $importType->getField('gibbonSchoolYearID', 'name', null);

                // Skip import types that dont relate to school years
                if ($gibbonSchoolYearID == null) return '';

                if ($importType->isFieldReadOnly('gibbonSchoolYearID') == false ) {
                    $data['gibbonSchoolYearID'] = $this->session->get('gibbonSchoolYearID');
                    $sql.= " WHERE gibbonSchoolYearID=:gibbonSchoolYearID ";
                }
            }
            $result = $this->pdo->executeQuery($data, $sql);
        } catch(\PDOException $e) {
            return 'Error';
        }

        return ($result->rowCount() > 0)? $result->fetchColumn(0) : 0;
    }

    public function getDuplicateRecords( ImportType $importType, $countOnly = false )
    {
        if (!$importType->isValid()) return;

        $tableName = $this->escapeIdentifier( $importType->getDetail('table') );
        $primaryKey = $importType->getPrimaryKey();
        $primaryKeyField = $this->escapeIdentifier($primaryKey);

        $tableFields = $importType->getTableFields();
        $uniqueKeyList = $importType->getUniqueKeys();

        // Tables with no unique keys can't have duplicates
        if (empty($uniqueKeyList) || empty($uniqueKeyList[0]))  return '';

        // Currently only checks the first set of unique keys
        $uniqueKeys = (is_array($uniqueKeyList[0]) && count($uniqueKeyList[0]) > 0)? $uniqueKeyList[0] : array($uniqueKeyList[0]);

        $sqlSelect = ($countOnly)? array("COUNT(*) count") : array("COUNT(*) count", "GROUP_CONCAT({$tableName}.{$primaryKeyField}) list");

        if (!$countOnly) {
            foreach ($uniqueKeys as $uniqueKey) {
                $sqlSelect[] = "{$tableName}.{$uniqueKey}";
            }
        }

        $sql = "SELECT " . implode(', ', $sqlSelect);
        $sql .= " FROM {$tableName}";
        $sql .= " GROUP BY ".implode(', ', $uniqueKeys);
        $sql .= " HAVING count > 1";

        try {
            $result = $this->pdo->executeQuery(array(), $sql);
        } catch(\PDOException $e) {
            echo 'Error: '. $e->getMessage();
            return;
        }

        if ($countOnly) {
            return ($result->rowCount() > 0)? array_sum($result->fetchAll(\PDO::FETCH_COLUMN, 0) ) : 0;
        } else {
            return ($result->rowCount() > 0)? $result->fetchAll() : array();
        }
    }

    public function getOrphanedRecords( ImportType $importType, $countOnly = false )
    {
        if (!$importType->isValid()) return;
        
        $tableName = $this->escapeIdentifier( $importType->getDetail('table') );
        $primaryKey = $this->escapeIdentifier( $importType->getPrimaryKey() );

        $relationships = array();

        // Get the relational fields
        foreach ($importType->getTableFields() as $fieldName) {
            if ($importType->isFieldrequired($fieldName) == false) continue; // Skip non-required fields for orphan checks

            if ($importType->isFieldRelational($fieldName) && !$importType->isFieldReadOnly($fieldName)) {
                $relationships[$fieldName] = $importType->getField($fieldName, 'relationship');
            }
        }

        // Non-relational tables cant have orphaned rows
        if (empty($relationships)) return '';

        $sqlSelect = ($countOnly)? array("COUNT(*)") : array("{$tableName}.{$primaryKey}");
        $sqlJoin = array();
        $sqlWhere = array();

        $count = 0;
        foreach ($relationships as $fieldName => $relationship) {
            $relationalTable = $this->escapeIdentifier($relationship['table']);
            $relationalKey = $this->escapeIdentifier($relationship['key']);

            $alias = "`rel{$count}`";

            if (!$countOnly) $sqlSelect[$relationalTable] = "{$alias}.{$relationalKey}";
            $sqlJoin[$relationalTable] = "LEFT JOIN {$relationalTable} AS {$alias} ON ({$tableName}.{$fieldName}={$alias}.{$relationalKey})";
            $sqlWhere[$relationalTable] = "{$alias}.{$relationalKey} IS NULL";

            $count++;
        }

        $sql = "SELECT ".implode(', ', $sqlSelect);
        $sql .= " FROM {$tableName} ".implode(' ', $sqlJoin);
        $sql .= " WHERE ".implode(' OR ', $sqlWhere);

        try {
            $result = $this->pdo->executeQuery(array(), $sql);
        } catch(\PDOException $e) {
            echo 'Error: '. $e->getMessage();
            return;
        }

        if ($countOnly) {
            return ($result->rowCount() > 0)? $result->fetchColumn(0) : 0;
        } else {
            return ($result->rowCount() > 0)? $result->fetchAll() : array();
        }
    }

    protected function escapeIdentifier($text) {
        return "`".str_replace("`","``",$text)."`";
    }

}
