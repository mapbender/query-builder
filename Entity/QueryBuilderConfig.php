<?php
namespace Mapbender\DataSourceBundle\Entity;

/**
 * Class QueryBuilderConfig
 *
 * @package Mapbender\DataSourceBundle\Entity
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QueryBuilderConfig extends BaseConfiguration
{

    /** @var string Data source id or name */
    public $source = "default";

    /**
     * Permissions
     */

    /**
     * @var array Allowed schema name list.
     * If empty array then any allowed.
     */
    public $allowedSchemas = array();

    /** @var boolean Allow remove SQL */
    public $allowRemove = false;

    /** @var boolean Allow open edit form */
    public $allowEdit = false;

    /** @var boolean Allow open edit form */
    public $allowExecute = true;

    /** @var boolean Allow save SQL */
    public $allowSave = false;

    /** @var boolean Allow create SQL */
    public $allowCreate = false;

    /** @var boolean Allow export */
    public $allowExport = true;

    /** @var boolean Allow html table export */
    public $allowHtmlExport = true;

    /** @var boolean Allow print */
    public $allowPrint = true;

    /** @var boolean Allow print */
    public $allowSearch = false;

    /** @var boolean Allow execute */
    public $allowUserPublishing = true;

    /**
     * Fields
     */

    /** @var int ID */
    public $idFieldName = "id";

    /** @var string SQL field name */
    public $sqlFieldName = "sql_definition";

    /** @var boolean Allow execute */
    public $orderByFieldName = "anzeigen_reihenfolge";

    /** @var string Doctrine connection field name */
    public $connectionFieldName = "connection_name";

    /** @var string Title field name */

    public $titleFieldName = "name";

    /** @var string Publish field name */
    public $publicFieldName = "anzeigen";

    /** @var array Display table columns */
    public $tableColumns = array(
        array("data"  => 'name',
              "title" => 'Title'),
        array("data"    => 'anzeigen_reihenfolge',
              'visible' => false,
              "title"   => 'Sort'),
    );

    /**
     * Export
     */
    public function toArray()
    {
        $data = parent::toArray();
        foreach ($data["tableColumns"] as &$tableColumn) {
            if ($tableColumn["title"] == "Title") {
                $tableColumn["data"] = $this->titleFieldName;
            }
            if ($tableColumn["title"] == "Sort") {
                $tableColumn["data"] = $this->orderByFieldName;
            }
        }
        return $data;
    }

}