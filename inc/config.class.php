<?php
/**
 * Configuracao principal do plugin Atribuicao Inteligente.
 *
 * Fork baseado no modulo SmartAssign do NexTool.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteConfig extends CommonDBTM {

   public const CONFIG_ID = 1;
   public const RIGHT_CONFIG = 'plugin_atribuicaointeligente';

   public static $rightname = self::RIGHT_CONFIG;
   protected static $entityConfigSchemaChecked = false;
   protected static $decisionLogSchemaChecked = false;
   protected static $configSchemaChecked = false;
   protected static $entityEnabledCache = [];

   public static function getTable($classname = null) {
      return 'glpi_plugin_atribuicaointeligente_config_display';
   }

   public static function getConfigTable(): string {
      return 'glpi_plugin_atribuicaointeligente_configs';
   }

   public static function getEntityConfigTable(): string {
      return 'glpi_plugin_atribuicaointeligente_entity_configs';
   }

   public static function getAssignmentsTable(): string {
      return 'glpi_plugin_atribuicaointeligente_assignments';
   }

   public static function getUnavailabilitiesTable(): string {
      return 'glpi_plugin_atribuicaointeligente_unavailabilities';
   }

   public static function getWorkSchedulesTable(): string {
      return 'glpi_plugin_atribuicaointeligente_work_schedules';
   }

   public static function getDecisionLogsTable(): string {
      return 'glpi_plugin_atribuicaointeligente_decision_logs';
   }

   public static function getTypeName($nb = 0) {
      return __('Atribuição Inteligente', 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-user-check';
   }

   public static function getMenuName() {
      return self::getTypeName(1);
   }

   public static function getMenuContent() {
      if (!self::canView()) {
         return false;
      }

      return [
         'title' => self::getMenuName(),
         'page'  => self::getFormURL(false),
         'icon'  => self::getIcon(),
         'links' => [
            'config' => self::getFormURL(false),
         ],
      ];
   }

   public static function getSearchURL($full = true) {
      return self::getFormURL($full);
   }

   public static function getFormURL($full = true) {
      return Plugin::getWebDir('atribuicaointeligente', $full) . '/front/config.form.php';
   }

   public function rawSearchOptions() {
      return [
         [
            'id'   => 'common',
            'name' => self::getTypeName(1),
         ],
         [
            'id'            => '1',
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'searchtype'    => ['equals'],
            'datatype'      => 'number',
            'massiveaction' => false,
         ],
      ];
   }

   public function defineTabs($options = []) {
      $ong = [];
      $this->addStandardTab(self::class, $ong, $options);
      return $ong;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$item instanceof self) {
         return '';
      }

      return [
         1 => self::createTabEntry(__('Configurações', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-settings'),
         2 => self::createTabEntry(__('Categorias', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-list-search'),
         3 => self::createTabEntry(__('Indisponibilidades', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-user-off'),
         4 => self::createTabEntry(__('Escala de atendimento', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-calendar-time'),
         5 => self::createTabEntry(__('Logs', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-list-details'),
         6 => self::createTabEntry(__('Sobre', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-info-circle'),
      ];
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if (!$item instanceof self) {
         return false;
      }

      switch ((int) $tabnum) {
         case 1:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/config.tab.php';
            break;
         case 2:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/categories.tab.php';
            break;
         case 3:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/unavailabilities.tab.php';
            break;
         case 4:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/work_schedules.tab.php';
            break;
         case 5:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/logs.tab.php';
            break;
         case 6:
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/about.tab.php';
            break;
      }

      return true;
   }

   public static function canView(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, READ)
         || Session::haveRight(Config::$rightname, READ)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canManage(): bool {
      return Session::haveRightsOr(self::RIGHT_CONFIG, [CREATE, UPDATE, DELETE, PURGE])
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canUpdateConfig(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, UPDATE)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canCreateUnavailability(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, CREATE)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canUpdateUnavailability(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, UPDATE)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canDeleteUnavailability(): bool {
      return Session::haveRightsOr(self::RIGHT_CONFIG, [DELETE, PURGE])
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canCreateWorkSchedule(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, CREATE)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canUpdateWorkSchedule(): bool {
      return Session::haveRight(self::RIGHT_CONFIG, UPDATE)
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function canDeleteWorkSchedule(): bool {
      return Session::haveRightsOr(self::RIGHT_CONFIG, [DELETE, PURGE])
         || Session::haveRight(Config::$rightname, UPDATE);
   }

   public static function assertCanView(): void {
      if (!self::canView()) {
         Html::displayRightError();
      }
   }

   public static function assertCanManage(): void {
      if (!self::canManage()) {
         Html::displayRightError();
      }
   }

   public static function assertCanUpdateConfig(): void {
      if (!self::canUpdateConfig()) {
         Html::displayRightError();
      }
   }

   public static function assertCanCreateUnavailability(): void {
      if (!self::canCreateUnavailability()) {
         Html::displayRightError();
      }
   }

   public static function assertCanUpdateUnavailability(): void {
      if (!self::canUpdateUnavailability()) {
         Html::displayRightError();
      }
   }

   public static function assertCanDeleteUnavailability(): void {
      if (!self::canDeleteUnavailability()) {
         Html::displayRightError();
      }
   }

   public static function assertCanCreateWorkSchedule(): void {
      if (!self::canCreateWorkSchedule()) {
         Html::displayRightError();
      }
   }

   public static function assertCanUpdateWorkSchedule(): void {
      if (!self::canUpdateWorkSchedule()) {
         Html::displayRightError();
      }
   }

   public static function assertCanDeleteWorkSchedule(): void {
      if (!self::canDeleteWorkSchedule()) {
         Html::displayRightError();
      }
   }

   public static function installRights(): void {
      $migration = new Migration(PLUGIN_ATRIBUICAOINTELIGENTE_VERSION);
      $migration->addRight(self::RIGHT_CONFIG, ALLSTANDARDRIGHT, [Config::$rightname => UPDATE]);
      $migration->executeMigration();

      ProfileRight::addProfileRights([self::RIGHT_CONFIG]);
      self::repairEmptyRightsForConfigAdmins();
   }

   public static function repairEmptyRightsForConfigAdmins(): void {
      global $DB;

      if (!$DB->tableExists('glpi_profilerights')) {
         return;
      }

      $right = self::RIGHT_CONFIG;
      $configRight = Config::$rightname;
      $hasGranted = $DB->doQuery(
         "SELECT `id`
          FROM `glpi_profilerights`
          WHERE `name` = '{$right}'
            AND COALESCE(`rights`, 0) > 0
          LIMIT 1"
      );

      if ($hasGranted && $hasGranted->num_rows > 0) {
         return;
      }

      $DB->doQuery(
         "UPDATE `glpi_profilerights` pluginright
          INNER JOIN `glpi_profilerights` configright
             ON configright.`profiles_id` = pluginright.`profiles_id`
            AND configright.`name` = '{$configRight}'
            AND (configright.`rights` & " . (int) UPDATE . ") = " . (int) UPDATE . "
          SET pluginright.`rights` = " . (int) ALLSTANDARDRIGHT . "
          WHERE pluginright.`name` = '{$right}'
            AND COALESCE(pluginright.`rights`, 0) = 0"
      );
   }

   public static function ensureDisplayItem(): bool {
      global $DB;

      if (!$DB->tableExists(self::getTable())) {
         return false;
      }

      $iterator = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ]);
      if ($iterator->current()) {
         return true;
      }

      return (bool) $DB->insert(self::getTable(), ['id' => self::CONFIG_ID]);
   }

   public static function canUseEntity(int $entities_id): bool {
      if ($entities_id < 0) {
         return false;
      }

      return Session::haveAccessToEntity($entities_id, true);
   }

   public static function getEntityRestrictCriteria(string $field = 'entities_id', bool $includeGlobal = true): array {
      if (Session::canViewAllEntities()) {
         return [];
      }

      $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
      if ($includeGlobal) {
         $entities[] = 0;
      }

      return [
         $field => array_values(array_unique($entities)),
      ];
   }

   public static function ensureEntityConfigSchema(): void {
      global $DB;

      if (self::$entityConfigSchemaChecked) {
         return;
      }

      $table = self::getEntityConfigTable();
      $created = false;

      if (!$DB->tableExists($table)) {
         $DB->doQuery(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
               `id` int unsigned NOT NULL AUTO_INCREMENT,
               `entities_id` int unsigned NOT NULL DEFAULT 0,
               `is_active` tinyint NOT NULL DEFAULT 0,
               `date_creation` timestamp NULL DEFAULT NULL,
               `date_mod` timestamp NULL DEFAULT NULL,
               PRIMARY KEY (`id`),
               UNIQUE KEY `ix_entities_id_uq` (`entities_id`),
               KEY `idx_entity_active` (`entities_id`, `is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
         );
         $created = true;
      }

      $DB->doQuery("ALTER TABLE `{$table}` MODIFY `is_active` tinyint NOT NULL DEFAULT 0");

      if ($created || self::isEntityConfigTableEmpty()) {
         self::seedExistingEntityConfigs();
      }

      self::$entityConfigSchemaChecked = true;
   }

   protected static function isEntityConfigTableEmpty(): bool {
      global $DB;

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table)) {
         return true;
      }

      $result = $DB->doQuery("SELECT `id` FROM `{$table}` LIMIT 1");
      return !$result || $result->num_rows === 0;
   }

   protected static function seedExistingEntityConfigs(): void {
      global $DB;

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table) || !$DB->tableExists('glpi_entities')) {
         return;
      }

      $DB->doQuery(
         "INSERT INTO `{$table}` (`entities_id`, `is_active`, `date_creation`, `date_mod`)
          SELECT ent.`id`, 0, NOW(), NOW()
          FROM `glpi_entities` ent
          LEFT JOIN `{$table}` cfg
             ON cfg.`entities_id` = ent.`id`
          WHERE cfg.`id` IS NULL"
      );
   }

   public static function getEntityConfigRows(): array {
      global $DB;

      self::ensureEntityConfigSchema();

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table) || !$DB->tableExists('glpi_entities')) {
         return [];
      }

      $where = '';
      $entityIds = self::getManageableEntityIds();
      if (!empty($entityIds)) {
         $where = 'WHERE ent.`id` IN (' . implode(',', array_map('intval', $entityIds)) . ')';
      }

      $result = $DB->doQuery(
         "SELECT ent.`id`,
                 ent.`completename`,
                 COALESCE(cfg.`is_active`, 0) AS `is_active`
          FROM `glpi_entities` ent
          LEFT JOIN `{$table}` cfg
             ON cfg.`entities_id` = ent.`id`
          {$where}
          ORDER BY ent.`completename` ASC, ent.`id` ASC"
      );

      $rows = [];
      if ($result) {
         while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) ($row['id'] ?? 0);
            $row['is_active'] = (int) ($row['is_active'] ?? 0);
            $rows[] = $row;
         }
      }

      return $rows;
   }

   public static function getManageableEntityIds(): array {
      if (Session::canViewAllEntities()) {
         return [];
      }

      $entities = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
      $entities[] = 0;
      return array_values(array_unique(array_filter($entities, static function($entityId) {
         return $entityId >= 0;
      })));
   }

   public static function saveEnabledEntities(array $enabledEntities): void {
      global $DB;

      self::ensureEntityConfigSchema();

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table)) {
         return;
      }

      $enabled = array_flip(array_map('intval', $enabledEntities));
      $rows = self::getEntityConfigRows();
      foreach ($rows as $row) {
         $entitiesId = (int) ($row['id'] ?? 0);
         $isActive = isset($enabled[$entitiesId]) ? 1 : 0;
         $DB->doQuery(
            "INSERT INTO `{$table}` (`entities_id`, `is_active`, `date_creation`, `date_mod`)
             VALUES ({$entitiesId}, {$isActive}, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                `is_active` = VALUES(`is_active`),
                `date_mod` = NOW()"
         );
         self::$entityEnabledCache[$entitiesId] = $isActive === 1;
      }
   }

   public static function setAllManageableEntitiesActive(bool $isActive): void {
      global $DB;

      self::ensureEntityConfigSchema();

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table)) {
         return;
      }

      $active = $isActive ? 1 : 0;
      $rows = self::getEntityConfigRows();
      foreach ($rows as $row) {
         $entitiesId = (int) ($row['id'] ?? 0);
         $DB->doQuery(
            "INSERT INTO `{$table}` (`entities_id`, `is_active`, `date_creation`, `date_mod`)
             VALUES ({$entitiesId}, {$active}, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                `is_active` = VALUES(`is_active`),
                `date_mod` = NOW()"
         );
         self::$entityEnabledCache[$entitiesId] = $isActive;
      }
   }

   public static function isEntityEnabled(int $entitiesId): bool {
      global $DB;

      if ($entitiesId < 0) {
         return false;
      }
      if (array_key_exists($entitiesId, self::$entityEnabledCache)) {
         return self::$entityEnabledCache[$entitiesId];
      }

      self::ensureEntityConfigSchema();

      $table = self::getEntityConfigTable();
      if (!$DB->tableExists($table)) {
         return false;
      }

      $iterator = $DB->request([
         'SELECT' => ['is_active'],
         'FROM'   => $table,
         'WHERE'  => ['entities_id' => $entitiesId],
         'LIMIT'  => 1,
      ]);
      $row = $iterator->current();

      self::$entityEnabledCache[$entitiesId] = $row ? (int) ($row['is_active'] ?? 0) === 1 : false;
      return self::$entityEnabledCache[$entitiesId];
   }

   public static function ensureDecisionLogSchema(): void {
      global $DB;

      if (self::$decisionLogSchemaChecked) {
         return;
      }

      $table = self::getDecisionLogsTable();
      if (!$DB->tableExists($table)) {
         return;
      }

      $index = $DB->doQuery("SHOW INDEX FROM `{$table}` WHERE `Key_name` = 'idx_entity_date'");
      if (!$index || $index->num_rows === 0) {
         $DB->doQuery("ALTER TABLE `{$table}` ADD KEY `idx_entity_date` (`entities_id`, `date_creation`)");
      }

      self::$decisionLogSchemaChecked = true;
   }

   public static function getDefaultConfig(): array {
      return [
         'auto_assign_group'   => 1,
         'auto_assign_type'    => 0,
         'auto_assign_mode'    => 0,
         'exclude_managers'    => 1,
         'use_entity_calendar' => 0,
         'assign_on_update'    => 0,
      ];
   }

   public static function ensureConfigSchema(): void {
      global $DB;

      if (self::$configSchemaChecked) {
         return;
      }

      $table = self::getConfigTable();
      if (!$DB->tableExists($table)) {
         return;
      }

      $result = $DB->doQuery("SHOW COLUMNS FROM `{$table}` LIKE 'use_entity_calendar'");
      if (!$result || $result->num_rows === 0) {
         $DB->doQuery("ALTER TABLE `{$table}` ADD `use_entity_calendar` tinyint NOT NULL DEFAULT 0 AFTER `exclude_managers`");
      }

      $result = $DB->doQuery("SHOW COLUMNS FROM `{$table}` LIKE 'assign_on_update'");
      if (!$result || $result->num_rows === 0) {
         $DB->doQuery("ALTER TABLE `{$table}` ADD `assign_on_update` tinyint NOT NULL DEFAULT 0 AFTER `use_entity_calendar`");
      }

      self::$configSchemaChecked = true;
   }

   public static function ensureDefaultConfig(): void {
      global $DB;

      if (!$DB->tableExists(self::getConfigTable())) {
         return;
      }
      self::ensureConfigSchema();

      $iterator = $DB->request([
         'FROM'  => self::getConfigTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ]);
      if ($iterator->current()) {
         return;
      }

      $DB->insert(self::getConfigTable(), array_merge(self::getDefaultConfig(), [
         'id'            => self::CONFIG_ID,
         'date_creation' => date('Y-m-d H:i:s'),
         'date_mod'      => date('Y-m-d H:i:s'),
      ]));
   }

   public static function getConfigValues(): array {
      global $DB;

      self::ensureDefaultConfig();
      $defaults = self::getDefaultConfig();

      if (!$DB->tableExists(self::getConfigTable())) {
         return $defaults;
      }

      $iterator = $DB->request([
         'FROM'  => self::getConfigTable(),
         'WHERE' => ['id' => self::CONFIG_ID],
         'LIMIT' => 1,
      ]);

      $row = $iterator->current();
      if (!$row) {
         return $defaults;
      }

      return [
         'auto_assign_group'   => (int) ($row['auto_assign_group'] ?? $defaults['auto_assign_group']),
         'auto_assign_type'    => (int) ($row['auto_assign_type'] ?? $defaults['auto_assign_type']),
         'auto_assign_mode'    => (int) ($row['auto_assign_mode'] ?? $defaults['auto_assign_mode']),
         'exclude_managers'    => (int) ($row['exclude_managers'] ?? $defaults['exclude_managers']),
         'use_entity_calendar' => (int) ($row['use_entity_calendar'] ?? $defaults['use_entity_calendar']),
         'assign_on_update'    => (int) ($row['assign_on_update'] ?? $defaults['assign_on_update']),
      ];
   }

   public static function saveConfigValues(array $values): void {
      global $DB;

      self::ensureDefaultConfig();
      $config = array_merge(self::getDefaultConfig(), $values);
      $payload = [
         'auto_assign_group'   => (int) $config['auto_assign_group'],
         'auto_assign_type'    => (int) $config['auto_assign_type'],
         'auto_assign_mode'    => (int) $config['auto_assign_mode'],
         'exclude_managers'    => (int) $config['exclude_managers'],
         'use_entity_calendar' => (int) $config['use_entity_calendar'],
         'assign_on_update'    => (int) $config['assign_on_update'],
         'date_mod'            => date('Y-m-d H:i:s'),
      ];

      $DB->update(self::getConfigTable(), $payload, ['id' => self::CONFIG_ID]);
   }

   public static function migrateFromNextool(): void {
      global $DB;

      if ($DB->tableExists('glpi_plugin_nextool_smartassign_assignments')
         && $DB->tableExists(self::getAssignmentsTable())
      ) {
         $sql = "INSERT INTO `" . self::getAssignmentsTable() . "`
                    (`itilcategories_id`, `is_active`, `last_assignment_index`)
                 SELECT old.`itilcategories_id`,
                        COALESCE(old.`is_active`, 1),
                        old.`last_assignment_index`
                 FROM `glpi_plugin_nextool_smartassign_assignments` old
                 LEFT JOIN `" . self::getAssignmentsTable() . "` cur
                    ON cur.`itilcategories_id` = old.`itilcategories_id`
                 WHERE cur.`id` IS NULL";
         try {
            $DB->doQuery($sql);
         } catch (Throwable $e) {
            PluginAtribuicaointeligenteLogger::addWarning('Falha ao migrar assignments do SmartAssign', [
               'error' => $e->getMessage(),
            ]);
         }
      }

      $config = null;
      if ($DB->tableExists('glpi_plugin_nextool_main_modules')) {
         $iterator = $DB->request([
            'SELECT' => ['config'],
            'FROM'   => 'glpi_plugin_nextool_main_modules',
            'WHERE'  => ['module_key' => 'smartassign'],
            'LIMIT'  => 1,
         ]);
         $row = $iterator->current();
         if (!empty($row['config'])) {
            $decoded = json_decode((string) $row['config'], true);
            if (is_array($decoded)) {
               $config = $decoded;
            }
         }
      }

      if ($config === null && $DB->tableExists('glpi_plugin_nextool_smartassign_options')) {
         $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_nextool_smartassign_options',
            'LIMIT' => 1,
         ]);
         $row = $iterator->current();
         if ($row) {
            $config = $row;
         }
      }

      if (is_array($config)) {
         self::saveConfigValues([
            'auto_assign_group' => (int) ($config['auto_assign_group'] ?? 1),
            'auto_assign_type'  => (int) ($config['auto_assign_type'] ?? 0),
            'auto_assign_mode'  => (int) ($config['auto_assign_mode'] ?? 0),
            'exclude_managers'  => (int) ($config['exclude_managers'] ?? 1),
         ]);
      }
   }
}
