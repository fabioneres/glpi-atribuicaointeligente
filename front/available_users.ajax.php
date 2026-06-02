<?php
/**
 * Dropdown de tecnicos disponiveis para atribuicao manual.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   $AJAX_INCLUDE = 1;
   require_once dirname(__DIR__, 3) . '/inc/includes.php';
   header('Content-Type: application/json; charset=UTF-8');
   Html::header_nocache();
}

Session::checkCentralAccess();

if (!class_exists('PluginAtribuicaointeligenteConfig')) {
   require_once dirname(__DIR__) . '/inc/logger.class.php';
   require_once dirname(__DIR__) . '/inc/config.class.php';
   require_once dirname(__DIR__) . '/inc/technicianunavailability.class.php';
   require_once dirname(__DIR__) . '/inc/technicianworkschedule.class.php';
   require_once dirname(__DIR__) . '/inc/availabilitychecker.class.php';
}

$request = $_POST;
$request['page_limit'] = max((int) ($request['page_limit'] ?? 0), 100);
$data = Dropdown::getDropdownUsers($request);
$entitiesId = plugin_atribuicaointeligente_dropdown_entity($_POST['entity_restrict'] ?? 0);
$data['results'] = plugin_atribuicaointeligente_filter_available_users($data['results'] ?? [], $entitiesId);
$data['count'] = count($data['results']);

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function plugin_atribuicaointeligente_dropdown_entity($value): int {
   if (is_array($value)) {
      $first = reset($value);
      return max(0, (int) $first);
   }

   $value = trim((string) $value);
   if ($value === '') {
      return 0;
   }

   if ($value[0] === '[' || $value[0] === '{') {
      $decoded = json_decode($value, true);
      if (is_array($decoded)) {
         $first = reset($decoded);
         return max(0, (int) $first);
      }
   }

   return max(0, (int) $value);
}

function plugin_atribuicaointeligente_filter_available_users(array $results, int $entitiesId): array {
   $filtered = [];

   foreach ($results as $entry) {
      if (isset($entry['children']) && is_array($entry['children'])) {
         $entry['children'] = plugin_atribuicaointeligente_filter_available_users($entry['children'], $entitiesId);
         if (!empty($entry['children'])) {
            $filtered[] = $entry;
         }
         continue;
      }

      $usersId = (int) ($entry['id'] ?? 0);
      if ($usersId <= 0) {
         $filtered[] = $entry;
         continue;
      }

      if (PluginAtribuicaointeligenteAvailabilityChecker::isAvailable($usersId, $entitiesId)) {
         $filtered[] = $entry;
      }
   }

   return array_values($filtered);
}
