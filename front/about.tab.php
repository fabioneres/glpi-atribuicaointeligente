<?php
/**
 * Aba Sobre.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
?>

<div class="m-3">
   <div class="card">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-info-circle me-2"></i>
            <?php echo __('Sobre a Atribuição Inteligente', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <div class="d-flex align-items-start gap-3">
            <div class="flex-shrink-0 text-center">
               <?php
               echo Html::image(
                  Plugin::getWebDir('atribuicaointeligente') . '/pics/icon.png',
                  [
                     'alt'   => 'Logo Atribuicao Inteligente',
                     'style' => 'width:72px;height:72px;object-fit:contain;',
                  ]
               );
               ?>
            </div>
            <div class="flex-grow-1">
               <p>
                  <strong><?php echo __('Autor deste fork:', 'atribuicaointeligente'); ?></strong>
                  Fabio Neres
               </p>
               <p>
                  <?php echo __('Plugin standalone para GLPI baseado no módulo SmartAssign do NexTool, mantendo a lógica de distribuição por balanceamento ou rodízio e adicionando indisponibilidade e escala de atendimento de técnicos.', 'atribuicaointeligente'); ?>
               </p>
               <p>
                  <strong><?php echo __('Referência original:', 'atribuicaointeligente'); ?></strong>
                  NexTool Solutions / SmartAssign, por Richard Loureiro / RPGMais.
               </p>
               <p>
                  <strong><?php echo __('Compatibilidade alvo:', 'atribuicaointeligente'); ?></strong>
                  GLPI 10.0.25.
               </p>
               <p class="text-muted mb-0">
                  <?php echo __('Este fork usa tabelas próprias e não altera o core nem tabelas nativas do GLPI.', 'atribuicaointeligente'); ?>
               </p>
            </div>
         </div>
      </div>
   </div>
</div>
