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
            <?php echo __('Sobre a Atribuicao Inteligente', 'atribuicaointeligente'); ?>
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
                  <strong><?php echo __('Autor:', 'atribuicaointeligente'); ?></strong>
                  Fabio Neres
               </p>
               <p>
                  <?php echo __('A Atribuicao Inteligente distribui chamados automaticamente para tecnicos dos grupos configurados, respeitando categorias, indisponibilidades, escalas e calendario da entidade quando habilitado.', 'atribuicaointeligente'); ?>
               </p>
               <p>
                  <?php echo __('O plugin evolui uma base tecnica de atribuicao automatica e hoje possui recursos proprios para controle operacional da distribuicao de chamados.', 'atribuicaointeligente'); ?>
               </p>
               <p>
                  <strong><?php echo __('Compatibilidade alvo:', 'atribuicaointeligente'); ?></strong>
                  GLPI 10.0.25.
               </p>
               <p class="text-muted mb-0">
                  <?php echo __('Usa tabelas proprias e nao altera o core nem tabelas nativas do GLPI.', 'atribuicaointeligente'); ?>
               </p>
            </div>
         </div>
      </div>
   </div>

   <div class="card mt-3">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-list-check me-2"></i>
            <?php echo __('O que o plugin faz', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <ul class="mb-0">
            <li><?php echo __('Atribui automaticamente chamados para tecnicos do grupo vinculado a categoria ITIL.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Permite escolher entre balanceamento por menor carga de chamados ou rodizio entre tecnicos.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Ignora tecnicos indisponiveis por ferias, ausencia em data especifica, ausencia temporaria ou recorrencia semanal.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Permite cadastrar escala de atendimento por tecnico, com dias da semana, horario e periodo de validade.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Opcionalmente respeita o calendario de atendimento da entidade do chamado.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Registra logs de decisao com chamado, grupo, tecnico escolhido, tecnicos ignorados e motivo.', 'atribuicaointeligente'); ?></li>
         </ul>
      </div>
   </div>

   <div class="card mt-3">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-settings me-2"></i>
            <?php echo __('Como configurar', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <ol class="mb-0">
            <li><?php echo __('Em Categorias, deixe ativo somente as categorias que devem receber atribuicao automatica.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Na propria categoria ITIL do GLPI, informe o grupo encarregado que sera usado como base para escolher o tecnico.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Em Configuracoes, escolha o modo de distribuicao: balanceamento ou rodizio.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Cadastre indisponibilidades dos tecnicos quando houver ferias, ausencias ou recorrencias por dia da semana.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Cadastre escalas de atendimento apenas quando quiser limitar o recebimento de chamados por dia e horario.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Use a aba Logs para conferir por que um tecnico foi escolhido ou ignorado.', 'atribuicaointeligente'); ?></li>
         </ol>
      </div>
   </div>

   <div class="card mt-3">
      <div class="card-header">
         <h3 class="card-title">
            <i class="ti ti-info-square-rounded me-2"></i>
            <?php echo __('Regras importantes', 'atribuicaointeligente'); ?>
         </h3>
      </div>
      <div class="card-body">
         <ul class="mb-0">
            <li><?php echo __('Tecnicos indisponiveis nao sao removidos dos grupos; eles apenas sao ignorados temporariamente pela distribuicao.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Se todos os tecnicos candidatos estiverem indisponiveis, o chamado nao recebe tecnico automaticamente e o motivo fica registrado em log.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('A atribuicao manual usa o dropdown nativo do GLPI, mas a gravacao e bloqueada se o tecnico estiver indisponivel.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('Indisponibilidades e escalas podem ser globais ou restritas a entidade, conforme o cadastro realizado.', 'atribuicaointeligente'); ?></li>
            <li><?php echo __('O plugin usa tabelas proprias e nao altera o core nem tabelas nativas do GLPI.', 'atribuicaointeligente'); ?></li>
         </ul>
      </div>
   </div>
</div>
