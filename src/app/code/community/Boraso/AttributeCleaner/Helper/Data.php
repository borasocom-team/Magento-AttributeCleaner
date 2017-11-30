<?php

class Boraso_AttributeCleaner_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function cleanProductAttribute()
    {
        $this->log("##### SECTION: cleanProductAttribute() #####", "=");

        //
        $arrExcludedAttrCodes   = $this->getExcludedAttrCodes(); //attribute_codes di default di Magento -> da conservare sempre
        $arrReport              = array( 'deleted' => array(), 'kept' => array(), 'skipped' => array(), "err" => array() );

        $product                = Mage::getModel('catalog/product');
        $attributesByTable      = $product->getResource()->loadAllAttributes($product)->getAttributesByTable();
        $adapter                = Mage::getResourceSingleton('core/resource')->getReadConnection();


        foreach ($attributesByTable as $table => $attributes) {

            foreach ($attributes as $attribute) {

                $attribute_code     = $attribute->getAttributeCode();
                $attribute_id       = $attribute->getAttributeId();
                $attribute_system   = $attribute->getIsUserDefined() ? false : true;

                $this->log("### Processing an attribute file", "-");
                $this->log("#" . $attribute_code . "#");
                $this->log("#" . $table . "#");


                if( in_array($attribute_code, $arrExcludedAttrCodes) ) {

                    $this->log("This is a Magento's default attribute. Skipping.");
                    $arrReport['skipped'][] = $attribute_code;
                    continue;
                }


                if( $attribute_system ) {

                    $this->log("This is a System, not User-defined attribute. Skipping.");
                    $arrReport['skipped'][] = $attribute_code;
                    continue;
                }


                $sql = "SELECT `value`, COUNT(*) AS `count` FROM `$table` WHERE `attribute_id` = " . $attribute_id . " GROUP BY `value`";
                $rows = $adapter->fetchAll($sql);

                if (count($rows) >= 0 && count($rows) <= 2) {

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

                    $this->log("Attribute in use, not deleting");
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


    protected function deleteAttribute(Mage_Customer_Model_Attribute $attribute)
    {
        if( Mage::getStoreConfig('attributecleaner/settings/dryrun') ) {

            return "Dry run is active";
        }

        //
        $esito = $attribute->delete();

        if($esito) {

            return null;

        } else {

            $message    = error_get_last()["message"];
            $message    = "!!! CRITICAL ERROR - Unable to delete: " . $message;
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
}
