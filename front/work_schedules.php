<?php
/**
 * Listagem de escalas de atendimento.
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
$canCreate = PluginAtribuicaointeligenteConfig::canCreateWorkSchedule();
$canUpdate = PluginAtribuicaointeligenteConfig::canUpdateWorkSchedule();
$table = PluginAtribuicaointeligenteConfig::getWorkSchedulesTable();

if (!$embedded) {
   Html::header(
      PluginAtribuicaointeligenteTechnicianWorkSchedule::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      'plugins',
      PluginAtribuicaointeligenteConfig::class
   );
}

$rows = [];
if ($DB->tableExists($table)) {
   $criteria = [
      'FROM'  => $table,
      'ORDER' => 'is_active DESC, users_id ASC, entities_id ASC, id DESC',
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

$formUrl = PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true);
?>

<div class="m-3">
   <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">
         <i class="ti ti-calendar-time me-2"></i>
         <?php echo __('Escala de atendimento', 'atribuicaointeligente'); ?>
      </h3>
      <?php if ($canCreate): ?>
         <a class="btn btn-primary" href="<?php echo htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="ti ti-plus me-1"></i>
            <?php echo __('Adicionar', 'atribuicaointeligente'); ?>
         </a>
      <?php endif; ?>
   </div>

   <div class="alert alert-info">
      <?php echo __('Se um técnico não possuir escala ativa, ele continua disponível pelo comportamento padrão. Quando houver escala ativa, ele só receberá chamados dentro dos dias e horários cadastrados.', 'atribuicaointeligente'); ?>
   </div>

   <div class="table-responsive">
      <table class="table table-striped table-hover">
         <thead>
            <tr>
               <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Entidade', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Dias', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Horário', 'atribuicaointeligente'); ?></th>
               <th><?php echo __('Validade', 'atribuicaointeligente'); ?></th>
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
                  <td colspan="<?php echo $canUpdate ? 8 : 7; ?>" class="text-muted text-center">
                     <?php echo __('Nenhuma escala cadastrada.', 'atribuicaointeligente'); ?>
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
               $timeStart = PluginAtribuicaointeligenteTechnicianWorkSchedule::formatTime($row['time_start'] ?? '');
               $timeEnd = PluginAtribuicaointeligenteTechnicianWorkSchedule::formatTime($row['time_end'] ?? '');
               $timeLabel = ($timeStart !== '' || $timeEnd !== '')
                  ? trim($timeStart . ' - ' . $timeEnd, ' -')
                  : __('Dia inteiro', 'atribuicaointeligente');
               $validity = trim((string) ($row['date_start'] ?? '') . ' - ' . (string) ($row['date_end'] ?? ''), ' -');
               ?>
               <tr>
                  <td><?php echo htmlspecialchars(getUserName((int) $row['users_id']), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(html_entity_decode($entityName, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianWorkSchedule::getWeekdaysLabel($row['weekdays'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($validity !== '' ? $validity : __('Sem limite', 'atribuicaointeligente'), ENT_QUOTES, 'UTF-8'); ?></td>
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
