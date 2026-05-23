<?php
/**
 * Registro de indisponibilidade de tecnico.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteTechnicianUnavailability extends CommonDBTM {

   public static $rightname = PluginAtribuicaointeligenteConfig::RIGHT_CONFIG;

   public static function getTable($classname = null) {
      return PluginAtribuicaointeligenteConfig::getUnavailabilitiesTable();
   }

   public static function getTypeName($nb = 0) {
      return _n('Indisponibilidade', 'Indisponibilidades', $nb, 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-user-off';
   }

   public static function getFormURL($full = true) {
      return Plugin::getWebDir('atribuicaointeligente') . '/front/unavailability.form.php';
   }

   public static function getTypes(): array {
      return [
         'vacation'      => __('Férias por período', 'atribuicaointeligente'),
         'specific_date' => __('Ausência em data específica', 'atribuicaointeligente'),
         'weekly'        => __('Recorrente por dia da semana', 'atribuicaointeligente'),
         'temporary'     => __('Ausência temporária', 'atribuicaointeligente'),
      ];
   }

   public static function getWeekdays(): array {
      return [
         0 => __('Domingo', 'atribuicaointeligente'),
         1 => __('Segunda-feira', 'atribuicaointeligente'),
         2 => __('Terça-feira', 'atribuicaointeligente'),
         3 => __('Quarta-feira', 'atribuicaointeligente'),
         4 => __('Quinta-feira', 'atribuicaointeligente'),
         5 => __('Sexta-feira', 'atribuicaointeligente'),
         6 => __('Sábado', 'atribuicaointeligente'),
      ];
   }

   public static function getTypeLabel(string $type): string {
      $types = self::getTypes();
      return $types[$type] ?? $type;
   }

   public static function getWeekdayLabel($weekday): string {
      $weekdays = self::getWeekdays();
      return $weekdays[(int) $weekday] ?? '';
   }

   public function getName($options = []) {
      $user = '';
      if (!empty($this->fields['users_id'])) {
         $user = getUserName((int) $this->fields['users_id']);
      }
      $type = self::getTypeLabel((string) ($this->fields['type'] ?? ''));
      return trim($user . ' - ' . $type, ' -');
   }
}
