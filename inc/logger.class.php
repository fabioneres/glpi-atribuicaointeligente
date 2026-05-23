<?php
/**
 * Logger do plugin Atribuicao Inteligente.
 *
 * Fork baseado no SmartAssign/NexTool.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteLogger {

   public const LOG_FILE = 'plugin_atribuicaointeligente';

   protected static function add(string $level, string $message, array $details = []): void {
      $line = '[' . $level . '] ' . $message;
      if (!empty($details)) {
         $line .= ' - ' . json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      Toolbox::logInFile(self::LOG_FILE, $line . PHP_EOL);
   }

   public static function addDebug(string $message, array $details = []): void {
      self::add('DEBUG', $message, $details);
   }

   public static function addInfo(string $message, array $details = []): void {
      self::add('INFO', $message, $details);
   }

   public static function addWarning(string $message, array $details = []): void {
      self::add('WARNING', $message, $details);
   }

   public static function addError(string $message, array $details = []): void {
      self::add('ERROR', $message, $details);
   }
}
