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

$embedded = !empty($_GET['embedded']);
$table = PluginAtribuicaointeligenteConfig::getDecisionLogsTable();
$rows = [];

if ($DB->tableExists($table)) {
   $criteria = [
      'FROM'  => $table,
      'ORDER' => 'id DESC',
      'LIMIT' => 100,
   ];
   $entityCriteria = PluginAtribuicaointeligenteConfig::getEntityRestrictCriteria('entities_id', true);
   if (!empty($entityCriteria)) {
      $criteria['WHERE'] = $entityCriteria;
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
      <?php echo __('Últimas decisões de atribuição', 'atribuicaointeligente'); ?>
   </h3>

   <div class="table-responsive">
      <table class="table table-striped table-hover">
         <thead>
            <tr>
               <th><?php echo __('Data', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Chamado', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Grupo', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Modo', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Técnico escolhido', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Técnicos ignorados', 'atribuicaointeligente'); ?></th>
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
               ?>
               <tr>
                  <td><?php echo htmlspecialchars((string) ($row['date_creation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo !empty($row['tickets_id']) ? (int) $row['tickets_id'] : '-'; ?></td>
                  <td><?php echo !empty($row['groups_id']) ? htmlspecialchars(Dropdown::getDropdownName('glpi_groups', (int) $row['groups_id']), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['mode'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo !empty($row['selected_users_id']) ? htmlspecialchars(getUserName((int) $row['selected_users_id']), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                  <td><?php echo nl2br(htmlspecialchars(implode("\n", $ignoredText), ENT_QUOTES, 'UTF-8')); ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
               </tr>
            <?php endforeach; ?>
         </tbody>
      </table>
   </div>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
