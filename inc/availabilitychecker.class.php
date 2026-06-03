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
      if (!PluginAtribuicaointeligenteConfig::isEntityEnabled($entitiesId)) {
         return null;
      }

      $now = $now ?: new DateTimeImmutable('now');
      $entityCalendarReason = self::getEntityCalendarReason($entitiesId, $now);
      if ($entityCalendarReason !== null) {
         return $entityCalendarReason;
      }

      $unavailabilityReason = self::getUnavailabilityReason($usersId, $entitiesId, $now);
      if ($unavailabilityReason !== null) {
         return $unavailabilityReason;
      }

      return self::getOutOfScheduleReason($usersId, $entitiesId, $now);
   }

   public static function isAvailable($usersId, $entitiesId = 0, ?DateTimeInterface $now = null): bool {
      return self::getUnavailableReason($usersId, $entitiesId, $now) === null;
   }

   protected static function getUnavailabilityReason(int $usersId, int $entitiesId, DateTimeInterface $now): ?string {
      global $DB;

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
         if (self::matchesUnavailability($row, $now)) {
            return self::formatUnavailabilityReason($row);
         }
      }

      return null;
   }

   protected static function getEntityCalendarReason(int $entitiesId, DateTimeInterface $now): ?string {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      if (empty($config['use_entity_calendar'])) {
         return null;
      }

      $calendarsId = self::getEntityCalendarId($entitiesId);
      if ($calendarsId <= 0) {
         return null;
      }

      $calendar = new Calendar();
      if ($calendar->getFromDB($calendarsId) && $calendar->isAWorkingHour($now->getTimestamp())) {
         return null;
      }

      return self::formatEntityCalendarReason($calendarsId, $now);
   }

   protected static function getEntityCalendarId(int $entitiesId): int {
      global $DB;

      if ($DB->tableExists('glpi_entities')) {
         $iterator = $DB->request([
            'SELECT' => ['calendars_id'],
            'FROM'   => 'glpi_entities',
            'WHERE'  => ['id' => $entitiesId],
            'LIMIT'  => 1,
         ]);
         $row = $iterator->current();
         $calendarsId = (int) ($row['calendars_id'] ?? 0);
         if ($calendarsId > 0) {
            return $calendarsId;
         }
      }

      try {
         return (int) Entity::getUsedConfig('calendars_strategy', $entitiesId, 'calendars_id', 0);
      } catch (Throwable $e) {
         PluginAtribuicaointeligenteLogger::addWarning('Falha ao obter calendario da entidade', [
            'entities_id' => $entitiesId,
            'error'       => $e->getMessage(),
         ]);
      }

      return 0;
   }

   protected static function getOutOfScheduleReason(int $usersId, int $entitiesId, DateTimeInterface $now): ?string {
      global $DB;

      $table = PluginAtribuicaointeligenteConfig::getWorkSchedulesTable();
      if (!$DB->tableExists($table)) {
         return null;
      }

      $sql = "SELECT *
              FROM `{$table}`
              WHERE `users_id` = {$usersId}
                AND `is_active` = 1
                AND `entities_id` IN (0, {$entitiesId})
              ORDER BY `entities_id` DESC, `id` ASC";

      $result = $DB->doQuery($sql);
      if (!$result || $result->num_rows === 0) {
         return null;
      }

      while ($row = $result->fetch_assoc()) {
         if (self::matchesWorkSchedule($row, $now)) {
            return null;
         }
      }

      return self::formatOutOfScheduleReason($now);
   }

   protected static function matchesUnavailability(array $row, DateTimeInterface $now): bool {
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

   protected static function matchesWorkSchedule(array $row, DateTimeInterface $now): bool {
      $weekdays = PluginAtribuicaointeligenteTechnicianWorkSchedule::normalizeWeekdays($row['weekdays'] ?? '');
      if (empty($weekdays) || !in_array((int) $now->format('w'), $weekdays, true)) {
         return false;
      }

      if (!self::isWithinOptionalDatePeriod($now, $row['date_start'] ?? null, $row['date_end'] ?? null)) {
         return false;
      }

      return self::isWithinOptionalTimePeriod($now, $row['time_start'] ?? null, $row['time_end'] ?? null);
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

   protected static function isWithinOptionalDatePeriod(DateTimeInterface $now, $start, $end): bool {
      $today = $now->format('Y-m-d');

      if (!empty($start) && $today < self::normalizeDate($start)) {
         return false;
      }

      if (!empty($end) && $today > self::normalizeDate($end)) {
         return false;
      }

      return true;
   }

   protected static function isWithinOptionalTimePeriod(DateTimeInterface $now, $start, $end): bool {
      if (empty($start) && empty($end)) {
         return true;
      }

      $current = $now->format('H:i:s');
      $startTime = !empty($start) ? substr((string) $start, 0, 8) : '00:00:00';
      $endTime = !empty($end) ? substr((string) $end, 0, 8) : '23:59:59';

      if (strlen($startTime) === 5) {
         $startTime .= ':00';
      }
      if (strlen($endTime) === 5) {
         $endTime .= ':00';
      }

      if ($startTime <= $endTime) {
         return $current >= $startTime && $current <= $endTime;
      }

      return $current >= $startTime || $current <= $endTime;
   }

   protected static function normalizeDate($value): ?string {
      if (empty($value)) {
         return null;
      }
      return (new DateTimeImmutable((string) $value))->format('Y-m-d');
   }

   protected static function formatUnavailabilityReason(array $row): string {
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

   protected static function formatOutOfScheduleReason(DateTimeInterface $now): string {
      return sprintf(
         '%s | %s %s',
         __('Fora da escala de atendimento', 'atribuicaointeligente'),
         PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdayLabel((int) $now->format('w')),
         $now->format('H:i')
      );
   }

   protected static function formatEntityCalendarReason(int $calendarsId, DateTimeInterface $now): string {
      $calendarName = Dropdown::getDropdownName('glpi_calendars', $calendarsId);
      return sprintf(
         '%s | %s | %s %s',
         __('Fora do calendário de atendimento da entidade', 'atribuicaointeligente'),
         html_entity_decode((string) $calendarName, ENT_QUOTES, 'UTF-8'),
         PluginAtribuicaointeligenteTechnicianUnavailability::getWeekdayLabel((int) $now->format('w')),
         $now->format('H:i')
      );
   }
}
