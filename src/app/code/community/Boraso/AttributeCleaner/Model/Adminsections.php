<?php

class Boraso_AttributeCleaner_Model_Adminsections
{
    protected $arrSelectedSections  = null;
    protected $dbr                  = null;


    public function __construct()
    {
        //db init
        $dbres              = Mage::getSingleton('core/resource');
        $this->dbr          = $dbres->getConnection('core_read');

        $this->loadSections();
    }


    public function loadSections()
    {
        $sql                = "
                                SELECT
                                    tblgroup.attribute_group_id AS id, tblgroup.attribute_group_name AS name, 
                                    tblset.attribute_set_name AS setname
                                FROM
                                    eav_attribute_set AS tblset
                                INNER JOIN
                                    eav_attribute_group AS tblgroup
                                ON
                                    tblset.attribute_set_id = tblgroup.attribute_set_id
                                WHERE
                                    tblset.entity_type_id = " .  Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId() . "
                                ORDER BY
                                    tblgroup.sort_order ASC
                            ";

        $this->arrSelectedSections    = $this->dbr->fetchAll($sql, \PDO::FETCH_ASSOC);
    }


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arrOptionArray = array();

        foreach($this->arrSelectedSections as $item) {

            $arrOptionArray[]   = array(
                                    "value"     => $item["id"],
                                    "label"     => $item["name"] . " (" . $item["setname"] . ")"
                                );
        }

        return $arrOptionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $arrToOptionArray       = $this->toOptionArray();
        $arrToArray             = array();

        foreach($arrToOptionArray as $item) {

            $value              = $item["id"];
            $arrToArray[$value] = $item["label"];
        }

        return $arrToArray;
    }


    public function getSelectedSections()
    {
        $string = Mage::getStoreConfig('attributecleaner/settings/limit_by_section');

        if(empty($string)) {

            //se non è selezionata nessuna sezione => interpreto come "tutte"
            $arrToOptionArray       = $this->toOptionArray();
            $arrSections            = empty($arrToOptionArray) ? array() : array_column($arrToOptionArray, "value");

        } else {

            $arrSections            = explode(",", $string);
        }

        return $arrSections;
    }


    public function getAttributeIdsInSelectedSections()
    {
        $sql                = "
                                SELECT
                                    attribute_id
                                FROM
                                    eav_entity_attribute 
                                WHERE
                                    attribute_group_id	IN(" . implode(",", $this->getSelectedSections()) . ") AND 
                                    entity_type_id		= " .  Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId()
                            ;

        $arrAttrIds         = $this->dbr->fetchAll($sql, array(), \PDO::FETCH_COLUMN);
        return $arrAttrIds;
    }
}
