<?php
/**
 * Formulario de indisponibilidade.
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

global $DB;

$table = PluginAtribuicaointeligenteConfig::getUnavailabilitiesTable();
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$item = new PluginAtribuicaointeligenteTechnicianUnavailability();
$canCreate = PluginAtribuicaointeligenteConfig::canCreateUnavailability();
$canUpdate = PluginAtribuicaointeligenteConfig::canUpdateUnavailability();
$canDelete = PluginAtribuicaointeligenteConfig::canDeleteUnavailability();

Toolbox::logInFile('plugin_atribuicaointeligente', 'FORM indisponibilidade acessado: ' . json_encode([
   'method'              => $_SERVER['REQUEST_METHOD'] ?? '',
   'uri'                 => $_SERVER['REQUEST_URI'] ?? '',
   'user_id'             => Session::getLoginUserID(),
   'profile_id'          => $_SESSION['glpiactiveprofile']['id'] ?? null,
   'plugin_right_value'  => $_SESSION['glpiactiveprofile'][PluginAtribuicaointeligenteConfig::RIGHT_CONFIG] ?? null,
   'can_create'          => $canCreate ? 1 : 0,
   'can_update'          => $canUpdate ? 1 : 0,
   'can_delete'          => $canDelete ? 1 : 0,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   if ($id > 0 && !$canUpdate) {
      Toolbox::logInFile('plugin_atribuicaointeligente', 'ACESSO NEGADO ao formulario de edicao de indisponibilidade.' . PHP_EOL);
      Html::displayRightError();
   }

   if ($id <= 0 && !$canCreate) {
      Toolbox::logInFile('plugin_atribuicaointeligente', 'ACESSO NEGADO ao formulario de criacao de indisponibilidade.' . PHP_EOL);
      Html::displayRightError();
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$canCreate && !$canUpdate && !$canDelete) {
   Toolbox::logInFile('plugin_atribuicaointeligente', 'ACESSO NEGADO ao POST de indisponibilidade.' . PHP_EOL);
   Html::displayRightError();
}

if (!function_exists('plugin_atribuicaointeligente_normalize_datetime')) {
   function plugin_atribuicaointeligente_normalize_datetime($value, bool $endOfDay = false) {
      $value = trim((string) $value);
      if ($value === '') {
         return null;
      }

      $value = str_replace('T', ' ', $value);
      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
         return $value . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
      }
      if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
         return $value . ':00';
      }
      return $value;
   }
}

if (!function_exists('plugin_atribuicaointeligente_datetime_value')) {
   function plugin_atribuicaointeligente_datetime_value($value) {
      if (empty($value)) {
         return '';
      }
      return str_replace(' ', 'T', substr((string) $value, 0, 16));
   }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   Session::checkCSRF($_POST);

   if (!$DB->tableExists($table)) {
      Session::addMessageAfterRedirect(
         __('Tabela de indisponibilidades não encontrada. Reinstale ou atualize o plugin.', 'atribuicaointeligente'),
         false,
         ERROR
      );
      Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
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
         Session::addMessageAfterRedirect(__('Indisponibilidade nao encontrada.', 'atribuicaointeligente'), false, ERROR);
         Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
      }

      if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) ($current['entities_id'] ?? 0))) {
         Html::displayRightError();
      }
   }

   if (isset($_POST['delete']) && $id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanDeleteUnavailability();
      $DB->delete($table, ['id' => $id]);
      Session::addMessageAfterRedirect(__('Indisponibilidade excluída.', 'atribuicaointeligente'), false, INFO);
      Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
   }

   if (isset($_POST['toggle']) && $id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanUpdateUnavailability();
      $newState = isset($_POST['is_active']) && (int) $_POST['is_active'] === 1 ? 0 : 1;
      $DB->update($table, [
         'is_active' => $newState,
         'date_mod'  => date('Y-m-d H:i:s'),
      ], ['id' => $id]);
      Session::addMessageAfterRedirect(__('Status atualizado.', 'atribuicaointeligente'), false, INFO);
      Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
   }

   $type = (string) ($_POST['type'] ?? 'temporary');
   $type = array_key_exists($type, PluginAtribuicaointeligenteTechnicianUnavailability::getTypes()) ? $type : 'temporary';
   $dateStart = plugin_atribuicaointeligente_normalize_datetime($_POST['date_start'] ?? null);
   $dateEnd = plugin_atribuicaointeligente_normalize_datetime($_POST['date_end'] ?? null, true);

   if ($type === 'specific_date' && $dateStart !== null && $dateEnd === null) {
      $dateEnd = plugin_atribuicaointeligente_normalize_datetime(substr($dateStart, 0, 10), true);
   }

   $input = [
      'users_id'    => isset($_POST['users_id']) ? (int) $_POST['users_id'] : 0,
      'entities_id' => max(0, isset($_POST['entities_id']) ? (int) $_POST['entities_id'] : 0),
      'type'        => $type,
      'date_start'  => $dateStart,
      'date_end'    => $dateEnd,
      'weekday'     => ($_POST['weekday'] ?? '') === '' ? null : (int) $_POST['weekday'],
      'comment'     => trim((string) ($_POST['comment'] ?? '')),
      'is_active'   => isset($_POST['is_active']) ? 1 : 0,
      'date_mod'    => date('Y-m-d H:i:s'),
   ];

   if ($id > 0) {
      PluginAtribuicaointeligenteConfig::assertCanUpdateUnavailability();
   } else {
      PluginAtribuicaointeligenteConfig::assertCanCreateUnavailability();
   }

   Toolbox::logInFile('plugin_atribuicaointeligente', 'POST indisponibilidade recebido: ' . json_encode([
      'id'          => $id,
      'users_id'    => $input['users_id'],
      'entities_id' => $input['entities_id'],
      'type'        => $input['type'],
      'date_start'  => $input['date_start'],
      'date_end'    => $input['date_end'],
      'weekday'     => $input['weekday'],
      'is_active'   => $input['is_active'],
      'profile_id'  => $_SESSION['glpiactiveprofile']['id'] ?? null,
      'can_create'  => $canCreate ? 1 : 0,
      'can_update'  => $canUpdate ? 1 : 0,
      'can_delete'  => $canDelete ? 1 : 0,
   ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

   $errors = [];
   if ($input['users_id'] <= 0) {
      $errors[] = __('Selecione um técnico.', 'atribuicaointeligente');
   }
   if (in_array($type, ['vacation', 'temporary'], true) && ($dateStart === null || $dateEnd === null)) {
      $errors[] = __('Informe data inicial e final para este tipo de indisponibilidade.', 'atribuicaointeligente');
   }
   if ($type === 'specific_date' && $dateStart === null) {
      $errors[] = __('Informe a data da ausência.', 'atribuicaointeligente');
   }
   if ($type === 'weekly' && $input['weekday'] === null) {
      $errors[] = __('Informe o dia da semana.', 'atribuicaointeligente');
   }
   if ($dateStart !== null && $dateEnd !== null && strtotime($dateStart) > strtotime($dateEnd)) {
      $errors[] = __('A data inicial não pode ser maior que a data final.', 'atribuicaointeligente');
   }

   if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) $input['entities_id'])) {
      $errors[] = __('Voce nao tem acesso a entidade selecionada.', 'atribuicaointeligente');
   }

   if (!empty($errors)) {
      foreach ($errors as $error) {
         Session::addMessageAfterRedirect($error, false, ERROR);
      }
      $url = Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php';
      Html::redirect($id > 0 ? $url . '?id=' . $id : $url);
   }

   try {
      if ($id > 0) {
         if (!$DB->update($table, $input, ['id' => $id])) {
            throw new RuntimeException($DB->error());
         }
         Toolbox::logInFile('plugin_atribuicaointeligente', 'Indisponibilidade atualizada: id=' . $id . PHP_EOL);
         Session::addMessageAfterRedirect(__('Indisponibilidade atualizada.', 'atribuicaointeligente'), false, INFO);
      } else {
         $input['date_creation'] = date('Y-m-d H:i:s');
         if (!$DB->insert($table, $input)) {
            throw new RuntimeException($DB->error());
         }
         Toolbox::logInFile('plugin_atribuicaointeligente', 'Indisponibilidade inserida com sucesso.' . PHP_EOL);
         Session::addMessageAfterRedirect(__('Indisponibilidade adicionada.', 'atribuicaointeligente'), false, INFO);
      }
      Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
   } catch (Throwable $e) {
      Toolbox::logInFile('plugin_atribuicaointeligente', 'Falha ao gravar indisponibilidade: ' . $e->getMessage() . PHP_EOL);
      Session::addMessageAfterRedirect(
         __('Erro ao gravar indisponibilidade. Verifique o log do plugin.', 'atribuicaointeligente'),
         false,
         ERROR
      );
      $url = Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php';
      Html::redirect($id > 0 ? $url . '?id=' . $id : $url);
   }
}

$fields = [
   'users_id'    => 0,
   'entities_id' => 0,
   'type'        => 'temporary',
   'date_start'  => '',
   'date_end'    => '',
   'weekday'     => '',
   'comment'     => '',
   'is_active'   => 1,
];

if ($id > 0) {
   if (!$item->getFromDB($id)) {
      Session::addMessageAfterRedirect(__('Indisponibilidade nao encontrada.', 'atribuicaointeligente'), false, ERROR);
      Html::redirect(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3');
   }

   if (!PluginAtribuicaointeligenteConfig::canUseEntity((int) ($item->fields['entities_id'] ?? 0))) {
      Html::displayRightError();
   }

   $fields = array_merge($fields, $item->fields);
}

Html::header(
   PluginAtribuicaointeligenteTechnicianUnavailability::getTypeName(1),
   $_SERVER['PHP_SELF'],
   'plugins',
   PluginAtribuicaointeligenteConfig::class
);
?>

<div class="m-3">
   <form method="post" action="<?php echo htmlspecialchars(Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php', ENT_QUOTES, 'UTF-8'); ?>" class="card">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-user-off me-2"></i>
            <?php echo $id > 0 ? __('Editar indisponibilidade', 'atribuicaointeligente') : __('Adicionar indisponibilidade', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
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
               <div class="form-text"><?php echo __('Use a entidade raiz/global quando a indisponibilidade valer para todos os chamados.', 'atribuicaointeligente'); ?></div>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="type"><?php echo __('Tipo', 'atribuicaointeligente'); ?></label>
               <?php Dropdown::showFromArray('type', PluginAtribuicaointeligenteTechnicianUnavailability::getTypes(), [
                  'value' => (string) $fields['type'],
                  'width' => '100%',
               ]); ?>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="weekday"><?php echo __('Dia da semana', 'atribuicaointeligente'); ?></label>
               <?php Dropdown::showFromArray('weekday', PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdays(), [
                  'value'               => $fields['weekday'] === null ? '' : (string) $fields['weekday'],
                  'display_emptychoice' => true,
                  'width'               => '100%',
               ]); ?>
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="date_start"><?php echo __('Data inicial', 'atribuicaointeligente'); ?></label>
               <input type="datetime-local" class="form-control" id="date_start" name="date_start" value="<?php echo htmlspecialchars(plugin_atribuicaointeligente_datetime_value($fields['date_start']), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-lg-6">
               <label class="form-label" for="date_end"><?php echo __('Data final', 'atribuicaointeligente'); ?></label>
               <input type="datetime-local" class="form-control" id="date_end" name="date_end" value="<?php echo htmlspecialchars(plugin_atribuicaointeligente_datetime_value($fields['date_end']), ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12">
               <label class="form-label" for="comment"><?php echo __('Observação / justificativa', 'atribuicaointeligente'); ?></label>
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
            <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(PluginAtribuicaointeligenteConfig::getFormURL(false) . '?forcetab=PluginAtribuicaointeligenteConfig$3', ENT_QUOTES, 'UTF-8'); ?>">
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
                  <button type="submit" name="delete" class="btn btn-outline-danger" onclick="return confirm('<?php echo htmlspecialchars(__('Excluir esta indisponibilidade?', 'atribuicaointeligente'), ENT_QUOTES, 'UTF-8'); ?>');">
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
