<?php
/**
 * Aba Indisponibilidades.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

$_GET['embedded'] = '1';
$availabilityContext = $_SESSION['plugin_atribuicaointeligente_unavailabilities'] ?? [];
if (!isset($_GET['availability_tab']) && isset($availabilityContext['availability_tab'])) {
   $_GET['availability_tab'] = (string) $availabilityContext['availability_tab'];
}
if (!isset($_GET['users_id']) && isset($availabilityContext['users_id'])) {
   $_GET['users_id'] = (string) (int) $availabilityContext['users_id'];
}
include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/unavailabilities.php';
unset($_GET['embedded']);
