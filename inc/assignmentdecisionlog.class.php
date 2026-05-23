<?php
/**
 * Log estruturado de decisoes de distribuicao.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteAssignmentDecisionLog extends CommonDBTM {

   public static $rightname = PluginAtribuicaointeligenteConfig::RIGHT_CONFIG;

   public static function getTable($classname = null) {
      return PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
   }

   public static function getTypeName($nb = 0) {
      return _n('Log de atribuição', 'Logs de atribuição', $nb, 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-list-details';
   }

   public static function addDecision(
      $ticketsId,
      $groupsId,
      $itilcategoriesId,
      $entitiesId,
      string $mode,
      $selectedUsersId,
      array $ignoredUsers,
      string $reason
   ): void {
      global $DB;

      $payload = [
         'tickets_id'        => $ticketsId ? (int) $ticketsId : null,
         'groups_id'         => $groupsId ? (int) $groupsId : null,
         'itilcategories_id' => $itilcategoriesId ? (int) $itilcategoriesId : null,
         'entities_id'       => $entitiesId !== null ? (int) $entitiesId : null,
         'mode'              => $mode,
         'selected_users_id' => $selectedUsersId ? (int) $selectedUsersId : null,
         'ignored_users'     => json_encode($ignoredUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
         'reason'            => $reason,
         'date_creation'     => date('Y-m-d H:i:s'),
      ];

      if ($DB->tableExists(self::getTable())) {
         try {
            $DB->insert(self::getTable(), $payload);
         } catch (Throwable $e) {
            PluginAtribuicaointeligenteLogger::addWarning('Falha ao gravar log estruturado de atribuicao', [
               'error' => $e->getMessage(),
            ]);
         }
      }

      PluginAtribuicaointeligenteLogger::addInfo('Decisao de atribuicao', $payload);
   }
}
