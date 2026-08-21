<?php
/**
 * Relatorio de distribuicoes de chamados.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

PluginAtribuicaointeligenteConfig::assertCanView();

global $DB;

if (!function_exists('plugin_atribuicaointeligente_distribution_escape')) {
   function plugin_atribuicaointeligente_distribution_escape($value): string {
      return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_decode_label')) {
   function plugin_atribuicaointeligente_distribution_decode_label($value): string {
      $label = (string) $value;
      for ($i = 0; $i < 2; $i++) {
         $decoded = html_entity_decode($label, ENT_QUOTES, 'UTF-8');
         if ($decoded === $label) {
            break;
         }
         $label = $decoded;
      }

      return $label;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_entity_label')) {
   function plugin_atribuicaointeligente_distribution_entity_label($entitiesId): string {
      $entitiesId = (int) $entitiesId;
      if ($entitiesId <= 0) {
         return __('Todas / global', 'atribuicaointeligente');
      }

      $label = Dropdown::getDropdownName('glpi_entities', $entitiesId);
      return plugin_atribuicaointeligente_distribution_decode_label($label !== '' ? $label : $entitiesId);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_date')) {
   function plugin_atribuicaointeligente_distribution_date(string $value): string {
      return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_filter_fields')) {
   function plugin_atribuicaointeligente_distribution_filter_fields(): array {
      return [
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
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_has_request_filters')) {
   function plugin_atribuicaointeligente_distribution_has_request_filters(): bool {
      if (isset($_GET['distribution_filter'])) {
         return true;
      }

      foreach (plugin_atribuicaointeligente_distribution_filter_fields() as $field) {
         if (array_key_exists($field, $_GET)) {
            return true;
         }
      }

      return false;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_normalize_filters')) {
   function plugin_atribuicaointeligente_distribution_normalize_filters(array $input): array {
      $distributionSource = (string) ($input['distribution_source'] ?? ($input['source'] ?? ''));

      $filters = [
         'date_start'        => plugin_atribuicaointeligente_distribution_date((string) ($input['date_start'] ?? date('Y-m-01'))),
         'date_end'          => plugin_atribuicaointeligente_distribution_date((string) ($input['date_end'] ?? date('Y-m-d'))),
         'users_id_actor'    => max(0, (int) ($input['users_id_actor'] ?? 0)),
         'users_id_to'       => max(0, (int) ($input['users_id_to'] ?? 0)),
         'groups_id_to'      => max(0, (int) ($input['groups_id_to'] ?? 0)),
         'entities_id'       => array_key_exists('entities_id', $input) && $input['entities_id'] !== '' ? max(0, (int) $input['entities_id']) : '',
         'entities_id_from'  => array_key_exists('entities_id_from', $input) && $input['entities_id_from'] !== '' ? max(0, (int) $input['entities_id_from']) : '',
         'itilcategories_id' => max(0, (int) ($input['itilcategories_id'] ?? 0)),
         'action_type'       => (string) ($input['action_type'] ?? ''),
         'source'            => $distributionSource,
      ];

      if (!in_array($filters['action_type'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedActions()), true)) {
         $filters['action_type'] = '';
      }
      if (!in_array($filters['source'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedSources()), true)) {
         $filters['source'] = '';
      }

      return $filters;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_filter_url')) {
   function plugin_atribuicaointeligente_distribution_filter_url(bool $embedded, array $filters): string {
      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];

      if ($embedded) {
         $filters['forcetab'] = 'PluginAtribuicaointeligenteConfig$5';
      }

      return $target . '?' . http_build_query($filters);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_has_filter')) {
   function plugin_atribuicaointeligente_distribution_has_filter(array $filters, string $field): bool {
      return array_key_exists($field, $filters)
         && $filters[$field] !== ''
         && $filters[$field] !== null;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_entity_dropdown')) {
   function plugin_atribuicaointeligente_distribution_entity_dropdown(string $name, $value): void {
      global $DB;

      echo '<select class="form-select" name="' . plugin_atribuicaointeligente_distribution_escape($name) . '">';
      echo '<option value="">' . plugin_atribuicaointeligente_distribution_escape(__('Todas', 'atribuicaointeligente')) . '</option>';

      if ($DB->tableExists('glpi_entities')) {
         $result = $DB->doQuery('SELECT `id`, `completename`, `name` FROM `glpi_entities` ORDER BY `completename` ASC, `name` ASC, `id` ASC');
         while ($result && ($row = $result->fetch_assoc())) {
            $entityId = (int) ($row['id'] ?? 0);
            $entityName = (string) ($row['completename'] ?? '');
            if ($entityName === '') {
               $entityName = (string) ($row['name'] ?? $entityId);
            }
            $entityName = plugin_atribuicaointeligente_distribution_decode_label($entityName);
            $selected = $value !== '' && (int) $value === $entityId ? ' selected' : '';
            echo '<option value="' . $entityId . '"' . $selected . '>' . plugin_atribuicaointeligente_distribution_escape($entityName) . '</option>';
         }
      }

      echo '</select>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_user_dropdown')) {
   function plugin_atribuicaointeligente_distribution_user_dropdown(string $name, $value, string $field, array $filters): void {
      global $DB;

      $value = (int) $value;
      $table = PluginAtribuicaointeligenteDistributionLog::getTable();
      if (!plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')
         || !$DB->tableExists($table)
      ) {
         User::dropdown([
            'name'                => $name,
            'value'               => $value,
            'right'               => 'all',
            'display_emptychoice' => true,
            'width'               => '100%',
         ]);
         return;
      }

      $field = $field === 'users_id_to' ? 'users_id_to' : 'users_id_actor';
      $optionFilters = $filters;
      if ($field === 'users_id_actor') {
         $optionFilters['users_id_actor'] = 0;
      } else {
         $optionFilters['users_id_to'] = 0;
      }
      $whereSql = plugin_atribuicaointeligente_distribution_where($optionFilters);
      $rows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT DISTINCT `{$field}` AS users_id
          FROM `{$table}`
          WHERE {$whereSql}
            AND `{$field}` IS NOT NULL
            AND `{$field}` > 0
          ORDER BY `{$field}` ASC
          LIMIT 250"
      );

      $userIds = [];
      foreach ($rows as $row) {
         $userId = (int) ($row['users_id'] ?? 0);
         if ($userId > 0) {
            $userIds[] = $userId;
         }
      }

      if ($field === 'users_id_to') {
         $decisionTable = PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
         if ($DB->tableExists($decisionTable)) {
            $decisionWhereSql = plugin_atribuicaointeligente_distribution_decision_log_where($optionFilters, 'decisionlog');
            $decisionRows = plugin_atribuicaointeligente_distribution_fetch_rows(
               "SELECT DISTINCT decisionlog.`selected_users_id` AS users_id
                FROM `{$decisionTable}` decisionlog
                WHERE {$decisionWhereSql}
                ORDER BY decisionlog.`selected_users_id` ASC
                LIMIT 250"
            );
            foreach ($decisionRows as $row) {
               $userId = (int) ($row['users_id'] ?? 0);
               if ($userId > 0) {
                  $userIds[] = $userId;
               }
            }
         }
      }

      $userIds = array_values(array_unique($userIds));
      if ($value > 0 && !in_array($value, $userIds, true)) {
         $userIds[] = $value;
      }

      echo '<select class="form-select" name="' . plugin_atribuicaointeligente_distribution_escape($name) . '">';
      echo '<option value="0">' . plugin_atribuicaointeligente_distribution_escape(__('-----')) . '</option>';
      foreach ($userIds as $userId) {
         $selected = $value === $userId ? ' selected' : '';
         echo '<option value="' . $userId . '"' . $selected . '>'
            . plugin_atribuicaointeligente_distribution_escape(getUserName($userId))
            . '</option>';
      }
      echo '</select>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_fetch_rows')) {
   function plugin_atribuicaointeligente_distribution_fetch_rows(string $sql): array {
      global $DB;

      $rows = [];
      $result = $DB->doQuery($sql);
      while ($result && ($row = $result->fetch_assoc())) {
         $rows[] = $row;
      }

      return $rows;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_max')) {
   function plugin_atribuicaointeligente_distribution_max(array $rows, string $field): int {
      $max = 0;
      foreach ($rows as $row) {
         $max = max($max, (int) ($row[$field] ?? 0));
      }

      return $max > 0 ? $max : 1;
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_bar')) {
   function plugin_atribuicaointeligente_distribution_bar(int $value, int $max): string {
      $width = $max > 0 ? max(4, min(100, (int) round(($value / $max) * 100))) : 0;

      return '<div class="progress" style="height: 0.75rem;">'
         . '<div class="progress-bar" role="progressbar" style="width: ' . $width . '%;" aria-valuenow="' . $value . '" aria-valuemin="0" aria-valuemax="' . $max . '"></div>'
         . '</div>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_where')) {
   function plugin_atribuicaointeligente_distribution_where(array $filters, string $alias = ''): string {
      $clauses = ['1 = 1'];
      $prefix = $alias !== '' ? '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $alias) . '`.' : '';

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = $prefix . '`entities_id` IN (' . implode(',', $entities) . ')';
      }

      if ($filters['date_start'] !== '') {
         $clauses[] = $prefix . "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = $prefix . "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }

      foreach (['users_id_actor', 'users_id_to', 'groups_id_to', 'itilcategories_id'] as $field) {
         if ((int) ($filters[$field] ?? 0) > 0) {
            $clauses[] = $prefix . "`{$field}` = " . (int) $filters[$field];
         }
      }
      foreach (['entities_id', 'entities_id_from'] as $field) {
         if (plugin_atribuicaointeligente_distribution_has_filter($filters, $field)) {
            $clauses[] = $prefix . "`{$field}` = " . (int) $filters[$field];
         }
      }

      if ($filters['action_type'] !== '') {
         $clauses[] = $prefix . "`action_type` = '" . addslashes($filters['action_type']) . "'";
      }
      if ($filters['source'] !== '') {
         $clauses[] = $prefix . "`source` = '" . addslashes($filters['source']) . "'";
      }

      return implode(' AND ', $clauses);
   }
}

if (!function_exists('plugin_atribuicaointeligente_distribution_decision_log_where')) {
   function plugin_atribuicaointeligente_distribution_decision_log_where(array $filters, string $alias = 'decisionlog'): string {
      $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
      $prefix = '`' . $alias . '`.';
      $clauses = [
         $prefix . "`selected_users_id` IS NOT NULL",
         $prefix . "`tickets_id` IS NOT NULL",
         $prefix . "`tickets_id` > 0",
         $prefix . "`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado')",
      ];

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = $prefix . '`entities_id` IN (' . implode(',', $entities) . ')';
      }
      if ($filters['date_start'] !== '') {
         $clauses[] = $prefix . "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = $prefix . "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }
      if (plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id')) {
         $clauses[] = $prefix . "`entities_id` = " . (int) $filters['entities_id'];
      }
      if (plugin_atribuicaointeligente_distribution_has_filter($filters, 'entities_id_from')) {
         $clauses[] = '1 = 0';
      }
      if ((int) ($filters['itilcategories_id'] ?? 0) > 0) {
         $clauses[] = $prefix . "`itilcategories_id` = " . (int) $filters['itilcategories_id'];
      }
      if ((int) ($filters['groups_id_to'] ?? 0) > 0) {
         $clauses[] = $prefix . "`groups_id` = " . (int) $filters['groups_id_to'];
      }
      if ((int) ($filters['users_id_to'] ?? 0) > 0) {
         $clauses[] = $prefix . "`selected_users_id` = " . (int) $filters['users_id_to'];
      }
      if ((int) ($filters['users_id_actor'] ?? 0) > 0) {
         $clauses[] = '1 = 0';
      }
      if (($filters['action_type'] ?? '') !== ''
         && $filters['action_type'] !== PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED
      ) {
         $clauses[] = '1 = 0';
      }
      if (($filters['source'] ?? '') === PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL) {
         $clauses[] = '1 = 0';
      }

      return implode(' AND ', $clauses);
   }
}

$embedded = !empty($_GET['embedded']);
$table = PluginAtribuicaointeligenteDistributionLog::getTable();

$filterSessionKey = 'plugin_atribuicaointeligente_distribution_filters';
if (!empty($_GET['distribution_clear'])) {
   unset($_SESSION[$filterSessionKey]);
}

if (empty($_GET['distribution_clear']) && plugin_atribuicaointeligente_distribution_has_request_filters()) {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters($_GET);
   $_SESSION[$filterSessionKey] = $filters;
} elseif (isset($_SESSION[$filterSessionKey]) && is_array($_SESSION[$filterSessionKey])) {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters($_SESSION[$filterSessionKey]);
} else {
   $filters = plugin_atribuicaointeligente_distribution_normalize_filters([]);
}

PluginAtribuicaointeligenteConfig::ensureDistributionLogSchema();

$whereSql = plugin_atribuicaointeligente_distribution_where($filters);
$summaryRows = [];
$topDistributorRows = [];
$technicianRows = [];
$topTechnicianRows = [];
$dailyRows = [];
$actuationRows = [
   'plugin_only'  => [
      'label'        => __('Automação integral', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
   'plugin_human' => [
      'label'        => __('Automação parcial', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
   'human_only'   => [
      'label'        => __('Atuação manual', 'atribuicaointeligente'),
      'tickets_count' => 0,
      'total_events'  => 0,
   ],
];
$transferRows = [];
$totalRows = 0;
$distinctTickets = 0;

if ($DB->tableExists($table)) {
   $countResult = $DB->doQuery(
      "SELECT COUNT(*) AS total,
              COUNT(DISTINCT `tickets_id`) AS distinct_tickets
       FROM `{$table}`
       WHERE {$whereSql}"
   );
   if ($countResult && ($countRow = $countResult->fetch_assoc())) {
      $totalRows = (int) ($countRow['total'] ?? 0);
      $distinctTickets = (int) ($countRow['distinct_tickets'] ?? 0);
   }

   $summaryResult = $DB->doQuery(
      "SELECT `users_id_actor`,
              SUM(CASE WHEN `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "' THEN 1 ELSE 0 END) AS technician_events,
              SUM(CASE WHEN `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_GROUP_ASSIGNED . "' THEN 1 ELSE 0 END) AS group_events,
              SUM(CASE WHEN `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "' THEN 1 ELSE 0 END) AS entity_events,
              COUNT(*) AS total_events,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
       GROUP BY `users_id_actor`
       ORDER BY tickets_count DESC, total_events DESC, `users_id_actor` ASC
       LIMIT 5"
   );
   if ($summaryResult) {
      while ($row = $summaryResult->fetch_assoc()) {
         $summaryRows[] = $row;
      }
   }

   $topDistributorRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT `users_id_actor`,
              COUNT(*) AS total_events,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
       GROUP BY `users_id_actor`
       ORDER BY total_events DESC, `users_id_actor` ASC
       LIMIT 5"
   );

    $dailyRows = plugin_atribuicaointeligente_distribution_fetch_rows(
       "SELECT DATE(`date_creation`) AS distribution_day,
               COUNT(*) AS total_events,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
       GROUP BY DATE(`date_creation`)
       ORDER BY distribution_day ASC
       LIMIT 120"
   );

    $decisionTable = PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
    if ($DB->tableExists($decisionTable)) {
       $whereSqlAliased = plugin_atribuicaointeligente_distribution_where($filters, 'dl');
      $decisionLogWhereSql = plugin_atribuicaointeligente_distribution_decision_log_where($filters, 'decisionlog');

      $technicianRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT technician_summary.`users_id_to`,
                 COUNT(*) AS total_events,
                 COUNT(DISTINCT technician_summary.`tickets_id`) AS tickets_count
          FROM (
             SELECT dl.`tickets_id`,
                    dl.`users_id_to`
             FROM `{$table}` dl
             WHERE {$whereSqlAliased}
               AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
               AND dl.`users_id_to` IS NOT NULL
               AND dl.`users_id_to` > 0
             UNION ALL
             SELECT decisionlog.`tickets_id`,
                    decisionlog.`selected_users_id` AS users_id_to
             FROM `{$decisionTable}` decisionlog
             WHERE {$decisionLogWhereSql}
          ) technician_summary
          GROUP BY technician_summary.`users_id_to`
          ORDER BY total_events DESC, technician_summary.`users_id_to` ASC
          LIMIT 10"
      );
      $topTechnicianRows = array_slice($technicianRows, 0, 5);

      $actuationRawRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT ticket_classification.`classification`,
                 COUNT(*) AS tickets_count,
                 SUM(ticket_classification.`total_events`) AS total_events
          FROM (
             SELECT ticket_summary.`tickets_id`,
                     ticket_summary.`total_events`,
                     CASE
                       WHEN ticket_summary.`manual_clear_events` > 0
                            AND (ticket_summary.`plugin_events` = 0
                                 OR ticket_summary.`last_manual_clear_date` >= ticket_summary.`last_plugin_date`)
                           THEN 'human_only'
                       WHEN ticket_summary.`plugin_update_events` > 0
                          THEN 'plugin_human'
                       WHEN ticket_summary.`plugin_only_events` > 0
                          THEN 'plugin_only'
                       WHEN ticket_summary.`manual_clear_events` > 0
                          THEN 'human_only'
                        ELSE 'human_only'
                     END AS classification
               FROM (
                  SELECT ticket_events.`tickets_id`,
                         SUM(ticket_events.`total_events`) AS total_events,
                         SUM(ticket_events.`plugin_events`) AS plugin_events,
                         SUM(ticket_events.`plugin_only_events`) AS plugin_only_events,
                         SUM(ticket_events.`plugin_update_events`) AS plugin_update_events,
                         SUM(ticket_events.`manual_clear_events`) AS manual_clear_events,
                         MAX(ticket_events.`last_plugin_date`) AS last_plugin_date,
                         MAX(ticket_events.`last_manual_clear_date`) AS last_manual_clear_date
                  FROM (
                     SELECT dl.`tickets_id`,
                            COUNT(*) AS total_events,
                            0 AS plugin_events,
                            0 AS plugin_only_events,
                            0 AS plugin_update_events,
                            SUM(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN 1
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                     THEN 1 ELSE 0 END) AS manual_technician_events,
                            SUM(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN 1
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                      THEN 1 ELSE 0 END) AS manual_clear_events,
                            NULL AS last_plugin_date,
                            MAX(CASE WHEN dl.`source` = '" . PluginAtribuicaointeligenteDistributionLog::SOURCE_MANUAL . "'
                                       AND dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
                                      THEN dl.`date_creation`
                                     WHEN dl.`action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
                                      THEN dl.`date_creation` ELSE NULL END) AS last_manual_clear_date
                     FROM `{$table}` dl
                     WHERE {$whereSqlAliased}
                     GROUP BY dl.`tickets_id`
                     UNION ALL
                     SELECT decisionlog.`tickets_id`,
                            COUNT(*) AS total_events,
                            SUM(CASE WHEN decisionlog.`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado') THEN 1 ELSE 0 END) AS plugin_events,
                            SUM(CASE WHEN decisionlog.`reason` = 'Tecnico atribuido automaticamente' THEN 1 ELSE 0 END) AS plugin_only_events,
                            SUM(CASE WHEN decisionlog.`reason` = 'Tecnico atribuido automaticamente apos atualizacao do chamado' THEN 1 ELSE 0 END) AS plugin_update_events,
                            0 AS manual_technician_events,
                            0 AS manual_clear_events,
                            MAX(CASE WHEN decisionlog.`reason` IN ('Tecnico atribuido automaticamente', 'Tecnico atribuido automaticamente apos atualizacao do chamado') THEN decisionlog.`date_creation` ELSE NULL END) AS last_plugin_date,
                            NULL AS last_manual_clear_date
                     FROM `{$decisionTable}` decisionlog
                     WHERE {$decisionLogWhereSql}
                     GROUP BY decisionlog.`tickets_id`
                 ) ticket_events
                 GROUP BY ticket_events.`tickets_id`
              ) ticket_summary
           ) ticket_classification
           GROUP BY ticket_classification.`classification`"
      );

      foreach ($actuationRawRows as $row) {
         $classification = (string) ($row['classification'] ?? '');
         if (isset($actuationRows[$classification])) {
            $actuationRows[$classification]['tickets_count'] = (int) ($row['tickets_count'] ?? 0);
            $actuationRows[$classification]['total_events'] = (int) ($row['total_events'] ?? 0);
         }
      }
      uasort($actuationRows, static function (array $left, array $right): int {
         $ticketDiff = (int) ($right['tickets_count'] ?? 0) <=> (int) ($left['tickets_count'] ?? 0);
         if ($ticketDiff !== 0) {
            return $ticketDiff;
         }

         return (int) ($right['total_events'] ?? 0) <=> (int) ($left['total_events'] ?? 0);
      });
   } else {
      $technicianRows = plugin_atribuicaointeligente_distribution_fetch_rows(
         "SELECT `users_id_to`,
                 COUNT(*) AS total_events,
                 COUNT(DISTINCT `tickets_id`) AS tickets_count
          FROM `{$table}`
          WHERE {$whereSql}
            AND `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_TECHNICIAN_ASSIGNED . "'
            AND `users_id_to` IS NOT NULL
          GROUP BY `users_id_to`
          ORDER BY total_events DESC, `users_id_to` ASC
          LIMIT 10"
      );
      $topTechnicianRows = array_slice($technicianRows, 0, 5);
   }

   $transferRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT `entities_id_from`,
              `entities_id_to`,
              COUNT(*) AS total_events,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
         AND `action_type` = '" . PluginAtribuicaointeligenteDistributionLog::ACTION_ENTITY_TRANSFERRED . "'
       GROUP BY `entities_id_from`, `entities_id_to`
       ORDER BY total_events DESC, `entities_id_from` ASC, `entities_id_to` ASC
       LIMIT 10"
   );
}

if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteDistributionLog::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}

$formAction = $embedded ? PluginAtribuicaointeligenteConfig::getFormURL(true) : $_SERVER['PHP_SELF'];
?>

<div class="m-3">
   <h3>
      <i class="ti ti-route me-2"></i>
      <?php echo __('Distribuicoes de chamados', 'atribuicaointeligente'); ?>
   </h3>

   <form method="get" action="<?php echo plugin_atribuicaointeligente_distribution_escape($formAction); ?>" class="card mb-3">
      <?php if ($embedded): ?>
         <?php echo Html::hidden('forcetab', ['value' => 'PluginAtribuicaointeligenteConfig$5']); ?>
      <?php endif; ?>
      <?php echo Html::hidden('distribution_filter', ['value' => '1']); ?>
      <div class="card-body">
         <div class="row g-3">
            <div class="col-12 col-md-3">
               <label class="form-label" for="date_start"><?php echo __('Inicio', 'atribuicaointeligente'); ?></label>
               <input class="form-control" type="date" id="date_start" name="date_start" value="<?php echo plugin_atribuicaointeligente_distribution_escape($filters['date_start']); ?>">
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="date_end"><?php echo __('Fim', 'atribuicaointeligente'); ?></label>
               <input class="form-control" type="date" id="date_end" name="date_end" value="<?php echo plugin_atribuicaointeligente_distribution_escape($filters['date_end']); ?>">
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Distribuidor', 'atribuicaointeligente'); ?></label>
               <?php
               plugin_atribuicaointeligente_distribution_user_dropdown(
                  'users_id_actor',
                  (int) $filters['users_id_actor'],
                  'users_id_actor',
                  $filters
               );
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Tecnico destino', 'atribuicaointeligente'); ?></label>
               <?php
               plugin_atribuicaointeligente_distribution_user_dropdown(
                  'users_id_to',
                  (int) $filters['users_id_to'],
                  'users_id_to',
                  $filters
               );
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Grupo destino', 'atribuicaointeligente'); ?></label>
               <?php
               Dropdown::show('Group', [
                  'name'                => 'groups_id_to',
                  'value'               => (int) $filters['groups_id_to'],
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Entidade do chamado', 'atribuicaointeligente'); ?></label>
               <?php
               plugin_atribuicaointeligente_distribution_entity_dropdown('entities_id', $filters['entities_id']);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Entidade origem da transferencia', 'atribuicaointeligente'); ?></label>
               <?php
               plugin_atribuicaointeligente_distribution_entity_dropdown('entities_id_from', $filters['entities_id_from']);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Categoria', 'atribuicaointeligente'); ?></label>
               <?php
               Dropdown::show('ITILCategory', [
                  'name'                => 'itilcategories_id',
                  'value'               => (int) $filters['itilcategories_id'],
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="action_type"><?php echo __('Tipo de distribuicao', 'atribuicaointeligente'); ?></label>
               <select class="form-select" id="action_type" name="action_type">
                  <option value=""><?php echo __('Todos'); ?></option>
                  <?php foreach (PluginAtribuicaointeligenteDistributionLog::getAllowedActions() as $action): ?>
                     <option value="<?php echo plugin_atribuicaointeligente_distribution_escape($action); ?>" <?php echo $filters['action_type'] === $action ? 'selected' : ''; ?>>
                        <?php echo plugin_atribuicaointeligente_distribution_escape(PluginAtribuicaointeligenteDistributionLog::getActionLabel($action)); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label" for="distribution_source"><?php echo __('Origem', 'atribuicaointeligente'); ?></label>
               <select class="form-select" id="distribution_source" name="distribution_source">
                  <option value=""><?php echo __('Todas'); ?></option>
                  <?php foreach (PluginAtribuicaointeligenteDistributionLog::getAllowedSources() as $source): ?>
                     <option value="<?php echo plugin_atribuicaointeligente_distribution_escape($source); ?>" <?php echo $filters['source'] === $source ? 'selected' : ''; ?>>
                        <?php echo plugin_atribuicaointeligente_distribution_escape(PluginAtribuicaointeligenteDistributionLog::getSourceLabel($source)); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
         </div>
      </div>
      <div class="card-footer d-flex gap-2">
         <button class="btn btn-primary" type="submit">
            <i class="ti ti-filter me-1"></i>
            <?php echo __('Filtrar', 'atribuicaointeligente'); ?>
         </button>
         <a class="btn btn-outline-secondary" href="<?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_filter_url($embedded, ['distribution_clear' => 1])); ?>">
            <i class="ti ti-x me-1"></i>
            <?php echo __('Limpar', 'atribuicaointeligente'); ?>
         </a>
      </div>
   </form>

   <div class="row g-3 mb-3">
      <div class="col-12 col-md-6">
         <div class="card">
            <div class="card-body">
               <div class="text-muted"><?php echo __('Eventos de distribuicao', 'atribuicaointeligente'); ?></div>
               <div class="fs-1 fw-bold"><?php echo (int) $totalRows; ?></div>
            </div>
         </div>
      </div>
      <div class="col-12 col-md-6">
         <div class="card">
            <div class="card-body">
               <div class="text-muted"><?php echo __('Chamados distintos', 'atribuicaointeligente'); ?></div>
               <div class="fs-1 fw-bold"><?php echo (int) $distinctTickets; ?></div>
            </div>
         </div>
      </div>
   </div>

   <div class="card mb-3">
      <div class="card-header">
         <h4 class="card-title mb-0"><?php echo __('Resumo por distribuidor', 'atribuicaointeligente'); ?></h4>
      </div>
      <div class="table-responsive">
         <table class="table table-striped table-hover mb-0">
            <thead>
               <tr>
                  <th><?php echo __('Distribuidor', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Tecnicos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Grupos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Transferencias', 'atribuicaointeligente'); ?></th>
               </tr>
            </thead>
            <tbody>
               <?php if (empty($summaryRows)): ?>
                  <tr>
                     <td colspan="6" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                  </tr>
               <?php endif; ?>
               <?php foreach ($summaryRows as $row): ?>
                  <tr>
                     <td><?php echo (int) $row['users_id_actor'] > 0 ? plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_actor'])) : __('Sistema', 'atribuicaointeligente'); ?></td>
                     <td><?php echo (int) $row['tickets_count']; ?></td>
                     <td><?php echo (int) $row['total_events']; ?></td>
                     <td><?php echo (int) $row['technician_events']; ?></td>
                     <td><?php echo (int) $row['group_events']; ?></td>
                     <td><?php echo (int) $row['entity_events']; ?></td>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   </div>

   <div class="row g-3 mb-3">
      <div class="col-12 col-xl-6">
         <div class="card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Distribuicao por tecnico', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Tecnico', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($technicianRows)): ?>
                        <tr>
                           <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php $technicianMax = plugin_atribuicaointeligente_distribution_max($technicianRows, 'total_events'); ?>
                     <?php foreach ($technicianRows as $row): ?>
                        <?php $total = (int) ($row['total_events'] ?? 0); ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_to'])); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($total, $technicianMax); ?></td>
                           <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>

      <div class="col-12 col-xl-6">
         <div class="card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Evolucao no periodo', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Dia', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($dailyRows)): ?>
                        <tr>
                           <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php $dailyMax = plugin_atribuicaointeligente_distribution_max($dailyRows, 'total_events'); ?>
                     <?php foreach ($dailyRows as $row): ?>
                        <?php $total = (int) ($row['total_events'] ?? 0); ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_distribution_escape($row['distribution_day'] ?? ''); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($total, $dailyMax); ?></td>
                           <td class="text-end"><?php echo $total; ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </div>

   <div class="row g-3 mb-3">
      <div class="col-12 col-xl-4">
         <div class="card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Top 5 distribuidores', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-striped mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Distribuidor', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($topDistributorRows)): ?>
                        <tr>
                           <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php $topDistributorMax = plugin_atribuicaointeligente_distribution_max($topDistributorRows, 'total_events'); ?>
                     <?php foreach ($topDistributorRows as $row): ?>
                        <?php $total = (int) ($row['total_events'] ?? 0); ?>
                        <tr>
                           <td><?php echo (int) $row['users_id_actor'] > 0 ? plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_actor'])) : __('Sistema', 'atribuicaointeligente'); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($total, $topDistributorMax); ?></td>
                           <td class="text-end"><?php echo $total; ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>

      <div class="col-12 col-xl-4">
         <div class="card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Atuação por chamado', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Classificação', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php $actuationMax = plugin_atribuicaointeligente_distribution_max($actuationRows, 'tickets_count'); ?>
                     <?php foreach ($actuationRows as $row): ?>
                        <?php
                        $tickets = (int) ($row['tickets_count'] ?? 0);
                        $events = (int) ($row['total_events'] ?? 0);
                        ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_distribution_escape($row['label'] ?? ''); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($tickets, $actuationMax); ?></td>
                           <td class="text-end"><?php echo $tickets; ?></td>
                           <td class="text-end"><?php echo $events; ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>

      <div class="col-12 col-xl-4">
         <div class="card h-100">
            <div class="card-header">
               <h4 class="card-title mb-0"><?php echo __('Top 5 tecnicos destino', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-striped mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Tecnico', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($topTechnicianRows)): ?>
                        <tr>
                           <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php $topTechnicianMax = plugin_atribuicaointeligente_distribution_max($topTechnicianRows, 'tickets_count'); ?>
                     <?php foreach ($topTechnicianRows as $row): ?>
                        <?php $tickets = (int) ($row['tickets_count'] ?? 0); ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_distribution_escape(getUserName((int) $row['users_id_to'])); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($tickets, $topTechnicianMax); ?></td>
                           <td class="text-end"><?php echo $tickets; ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </div>

   <div class="card mb-3">
      <div class="card-header">
         <h4 class="card-title mb-0"><?php echo __('Transferencias por entidade', 'atribuicaointeligente'); ?></h4>
      </div>
      <div class="table-responsive">
         <table class="table table-striped table-hover mb-0">
            <thead>
               <tr>
                  <th><?php echo __('Origem', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Destino', 'atribuicaointeligente'); ?></th>
                  <th class="text-end"><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                  <th class="text-end"><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
               </tr>
            </thead>
            <tbody>
               <?php if (empty($transferRows)): ?>
                  <tr>
                     <td colspan="4" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                  </tr>
               <?php endif; ?>
               <?php foreach ($transferRows as $row): ?>
                  <tr>
                     <td><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_from'] ?? 0)); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_entity_label($row['entities_id_to'] ?? 0)); ?></td>
                     <td class="text-end"><?php echo (int) ($row['total_events'] ?? 0); ?></td>
                     <td class="text-end"><?php echo (int) ($row['tickets_count'] ?? 0); ?></td>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
