<?php
/**
 * Aba Categorias.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

$_GET['embedded'] = '1';
echo '<div id="atribuicaointeligente-categories-tab">';
include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/categories.php';
echo '</div>';
unset($_GET['embedded']);
