<?php

class Boraso_AttributeCleaner_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function cleanProductAttribute()
    {
        $this->log("##### SECTION: cleanProductAttribute() #####", "=");

        //
        $arrExcludedAttrCodes   = $this->getExcludedAttrCodes(); //attribute_codes di default di Magento -> da conservare sempre
        $arrAttrIdsInSelectedSections = Mage::getModel("boraso_attributecleaner/adminsections")->getAttributeIdsInSelectedSections(); //sezioni sulle quali operare
        $arrReport              = array( 'deleted' => array(), 'kept' => array(), 'skipped' => array(), "err" => array() );

        $product                = Mage::getModel('catalog/product');
        $attributesByTable      = $product->getResource()->loadAllAttributes($product)->getAttributesByTable();
        $adapter                = Mage::getResourceSingleton('core/resource')->getReadConnection();


        foreach ($attributesByTable as $table => $attributes) {

            foreach ($attributes as $attribute) {

                $attribute_code     = $attribute->getAttributeCode();
                $attribute_id       = $attribute->getAttributeId();

                $this->log("### Processing an attribute", "-");
                $this->log("#" . $attribute_code . "# [" . $attribute_id . "]");
                $this->log("#" . $table . "#");


                if( in_array($attribute_code, $arrExcludedAttrCodes) ) {

                    $this->log("This is a Magento's default attribute. Skipping.");
                    $arrReport['skipped'][] = $attribute_code;
                    continue;
                }


                if( !$attribute->getIsUserDefined() ) {

                    $this->log("This is a System, not User-defined attribute. Skipping.");
                    $arrReport['skipped'][] = $attribute_code;
                    continue;
                }


                if( !in_array($attribute_id, $arrAttrIdsInSelectedSections) ) {

                    $this->log("This attribute is in a section not selected in `Limit by admin section`. Skipping.");
                    $arrReport['skipped'][] = $attribute_code;
                    continue;
                }

                $sql = "SELECT `value`, COUNT(*) AS `count` FROM `$table` WHERE `attribute_id` = " . $attribute_id . " GROUP BY `value`";
                $rows = $adapter->fetchAll($sql);
                $usage = $this->countUsage($rows);

                if( $usage === 0 ) {

                    $this->log("Attribute NOT in use! Must be deleted");
                    $esito = $this->deleteAttribute($attribute);

                    if(empty($esito)) {

                        $this->log("Success: attribute deleted");
                        $arrReport['deleted'][] = $attribute_code;

                    } else {

                        $this->log("Warning: attribute NOT deleted");
                        $this->log($esito);
                        $arrReport['err'][] = $attribute_code;
                    }

                } else {

                    $this->log("Attribute in use by " . $usage. " products, not deleting");
                    $arrReport['kept'][] = $attribute_code;
                }
            }
        }

        $this->log("### Report", "*");

        foreach($arrReport as $key => $val) {

            $this->log( ucfirst($key) . ": " . count($val));
        }

        return $arrReport;
    }


    protected function deleteAttribute(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
    {
        if( Mage::getStoreConfig('attributecleaner/settings/dryrun') ) {

            return "Dry run is active";
        }

        try {

            $esito = $attribute->delete();

        } catch (Exception $ex) {

            $message = "!!! CRITICAL ERROR - Exception, unable to delete: " . $ex->getMessage();
            return $message;
        }


        if($esito) {

            return null;

        } else {

            $message    = "!!! CRITICAL ERROR - Error, unable to delete: " . error_get_last()["message"];
            return $message;
        }
    }


    protected function log($message, $titleSeparator = false)
    {
        if($titleSeparator) {

            Mage::log("", null, 'boraso_attributecleaner.log');
        }


        Mage::log($message, null, 'boraso_attributecleaner.log');

        if($titleSeparator) {

            Mage::log( str_repeat($titleSeparator, mb_strlen($message)), null, 'boraso_attributecleaner.log' );
        }
    }


    /**
     * Ritorna gli attribute_codes di default di Magento.
     *
     * @return array
     */
    protected function getExcludedAttrCodes()
    {
        $txt = file_get_contents(Mage::getModuleDir("data", "Boraso_AttributeCleaner") . DIRECTORY_SEPARATOR . 'magento_default_attributes.txt');
        $arrAttrCodes = explode(PHP_EOL, $txt);
        return $arrAttrCodes;
    }


    protected function countUsage($rows)
    {
        if( empty($rows) ) {

            return 0;
        }


        if( count($rows) === 1 && $rows[0]["value"] === null ) {

            return 0;
        }


        //count usage
        $total_usage = 0;
        foreach($rows as $key => $value) {

            if( $value["value"] !== null ) {

                $total_usage += $value["count"];
            }
        }

        return $total_usage;
    }
}
