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

   public static function getTable($classname = null) {
      return 'glpi_plugin_atribuicaointeligente_config_display';
   }

   public static function getConfigTable(): string {
      return 'glpi_plugin_atribuicaointeligente_configs';
   }

   public static function getAssignmentsTable(): string {
      return 'glpi_plugin_atribuicaointeligente_assignments';
   }

   public static function getUnavailabilitiesTable(): string {
      return 'glpi_plugin_atribuicaointeligente_unavailabilities';
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
      return Plugin::getWebDir('atribuicaointeligente') . '/front/config.form.php';
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
         4 => self::createTabEntry(__('Logs', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-list-details'),
         5 => self::createTabEntry(__('Sobre', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-info-circle'),
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
            include PLUGIN_ATRIBUICAOINTELIGENTE_DIR . '/front/logs.tab.php';
            break;
         case 5:
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

   public static function getDefaultConfig(): array {
      return [
         'auto_assign_group' => 1,
         'auto_assign_type'  => 0,
         'auto_assign_mode'  => 0,
         'exclude_managers'  => 1,
      ];
   }

   public static function ensureDefaultConfig(): void {
      global $DB;

      if (!$DB->tableExists(self::getConfigTable())) {
         return;
      }

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
         'auto_assign_group' => (int) ($row['auto_assign_group'] ?? $defaults['auto_assign_group']),
         'auto_assign_type'  => (int) ($row['auto_assign_type'] ?? $defaults['auto_assign_type']),
         'auto_assign_mode'  => (int) ($row['auto_assign_mode'] ?? $defaults['auto_assign_mode']),
         'exclude_managers'  => (int) ($row['exclude_managers'] ?? $defaults['exclude_managers']),
      ];
   }

   public static function saveConfigValues(array $values): void {
      global $DB;

      self::ensureDefaultConfig();
      $config = array_merge(self::getDefaultConfig(), $values);
      $payload = [
         'auto_assign_group' => (int) $config['auto_assign_group'],
         'auto_assign_type'  => (int) $config['auto_assign_type'],
         'auto_assign_mode'  => (int) $config['auto_assign_mode'],
         'exclude_managers'  => (int) $config['exclude_managers'],
         'date_mod'          => date('Y-m-d H:i:s'),
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
