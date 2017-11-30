<?php

class Boraso_AttributeCleaner_Model_Observer
{
    protected $helper;

    public function __construct()
    {
        $this->helper = Mage::helper("boraso_attributecleaner");
    }


    public function run()
    {
	    if( Mage::getStoreConfig('attributecleaner/settings/modenabled') ) {

		    $this->helper->cleanProductAttribute();
        }
    }
}
