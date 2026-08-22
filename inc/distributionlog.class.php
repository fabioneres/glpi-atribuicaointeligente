<?php
/**
 * Auditoria de distribuicao de chamados.
 *
 * Registra atribuicoes manuais, atribuicoes automaticas do plugin e
 * transferencias de entidade para consulta administrativa.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteDistributionLog extends CommonDBTM {

   public const ACTION_TECHNICIAN_ASSIGNED = 'technician_assigned';
   public const ACTION_GROUP_ASSIGNED = 'group_assigned';
   public const ACTION_ENTITY_TRANSFERRED = 'entity_transferred';

   public const SOURCE_MANUAL = 'manual';
   public const SOURCE_PLUGIN = 'plugin';

   public static $rightname = PluginAtribuicaointeligenteConfig::RIGHT_CONFIG;

   public static function getTable($classname = null) {
      return PluginAtribuicaointeligenteConfig::getDistributionLogsTable();
   }

   public static function getTypeName($nb = 0) {
      return _n('Distribuição de chamado', 'Distribuições de chamados', $nb, 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-route';
   }

   public static function getAllowedActions(): array {
      return [
         self::ACTION_TECHNICIAN_ASSIGNED,
         self::ACTION_GROUP_ASSIGNED,
         self::ACTION_ENTITY_TRANSFERRED,
      ];
   }

   public static function getAllowedSources(): array {
      return [
         self::SOURCE_MANUAL,
         self::SOURCE_PLUGIN,
      ];
   }

   public static function getActionLabel(string $action): string {
      $labels = [
         self::ACTION_TECHNICIAN_ASSIGNED => __('Tecnico atribuido', 'atribuicaointeligente'),
         self::ACTION_GROUP_ASSIGNED      => __('Grupo atribuido', 'atribuicaointeligente'),
         self::ACTION_ENTITY_TRANSFERRED  => __('Entidade transferida', 'atribuicaointeligente'),
      ];

      return $labels[$action] ?? $action;
   }

   public static function getSourceLabel(string $source): string {
      $labels = [
         self::SOURCE_MANUAL => __('Manual', 'atribuicaointeligente'),
         self::SOURCE_PLUGIN => __('Plugin', 'atribuicaointeligente'),
      ];

      return $labels[$source] ?? $source;
   }

   public static function getCurrentActorId(): int {
      if (Session::getLoginUserID()) {
         return (int) Session::getLoginUserID();
      }

      return (int) ($_SESSION['glpiID'] ?? 0);
   }

   public static function addLog(array $payload): bool {
      global $DB;

      PluginAtribuicaointeligenteConfig::ensureDistributionLogSchema();
      $table = self::getTable();
      if (!$DB->tableExists($table)) {
         return false;
      }

      $action = (string) ($payload['action_type'] ?? '');
      $source = (string) ($payload['source'] ?? self::SOURCE_MANUAL);
      if (!in_array($action, self::getAllowedActions(), true)
         || !in_array($source, self::getAllowedSources(), true)
      ) {
         return false;
      }

      $ticketContext = self::getTicketContext((int) ($payload['tickets_id'] ?? 0));
      $entitiesId = (int) ($payload['entities_id'] ?? ($ticketContext['entities_id'] ?? 0));
      $entitiesIdFrom = self::nullableInt($payload['entities_id_from'] ?? null);
      $entitiesIdTo = self::nullableInt($payload['entities_id_to'] ?? null);
      if (!self::shouldLogForEntity($entitiesId, $entitiesIdFrom, $entitiesIdTo)) {
         return false;
      }

      $input = [
         'tickets_id'         => (int) ($payload['tickets_id'] ?? 0),
         'action_type'        => $action,
         'source'             => $source,
         'users_id_actor'     => (int) ($payload['users_id_actor'] ?? self::getCurrentActorId()),
         'users_id_from'      => self::nullableInt($payload['users_id_from'] ?? null),
         'users_id_to'        => self::nullableInt($payload['users_id_to'] ?? null),
         'groups_id_from'     => self::nullableInt($payload['groups_id_from'] ?? null),
         'groups_id_to'       => self::nullableInt($payload['groups_id_to'] ?? null),
         'entities_id'        => $entitiesId,
         'entities_id_from'   => $entitiesIdFrom,
         'entities_id_to'     => $entitiesIdTo,
         'itilcategories_id'  => self::nullableInt($payload['itilcategories_id'] ?? ($ticketContext['itilcategories_id'] ?? null)),
         'date_creation'      => date('Y-m-d H:i:s'),
      ];

      if ($input['tickets_id'] <= 0) {
         return false;
      }

      if (self::isDuplicateRecent($input)) {
         return true;
      }

      return (bool) $DB->insert($table, $input);
   }

   public static function logTicketUserAdd(CommonDBTM $item, string $source = self::SOURCE_MANUAL): void {
      $input = $item->input ?? [];
      $fields = $item->fields ?? [];
      $ticketsId = (int) ($fields['tickets_id'] ?? $input['tickets_id'] ?? 0);
      $usersId = (int) ($fields['users_id'] ?? $input['users_id'] ?? 0);
      $type = (int) ($fields['type'] ?? $input['type'] ?? 0);

      if ($ticketsId <= 0 || $usersId <= 0 || $type !== CommonITILActor::ASSIGN) {
         return;
      }

      self::addLog([
         'tickets_id'     => $ticketsId,
         'action_type'    => self::ACTION_TECHNICIAN_ASSIGNED,
         'source'         => $source,
         'users_id_to'    => $usersId,
      ]);
   }

   public static function logGroupTicketAdd(CommonDBTM $item, string $source = self::SOURCE_MANUAL): void {
      $input = $item->input ?? [];
      $fields = $item->fields ?? [];
      $ticketsId = (int) ($fields['tickets_id'] ?? $input['tickets_id'] ?? 0);
      $groupsId = (int) ($fields['groups_id'] ?? $input['groups_id'] ?? 0);
      $type = (int) ($fields['type'] ?? $input['type'] ?? 0);

      if ($ticketsId <= 0 || $groupsId <= 0 || $type !== CommonITILActor::ASSIGN) {
         return;
      }

      self::addLog([
         'tickets_id'    => $ticketsId,
         'action_type'   => self::ACTION_GROUP_ASSIGNED,
         'source'        => $source,
         'groups_id_to'  => $groupsId,
      ]);
   }

   public static function logTicketEntityTransfer(int $ticketsId, int $fromEntityId, int $toEntityId, string $source = self::SOURCE_MANUAL): void {
      if ($ticketsId <= 0 || $fromEntityId === $toEntityId) {
         return;
      }

      self::addLog([
         'tickets_id'        => $ticketsId,
         'action_type'       => self::ACTION_ENTITY_TRANSFERRED,
         'source'            => $source,
         'entities_id'       => $toEntityId,
         'entities_id_from'  => $fromEntityId,
         'entities_id_to'    => $toEntityId,
      ]);
   }

   protected static function getTicketContext(int $ticketsId): array {
      if ($ticketsId <= 0) {
         return [
            'entities_id'       => 0,
            'itilcategories_id' => null,
         ];
      }

      $ticket = new Ticket();
      if (!$ticket->getFromDB($ticketsId)) {
         return [
            'entities_id'       => 0,
            'itilcategories_id' => null,
         ];
      }

      return [
         'entities_id'       => (int) ($ticket->fields['entities_id'] ?? 0),
         'itilcategories_id' => self::nullableInt($ticket->fields['itilcategories_id'] ?? null),
      ];
   }

   protected static function shouldLogForEntity(int $entitiesId, $entitiesIdFrom = null, $entitiesIdTo = null): bool {
      $candidateIds = [$entitiesId];
      if ($entitiesIdFrom !== null) {
         $candidateIds[] = (int) $entitiesIdFrom;
      }
      if ($entitiesIdTo !== null) {
         $candidateIds[] = (int) $entitiesIdTo;
      }

      foreach (array_unique($candidateIds) as $candidateId) {
         if (PluginAtribuicaointeligenteConfig::isEntityEnabled((int) $candidateId)) {
            return true;
         }
      }

      return false;
   }

   protected static function nullableInt($value) {
      if ($value === null || $value === '') {
         return null;
      }

      $value = (int) $value;
      return $value > 0 ? $value : null;
   }

   protected static function isDuplicateRecent(array $input): bool {
      global $DB;

      $table = self::getTable();
      $ticketsId = (int) $input['tickets_id'];
      $action = addslashes((string) $input['action_type']);
      $source = addslashes((string) $input['source']);
      $actorId = (int) $input['users_id_actor'];
      $userToSql = self::nullableCompareSql('users_id_to', $input['users_id_to']);
      $groupToSql = self::nullableCompareSql('groups_id_to', $input['groups_id_to']);
      $entityToSql = self::nullableCompareSql('entities_id_to', $input['entities_id_to']);

      $result = $DB->doQuery(
         "SELECT `id`
          FROM `{$table}`
          WHERE `tickets_id` = {$ticketsId}
            AND `action_type` = '{$action}'
            AND `source` = '{$source}'
            AND `users_id_actor` = {$actorId}
            AND {$userToSql}
            AND {$groupToSql}
            AND {$entityToSql}
            AND `date_creation` >= DATE_SUB(NOW(), INTERVAL 2 SECOND)
          LIMIT 1"
      );

      return $result && $result->num_rows > 0;
   }

   protected static function nullableCompareSql(string $field, $value): string {
      if ($value === null || $value === '') {
         return "`{$field}` IS NULL";
      }

      return "`{$field}` = " . (int) $value;
   }
}
