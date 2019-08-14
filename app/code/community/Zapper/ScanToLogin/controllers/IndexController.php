<?php
    class Zapper_ScanToLogin_IndexController extends Mage_Core_Controller_Front_Action
    {
        public function IndexAction() 
        {
            Mage::helper('scantologin')->hijack();
        }
    }