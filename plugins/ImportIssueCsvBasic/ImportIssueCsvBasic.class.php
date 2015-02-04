<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImportIssueCsvBasic
 *
 * @author lob
 */
class ImportIssueCsvBasic  extends IndicatorPluginAbstract {


   //const OPTION_IS_DATE_DISPLAYED = 'isDateDisplayed';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $teamid;
   private $session_userid;
   private $session_user;
   private $selectedProject;

   // config options from Dashboard
   // none.

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_IMPORT_EXPORT,
      );
      self::$categories = array (
         self::CATEGORY_IMPORT
      );
   }

   public static function getName() {
      return 'CSV issue import (basic)';
   }
   public static function getDesc() {
      return 'Import a list of issues to MantisBT / CodevTT';
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         //'js_min/datepicker.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->session_userid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
         $this->session_user   = UserCache::getInstance()->getUser($this->session_userid);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID)) {
         $this->selectedProject = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID);
      } else {
         $this->selectedProject = 0;
      }

      // set default pluginSettings
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(PluginDataProviderInterface::PARAM_PROJECT_ID, $pluginSettings)) {
            $this->selectedProject = $pluginSettings[PluginDataProviderInterface::PARAM_PROJECT_ID];
         }
      }
   }


   /**
    * user shall not be observer or customer
    */
   private function isAccessGranted() {
      if ((0 == $this->teamid) ||
          ($this->session_user->isTeamObserver($this->teamid)) ||
          ($this->session_user->isTeamCustomer($this->teamid))
         ) {
         return false;
      }
      return true;
   }

  /**
    *
    */
   public function execute() {

      $this->execData = array (
         );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables = array(
         'importIssueCsvBasic_greetings' => $this->execData['greetings'],

         // add pluginSettings (if needed by smarty)
      );

      if (false == $isAjaxCall) {
         $smartyVariables['importIssueCsvBasic_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['importIssueCsvBasic_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
ImportIssueCsvBasic::staticInit();
