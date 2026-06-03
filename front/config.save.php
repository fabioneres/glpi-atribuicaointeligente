<?php
/**
 * Salvamento das configuracoes globais.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

PluginAtribuicaointeligenteConfig::assertCanUpdateConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // O GLPI 10 ja valida tokens CSRF de POST em inc/includes.php.
   $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
   $entity->saveOptions([
      'auto_assign_group'   => isset($_POST['auto_assign_group']) ? 1 : 0,
      'auto_assign_type'    => isset($_POST['auto_assign_type']) ? (int) $_POST['auto_assign_type'] : 0,
      'auto_assign_mode'    => isset($_POST['auto_assign_mode']) ? (int) $_POST['auto_assign_mode'] : 0,
      'exclude_managers'    => isset($_POST['exclude_managers']) ? 1 : 0,
      'use_entity_calendar' => isset($_POST['use_entity_calendar']) ? 1 : 0,
   ]);
   PluginAtribuicaointeligenteConfig::saveEnabledEntities($_POST['enabled_entities'] ?? []);

   Session::addMessageAfterRedirect(__('Configuracoes salvas com sucesso.', 'atribuicaointeligente'), false, INFO);
}

$forcetab = $_POST['forcetab'] ?? 'PluginAtribuicaointeligenteConfig$1';
Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(true) . '?forcetab=' . urlencode($forcetab));
