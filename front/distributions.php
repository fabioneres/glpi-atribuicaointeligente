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

if (!function_exists('plugin_atribuicaointeligente_distribution_date')) {
   function plugin_atribuicaointeligente_distribution_date(string $value): string {
      return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
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
            $selected = $value !== '' && (int) $value === $entityId ? ' selected' : '';
            echo '<option value="' . $entityId . '"' . $selected . '>' . plugin_atribuicaointeligente_distribution_escape($entityName) . '</option>';
         }
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
   function plugin_atribuicaointeligente_distribution_where(array $filters): string {
      $clauses = ['1 = 1'];

      if (!Session::canViewAllEntities()) {
         $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
         $entities[] = 0;
         $entities = array_values(array_unique(array_filter($entities, static function($entityId) {
            return $entityId >= 0;
         })));
         if (empty($entities)) {
            $entities = [0];
         }
         $clauses[] = '`entities_id` IN (' . implode(',', $entities) . ')';
      }

      if ($filters['date_start'] !== '') {
         $clauses[] = "`date_creation` >= '" . addslashes($filters['date_start']) . " 00:00:00'";
      }
      if ($filters['date_end'] !== '') {
         $clauses[] = "`date_creation` <= '" . addslashes($filters['date_end']) . " 23:59:59'";
      }

      foreach (['users_id_actor', 'users_id_to', 'groups_id_to', 'itilcategories_id'] as $field) {
         if ((int) ($filters[$field] ?? 0) > 0) {
            $clauses[] = "`{$field}` = " . (int) $filters[$field];
         }
      }
      foreach (['entities_id', 'entities_id_from'] as $field) {
         if (plugin_atribuicaointeligente_distribution_has_filter($filters, $field)) {
            $clauses[] = "`{$field}` = " . (int) $filters[$field];
         }
      }

      if ($filters['action_type'] !== '') {
         $clauses[] = "`action_type` = '" . addslashes($filters['action_type']) . "'";
      }
      if ($filters['source'] !== '') {
         $clauses[] = "`source` = '" . addslashes($filters['source']) . "'";
      }

      return implode(' AND ', $clauses);
   }
}

$embedded = !empty($_GET['embedded']);
$table = PluginAtribuicaointeligenteDistributionLog::getTable();
$distributionSource = (string) ($_GET['distribution_source'] ?? ($_GET['source'] ?? ''));

$filters = [
   'date_start'        => plugin_atribuicaointeligente_distribution_date((string) ($_GET['date_start'] ?? date('Y-m-01'))),
   'date_end'          => plugin_atribuicaointeligente_distribution_date((string) ($_GET['date_end'] ?? date('Y-m-d'))),
   'users_id_actor'    => max(0, (int) ($_GET['users_id_actor'] ?? 0)),
   'users_id_to'       => max(0, (int) ($_GET['users_id_to'] ?? 0)),
   'groups_id_to'      => max(0, (int) ($_GET['groups_id_to'] ?? 0)),
   'entities_id'       => array_key_exists('entities_id', $_GET) && $_GET['entities_id'] !== '' ? max(0, (int) $_GET['entities_id']) : '',
   'entities_id_from'  => array_key_exists('entities_id_from', $_GET) && $_GET['entities_id_from'] !== '' ? max(0, (int) $_GET['entities_id_from']) : '',
   'itilcategories_id' => max(0, (int) ($_GET['itilcategories_id'] ?? 0)),
   'action_type'       => (string) ($_GET['action_type'] ?? ''),
   'source'            => $distributionSource,
];

if (!in_array($filters['action_type'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedActions()), true)) {
   $filters['action_type'] = '';
}
if (!in_array($filters['source'], array_merge([''], PluginAtribuicaointeligenteDistributionLog::getAllowedSources()), true)) {
   $filters['source'] = '';
}

PluginAtribuicaointeligenteConfig::ensureDistributionLogSchema();

$whereSql = plugin_atribuicaointeligente_distribution_where($filters);
$summaryRows = [];
$topDistributorRows = [];
$technicianRows = [];
$topTechnicianRows = [];
$dailyRows = [];
$sourceRows = [];
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
       ORDER BY total_events DESC, `users_id_actor` ASC
       LIMIT 50"
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
       LIMIT 20"
   );
   $topTechnicianRows = array_slice($technicianRows, 0, 5);

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

   $sourceRows = plugin_atribuicaointeligente_distribution_fetch_rows(
      "SELECT `source`,
              COUNT(*) AS total_events,
              COUNT(DISTINCT `tickets_id`) AS tickets_count
       FROM `{$table}`
       WHERE {$whereSql}
       GROUP BY `source`
       ORDER BY total_events DESC, `source` ASC"
   );

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
               User::dropdown([
                  'name'                => 'users_id_actor',
                  'value'               => (int) $filters['users_id_actor'],
                  'right'               => 'all',
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]);
               ?>
            </div>
            <div class="col-12 col-md-3">
               <label class="form-label"><?php echo __('Tecnico destino', 'atribuicaointeligente'); ?></label>
               <?php
               User::dropdown([
                  'name'                => 'users_id_to',
                  'value'               => (int) $filters['users_id_to'],
                  'right'               => 'all',
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]);
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
         <a class="btn btn-outline-secondary" href="<?php echo plugin_atribuicaointeligente_distribution_escape(plugin_atribuicaointeligente_distribution_filter_url($embedded, [])); ?>">
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
                  <th><?php echo __('Tecnicos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Grupos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Transferencias', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Chamados', 'atribuicaointeligente'); ?></th>
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
                     <td><?php echo (int) $row['technician_events']; ?></td>
                     <td><?php echo (int) $row['group_events']; ?></td>
                     <td><?php echo (int) $row['entity_events']; ?></td>
                     <td><?php echo (int) $row['total_events']; ?></td>
                     <td><?php echo (int) $row['tickets_count']; ?></td>
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
               <h4 class="card-title mb-0"><?php echo __('Manual x Plugin', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Origem', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Volume', 'atribuicaointeligente'); ?></th>
                        <th class="text-end"><?php echo __('Eventos', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($sourceRows)): ?>
                        <tr>
                           <td colspan="3" class="text-muted text-center"><?php echo __('Nenhum registro encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php $sourceMax = plugin_atribuicaointeligente_distribution_max($sourceRows, 'total_events'); ?>
                     <?php foreach ($sourceRows as $row): ?>
                        <?php $total = (int) ($row['total_events'] ?? 0); ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_distribution_escape(PluginAtribuicaointeligenteDistributionLog::getSourceLabel((string) ($row['source'] ?? ''))); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_distribution_bar($total, $sourceMax); ?></td>
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
                     <td><?php echo plugin_atribuicaointeligente_distribution_escape(Dropdown::getDropdownName('glpi_entities', (int) ($row['entities_id_from'] ?? 0))); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_distribution_escape(Dropdown::getDropdownName('glpi_entities', (int) ($row['entities_id_to'] ?? 0))); ?></td>
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
