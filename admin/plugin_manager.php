<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

class PluginManagerController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      $this->smartyHelper->assign('activeGlobalMenuItem', 'Admin');

      if(Tools::isConnectedUser()) {

         if (!$this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {

            $action = Tools::getSecurePOSTStringValue('action', 'display');
            $pm = PluginManager::getInstance();

            // === ACTIONS =====================================================
            if ('enablePlugin' == $action) {
               $pluginName = Tools::getSecurePOSTStringValue('pluginName');
               $pm->enablePlugin($pluginName);

            } else if ('disablePlugin' == $action) {
               $pluginName = Tools::getSecurePOSTStringValue('pluginName');
               $pm->disablePlugin($pluginName);

            } else if ('discoverNewPlugins' == $action) {
               if (FALSE == Tools::createClassMap()) {
                  $this->smartyHelper->assign('errorMsg', T_('Could not create classmap, check classmap.ser permissions.'));
               } else {
                  $pm->discoverNewPlugins();
                  //$this->smartyHelper->assign('infoMsg', T_('Found xx new plugins !'));
               }
            }
            
            // === DISPLAY =====================================================

            // set values to display plugin table
            $plugins = $pm->getPlugins();
            $formattedPlugins = array();
            foreach ($plugins as $name => $plugin) {
               $className = $plugin['name'];
               $formattedPlugins[$className::getName()] = array(
               'name' => $plugin['name'],
               'status' => $plugin['status'],
               'statusName' => PluginManager::getStatusName($plugin['status']),
               'domains' => implode(',<br>', $plugin['domains']),
               'categories' => implode(',<br>', $plugin['categories']),
               'version' => $plugin['version'],
               'description' => $plugin['description'],
               );
            }
            
            $this->smartyHelper->assign('availablePlugins', $formattedPlugins);
         
         
            
         }
      }
   }

}

// ========== MAIN ===========
PluginManagerController::staticInit();
$controller = new PluginManagerController('../', 'Plugin Manager','Admin');
$controller->execute();

?>
