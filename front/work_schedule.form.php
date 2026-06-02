<?php
/**
 * Formulario de escala de atendimento.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
}

if (!defined('PLUGIN_ATRIBUICAOINTELIGENTE_DIR')) {
   define('PLUGIN_ATRIBUICAOINTELIGENTE_DIR', dirname(__DIR__));
}

if (!class_exists('PluginAtribuicaointeligenteConfig')) {
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/config.class.php';
}
if (!class_exists('PluginAtribuicaointeligenteProfile')) {
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/profile.class.php';
}
if (!class_exists('PluginAtribuicaointeligenteTechnicianUnavailability')) {
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/technicianunavailability.class.php';
}
if (!class_exists('PluginAtribuicaointeligenteTechnicianWorkSchedule')) {
   require_once PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/inc/technicianworkschedule.class.php';
}

global $DB;

$table = PluginAtribuicaointeligenteConfig::getWorkSchedulesTable();
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$item = new PluginAtribuicaointeligenteTechnicianWorkSchedule();
$canCreate = PluginAtribuicaointeligenteConfig::canCreateWorkSchedule();
$canUpdate = PluginAtribuicaointeligenteConfig::canUpdateWorkSchedule();
$canDelete = PluginAtribuicaointeligenteConfig::canDeleteWorkSchedule();
$backUrl = PluginAtribuicaointeligenteConfig::getFormURL(true) . '?forcetab=PluginAtribuicaointeligenteConfig$4';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   if ($id > 0 && !$canUpdate) {
      Html::displayRightError();
   }

   if ($id <= 0 && !$canCreate) {
      Html::displayRightError();
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$canCreate && !$canUpdate && !$canDelete) {
   Html::displayRightError();
}

if (!function_exists('plugin_atribuicaointeligente_schedule_date')) {
   function plugin_atribuicaointeligente_schedule_date($value) {
      $value = trim((string) $value);
      if ($value === '') {
         return null;
      }

      try {
         return (new DateTimeImmutable($value))->format('Y-m-d');
      } catch (Throwable $e) {
         return null;
      }
   }
}

if (!function_exists('plugin_atribuicaointeligente_schedule_time')) {
   function plugin_atribuicaointeligente_schedule_time($value) {
      $value = trim((string) $value);
      if ($value === '') {
         return null;
      }

      if (preg_match('/^\d{2}:\d{2}$/', $value)) {
         return $value . ':00';
      }

      if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
         return $value;
      }

      return null;
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   if (!$DB->tableExists($table)) {
      Session::addMessageAfterRedirect(
         __('Tabela de escala de atendimento não encontrada. Atualize o plugin.', 'atribuicaointeligente'),
         false,
         ERROR
      );
      Html::redirect($backUrl);
   }

   $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
   $current = null;
   if ($id > 0) {
      $iterator = $DB->request([
         'FROM'  => $table,
         'WHERE' => ['id' => $id],
         'LIMIT' => 1,
      ]);
      $current = $iterator->current();

      if (!$current) {
         Session::addMessageAfterRedirect(__('Escala não encontrada.', 'atribuicaointeligente'), false, ERROR);
         Html::redirect($backUrl);
      }

      if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) ($current['entities_id'] ?? 0))) {
         Html::displayRightError();
      }
   }

   if (isset($_POST['delete']) && $id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanDeleteWorkSchedule();
      $DB->delete($table, ['id' => $id]);
      Session::addMessageAfterRedirect(__('Escala excluída.', 'atribuicaointeligente'), false, INFO);
      Html::redirect($backUrl);
   }

   if (isset($_POST['toggle']) && $id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanUpdateWorkSchedule();
      $newState = isset($_POST['is_active']) && (int) $_POST['is_active'] === 1 ? 0 : 1;
      $DB->update($table, [
         'is_active' => $newState,
         'date_mod'  => date('Y-m-d H:i:s'),
      ], ['id' => $id]);
      Session::addMessageAfterRedirect(__('Status atualizado.', 'atribuicaointeligente'), false, INFO);
      Html::redirect($backUrl);
   }

   $weekdays = PluginAtribuicaointeligenteTechnicianWorkSchedule::normalizeWeekdays($_POST['weekdays'] ?? []);
   $dateStart = plugin_atribuicaointeligente_schedule_date($_POST['date_start'] ?? null);
   $dateEnd = plugin_atribuicaointeligente_schedule_date($_POST['date_end'] ?? null);
   $timeStart = plugin_atribuicaointeligente_schedule_time($_POST['time_start'] ?? null);
   $timeEnd = plugin_atribuicaointeligente_schedule_time($_POST['time_end'] ?? null);

   $input = [
      'users_id'    => isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0,
      'entities_id' => max(0, isset($_POST['entities_id']) ? (int) $_POST['entities_id'] : 0),
      'weekdays'    => PluginAtribuicaointeligenteTechnicianWorkSchedule::serializeWeekdays($weekdays),
      'time_start'  => $timeStart,
      'time_end'    => $timeEnd,
      'date_start'  => $dateStart,
      'date_end'    => $dateEnd,
      'comment'     => trim((string) ($_POST['comment'] ?? '')),
      'is_active'   => isset($_POST['is_active']) ? 1 : 0,
      'date_mod'    => date('Y-m-d H:i:s'),
   ];

   if ($id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanUpdateWorkSchedule();
   } else {
      PluginAtribuicaointeligenteConfig::assertCanCreateWorkSchedule();
   }

   $errors = [];
   if ($input['users_id'] <= 0) {
      $errors[] = __('Selecione um técnico.', 'atribuicaointeligente');
   }
   if (empty($weekdays)) {
      $errors[] = __('Selecione pelo menos um dia da semana.', 'atribuicaointeligente');
   }
   if ($dateStart !== null && $dateEnd !== null && $dateStart > $dateEnd) {
      $errors[] = __('A data inicial não pode ser maior que a data final.', 'atribuicaointeligente');
   }
   if (($timeStart === null && $timeEnd !== null) || ($timeStart !== null && $timeEnd === null)) {
      $errors[] = __('Informe horário inicial e final, ou deixe ambos vazios para dia inteiro.', 'atribuicaointeligente');
   }
   if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) $input['entities_id'])) {
      $errors[] = __('Voce nao tem acesso a entidade selecionada.', 'atribuicaointeligente');
   }

   if (!empty($errors)) {
      foreach ($errors as $error) {
         Session::addMessageAfterRedirect($error, false, ERROR);
      }
      Html::redirect($id > 0 ? PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true) . '?id=' . $id : PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true));
   }

   try {
      if ($id > 0) {
         if (!$DB->update($table, $input, ['id' => $id])) {
            throw new RuntimeException($DB->error());
         }
         Session::addMessageAfterRedirect(__('Escala atualizada.', 'atribuicaointeligente'), false, INFO);
      } else {
         $input['date_creation'] = date('Y-m-d H:i:s');
         if (!$DB->insert($table, $input)) {
            throw new RuntimeException($DB->error());
         }
         Session::addMessageAfterRedirect(__('Escala adicionada.', 'atribuicaointeligente'), false, INFO);
      }
      Html::redirect($backUrl);
   } catch (Throwable $e) {
      Toolbox::logInFile('plugin_atribuicaointeligente', 'Falha ao gravar escala de atendimento: ' . $e->getMessage() . PHP_EOL);
      Session::addMessageAfterRedirect(
         __('Erro ao gravar escala de atendimento. Verifique o log do plugin.', 'atribuicaointeligente'),
         false,
         ERROR
      );
      Html::redirect($id > 0 ? PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true) . '?id=' . $id : PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true));
   }
}

$fields = [
   'users_id'    => 0,
   'entities_id' => 0,
   'weekdays'    => '',
   'time_start'  => '',
   'time_end'    => '',
   'date_start'  => '',
   'date_end'    => '',
   'comment'     => '',
   'is_active'   => 1,
];

if ($id > 0) {
   if (!$item->getFromDB($id)) {
      Session::addMessageAfterRedirect(__('Escala não encontrada.', 'atribuicaointeligente'), false, ERROR);
      Html::redirect($backUrl);
   }

   if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) ($item->fields['entities_id'] ?? 0))) {
      Html::displayRightError();
   }

   $fields = array_merge($fields, $item->fields);
}

$selectedWeekdays = PluginAtribuicaointeligenteTechnicianWorkSchedule::normalizeWeekdays($fields['weekdays']);

Html::header(
   PluginAtribuicaointeligenteTechnicianWorkSchedule::getTypeName(1),
   $_SERVER['PHP_SELF'],
   'plugins',
   PluginAtribuicaointeligenteConfig::class
);
?>

<div class="m-3">
   <form method="post" action="<?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianWorkSchedule::getFormURL(true), ENT_QUOTES, 'UTF-8'); ?>" class="card">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-calendar-time me-2"></i>
            <?php echo $id > 0 ? __('Editar escala de atendimento', 'atribuicaointeligente') : __('Adicionar escala de atendimento', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken(true)]); ?>
         <?php echo Html::hidden('id', ['value' => $id]); ?>

         <div class="row g-3">
            <div class="col-12 col-lg-6">
               <label class="form-label"><?php echo __('Técnico', 'atribuicaointeligente'); ?></label>
               <?php
               User::dropdown([
                  'name'  => 'users_id',
                  'value' => (int) $fields['users_id'],
                  'right' => 'all',
                  'width' => '100%',
               ]);
               ?>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label"><?php echo __('Entidade', 'atribuicaointeligente'); ?></label>
               <?php
               Dropdown::show('Entity', [
                  'name'                => 'entities_id',
                  'value'               => (int) $fields['entities_id'],
                  'display_emptychoice' => false,
                  'width'               => '100%',
               ]);
               ?>
               <div class="form-text"><?php echo __('Use a entidade raiz/global quando a escala valer para todos os chamados.', 'atribuicaointeligente'); ?></div>
            </div>

            <div class="col-12">
               <label class="form-label"><?php echo __('Dias da semana', 'atribuicaointeligente'); ?></label>
               <div class="d-flex flex-wrap gap-3">
                  <?php foreach (PluginAtribuicaointeligenteTechnicianWorkSchedule::getWeekdays() as $weekday => $label): ?>
                     <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="weekday_<?php echo (int) $weekday; ?>" name="weekdays[]" value="<?php echo (int) $weekday; ?>" <?php echo in_array((int) $weekday, $selectedWeekdays, true) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="weekday_<?php echo (int) $weekday; ?>">
                           <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                     </div>
                  <?php endforeach; ?>
               </div>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="time_start"><?php echo __('Horário inicial', 'atribuicaointeligente'); ?></label>
               <input type="time" class="form-control" id="time_start" name="time_start" value="<?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianWorkSchedule::formatTime($fields['time_start']), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="time_end"><?php echo __('Horário final', 'atribuicaointeligente'); ?></label>
               <input type="time" class="form-control" id="time_end" name="time_end" value="<?php echo htmlspecialchars(PluginAtribuicaointeligenteTechnicianWorkSchedule::formatTime($fields['time_end']), ENT_QUOTES, 'UTF-8'); ?>">
               <div class="form-text"><?php echo __('Deixe os dois horários vazios para considerar o dia inteiro.', 'atribuicaointeligente'); ?></div>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="date_start"><?php echo __('Validade inicial', 'atribuicaointeligente'); ?></label>
               <input type="date" class="form-control" id="date_start" name="date_start" value="<?php echo htmlspecialchars((string) ($fields['date_start'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="date_end"><?php echo __('Validade final', 'atribuicaointeligente'); ?></label>
               <input type="date" class="form-control" id="date_end" name="date_end" value="<?php echo htmlspecialchars((string) ($fields['date_end'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12">
               <label class="form-label" for="comment"><?php echo __('Observação', 'atribuicaointeligente'); ?></label>
               <textarea class="form-control" id="comment" name="comment" rows="4"><?php echo htmlspecialchars((string) $fields['comment'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="col-12">
               <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (int) $fields['is_active'] === 1 ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="is_active"><?php echo __('Ativo', 'atribuicaointeligente'); ?></label>
               </div>
            </div>
         </div>
      </div>
      <div class="card-footer d-flex flex-wrap gap-2 justify-content-between">
         <div class="d-flex flex-wrap gap-2">
            <button type="submit" name="save" class="btn btn-primary">
               <i class="ti ti-device-floppy me-1"></i>
               <?php echo __('Salvar', 'atribuicaointeligente'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">
               <?php echo __('Voltar', 'atribuicaointeligente'); ?>
            </a>
         </div>

         <?php if ($id > 0 && ($canUpdate || $canDelete)): ?>
            <div class="d-flex flex-wrap gap-2">
               <?php if ($canUpdate): ?>
                  <button type="submit" name="toggle" class="btn btn-outline-warning">
                     <i class="ti ti-power me-1"></i>
                     <?php echo (int) $fields['is_active'] === 1 ? __('Desativar', 'atribuicaointeligente') : __('Ativar', 'atribuicaointeligente'); ?>
                  </button>
               <?php endif; ?>
               <?php if ($canDelete): ?>
                  <button type="submit" name="delete" class="btn btn-outline-danger" onclick="return confirm('<?php echo htmlspecialchars(__('Excluir esta escala?', 'atribuicaointeligente'), ENT_QUOTES, 'UTF-8'); ?>');">
                     <i class="ti ti-trash me-1"></i>
                     <?php echo __('Excluir', 'atribuicaointeligente'); ?>
                  </button>
               <?php endif; ?>
            </div>
         <?php endif; ?>
      </div>
   </form>
</div>

<?php
Html::footer();
