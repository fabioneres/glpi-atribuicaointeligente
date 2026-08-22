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
   $currentConfig = PluginAtribuicaointeligenteConfig::getConfigValues();
   $options = $currentConfig;
   if (isset($_POST['save']) || isset($_POST['entity_bulk_action'])) {
      foreach (['auto_assign_group', 'exclude_managers', 'use_entity_calendar', 'assign_on_update'] as $field) {
         if (array_key_exists($field, $_POST)) {
            $options[$field] = 1;
         } elseif (array_key_exists($field, $currentConfig) && isset($_POST['save'])) {
            $options[$field] = 0;
         }
      }

      if (array_key_exists('auto_assign_type', $_POST)) {
         $options['auto_assign_type'] = (int) $_POST['auto_assign_type'];
      }
      if (array_key_exists('auto_assign_mode', $_POST)) {
         $options['auto_assign_mode'] = (int) $_POST['auto_assign_mode'];
      }

      $entity->saveOptions($options);
   }

   $bulkAction = (string) ($_POST['entity_bulk_action'] ?? '');
   if ($bulkAction === 'enable_all') {
      PluginAtribuicaointeligenteConfig::setAllManageableEntitiesActive(true);
      Session::addMessageAfterRedirect(__('Todas as entidades visiveis foram habilitadas.', 'atribuicaointeligente'), false, INFO);
   } else if ($bulkAction === 'disable_all') {
      PluginAtribuicaointeligenteConfig::setAllManageableEntitiesActive(false);
      Session::addMessageAfterRedirect(__('Todas as entidades visiveis foram desabilitadas.', 'atribuicaointeligente'), false, INFO);
   } else {
      PluginAtribuicaointeligenteConfig::saveEnabledEntities($_POST['enabled_entities'] ?? []);
      Session::addMessageAfterRedirect(__('Configuracoes salvas com sucesso.', 'atribuicaointeligente'), false, INFO);
   }
}

$forcetab = $_POST['forcetab'] ?? 'PluginAtribuicaointeligenteConfig$1';
Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(true) . '?forcetab=' . urlencode($forcetab));
