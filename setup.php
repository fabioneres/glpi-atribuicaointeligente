<?php
/**
 * -------------------------------------------------------------------------
 * Atribuicao Inteligente plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * Fork standalone do modulo SmartAssign do NexTool, com indisponibilidade de
 * tecnicos para a distribuicao automatica de chamados.
 *
 * @author Fabio Neres
 * @license GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_VERSION')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_VERSION', '1.1.4');
}
if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION', '10.0.0');
}
if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION', '10.0.99');
}
if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_DIR')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_DIR', __DIR__);
}

/**
 * Plugin initialization.
 */
function plugin_init_atribuicaointeligente() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['atribuicaointeligente'] = true;
   $PLUGIN_HOOKS['add_css']['atribuicaointeligente'][] = 'css/atribuicaointeligente.css';
   $PLUGIN_HOOKS['add_javascript']['atribuicaointeligente'][] = 'js/manual_assignment_filter.js';

   Plugin::loadLang('atribuicaointeligente');

   $plugin = new Plugin();
   if (!$plugin->isActivated('atribuicaointeligente')) {
      return;
   }

   require_once __DIR__ . '/inc/logger.class.php';
   require_once __DIR__ . '/inc/config.class.php';
   require_once __DIR__ . '/inc/profile.class.php';
   require_once __DIR__ . '/inc/assignmentsentity.class.php';
   require_once __DIR__ . '/inc/categoryassignment.class.php';
   require_once __DIR__ . '/inc/technicianunavailability.class.php';
   require_once __DIR__ . '/inc/technicianworkschedule.class.php';
   require_once __DIR__ . '/inc/assignmentdecisionlog.class.php';
   require_once __DIR__ . '/inc/availabilitychecker.class.php';
   require_once __DIR__ . '/inc/tickethookhandler.class.php';
   require_once __DIR__ . '/inc/itilcategoryhookhandler.class.php';

   Plugin::registerClass('PluginAtribuicaointeligenteConfig');
   Plugin::registerClass('PluginAtribuicaointeligenteProfile', ['addtabon' => ['Profile']]);
   Plugin::registerClass('PluginAtribuicaointeligenteCategoryAssignment');
   Plugin::registerClass('PluginAtribuicaointeligenteTechnicianUnavailability');
   Plugin::registerClass('PluginAtribuicaointeligenteTechnicianWorkSchedule');
   Plugin::registerClass('PluginAtribuicaointeligenteAssignmentDecisionLog');

   if (Session::getLoginUserID()) {
      PluginAtribuicaointeligenteProfile::syncCurrentProfileRight();
      $menuCacheKey = 'plugin_atribuicaointeligente_menu_url_fix';
      if (($_SESSION[$menuCacheKey] ?? 0) !== 1) {
         unset($_SESSION['glpimenu']);
         $_SESSION[$menuCacheKey] = 1;
      }
   }

   $PLUGIN_HOOKS['config_page']['atribuicaointeligente'] = 'front/config.form.php?id=1';
   $PLUGIN_HOOKS['menu_toadd']['atribuicaointeligente'] = [
      'plugins' => 'PluginAtribuicaointeligenteConfig',
   ];

   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS['use_massive_action']['atribuicaointeligente'] = 1;
   }

   $PLUGIN_HOOKS['pre_item_add']['atribuicaointeligente']['Ticket'] = [
      'PluginAtribuicaointeligenteTicketHookHandler',
      'preItemAdd',
   ];
   $PLUGIN_HOOKS['pre_item_add']['atribuicaointeligente']['Ticket_User'] = [
      'PluginAtribuicaointeligenteTicketHookHandler',
      'preTicketUserAdd',
   ];
   $PLUGIN_HOOKS['item_add']['atribuicaointeligente']['Ticket'] = [
      'PluginAtribuicaointeligenteTicketHookHandler',
      'itemAdded',
   ];
   $PLUGIN_HOOKS['item_add']['atribuicaointeligente']['ITILCategory'] = [
      'PluginAtribuicaointeligenteITILCategoryHookHandler',
      'itemAdded',
   ];
   $PLUGIN_HOOKS['item_delete']['atribuicaointeligente']['ITILCategory'] = [
      'PluginAtribuicaointeligenteITILCategoryHookHandler',
      'itemDeleted',
   ];
   $PLUGIN_HOOKS['item_purge']['atribuicaointeligente']['ITILCategory'] = [
      'PluginAtribuicaointeligenteITILCategoryHookHandler',
      'itemPurged',
   ];
}

/**
 * Plugin metadata.
 */
function plugin_version_atribuicaointeligente() {
   return [
      'name'         => 'Atribuição Inteligente',
      'version'      => PLUGIN_ATRIBUICAOINTELIGENTE_VERSION,
      'author'       => 'Fabio Neres',
      'license'      => 'GPLv3+',
      'homepage'     => 'https://github.com/fabioneres/glpi-atribuicaointeligente',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION,
            'max' => PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION,
         ],
      ],
   ];
}

function plugin_atribuicaointeligente_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION, 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION,
            PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION
         );
      } else {
         echo 'Este plugin requer GLPI >= ' . PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION;
      }
      return false;
   }

   if (version_compare(GLPI_VERSION, PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION, 'gt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         Plugin::messageIncompatible(
            'core',
            PLUGIN_ATRIBUICAOINTELIGENTE_MIN_GLPI_VERSION,
            PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION
         );
      } else {
         echo 'Este plugin suporta GLPI ate ' . PLUGIN_ATRIBUICAOINTELIGENTE_MAX_GLPI_VERSION;
      }
      return false;
   }

   return true;
}

function plugin_atribuicaointeligente_check_config() {
   return true;
}
