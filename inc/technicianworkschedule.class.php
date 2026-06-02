<?php
/**
 * Escala de atendimento de tecnico.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteTechnicianWorkSchedule extends CommonDBTM {

   public static $rightname = PluginAtribuicaointeligenteConfig::RIGHT_CONFIG;

   public static function getTable($classname = null) {
      return PluginAtribuicaointeligenteConfig::getWorkSchedulesTable();
   }

   public static function getTypeName($nb = 0) {
      return _n('Escala de atendimento', 'Escalas de atendimento', $nb, 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-calendar-time';
   }

   public static function getFormURL($full = true) {
      return Plugin::getWebDir('atribuicaointeligente', $full) . '/front/work_schedule.form.php';
   }

   public static function getWeekdays(): array {
      return PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdays();
   }

   public static function normalizeWeekdays($value): array {
      if (is_array($value)) {
         $items = $value;
      } else {
         $items = explode(',', (string) $value);
      }

      $weekdays = [];
      foreach ($items as $item) {
         if ($item === '' || $item === null) {
            continue;
         }
         $weekday = (int) $item;
         if ($weekday >= 0 && $weekday <= 6) {
            $weekdays[] = $weekday;
         }
      }

      sort($weekdays);
      return array_values(array_unique($weekdays));
   }

   public static function serializeWeekdays($value): string {
      return implode(',', self::normalizeWeekdays($value));
   }

   public static function getWeekdaysLabel($value): string {
      $labels = [];
      $weekdays = self::getWeekdays();
      foreach (self::normalizeWeekdays($value) as $weekday) {
         if (isset($weekdays[$weekday])) {
            $labels[] = $weekdays[$weekday];
         }
      }

      return implode(', ', $labels);
   }

   public static function formatTime($value): string {
      if (empty($value)) {
         return '';
      }

      return substr((string) $value, 0, 5);
   }

   public function getName($options = []) {
      $user = '';
      if (!empty($this->fields['users_id'])) {
         $user = getUserName((int) $this->fields['users_id']);
      }

      return trim($user . ' - ' . self::getWeekdaysLabel($this->fields['weekdays'] ?? ''), ' -');
   }
}
