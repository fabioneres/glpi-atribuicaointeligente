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

if (!function_exists('plugin_atribuicaointeligente_unavailability_escape')) {
   function plugin_atribuicaointeligente_unavailability_escape($value): string {
      return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_tab_url')) {
   function plugin_atribuicaointeligente_unavailability_tab_url(bool $embedded, string $tab, array $extra = []): string {
      $target = $embedded
         ? PluginAtribuicaointeligenteConfig::getFormURL(true)
         : $_SERVER['PHP_SELF'];

      $params = array_merge(['availability_tab' => $tab], $extra);
      if ($embedded) {
         $params['forcetab'] = 'PluginAtribuicaointeligenteConfig$3';
      }

      return $target . '?' . http_build_query($params);
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_form_url')) {
   function plugin_atribuicaointeligente_unavailability_form_url(string $type, string $returnTab, int $id = 0): string {
      $params = [
         'type'       => $type,
         'return_tab' => $returnTab,
      ];
      if ($id > 0) {
         $params['id'] = $id;
      }

      return Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php?' . http_build_query($params);
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_entity_name')) {
   function plugin_atribuicaointeligente_unavailability_entity_name($entitiesId): string {
      $entitiesId = (int) $entitiesId;
      if ($entitiesId === 0) {
         return __('Todas / global', 'atribuicaointeligente');
      }

      return html_entity_decode(Dropdown::getDropdownName('glpi_entities', $entitiesId), ENT_QUOTES, 'UTF-8');
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_status_badge')) {
   function plugin_atribuicaointeligente_unavailability_status_badge($isActive): string {
      if ((int) $isActive === 1) {
         return '<span class="badge bg-success text-white">' . plugin_atribuicaointeligente_unavailability_escape(__('Sim', 'atribuicaointeligente')) . '</span>';
      }

      return '<span class="badge bg-secondary text-white">' . plugin_atribuicaointeligente_unavailability_escape(__('Não', 'atribuicaointeligente')) . '</span>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_edit_button')) {
   function plugin_atribuicaointeligente_unavailability_edit_button(array $row, bool $canUpdate, string $returnTab): string {
      if (!$canUpdate || !PluginAtribuicaointeligenteConfig::canUseEntity((int) ($row['entities_id'] ?? 0))) {
         return '';
      }

      $url = plugin_atribuicaointeligente_unavailability_form_url((string) ($row['type'] ?? 'temporary'), $returnTab, (int) ($row['id'] ?? 0));
      return '<a class="btn btn-sm btn-outline-secondary" href="' . plugin_atribuicaointeligente_unavailability_escape($url) . '">'
         . '<i class="ti ti-edit"></i>'
         . '</a>';
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_date')) {
   function plugin_atribuicaointeligente_unavailability_date($value, bool $showTime = true): string {
      if (empty($value)) {
         return '-';
      }

      $timestamp = strtotime((string) $value);
      if ($timestamp !== false) {
         return date($showTime ? 'd-m-Y H:i:s' : 'd-m-Y', $timestamp);
      }

      return (string) $value;
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_period_text')) {
   function plugin_atribuicaointeligente_unavailability_period_text(array $row, bool $showTime = true): string {
      return plugin_atribuicaointeligente_unavailability_date($row['date_start'] ?? '', $showTime)
         . ' - '
         . plugin_atribuicaointeligente_unavailability_date($row['date_end'] ?? '', $showTime);
   }
}

if (!function_exists('plugin_atribuicaointeligente_unavailability_overlaps')) {
   function plugin_atribuicaointeligente_unavailability_overlaps(array $vacationRows): array {
      $activeRows = array_values(array_filter($vacationRows, static function($row) {
         return (int) ($row['is_active'] ?? 0) === 1
            && !empty($row['date_start'])
            && !empty($row['date_end'])
            && strtotime((string) $row['date_end']) >= strtotime(date('Y-m-d 00:00:00'));
      }));

      $overlaps = [];
      $count = count($activeRows);
      for ($i = 0; $i < $count; $i++) {
         for ($j = $i + 1; $j < $count; $j++) {
            $left = $activeRows[$i];
            $right = $activeRows[$j];
            if ((int) ($left['users_id'] ?? 0) === (int) ($right['users_id'] ?? 0)) {
               continue;
            }

            $start = max(strtotime((string) $left['date_start']), strtotime((string) $right['date_start']));
            $end = min(strtotime((string) $left['date_end']), strtotime((string) $right['date_end']));
            if ($start <= $end) {
               $overlaps[] = [
                  'users' => [
                     getUserName((int) ($left['users_id'] ?? 0)),
                     getUserName((int) ($right['users_id'] ?? 0)),
                  ],
                  'date_start' => date('d-m-Y', $start),
                  'date_end'   => date('d-m-Y', $end),
               ];
            }
         }
      }

      usort($overlaps, static function($a, $b) {
         return strcmp((string) $a['date_start'], (string) $b['date_start']);
      });

      return array_slice($overlaps, 0, 20);
   }
}

$embedded = !empty($_GET['embedded']);
$canCreate = PluginAtribuicaointeligenteConfig::canCreateUnavailability();
$canUpdate = PluginAtribuicaointeligenteConfig::canUpdateUnavailability();
$table = PluginAtribuicaointeligenteConfig::getUnavailabilitiesTable();
$maxVacationPeriodsPerYear = 3;
$maxVacationYears = 2;
$maxVacationPeriods = $maxVacationPeriodsPerYear * $maxVacationYears;
$allowedTabs = ['vacation', 'temporary', 'other'];
$currentTab = (string) ($_GET['availability_tab'] ?? 'vacation');
if (!in_array($currentTab, $allowedTabs, true)) {
   $currentTab = 'vacation';
}
$selectedVacationUser = max(0, (int) ($_GET['users_id'] ?? 0));

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
      'ORDER' => 'is_active DESC, date_start ASC, id ASC',
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

$vacationRows = array_values(array_filter($rows, static function($row) {
   return (string) ($row['type'] ?? '') === 'vacation';
}));
$temporaryRows = array_values(array_filter($rows, static function($row) {
   return (string) ($row['type'] ?? '') === 'temporary';
}));
$otherRows = array_values(array_filter($rows, static function($row) {
   return !in_array((string) ($row['type'] ?? ''), ['vacation', 'temporary'], true);
}));

$vacationsByUser = [];
foreach ($vacationRows as $row) {
   $usersId = (int) ($row['users_id'] ?? 0);
   if (!isset($vacationsByUser[$usersId])) {
      $vacationsByUser[$usersId] = [
         'users_id'        => $usersId,
         'rows'            => [],
         'years'           => [],
         'entities'        => [],
         'active_count'    => 0,
         'next_date_start' => null,
         'next_date_end'   => null,
      ];
   }

   $year = !empty($row['date_start']) ? substr((string) $row['date_start'], 0, 4) : '';
   if ($year !== ''
      && count($vacationsByUser[$usersId]['rows']) < $maxVacationPeriods
      && ((isset($vacationsByUser[$usersId]['years'][$year])
            && $vacationsByUser[$usersId]['years'][$year] < $maxVacationPeriodsPerYear)
         || (!isset($vacationsByUser[$usersId]['years'][$year])
            && count($vacationsByUser[$usersId]['years']) < $maxVacationYears))
   ) {
      $vacationsByUser[$usersId]['rows'][] = $row;
      $vacationsByUser[$usersId]['years'][$year] = ($vacationsByUser[$usersId]['years'][$year] ?? 0) + 1;
   }
   $vacationsByUser[$usersId]['entities'][(int) ($row['entities_id'] ?? 0)] = plugin_atribuicaointeligente_unavailability_entity_name($row['entities_id'] ?? 0);
   if ((int) ($row['is_active'] ?? 0) === 1) {
      $vacationsByUser[$usersId]['active_count']++;
   }

   if ((int) ($row['is_active'] ?? 0) === 1
      && !empty($row['date_start'])
      && !empty($row['date_end'])
      && strtotime((string) $row['date_end']) >= strtotime(date('Y-m-d 00:00:00'))
      && ($vacationsByUser[$usersId]['next_date_start'] === null
         || strcmp((string) $row['date_start'], (string) $vacationsByUser[$usersId]['next_date_start']) < 0)
   ) {
      $vacationsByUser[$usersId]['next_date_start'] = (string) $row['date_start'];
      $vacationsByUser[$usersId]['next_date_end'] = (string) $row['date_end'];
   }
}
uasort($vacationsByUser, static function($a, $b) {
   return strcasecmp(getUserName((int) $a['users_id']), getUserName((int) $b['users_id']));
});

$upcomingVacationRows = array_values(array_filter($vacationRows, static function($row) {
   $periodStart = strtotime((string) ($row['date_start'] ?? ''));
   $windowStart = strtotime(date('Y-m-d 00:00:00'));
   $windowEnd = strtotime(date('Y-m-01 00:00:00', strtotime('first day of +2 month')));

   return (int) ($row['is_active'] ?? 0) === 1
      && !empty($row['date_start'])
      && $periodStart !== false
      && $periodStart >= $windowStart
      && $periodStart < $windowEnd;
}));
usort($upcomingVacationRows, static function($a, $b) {
   return strcmp((string) ($a['date_start'] ?? ''), (string) ($b['date_start'] ?? ''));
});
$upcomingVacationRowsByUser = [];
$visibleUpcomingVacationRows = [];
foreach ($upcomingVacationRows as $row) {
   $usersId = (int) ($row['users_id'] ?? 0);
   $year = !empty($row['date_start']) ? substr((string) $row['date_start'], 0, 4) : '';
   if ($year === '') {
      continue;
   }

   if (!isset($upcomingVacationRowsByUser[$usersId])) {
      $upcomingVacationRowsByUser[$usersId] = [
         'total' => 0,
         'years' => [],
      ];
   }

   $userWindow = &$upcomingVacationRowsByUser[$usersId];
   if ($userWindow['total'] < $maxVacationPeriods
      && ((isset($userWindow['years'][$year]) && $userWindow['years'][$year] < $maxVacationPeriodsPerYear)
         || (!isset($userWindow['years'][$year]) && count($userWindow['years']) < $maxVacationYears))
   ) {
      $visibleUpcomingVacationRows[] = $row;
      $userWindow['total']++;
      $userWindow['years'][$year] = ($userWindow['years'][$year] ?? 0) + 1;
   }
   unset($userWindow);
}
$upcomingVacationRows = array_slice($visibleUpcomingVacationRows, 0, 20);
$overlapRows = plugin_atribuicaointeligente_unavailability_overlaps($visibleUpcomingVacationRows);

$tabs = [
   'vacation'  => __('Férias', 'atribuicaointeligente'),
   'temporary' => __('Ausência temporária', 'atribuicaointeligente'),
   'other'     => __('Outras ausências', 'atribuicaointeligente'),
];
?>

<div class="m-3">
   <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">
         <i class="ti ti-user-off me-2"></i>
         <?php echo __('Indisponibilidades', 'atribuicaointeligente'); ?>
      </h3>
      <?php if ($canCreate): ?>
         <?php
         $createType = $currentTab === 'other' ? 'specific_date' : $currentTab;
         ?>
         <a class="btn btn-primary" href="<?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_form_url($createType, $currentTab)); ?>">
            <i class="ti ti-plus me-1"></i>
            <?php echo __('Adicionar', 'atribuicaointeligente'); ?>
         </a>
      <?php endif; ?>
   </div>

   <ul class="nav nav-tabs mb-3">
      <?php foreach ($tabs as $tabKey => $tabLabel): ?>
         <li class="nav-item">
            <a class="nav-link <?php echo $currentTab === $tabKey ? 'active' : ''; ?>" href="<?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_tab_url($embedded, $tabKey)); ?>">
               <?php echo plugin_atribuicaointeligente_unavailability_escape($tabLabel); ?>
            </a>
         </li>
      <?php endforeach; ?>
   </ul>

   <?php if ($currentTab === 'vacation'): ?>
      <?php if ($selectedVacationUser <= 0): ?>
         <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
               <div class="card h-100">
                  <div class="card-header">
                     <h4 class="card-title mb-0"><?php echo __('Técnicos com férias cadastradas', 'atribuicaointeligente'); ?></h4>
                  </div>
                  <div class="table-responsive">
                     <table class="table table-striped table-hover mb-0">
                        <thead>
                           <tr>
                              <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
                              <th><?php echo __('Entidades', 'atribuicaointeligente'); ?></th>
                              <th class="text-end"><?php echo __('Períodos', 'atribuicaointeligente'); ?></th>
                              <th><?php echo __('Próximo período', 'atribuicaointeligente'); ?></th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php if (empty($vacationsByUser)): ?>
                              <tr>
                                 <td colspan="4" class="text-muted text-center"><?php echo __('Nenhuma férias cadastrada.', 'atribuicaointeligente'); ?></td>
                              </tr>
                           <?php endif; ?>
                           <?php foreach ($vacationsByUser as $group): ?>
                              <?php
                              $usersId = (int) $group['users_id'];
                              $periodCount = count($group['rows']);
                              $nextPeriod = $group['next_date_start'] !== null
                                 ? plugin_atribuicaointeligente_unavailability_period_text([
                                    'date_start' => $group['next_date_start'],
                                    'date_end'   => $group['next_date_end'],
                                 ], false)
                                 : '-';
                              ?>
                              <tr>
                                 <td>
                                    <a href="<?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_tab_url($embedded, 'vacation', ['users_id' => $usersId])); ?>">
                                       <?php echo plugin_atribuicaointeligente_unavailability_escape(getUserName($usersId)); ?>
                                    </a>
                                 </td>
                                 <td><?php echo plugin_atribuicaointeligente_unavailability_escape(implode(', ', $group['entities'])); ?></td>
                                 <td class="text-end"><?php echo (int) $periodCount; ?></td>
                                 <td><?php echo plugin_atribuicaointeligente_unavailability_escape($nextPeriod); ?></td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>

            <div class="col-12 col-xl-5">
               <div class="card h-100">
                  <div class="card-header">
                     <h4 class="card-title mb-0"><?php echo __('Próximas férias', 'atribuicaointeligente'); ?></h4>
                  </div>
                  <div class="table-responsive">
                     <table class="table table-hover mb-0">
                        <thead>
                           <tr>
                              <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
                              <th><?php echo __('Período', 'atribuicaointeligente'); ?></th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php if (empty($upcomingVacationRows)): ?>
                              <tr>
                                 <td colspan="2" class="text-muted text-center"><?php echo __('Nenhuma férias futura encontrada.', 'atribuicaointeligente'); ?></td>
                              </tr>
                           <?php endif; ?>
                           <?php foreach ($upcomingVacationRows as $row): ?>
                              <tr>
                                 <td><?php echo plugin_atribuicaointeligente_unavailability_escape(getUserName((int) $row['users_id'])); ?></td>
                                 <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_period_text($row, false)); ?></td>
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
               <h4 class="card-title mb-0"><?php echo __('Férias simultâneas', 'atribuicaointeligente'); ?></h4>
            </div>
            <div class="table-responsive">
               <table class="table table-striped table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Técnicos', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Sobreposição', 'atribuicaointeligente'); ?></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($overlapRows)): ?>
                        <tr>
                           <td colspan="2" class="text-muted text-center"><?php echo __('Nenhuma sobreposição de férias ativa ou futura encontrada.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php foreach ($overlapRows as $row): ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_escape(implode(' / ', $row['users'])); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_escape($row['date_start'] . ' - ' . $row['date_end']); ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      <?php endif; ?>

      <?php if ($selectedVacationUser > 0): ?>
         <?php $selectedRows = array_slice($vacationsByUser[$selectedVacationUser]['rows'] ?? [], 0, $maxVacationPeriods); ?>
         <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
               <h4 class="card-title mb-0">
                  <?php echo plugin_atribuicaointeligente_unavailability_escape(sprintf(__('Períodos de férias - %s', 'atribuicaointeligente'), getUserName($selectedVacationUser))); ?>
               </h4>
               <a class="btn btn-sm btn-outline-secondary" href="<?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_tab_url($embedded, 'vacation')); ?>">
                  <?php echo __('Voltar', 'atribuicaointeligente'); ?>
               </a>
            </div>
            <div class="table-responsive">
               <table class="table table-striped table-hover mb-0">
                  <thead>
                     <tr>
                        <th><?php echo __('Entidade', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Início', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Fim', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Observação', 'atribuicaointeligente'); ?></th>
                        <th><?php echo __('Ativo', 'atribuicaointeligente'); ?></th>
                        <?php if ($canUpdate): ?>
                           <th class="text-end"><?php echo __('Ações', 'atribuicaointeligente'); ?></th>
                        <?php endif; ?>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($selectedRows)): ?>
                        <tr>
                           <td colspan="<?php echo $canUpdate ? 6 : 5; ?>" class="text-muted text-center"><?php echo __('Nenhum período encontrado.', 'atribuicaointeligente'); ?></td>
                        </tr>
                     <?php endif; ?>
                     <?php foreach ($selectedRows as $row): ?>
                        <?php
                        $canUpdateRow = $canUpdate
                           && PluginAtribuicaointeligenteConfig::canUseEntity((int) ($row['entities_id'] ?? 0));
                        $startDate = plugin_atribuicaointeligente_unavailability_date($row['date_start'] ?? '', false);
                        $endDate = plugin_atribuicaointeligente_unavailability_date($row['date_end'] ?? '', false);
                        ?>
                        <tr>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_entity_name($row['entities_id'] ?? 0)); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_escape($startDate); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_escape($endDate); ?></td>
                           <td><?php echo nl2br(plugin_atribuicaointeligente_unavailability_escape($row['comment'] ?? '')); ?></td>
                           <td><?php echo plugin_atribuicaointeligente_unavailability_status_badge($row['is_active'] ?? 0); ?></td>
                           <?php if ($canUpdate): ?>
                              <td class="text-end text-nowrap"><?php echo plugin_atribuicaointeligente_unavailability_edit_button($row, $canUpdateRow, 'vacation'); ?></td>
                           <?php endif; ?>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      <?php endif; ?>
   <?php endif; ?>

   <?php if ($currentTab === 'temporary'): ?>
      <div class="table-responsive">
         <table class="table table-striped table-hover">
            <thead>
               <tr>
                  <th><?php echo __('Técnico', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Entidade', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Início', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Fim', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Observação', 'atribuicaointeligente'); ?></th>
                  <th><?php echo __('Ativo', 'atribuicaointeligente'); ?></th>
                  <?php if ($canUpdate): ?>
                     <th class="text-end"><?php echo __('Ações', 'atribuicaointeligente'); ?></th>
                  <?php endif; ?>
               </tr>
            </thead>
            <tbody>
               <?php if (empty($temporaryRows)): ?>
                  <tr>
                     <td colspan="<?php echo $canUpdate ? 7 : 6; ?>" class="text-muted text-center"><?php echo __('Nenhuma ausência temporária cadastrada.', 'atribuicaointeligente'); ?></td>
                  </tr>
               <?php endif; ?>
               <?php foreach ($temporaryRows as $row): ?>
                  <tr>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(getUserName((int) $row['users_id'])); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_entity_name($row['entities_id'] ?? 0)); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_date($row['date_start'] ?? '')); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_date($row['date_end'] ?? '')); ?></td>
                     <td><?php echo nl2br(plugin_atribuicaointeligente_unavailability_escape($row['comment'] ?? '')); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_status_badge($row['is_active'] ?? 0); ?></td>
                     <?php if ($canUpdate): ?>
                        <td class="text-end text-nowrap"><?php echo plugin_atribuicaointeligente_unavailability_edit_button($row, $canUpdate, 'temporary'); ?></td>
                     <?php endif; ?>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   <?php endif; ?>

   <?php if ($currentTab === 'other'): ?>
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
               <?php if (empty($otherRows)): ?>
                  <tr>
                     <td colspan="<?php echo $canUpdate ? 9 : 8; ?>" class="text-muted text-center"><?php echo __('Nenhuma outra ausência cadastrada.', 'atribuicaointeligente'); ?></td>
                  </tr>
               <?php endif; ?>
               <?php foreach ($otherRows as $row): ?>
                  <tr>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(getUserName((int) $row['users_id'])); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_entity_name($row['entities_id'] ?? 0)); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(PluginAtribuicaointeligenteTechnicianUnavailability::getTypeLabel((string) $row['type'])); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_date($row['date_start'] ?? '')); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(plugin_atribuicaointeligente_unavailability_date($row['date_end'] ?? '')); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_escape(PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdayLabel($row['weekday'] ?? '')); ?></td>
                     <td><?php echo nl2br(plugin_atribuicaointeligente_unavailability_escape($row['comment'] ?? '')); ?></td>
                     <td><?php echo plugin_atribuicaointeligente_unavailability_status_badge($row['is_active'] ?? 0); ?></td>
                     <?php if ($canUpdate): ?>
                        <td class="text-end text-nowrap"><?php echo plugin_atribuicaointeligente_unavailability_edit_button($row, $canUpdate, 'other'); ?></td>
                     <?php endif; ?>
                  </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   <?php endif; ?>
</div>

<?php
if (!$embedded) {
   Html::footer();
}
