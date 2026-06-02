<?php
/**
 * Aba Escala de atendimento.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

$_GET['embedded'] = '1';
include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/work_schedules.php';
unset($_GET['embedded']);
