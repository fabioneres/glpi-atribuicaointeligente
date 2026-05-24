<?php
/**
 * Aba Configuracoes.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

$canEdit = PluginAtribuicaointeligenteConfig::canUpdateConfig();
$config = PluginAtribuicaointeligenteConfig::getConfigValues();
$action = Plugin::getWebDir('atribuicaointeligente') . '/front/config.save.php';
?>

<div class="m-3" id="atribuicaointeligente-config-tab">
   <div class="card">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-user-check me-2"></i>
            <?php echo __('Configuração - Atribuição Inteligente', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <form method="post" action="<?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
            <?php echo Html::hidden('forcetab', ['value' => 'PluginAtribuicaointeligenteConfig$1']); ?>

            <div class="row g-4">
               <div class="col-12 col-xl-6">
                  <label class="form-label fw-bold">
                     <i class="ti ti-users me-1"></i>
                     <?php echo __('Atribuir grupo encarregado', 'atribuicaointeligente'); ?>
                  </label>
                  <div class="form-check form-switch">
                     <input class="form-check-input" type="checkbox" id="auto_assign_group" name="auto_assign_group" value="1" <?php echo !empty($config['auto_assign_group']) ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="auto_assign_group">
                        <?php echo __('Atribuir o grupo da categoria junto com o técnico', 'atribuicaointeligente'); ?>
                     </label>
                  </div>
               </div>

               <div class="col-12 col-xl-6">
                  <label class="form-label fw-bold">
                     <i class="ti ti-user-off me-1"></i>
                     <?php echo __('Excluir gerentes', 'atribuicaointeligente'); ?>
                  </label>
                  <div class="form-check form-switch">
                     <input class="form-check-input" type="checkbox" id="exclude_managers" name="exclude_managers" value="1" <?php echo !empty($config['exclude_managers']) ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="exclude_managers">
                        <?php echo __('Ignorar usuários marcados como gerente no grupo', 'atribuicaointeligente'); ?>
                     </label>
                  </div>
               </div>

               <div class="col-12 col-xl-6">
                  <label class="form-label fw-bold">
                     <i class="ti ti-filter me-1"></i>
                     <?php echo __('Tipo de atribuição', 'atribuicaointeligente'); ?>
                  </label>
                  <div class="form-check">
                     <input class="form-check-input" type="radio" name="auto_assign_type" id="type_group" value="0" <?php echo (int) $config['auto_assign_type'] === 0 ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="type_group"><?php echo __('Por grupo', 'atribuicaointeligente'); ?></label>
                  </div>
                  <div class="form-check">
                     <input class="form-check-input" type="radio" name="auto_assign_type" id="type_category" value="1" <?php echo (int) $config['auto_assign_type'] === 1 ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="type_category"><?php echo __('Por categoria', 'atribuicaointeligente'); ?></label>
                  </div>
               </div>

               <div class="col-12 col-xl-6">
                  <label class="form-label fw-bold">
                     <i class="ti ti-refresh me-1"></i>
                     <?php echo __('Modo de distribuição', 'atribuicaointeligente'); ?>
                  </label>
                  <div class="form-check">
                     <input class="form-check-input" type="radio" name="auto_assign_mode" id="mode_balancing" value="0" <?php echo (int) $config['auto_assign_mode'] === 0 ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="mode_balancing"><?php echo __('Balanceamento', 'atribuicaointeligente'); ?></label>
                  </div>
                  <div class="form-check">
                     <input class="form-check-input" type="radio" name="auto_assign_mode" id="mode_rotation" value="1" <?php echo (int) $config['auto_assign_mode'] === 1 ? 'checked' : ''; ?> <?php echo $canEdit ? '' : 'disabled'; ?>>
                     <label class="form-check-label" for="mode_rotation"><?php echo __('Rodízio', 'atribuicaointeligente'); ?></label>
                  </div>
               </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center mt-4">
               <button type="submit" name="save" class="btn btn-primary" <?php echo $canEdit ? '' : 'disabled'; ?>>
                  <i class="ti ti-device-floppy me-1"></i>
                  <?php echo __('Salvar configurações', 'atribuicaointeligente'); ?>
               </button>
               <?php if (!$canEdit): ?>
                  <div class="alert alert-info mb-0 py-2">
                     <?php echo __('Você possui apenas permissão de visualização.', 'atribuicaointeligente'); ?>
                  </div>
               <?php endif; ?>
            </div>
         </form>
      </div>
   </div>
</div>
