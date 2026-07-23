<?php
/**
 * Camada de acesso as regras categoria -> grupo/rodizio.
 *
 * Fork baseado no RRAssignmentsEntity do SmartAssign/NexTool.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteAssignmentsEntity extends CommonDBTM {

   protected $DB;
   protected $assignmentTable;
   protected static $schemaChecked = false;

   public function __construct() {
      global $DB;
      $this->DB = $DB;
      $this->assignmentTable = PluginAtribuicaointeligenteConfig::getAssignmentsTable();
   }

   protected function executeQuery(string $sql, string $context = '') {
      try {
         $result = $this->DB->doQuery($sql);
         if ($result === false) {
            throw new RuntimeException($this->DB->error());
         }
         return $result;
      } catch (Throwable $e) {
         PluginAtribuicaointeligenteLogger::addError('Erro na query ' . $context, [
            'error' => $e->getMessage(),
         ]);
         throw $e;
      }
   }

   public function syncMissingCategories(): void {
      if (!$this->DB->tableExists($this->assignmentTable)) {
         return;
      }

      $this->ensureSchema();

      $sql = "INSERT INTO `{$this->assignmentTable}` (`itilcategories_id`, `is_active`)
              SELECT ic.id, 0
              FROM `glpi_itilcategories` ic
              LEFT JOIN `{$this->assignmentTable}` ai
                 ON ai.itilcategories_id = ic.id
              WHERE ai.id IS NULL";

      try {
         $this->DB->doQuery($sql);
      } catch (Throwable $e) {
         PluginAtribuicaointeligenteLogger::addError('Erro ao sincronizar categorias ITIL', [
            'error' => $e->getMessage(),
         ]);
      }
   }

   public function insertItilCategory($itilCategory): void {
      $itilCategory = (int) $itilCategory;
      if ($itilCategory <= 0) {
         return;
      }

      $this->ensureSchema();

      $sql = "INSERT INTO `{$this->assignmentTable}` (`itilcategories_id`, `is_active`)
              SELECT {$itilCategory}, 0
              WHERE NOT EXISTS (
                 SELECT 1
                 FROM `{$this->assignmentTable}`
                 WHERE `itilcategories_id` = {$itilCategory}
              )";
      $this->executeQuery($sql, 'insertItilCategory');
   }

   protected function ensureSchema(): void {
      if (self::$schemaChecked) {
         return;
      }

      if (!$this->DB->tableExists($this->assignmentTable)) {
         return;
      }

      $this->DB->doQuery(
         "ALTER TABLE `{$this->assignmentTable}`
          MODIFY `is_active` tinyint NOT NULL DEFAULT 0"
      );
      self::$schemaChecked = true;
   }

   public function deleteItilCategory($itilCategory): void {
      $this->DB->delete($this->assignmentTable, ['itilcategories_id' => (int) $itilCategory]);
   }

   public function getOptionAutoAssignGroup(): int {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      return (int) ($config['auto_assign_group'] ?? 1);
   }

   public function getOptionAutoAssignType(): int {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      return (int) ($config['auto_assign_type'] ?? 0);
   }

   public function getOptionAutoAssignMode(): int {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      return (int) ($config['auto_assign_mode'] ?? 0);
   }

   public function getOptionExcludeManagers(): int {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      return (int) ($config['exclude_managers'] ?? 1);
   }

   public function getOptionAssignOnUpdate(): int {
      $config = PluginAtribuicaointeligenteConfig::getConfigValues();
      return (int) ($config['assign_on_update'] ?? 0);
   }

   public function saveOptions(array $options): void {
      PluginAtribuicaointeligenteConfig::saveConfigValues($options);
   }

   public function getGroupByItilCategory($itilCategory) {
      $itilCategory = (int) $itilCategory;
      if ($itilCategory <= 0) {
         return false;
      }

      $iterator = $this->DB->request([
         'SELECT' => ['groups_id'],
         'FROM'   => 'glpi_itilcategories',
         'WHERE'  => ['id' => $itilCategory],
         'LIMIT'  => 1,
      ]);

      $row = $iterator->current();
      if (!$row || !isset($row['groups_id'])) {
         PluginAtribuicaointeligenteLogger::addWarning('Categoria ITIL nao encontrada', [
            'itilcategories_id' => $itilCategory,
         ]);
         return false;
      }

      $groupsId = (int) $row['groups_id'];
      return $groupsId > 0 ? $groupsId : false;
   }

   public function isCategoryActive($itilCategory): bool {
      $itilCategory = (int) $itilCategory;
      if ($itilCategory <= 0) {
         return false;
      }

      $iterator = $this->DB->request([
         'SELECT' => ['is_active'],
         'FROM'   => $this->assignmentTable,
         'WHERE'  => [
            'itilcategories_id' => $itilCategory,
            'is_active'         => 1,
         ],
         'LIMIT' => 1,
      ]);

      return (bool) $iterator->current();
   }

   public function updateLastAssignmentIndexCategoria($itilcategoriesId, $index): void {
      $this->DB->update(
         $this->assignmentTable,
         ['last_assignment_index' => (int) $index],
         ['itilcategories_id' => (int) $itilcategoriesId]
      );
   }

   public function updateLastAssignmentIndexGrupo($itilcategoriesId, $index): void {
      $groupId = $this->getGroupByItilCategory($itilcategoriesId);
      if ($groupId === false) {
         PluginAtribuicaointeligenteLogger::addWarning('Grupo nao encontrado para atualizar indice do rodizio', [
            'itilcategories_id' => (int) $itilcategoriesId,
         ]);
         return;
      }

      $iterator = $this->DB->request([
         'SELECT' => [$this->assignmentTable . '.id'],
         'FROM'   => $this->assignmentTable,
         'INNER JOIN' => [
            'glpi_itilcategories' => [
               'ON' => [
                  $this->assignmentTable => 'itilcategories_id',
                  'glpi_itilcategories'  => 'id',
               ],
            ],
         ],
         'WHERE' => [
            $this->assignmentTable . '.is_active' => 1,
            'glpi_itilcategories.groups_id'       => (int) $groupId,
         ],
      ]);

      foreach ($iterator as $row) {
         $this->DB->update(
            $this->assignmentTable,
            ['last_assignment_index' => (int) $index],
            ['id' => (int) $row['id']]
         );
      }
   }

   public function updateIsActive($itilcategoriesId, $isActive): void {
      $this->DB->update(
         $this->assignmentTable,
         ['is_active' => (int) $isActive],
         ['itilcategories_id' => (int) $itilcategoriesId]
      );
   }

   public function updateCategoryGroup($itilcategoriesId, $groupsId): bool {
      $category = new ITILCategory();
      if (!$category->getFromDB((int) $itilcategoriesId)) {
         PluginAtribuicaointeligenteLogger::addWarning('Categoria ITIL nao encontrada para atualizar grupo', [
            'itilcategories_id' => (int) $itilcategoriesId,
         ]);
         return false;
      }

      return (bool) $category->update([
         'id'        => (int) $itilcategoriesId,
         'groups_id' => (int) $groupsId,
      ]);
   }

   public function getLastAssignmentIndex($itilcategoriesId) {
      $iterator = $this->DB->request([
         'SELECT' => ['last_assignment_index'],
         'FROM'   => $this->assignmentTable,
         'WHERE'  => [
            'itilcategories_id' => (int) $itilcategoriesId,
            'is_active'         => 1,
         ],
         'LIMIT' => 1,
      ]);

      $row = $iterator->current();
      if (!$row) {
         return false;
      }

      return $row['last_assignment_index'];
   }

   public function getAll(): array {
      $assignTable = $this->assignmentTable;
      $iterator = $this->DB->request([
         'SELECT' => [
            "{$assignTable}.id",
            "{$assignTable}.itilcategories_id",
            'glpi_itilcategories.completename AS category_name',
            'glpi_itilcategories.groups_id',
            'glpi_groups.completename AS group_name',
            "{$assignTable}.is_active",
            new QueryExpression('COUNT(glpi_groups_users.users_id) AS num_group_members'),
         ],
         'FROM' => $assignTable,
         'INNER JOIN' => [
            'glpi_itilcategories' => [
               'ON' => [
                  $assignTable          => 'itilcategories_id',
                  'glpi_itilcategories' => 'id',
               ],
            ],
         ],
         'LEFT JOIN' => [
            'glpi_groups' => [
               'ON' => [
                  'glpi_itilcategories' => 'groups_id',
                  'glpi_groups'         => 'id',
               ],
            ],
            'glpi_groups_users' => [
               'ON' => [
                  'glpi_groups'       => 'id',
                  'glpi_groups_users' => 'groups_id',
               ],
            ],
         ],
         'GROUP' => [
            "{$assignTable}.id",
            "{$assignTable}.itilcategories_id",
            'glpi_itilcategories.completename',
            'glpi_itilcategories.groups_id',
            'glpi_groups.completename',
            "{$assignTable}.is_active",
         ],
      ]);

      $rows = [];
      foreach ($iterator as $row) {
         $row['num_group_members'] = (int) ($row['num_group_members'] ?? 0);
         $rows[] = $row;
      }

      return $rows;
   }
}
