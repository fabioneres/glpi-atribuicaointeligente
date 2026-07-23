<?php
/**
 * Hook de atribuicao automatica de tickets.
 *
 * Lógica baseada no SmartAssign/NexTool, com filtro adicional de
 * indisponibilidade de tecnicos.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteTicketHookHandler {

   protected $DB;
   protected $assignments;
   protected static $assignmentInProgress = false;

   public function __construct() {
      global $DB;
      $this->DB = $DB;
      $this->assignments = new PluginAtribuicaointeligenteAssignmentsEntity();
   }

   public static function preItemAdd(CommonDBTM $item) {
      return $item;
   }

   public static function preTicketUserAdd(CommonDBTM $item) {
      if ($item->getType() !== 'Ticket_User') {
         return $item;
      }

      $input = $item->input ?? [];
      if ((int) ($input['type'] ?? 0) !== CommonITILActor::ASSIGN) {
         return $item;
      }

      $usersId = (int) ($input['users_id'] ?? 0);
      $ticketsId = (int) ($input['tickets_id'] ?? 0);
      if ($usersId <= 0 || $ticketsId <= 0) {
         return $item;
      }

      $entitiesId = 0;
      $ticket = new Ticket();
      if ($ticket->getFromDB($ticketsId)) {
         $entitiesId = (int) ($ticket->fields['entities_id'] ?? 0);
      }
      if (!PluginAtribuicaointeligenteConfig::isEntityEnabled($entitiesId)) {
         return $item;
      }

      $reason = PluginAtribuicaointeligenteAvailabilityChecker::getUnavailableReason($usersId, $entitiesId);
      if ($reason === null) {
         return $item;
      }

      Session::addMessageAfterRedirect(
         sprintf(
            __('Técnico indisponível para atribuição manual: %s', 'atribuicaointeligente'),
            $reason
         ),
         false,
         ERROR
      );

      PluginAtribuicaointeligenteLogger::addWarning('Atribuicao manual bloqueada para tecnico indisponivel', [
         'tickets_id'  => $ticketsId,
         'users_id'    => $usersId,
         'entities_id' => $entitiesId,
         'reason'      => $reason,
      ]);

      $item->input = [];
      return false;
   }

   public static function itemAdded(CommonDBTM $item) {
      if ($item->getType() !== 'Ticket') {
         return $item;
      }

      $handler = new self();
      $handler->assignTicket($item);
      return $item;
   }

   public static function itemUpdated(CommonDBTM $item) {
      if ($item->getType() !== 'Ticket') {
         return $item;
      }

      if (self::$assignmentInProgress) {
         return $item;
      }

      $handler = new self();
      if ($handler->assignments->getOptionAssignOnUpdate() !== 1) {
         return $item;
      }

      $handler->assignTicket($item, 'update');
      return $item;
   }

   protected function getTicketId(CommonDBTM $item): int {
      return (int) ($item->fields['id'] ?? 0);
   }

   protected function getTicketCategory(CommonDBTM $item): int {
      return (int) ($item->fields['itilcategories_id'] ?? 0);
   }

   protected function getTicketEntity(CommonDBTM $item): int {
      return (int) ($item->fields['entities_id'] ?? 0);
   }

   protected function assignTicket(CommonDBTM $item, string $trigger = 'add'): void {
      $ticketId = $this->getTicketId($item);
      $itilcategoriesId = $this->getTicketCategory($item);
      $entitiesId = $this->getTicketEntity($item);

      if (!PluginAtribuicaointeligenteConfig::isEntityEnabled($entitiesId)) {
         return;
      }

      if ($ticketId <= 0 || $itilcategoriesId <= 0) {
         PluginAtribuicaointeligenteLogger::addDebug('Ticket sem categoria valida para atribuicao', [
            'tickets_id' => $ticketId,
            'itilcategories_id' => $itilcategoriesId,
         ]);
         return;
      }

      if (!$this->assignments->isCategoryActive($itilcategoriesId)) {
         PluginAtribuicaointeligenteLogger::addDebug('Categoria nao habilitada para atribuicao automatica', [
            'tickets_id' => $ticketId,
            'itilcategories_id' => $itilcategoriesId,
            'trigger' => $trigger,
         ]);
         return;
      }

      $groupId = $this->assignments->getGroupByItilCategory($itilcategoriesId);
      if ($groupId === false) {
         $this->logDecision($ticketId, 0, $itilcategoriesId, $entitiesId, 'none', null, [], 'Grupo nao encontrado para a categoria');
         return;
      }
      $groupId = (int) $groupId;

      if ($this->ticketHasTechnician($ticketId)) {
         if ($this->assignments->getOptionAutoAssignGroup() === 1) {
            $this->withConflictGuard(function() use ($ticketId, $itilcategoriesId) {
               $this->ensureGroupAssigned($ticketId, $itilcategoriesId);
            });
         }

         $this->logDecision($ticketId, $groupId, $itilcategoriesId, $entitiesId, 'skip', null, [], 'Ticket ja possui tecnico atribuido');
         return;
      }

      $mode = $this->assignments->getOptionAutoAssignMode() === 0 ? 'balancing' : 'rotation';
      if ($mode === 'balancing') {
         [$userId, $ignored] = $this->chooseByBalancing($groupId, $itilcategoriesId, $entitiesId);
      } else {
         [$userId, $ignored] = $this->chooseByRotation($item, $groupId, $itilcategoriesId, $entitiesId);
      }

      if ($userId <= 0) {
         $this->logDecision(
            $ticketId,
            $groupId,
            $itilcategoriesId,
            $entitiesId,
            $mode,
            null,
            $ignored,
            empty($ignored) ? 'Nenhum tecnico encontrado no grupo' : 'Todos os tecnicos candidatos estavam indisponiveis'
         );
         return;
      }

      $this->setAssignment($ticketId, $userId, $itilcategoriesId);
      $reason = $trigger === 'update'
         ? 'Tecnico atribuido automaticamente apos atualizacao do chamado'
         : 'Tecnico atribuido automaticamente';
      $this->logDecision($ticketId, $groupId, $itilcategoriesId, $entitiesId, $mode, $userId, $ignored, $reason);
   }

   protected function chooseByBalancing(int $groupId, int $itilcategoriesId, int $entitiesId): array {
      $candidates = $this->buildBalancementRequestCriteria($groupId, $itilcategoriesId);
      $ignored = [];

      foreach ($candidates as $candidate) {
         $userId = (int) ($candidate['users_id'] ?? 0);
         if ($userId <= 0) {
            continue;
         }

         $reason = PluginAtribuicaointeligenteAvailabilityChecker::getUnavailableReason($userId, $entitiesId);
         if ($reason === null) {
            return [$userId, $ignored];
         }

         $ignored[] = [
            'users_id' => $userId,
            'reason'   => $reason,
         ];
      }

      return [0, $ignored];
   }

   protected function chooseByRotation(CommonDBTM $item, int $groupId, int $itilcategoriesId, int $entitiesId): array {
      $lastAssignmentIndex = $this->assignments->getLastAssignmentIndex($itilcategoriesId);
      if ($lastAssignmentIndex === false) {
         return [0, []];
      }

      $members = $this->getGroupsUsersByCategory($itilcategoriesId);
      $count = count($members);
      if ($count === 0) {
         return [0, []];
      }

      $startIndex = is_numeric($lastAssignmentIndex) ? ((int) $lastAssignmentIndex + 1) : 0;
      $startIndex = $startIndex % $count;
      $ignored = [];

      for ($offset = 0; $offset < $count; $offset++) {
         $index = ($startIndex + $offset) % $count;
         $userId = (int) ($members[$index]['UserId'] ?? 0);
         if ($userId <= 0) {
            continue;
         }

         $reason = PluginAtribuicaointeligenteAvailabilityChecker::getUnavailableReason($userId, $entitiesId);
         if ($reason === null) {
            if ($this->assignments->getOptionAutoAssignType() === 1) {
               $this->assignments->updateLastAssignmentIndexCategoria($itilcategoriesId, $index);
            } else {
               $this->assignments->updateLastAssignmentIndexGrupo($itilcategoriesId, $index);
            }
            return [$userId, $ignored];
         }

         $ignored[] = [
            'users_id' => $userId,
            'reason'   => $reason,
         ];
      }

      return [0, $ignored];
   }

   public function getGroupsUsersByCategory($categoryId): array {
      $categoryId = (int) $categoryId;
      $excludeManagers = $this->assignments->getOptionExcludeManagers() === 1;

      $criteria = [
         'SELECT' => [
            'glpi_itilcategories.name AS Category',
            'glpi_itilcategories.completename AS CategoryCompleteName',
            'glpi_groups.name AS Group',
            'glpi_groups_users.id AS UserGroupId',
            'glpi_groups_users.users_id AS UserId',
            'glpi_users.name AS Username',
            'glpi_users.firstname AS UserFirstname',
            'glpi_users.realname AS UserRealname',
         ],
         'FROM' => 'glpi_itilcategories',
         'INNER JOIN' => [
            'glpi_groups' => [
               'ON' => [
                  'glpi_itilcategories' => 'groups_id',
                  'glpi_groups'         => 'id',
               ],
            ],
            'glpi_groups_users' => [
               'ON' => [
                  'glpi_groups'       => 'id',
                  'glpi_groups_users' => 'groups_id',
               ],
            ],
            'glpi_users' => [
               'ON' => [
                  'glpi_groups_users' => 'users_id',
                  'glpi_users'        => 'id',
               ],
            ],
         ],
         'WHERE' => [
            'glpi_itilcategories.id' => $categoryId,
            'glpi_users.is_active'   => 1,
            'glpi_users.is_deleted'  => 0,
         ],
         'ORDER' => 'glpi_groups_users.id ASC',
      ];

      if ($excludeManagers) {
         $criteria['WHERE']['glpi_groups_users.is_manager'] = 0;
      }

      return iterator_to_array($this->DB->request($criteria));
   }

   protected function buildBalancementRequestCriteria($groupId, $itilcategoriesId): array {
      $groupId = (int) $groupId;
      $itilcategoriesId = (int) $itilcategoriesId;
      $typeAssign = (int) CommonITILActor::ASSIGN;
      $statusSolved = (int) Ticket::SOLVED;
      $statusClosed = (int) Ticket::CLOSED;
      $excludeManagers = $this->assignments->getOptionExcludeManagers() === 1;
      $byCategoryOnly = $this->assignments->getOptionAutoAssignType() === 1;

      $categoryFilter = $byCategoryOnly
         ? " AND t.itilcategories_id = {$itilcategoriesId}"
         : '';
      $managerFilter = $excludeManagers
         ? ' AND gu.is_manager = 0'
         : '';

      $sql = "SELECT gu.users_id,
                     COUNT(DISTINCT t.id) AS active_tickets
              FROM glpi_groups_users gu
              INNER JOIN glpi_users u
                 ON u.id = gu.users_id
                AND u.is_active = 1
                AND u.is_deleted = 0
              LEFT JOIN glpi_tickets_users tu
                 ON tu.users_id = gu.users_id
                AND tu.type = {$typeAssign}
              LEFT JOIN glpi_tickets t
                 ON t.id = tu.tickets_id
                AND t.status NOT IN ({$statusSolved}, {$statusClosed})
                AND t.is_deleted = 0{$categoryFilter}
              WHERE gu.groups_id = {$groupId}{$managerFilter}
              GROUP BY gu.users_id
              ORDER BY active_tickets ASC, gu.users_id ASC";

      $result = $this->DB->doQuery($sql);
      $rows = [];
      if ($result && $result->num_rows > 0) {
         while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
         }
      }

      return $rows;
   }

   protected function setAssignment($ticketId, $userId, $itilcategoriesId): void {
      $ticketId = (int) $ticketId;
      $userId = (int) $userId;
      $itilcategoriesId = (int) $itilcategoriesId;

      $this->withConflictGuard(function() use ($ticketId, $userId, $itilcategoriesId) {
         if ($this->assignments->getOptionAutoAssignGroup() === 1) {
            $this->ensureGroupAssigned($ticketId, $itilcategoriesId);
         }

         $ticketUser = new Ticket_User();
         $ticketUser->deleteByCriteria([
            'tickets_id' => $ticketId,
            'type'       => CommonITILActor::ASSIGN,
         ]);

         $ticketUser->add([
            'tickets_id'        => $ticketId,
            'users_id'          => $userId,
            'type'              => CommonITILActor::ASSIGN,
            'use_notification'  => 1,
         ]);

         $ticket = new Ticket();
         if ($ticket->getFromDB($ticketId) && (int) $ticket->fields['status'] === Ticket::INCOMING) {
            $ticket->update([
               'id'     => $ticketId,
               'status' => Ticket::ASSIGNED,
            ]);
         }
      });
   }

   protected function ticketHasTechnician($ticketId): bool {
      $ticketUser = new Ticket_User();
      $existing = $ticketUser->find([
         'tickets_id' => (int) $ticketId,
         'type'       => CommonITILActor::ASSIGN,
      ]);
      return count($existing) > 0;
   }

   protected function ensureGroupAssigned($ticketId, $itilcategoriesId): void {
      $groupId = $this->assignments->getGroupByItilCategory((int) $itilcategoriesId);
      if ($groupId === false) {
         return;
      }

      $groupTicket = new Group_Ticket();
      $existing = $groupTicket->find([
         'tickets_id' => (int) $ticketId,
         'groups_id'  => (int) $groupId,
         'type'       => CommonITILActor::ASSIGN,
      ]);

      if (count($existing) > 0) {
         return;
      }

      $groupTicket->add([
         'tickets_id' => (int) $ticketId,
         'groups_id'  => (int) $groupId,
         'type'       => CommonITILActor::ASSIGN,
      ]);
   }

   protected function withConflictGuard(callable $fn): void {
      global $PLUGIN_HOOKS;

      $alreadyAssigning = self::$assignmentInProgress;
      self::$assignmentInProgress = true;

      $targets = [
         ['item_add', 'escalade', 'Group_Ticket'],
         ['pre_item_add', 'escalade', 'Group_Ticket'],
         ['item_add', 'escalade', 'Ticket_User'],
         ['item_add', 'behaviors', 'Group_Ticket'],
         ['item_add', 'behaviors', 'Ticket_User'],
      ];

      $saved = [];
      foreach ($targets as $i => $target) {
         if (isset($PLUGIN_HOOKS[$target[0]][$target[1]][$target[2]])) {
            $saved[$i] = $PLUGIN_HOOKS[$target[0]][$target[1]][$target[2]];
            unset($PLUGIN_HOOKS[$target[0]][$target[1]][$target[2]]);
         }
      }

      try {
         $fn();
      } finally {
         foreach ($saved as $i => $value) {
            $PLUGIN_HOOKS[$targets[$i][0]][$targets[$i][1]][$targets[$i][2]] = $value;
         }
         self::$assignmentInProgress = $alreadyAssigning;
      }
   }

   protected function logDecision($ticketId, $groupId, $itilcategoriesId, $entitiesId, string $mode, $selectedUserId, array $ignored, string $reason): void {
      PluginAtribuicaointeligenteAssignmentDecisionLog::addDecision(
         $ticketId,
         $groupId,
         $itilcategoriesId,
         $entitiesId,
         $mode,
         $selectedUserId,
         $ignored,
         $reason
      );
   }
}
