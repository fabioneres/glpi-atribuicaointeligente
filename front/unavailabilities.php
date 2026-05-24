<?php
/**
 * Listagem de indisponibilidades.
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
$canCreate = PluginAtribuicaointeligenteConfig::canCreateUnavailability();
$canUpdate = PluginAtribuicaointeligenteConfig::canUpdateUnavailability();
$table = PluginAtribuicaointeligenteConfig::getUnavailabilitiesTable();

if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteTechnicianUnavailability::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}

$rows = [];
if ($DB->tableExists($table)) {
   $criteria = [
      'FROM'  => $table,
      'ORDER' => 'is_active DESC, date_start DESC, id DESC',
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

$formUrl = Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php';
?>

<div class="m-3">
   <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">
         <i class="ti ti-user-off me-2"></i>
         <?php echo __('Indisponibilidades', 'atribuicaointeligente'); ?>
      </h3>
      <?php if ($canCreate): ?>
         <a class="btn btn-primary" href="<?php echo htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="ti ti-plus me-1"></i>
            <?php echo __('Adicionar', 'atribuicaointeligente'); ?>
         </a>
      <?php endif; ?>
   </div>

   <div class="table-responsive">
      <table class="table table-striped table-hover">
         <thead>
            <tr>
               <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Entidade', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Tipo', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Início', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Fim', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Dia', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Observação', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Ativo', 'atribuicaointeligente'); ?></th>
               <?php if ($canUpdate): ?>
                  <th class="text-end"><?php echo __('Ações', 'atribuicaointeligente'); ?></th>
               <?php endif; ?>
            </tr>
         </thead>
         <tbody>
            <?php if (empty($rows)): ?>
               <tr>
                  <td colspan="<?php echo $canUpdate ? 9 : 8; ?>" class="text-muted text-center">
                     <?php echo __('Nenhuma indisponibilidade cadastrada.', 'atribuicaointeligente'); ?>
                  </td>
               </tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
               <?php
               $id = (int) $row['id'];
               $canUpdateRow = $canUpdate
                  && PluginAtribuicaointeligenteConfig::canUseEntity((int) ($row['entities_id'] ?? 0));
               $entityName = ((int) $row['entities_id'] === 0)
                  ? __('Todas / global', 'atribuicaointeligente')
                  : Dropdown::getDropdownName('glpi_entities', (int) $row['entities_id']);
               ?>
               <tr>
                  <td><?php echo htmlspecialchars(getUserName((int) $row['users_id']), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($entityName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianUnavailability::getTypeLabel((string) $row['type']), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['date_start'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) ($row['date_end'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdayLabel($row['weekday'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo nl2br(htmlspecialchars((string) ($row['comment'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></td>
                  <td>
                     <?php if ((int) $row['is_active'] === 1): ?>
                        <span class="badge bg-success text-white"><?php echo __('Sim', 'atribuicaointeligente'); ?></span>
                     <?php else: ?>
                        <span class="badge bg-secondary text-white"><?php echo __('Não', 'atribuicaointeligente'); ?></span>
                     <?php endif; ?>
                  </td>
                  <?php if ($canUpdate): ?>
                     <td class="text-end text-nowrap">
                        <?php if ($canUpdateRow): ?>
                           <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($formUrl . '?id=' . $id, ENT_QUOTES, 'UTF-8'); ?>">
                              <i class="ti ti-edit"></i>
                           </a>
                        <?php endif; ?>
                     </td>
                  <?php endif; ?>
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
