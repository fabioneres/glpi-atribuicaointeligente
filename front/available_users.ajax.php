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

$request = $_REQUEST;
$request['page_limit'] = max((int) ($request['page_limit'] ?? 0), 100);

try {
   $data = Dropdown::getDropdownUsers($request, false);
   if (!is_array($data)) {
      throw new RuntimeException('Dropdown::getDropdownUsers nao retornou dados em formato array');
   }
   $entitiesId = plugin_atribuicaointeligente_dropdown_entity($request['entity_restrict'] ?? 0);
   if (PluginAtribuicaointeligenteConfig::isEntityEnabled($entitiesId)) {
      $data['results'] = plugin_atribuicaointeligente_filter_available_users($data['results'] ?? [], $entitiesId);
      $data['count'] = plugin_atribuicaointeligente_count_leaf_results($data['results']);
   }
} catch (Throwable $e) {
   PluginAtribuicaointeligenteLogger::addError('Falha ao carregar dropdown de tecnicos disponiveis', [
      'error'   => $e->getMessage(),
      'request' => $request,
   ]);
   $data = [
      'results' => [],
      'count'   => 0,
      'error'   => __('Não foi possível carregar os técnicos disponíveis.', 'atribuicaointeligente'),
   ];
}

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

function plugin_atribuicaointeligente_count_leaf_results(array $results): int {
   $count = 0;

   foreach ($results as $entry) {
      if (isset($entry['children']) && is_array($entry['children'])) {
         $count += plugin_atribuicaointeligente_count_leaf_results($entry['children']);
         continue;
      }

      if ((int) ($entry['id'] ?? 0) > 0) {
         $count++;
      }
   }

   return $count;
}
