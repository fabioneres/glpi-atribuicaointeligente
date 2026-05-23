<?php
/**
 * Verifica indisponibilidades ativas de tecnicos.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteAvailabilityChecker {

   public static function getUnavailableReason($usersId, $entitiesId = 0, ?DateTimeInterface $now = null): ?string {
      global $DB;

      $usersId = (int) $usersId;
      $entitiesId = (int) $entitiesId;
      if ($usersId <= 0) {
         return null;
      }

      $now = $now ?: new DateTimeImmutable('now');
      $table = PluginAtribuicaointeligenteConfig::getUnavailabilitiesTable();
      if (!$DB->tableExists($table)) {
         return null;
      }

      $sql = "SELECT *
              FROM `{$table}`
              WHERE `users_id` = {$usersId}
                AND `is_active` = 1
                AND `entities_id` IN (0, {$entitiesId})
              ORDER BY `date_start` ASC, `id` ASC";

      $result = $DB->doQuery($sql);
      if (!$result) {
         return null;
      }

      while ($row = $result->fetch_assoc()) {
         if (self::matches($row, $now)) {
            return self::formatReason($row);
         }
      }

      return null;
   }

   public static function isAvailable($usersId, $entitiesId = 0, ?DateTimeInterface $now = null): bool {
      return self::getUnavailableReason($usersId, $entitiesId, $now) === null;
   }

   protected static function matches(array $row, DateTimeInterface $now): bool {
      $type = (string) ($row['type'] ?? '');
      $today = $now->format('Y-m-d');
      $weekday = (int) $now->format('w');

      switch ($type) {
         case 'vacation':
         case 'temporary':
            return self::isWithinPeriod($now, $row['date_start'] ?? null, $row['date_end'] ?? null);

         case 'specific_date':
            $start = self::normalizeDate($row['date_start'] ?? null);
            $end = self::normalizeDate($row['date_end'] ?? null) ?: $start;
            if ($start === null) {
               return false;
            }
            return $today >= $start && $today <= $end;

         case 'weekly':
            if ((int) ($row['weekday'] ?? -1) !== $weekday) {
               return false;
            }
            return self::isWithinOptionalPeriod($now, $row['date_start'] ?? null, $row['date_end'] ?? null);
      }

      return false;
   }

   protected static function isWithinPeriod(DateTimeInterface $now, $start, $end): bool {
      if (empty($start) || empty($end)) {
         return false;
      }

      $startDate = new DateTimeImmutable((string) $start);
      $endDate = new DateTimeImmutable((string) $end);
      return $now >= $startDate && $now <= $endDate;
   }

   protected static function isWithinOptionalPeriod(DateTimeInterface $now, $start, $end): bool {
      if (!empty($start)) {
         $startDate = new DateTimeImmutable((string) $start);
         if ($now < $startDate) {
            return false;
         }
      }

      if (!empty($end)) {
         $endDate = new DateTimeImmutable((string) $end);
         if ($now > $endDate) {
            return false;
         }
      }

      return true;
   }

   protected static function normalizeDate($value): ?string {
      if (empty($value)) {
         return null;
      }
      return (new DateTimeImmutable((string) $value))->format('Y-m-d');
   }

   protected static function formatReason(array $row): string {
      $type = PluginAtribuicaointeligenteTechnicianUnavailability::getTypeLabel((string) ($row['type'] ?? ''));
      $parts = [$type];

      if (!empty($row['weekday']) || (string) ($row['type'] ?? '') === 'weekly') {
         $parts[] = PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdayLabel($row['weekday']);
      }

      if (!empty($row['date_start']) || !empty($row['date_end'])) {
         $period = '';
         if (!empty($row['date_start'])) {
            $period .= (string) $row['date_start'];
         }
         if (!empty($row['date_end'])) {
            $period .= ($period !== '' ? ' ate ' : '') . (string) $row['date_end'];
         }
         $parts[] = $period;
      }

      if (!empty($row['comment'])) {
         $parts[] = (string) $row['comment'];
      }

      return implode(' | ', array_filter($parts));
   }
}
