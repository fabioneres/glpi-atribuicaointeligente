<?php
/**
 * Logs de decisoes de atribuicao.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

PluginAtribuicaointeligenteConfig::assertCanView();

global $DB;

if (!function_exists('plugin_atribuicaointeligente_logs_url')) {
   function plugin_atribuicaointeligente_logs_url(bool $embedded, array $params): string {
      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];
      if ($embedded) {
         $params = ['forcetab' => 'PluginAtribuicaointeligenteConfig$6'] + $params;
      }

      return $target . '?' . http_build_query($params);
   }
}

if (!function_exists('plugin_atribuicaointeligente_logs_pager')) {
   function plugin_atribuicaointeligente_logs_pager(bool $embedded, int $start, int $totalRows, int $limit, string $scope): void {
      if ($totalRows <= $limit) {
         return;
      }

      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];
      $parameters = $embedded
         ? http_build_query(['forcetab' => 'PluginAtribuicaointeligenteConfig$6', 'logs_scope' => $scope])
         : http_build_query(['logs_scope' => $scope]);

      $previousLimit = $_SESSION['glpilist_limit'] ?? null;
      $_SESSION['glpilist_limit'] = $limit;
      Html::printPager($start, $totalRows, $target, $parameters);
      if ($previousLimit === null) {
         unset($_SESSION['glpilist_limit']);
      } else {
         $_SESSION['glpilist_limit'] = $previousLimit;
      }
   }
}

$embedded = !empty($_GET['embedded']);
$table = PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
$rows = [];
$totalRows = 0;
$allRows = 0;
$limit = min(max((int) ($_SESSION['glpilist_limit'] ?? 20), 1), 100);

// Dentro da aba, os parametros da pagina nao chegam aqui: o front/config.form.php
// os guarda na sessao antes de montar as abas. Acessado direto, vale a URL.
$logsState = $_SESSION['plugin_atribuicaointeligente_logs'] ?? [];
$start = max(0, (int) ($_GET['start'] ?? ($logsState['start'] ?? 0)));
$scope = (string) ($_GET['logs_scope'] ?? ($logsState['scope'] ?? 'acting'));
if (!in_array($scope, ['acting', 'all'], true)) {
   $scope = 'acting';
}

// "Atuacao do plugin" = decisoes em que ele distribuiu ou tentou distribuir e
// nao encontrou tecnico. Fica de fora o que nao era com ele: chamado que ja tinha
// tecnico (`skip`) e categoria sem grupo encarregado (`none`). O filtro usa o
// campo estruturado `mode`, e nao o texto do motivo, que pode mudar.
$actingModes = ['balancing', 'rotation'];

if ($DB->tableExists($table)) {
   $where = [];
   $entityCriteria = PluginAtribuicaointeligenteConfig::getEntityRestrictCriteria('entities_id', true);
   if (!empty($entityCriteria)) {
      $where = $entityCriteria;
   }

   $countCriteria = [
      'SELECT' => [new QueryExpression('COUNT(*) AS total')],
      'FROM'   => $table,
   ];
   if (!empty($where)) {
      $countCriteria['WHERE'] = $where;
   }
   $countRow = $DB->request($countCriteria)->current();
   $allRows = (int) ($countRow['total'] ?? 0);

   if ($scope === 'acting') {
      $where['mode'] = $actingModes;
      $countCriteria['WHERE'] = $where;
      $countRow = $DB->request($countCriteria)->current();
      $totalRows = (int) ($countRow['total'] ?? 0);
   } else {
      $totalRows = $allRows;
   }

   // Pagina guardada na sessao pode ter ficado alem do fim, por exemplo apos
   // expurgo de registros: volta para a ultima pagina existente.
   if ($totalRows > 0 && $start >= $totalRows) {
      $start = (int) (floor(($totalRows - 1) / $limit) * $limit);
   }

   $criteria = [
      'FROM'  => $table,
      'ORDER' => 'id DESC',
      'START' => $start,
      'LIMIT' => $limit,
   ];
   if (!empty($where)) {
      $criteria['WHERE'] = $where;
   }

   $iterator = $DB->request($criteria);
   foreach ($iterator as $row) {
      $rows[] = $row;
   }
}

if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteAssignmentDecisionLog::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}
?>

<div class="m-3">
   <h3>
      <i class="ti ti-list-details me-2"></i>
      <?php echo __('Logs de decisoes de atribuicao', 'atribuicaointeligente'); ?>
   </h3>

   <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
      <div class="text-muted">
         <?php
         if ($scope === 'acting') {
            echo sprintf(
               __('Exibindo as decisões em que o plugin atuou ou tentou atuar: %1$s registros, até %2$s por página.', 'atribuicaointeligente'),
               (int) $totalRows,
               (int) $limit
            );
            $hiddenRows = max(0, $allRows - $totalRows);
            if ($hiddenRows > 0) {
               echo ' ' . sprintf(
                  __('%s decisões em que o plugin não tinha o que fazer estão ocultas.', 'atribuicaointeligente'),
                  (int) $hiddenRows
               );
            }
         } else {
            echo sprintf(
               __('Exibindo todas as decisões: %1$s registros, até %2$s por página.', 'atribuicaointeligente'),
               (int) $totalRows,
               (int) $limit
            );
         }
         ?>
      </div>
      <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo htmlspecialchars(__('Filtro dos logs', 'atribuicaointeligente'), ENT_QUOTES, 'UTF-8'); ?>">
         <a class="btn <?php echo $scope === 'acting' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
            href="<?php echo htmlspecialchars(plugin_atribuicaointeligente_logs_url($embedded, ['logs_scope' => 'acting']), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo __('Atuação do plugin', 'atribuicaointeligente'); ?>
         </a>
         <a class="btn <?php echo $scope === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
            href="<?php echo htmlspecialchars(plugin_atribuicaointeligente_logs_url($embedded, ['logs_scope' => 'all']), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo __('Todas as decisões', 'atribuicaointeligente'); ?>
         </a>
      </div>
   </div>

   <?php plugin_atribuicaointeligente_logs_pager($embedded, $start, $totalRows, $limit, $scope); ?>

   <div class="table-responsive">
      <table class="table table-striped table-hover">
         <thead>
            <tr>
               <th><?php echo __('Data', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Chamado', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Grupo', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Modo', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Tecnico escolhido', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Tecnicos ignorados', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Motivo', 'atribuicaointeligente'); ?></th>
            </tr>
         </thead>
         <tbody>
            <?php if (empty($rows)): ?>
               <tr>
                  <td colspan="7" class="text-muted text-center">
                     <?php echo __('Nenhum log registrado ainda.', 'atribuicaointeligente'); ?>
                  </td>
               </tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
               <?php
               $ignored = json_decode((string) ($row['ignored_users'] ?? '[]'), true);
               $ignoredText = [];
               if (is_array($ignored)) {
                  foreach ($ignored as $ignoredRow) {
                     $ignoredText[] = getUserName((int) ($ignoredRow['users_id'] ?? 0)) . ': ' . (string) ($ignoredRow['reason'] ?? '');
                  }
               }
               $groupName = !empty($row['groups_id'])
                  ? html_entity_decode(Dropdown::getDropdownName('glpi_groups', (int) $row['groups_id']), ENT_QUOTES, 'UTF-8')
                  : '-';
               ?>
               <tr>
                  <td><?php echo htmlspecialchars((string) ($row['date_creation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo !empty($row['tickets_id']) ? (int) $row['tickets_id'] : '-'; ?></td>
                  <td><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['mode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo !empty($row['selected_users_id']) ? htmlspecialchars(getUserName((int) $row['selected_users_id']), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                  <td><?php echo nl2br(htmlspecialchars(implode("\n", $ignoredText), ENT_QUOTES, 'UTF-8')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
               </tr>
            <?php endforeach; ?>
         </tbody>
      </table>
   </div>

   <?php plugin_atribuicaointeligente_logs_pager($embedded, $start, $totalRows, $limit, $scope); ?>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
