<?php
/**
 * Pagina principal de configuracao do plugin.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_DIR')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_DIR', dirname(__DIR__));
}

if (!class_exists('PluginAtribuicaointeligenteConfig')) {
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/logger.class.php';
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/config.class.php';
}

PluginAtribuicaointeligenteConfig::assertCanView();

global $CFG_GLPI;

$item = new PluginAtribuicaointeligenteConfig();
$id = PluginAtribuicaointeligenteConfig::CONFIG_ID;
if (!$item->getFromDB($id)) {
   if (PluginAtribuicaointeligenteConfig::canUpdateConfig()) {
      PluginAtribuicaointeligenteConfig::ensureDisplayItem();
   }

   if (!$item->getFromDB($id)) {
      Session::addMessageAfterRedirect(
         __('Registro de configuracao do plugin nao encontrado. Reinstale ou atualize o plugin.', 'atribuicaointeligente'),
         false,
         ERROR
      );
      Html::redirect($CFG_GLPI['root_doc'] . '/front/plugin.php');
   }
}

Html::header(
   PluginAtribuicaointeligenteConfig::getTypeName(1),
   $_SERVER['PHP_SELF'],
   'plugins',
   PluginAtribuicaointeligenteConfig::class
);

$validTabs = [
   'PluginAtribuicaointeligenteConfig$1',
   'PluginAtribuicaointeligenteConfig$2',
   'PluginAtribuicaointeligenteConfig$3',
   'PluginAtribuicaointeligenteConfig$4',
   'PluginAtribuicaointeligenteConfig$5',
   'PluginAtribuicaointeligenteConfig$6',
];

$tabKey = strtolower($item::getType());
if (!isset($_SESSION['glpi_tabs'])) {
   $_SESSION['glpi_tabs'] = [];
}

if (isset($_GET['forcetab']) && in_array($_GET['forcetab'], $validTabs, true)) {
   $forcetab = $_GET['forcetab'];
   $_SESSION['glpi_tabs'][$tabKey] = $forcetab;
} else {
   $forcetab = $_SESSION['glpi_tabs'][$tabKey] ?? 'PluginAtribuicaointeligenteConfig$1';
}

if ((int) substr((string) $forcetab, -1) === 3) {
   $allowedAvailabilityTabs = ['vacation', 'temporary', 'other'];
   $availabilityTab = (string) ($_GET['availability_tab'] ?? 'vacation');
   if (!in_array($availabilityTab, $allowedAvailabilityTabs, true)) {
      $availabilityTab = 'vacation';
   }

   $_SESSION['plugin_atribuicaointeligente_unavailabilities'] = [
      'availability_tab' => $availabilityTab,
      'users_id'         => max(0, (int) ($_GET['users_id'] ?? 0)),
   ];
}

if ((int) substr((string) $forcetab, -1) === 5) {
   $distributionFilterKey = 'plugin_atribuicaointeligente_distribution_filters';
   $distributionFilterFields = [
      'date_start',
      'date_end',
      'users_id_actor',
      'users_id_to',
      'groups_id_to',
      'entities_id',
      'entities_id_from',
      'itilcategories_id',
      'action_type',
      'distribution_source',
      'source',
   ];

   if (!empty($_GET['distribution_clear'])) {
      unset($_SESSION[$distributionFilterKey]);
   } elseif (isset($_GET['distribution_filter'])) {
      $_SESSION[$distributionFilterKey] = array_intersect_key(
         $_GET,
         array_flip($distributionFilterFields)
      );
   }
}

$item->display([
   'id'       => $id,
   'target'   => PluginAtribuicaointeligenteConfig::getFormURL(false),
   'forcetab' => $forcetab,
]);

Html::footer();
