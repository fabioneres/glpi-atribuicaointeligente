<?php
/**
 * -------------------------------------------------------------------------
 * Atribuicao Inteligente plugin for GLPI
 * -------------------------------------------------------------------------
 * Installation, uninstall and display hooks.
 * -------------------------------------------------------------------------
 *
 * @author Fabio Neres
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_VERSION')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_VERSION', '1.0.2');
}
if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_DIR')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_DIR', __DIR__);
}

require_once __DIR__ . '/inc/logger.class.php';
require_once __DIR__ . '/inc/config.class.php';
require_once __DIR__ . '/inc/profile.class.php';
require_once __DIR__ . '/inc/assignmentsentity.class.php';
require_once __DIR__ . '/inc/categoryassignment.class.php';
require_once __DIR__ . '/inc/technicianunavailability.class.php';
require_once __DIR__ . '/inc/assignmentdecisionlog.class.php';

function plugin_atribuicaointeligente_install() {
   global $DB;

   try {
      $sqlfile = __DIR__ . '/sql/install.sql';
      if (file_exists($sqlfile)) {
         $DB->runFile($sqlfile);
      }
   } catch (Throwable $e) {
      Toolbox::logInFile('plugin_atribuicaointeligente', 'Falha critica no install.sql: ' . $e->getMessage() . PHP_EOL);
      Session::addMessageAfterRedirect(
         'Atribuição Inteligente: falha ao criar tabelas. Verifique files/_log/plugin_atribuicaointeligente.log',
         false,
         ERROR
      );
      return false;
   }

   $steps = [
      'install_rights' => function() {
         PluginAtribuicaointeligenteConfig::installRights();
      },
      'default_config' => function() {
         PluginAtribuicaointeligenteConfig::ensureDisplayItem();
         PluginAtribuicaointeligenteConfig::ensureDefaultConfig();
      },
      'migrate_nextool' => function() {
         PluginAtribuicaointeligenteConfig::migrateFromNextool();
      },
      'sync_categories' => function() {
         $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
         $entity->syncMissingCategories();
      },
      'display_preferences' => function() {
         PluginAtribuicaointeligenteCategoryAssignment::ensureDisplayPreferences();
      },
      'first_access' => function() {
         if (isset($_SESSION['glpiactiveprofile']['id'])) {
            PluginAtribuicaointeligenteProfile::createFirstAccess((int) $_SESSION['glpiactiveprofile']['id']);
         }
      },
   ];

   foreach ($steps as $step => $callback) {
      try {
         $callback();
      } catch (Throwable $e) {
         Toolbox::logInFile(
            'plugin_atribuicaointeligente',
            sprintf('Install step %s falhou: %s', $step, $e->getMessage()) . PHP_EOL
         );
      }
   }

   return true;
}

function plugin_atribuicaointeligente_upgrade($old_version) {
   return plugin_atribuicaointeligente_install();
}

function plugin_atribuicaointeligente_uninstall() {
   global $DB;

   $sqlfile = __DIR__ . '/sql/uninstall.sql';
   if (file_exists($sqlfile)) {
      $DB->runFile($sqlfile);
   }

   ProfileRight::deleteProfileRights([PluginAtribuicaointeligenteConfig::RIGHT_CONFIG]);
   return true;
}

function plugin_atribuicaointeligente_MassiveActions($type) {
   return PluginAtribuicaointeligenteCategoryAssignment::getMassiveActions((string) $type);
}

function plugin_atribuicaointeligente_MassiveActionsFieldsDisplay($options = []) {
   return PluginAtribuicaointeligenteCategoryAssignment::massiveActionsFieldsDisplay((array) $options);
}

function plugin_atribuicaointeligente_giveItem($itemtype, $ID, $data, $num) {
   return PluginAtribuicaointeligenteCategoryAssignment::giveItem($itemtype, $ID, $data, $num);
}
