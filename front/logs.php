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

if (!function_exists('plugin_atribuicaointeligente_logs_pager')) {
   function plugin_atribuicaointeligente_logs_pager(bool $embedded, int $start, int $totalRows, int $limit): void {
      if ($totalRows <= $limit) {
         return;
      }

      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];
      $parameters = $embedded
         ? http_build_query(['forcetab' => 'PluginAtribuicaointeligenteConfig$5'])
         : '';

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
$start = max(0, (int) ($_GET['start'] ?? 0));
$limit = min(max((int) ($_SESSION['glpilist_limit'] ?? 20), 1), 100);

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
   $totalRows = (int) ($countRow['total'] ?? 0);

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

   <div class="text-muted mb-2">
      <?php
      echo sprintf(
         __('Exibindo ate %1$s registros por pagina de um total de %2$s.', 'atribuicaointeligente'),
         (int) $limit,
         (int) $totalRows
      );
      ?>
   </div>

   <?php plugin_atribuicaointeligente_logs_pager($embedded, $start, $totalRows, $limit); ?>

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

   <?php plugin_atribuicaointeligente_logs_pager($embedded, $start, $totalRows, $limit); ?>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
