<?php
/**
 * Listagem Search de categorias.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

PluginAtribuicaointeligenteConfig::assertCanView();

$entity = new PluginAtribuicaointeligenteAssignmentsEntity();
if (PluginAtribuicaointeligenteConfig::canUpdateConfig()) {
   $entity->syncMissingCategories();
   PluginAtribuicaointeligenteCategoryAssignment::ensureDisplayPreferences();
}

$embedded = !empty($_GET['embedded']);
if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteCategoryAssignment::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}

echo "<div class='m-3'>";
Search::show(PluginAtribuicaointeligenteCategoryAssignment::class);
echo "</div>";

if (!$embedded) {
   Html::footer();
}
