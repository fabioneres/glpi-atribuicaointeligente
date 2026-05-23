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

PluginAtribuicaointeligenteConfig::assertCanView();

$item = new PluginAtribuicaointeligenteConfig();
$id = PluginAtribuicaointeligenteConfig::CONFIG_ID;
if (!$item->getFromDB($id)) {
   $item->add(['id' => $id]);
   $item->getFromDB($id);
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

$item->display([
   'id'       => $id,
   'target'   => PluginAtribuicaointeligenteConfig::getFormURL(false),
   'forcetab' => $forcetab,
]);

Html::footer();
