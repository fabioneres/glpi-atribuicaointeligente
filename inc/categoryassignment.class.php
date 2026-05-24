<?php
/**
 * Itemtype Search para regras de categoria.
 *
 * Fork baseado no CategoryAssignment do SmartAssign/NexTool.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteCategoryAssignment extends CommonDBTM {

   public $dont_pass_handled = true;

   public static $rightname = PluginAtribuicaointeligenteConfig::RIGHT_CONFIG;

   public static function getTable($classname = null) {
      return PluginAtribuicaointeligenteConfig::getAssignmentsTable();
   }

   public static function getTypeName($nb = 0) {
      return _n('Atribuição de categoria', 'Atribuições de categorias', $nb, 'atribuicaointeligente');
   }

   public static function getIcon() {
      return 'ti ti-tag';
   }

   public static function getSearchURL($full = true) {
      return Plugin::getWebDir('atribuicaointeligente') . '/front/categories.php';
   }

   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => __('Categorias ITIL - Atribuição Inteligente', 'atribuicaointeligente'),
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => 'glpi_itilcategories',
         'field'         => 'completename',
         'name'          => __('Categoria ITIL', 'atribuicaointeligente'),
         'searchtype'    => ['equals', 'notequals', 'contains'],
         'datatype'      => 'specific',
         'itemtype'      => 'ITILCategory',
         'massiveaction' => false,
         'joinparams'    => [
            'jointype'  => '',
            'linkfield' => 'itilcategories_id',
         ],
         'forcegroupby' => false,
      ];

      $tab[] = [
         'id'            => '80',
         'table'         => 'glpi_entities',
         'field'         => 'completename',
         'name'          => __('Entidade', 'atribuicaointeligente'),
         'searchtype'    => ['equals', 'notequals', 'contains'],
         'datatype'      => 'itemlink',
         'itemtype'      => 'Entity',
         'massiveaction' => false,
         'joinparams'    => [
            'beforejoin' => [
               'table'      => 'glpi_itilcategories',
               'joinparams' => [
                  'jointype'  => '',
                  'linkfield' => 'itilcategories_id',
               ],
            ],
            'jointype'  => '',
            'linkfield' => 'entities_id',
         ],
         'forcegroupby' => true,
         'usehaving'    => true,
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => 'glpi_groups',
         'field'         => 'id',
         'name'          => __('Grupo', 'atribuicaointeligente'),
         'searchtype'    => ['equals', 'notequals'],
         'datatype'      => 'dropdown',
         'itemtype'      => 'Group',
         'massiveaction' => false,
         'joinparams'    => [
            'beforejoin' => [
               'table'      => 'glpi_itilcategories',
               'joinparams' => [
                  'jointype'  => '',
                  'linkfield' => 'itilcategories_id',
               ],
            ],
            'jointype'  => '',
            'linkfield' => 'groups_id',
         ],
      ];

      $tab[] = [
         'id'         => '5',
         'table'      => $this->getTable(),
         'field'      => 'is_active',
         'name'       => __('Ativo', 'atribuicaointeligente'),
         'searchtype' => ['equals', 'notequals'],
         'datatype'   => 'bool',
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'searchtype'    => ['equals', 'notequals', 'lessthan', 'morethan'],
         'datatype'      => 'number',
         'massiveaction' => false,
      ];

      return $tab;
   }

   public static function ensureDisplayPreferences(): void {
      global $DB;

      $itemtype = self::class;
      $dPref = new DisplayPreference();
      $found = $dPref->find(['itemtype' => $itemtype, 'users_id' => 0]);
      if (count($found) > 0) {
         return;
      }

      foreach ([2 => 1, 80 => 2, 4 => 3, 5 => 4, 1 => 5] as $num => $rank) {
         $DB->insert(DisplayPreference::getTable(), [
            'itemtype' => $itemtype,
            'num'      => $num,
            'rank'     => $rank,
            'users_id' => 0,
         ]);
      }
   }

   public function getName($options = []) {
      global $DB;
      if (empty($this->fields['itilcategories_id'])) {
         return __('Sem categoria', 'atribuicaointeligente');
      }

      $iterator = $DB->request([
         'SELECT' => ['completename'],
         'FROM'   => 'glpi_itilcategories',
         'WHERE'  => ['id' => (int) $this->fields['itilcategories_id']],
         'LIMIT'  => 1,
      ]);
      $row = $iterator->current();
      return $row ? $row['completename'] : __('Categoria #', 'atribuicaointeligente') . $this->fields['itilcategories_id'];
   }

   public static function getMassiveActions(string $type): array {
      if ($type !== self::class || !PluginAtribuicaointeligenteConfig::canUpdateConfig()) {
         return [];
      }

      return [
         self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'SetResponsibleGroup'
            => '<i class="ti ti-users"></i> ' . __('Alterar grupo responsável', 'atribuicaointeligente'),
      ];
   }

   public static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() !== 'SetResponsibleGroup') {
         return false;
      }

      echo '<div class="mb-3">';
      echo '<label for="groups_id" class="form-label">' . __('Grupo responsável', 'atribuicaointeligente') . '</label>';
      Dropdown::show('Group', [
         'name'                => 'groups_id',
         'value'               => -1,
         'display_emptychoice' => true,
         'condition'           => ['is_usergroup' => 1],
         'width'               => '100%',
      ]);
      echo '</div>';

      echo Html::submit(_sx('button', 'Post'), [
         'id'    => 'atribuicaointeligentemassivegroup',
         'class' => 'btn btn-primary',
      ]);
      return true;
   }

   public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
      if ($ma->getAction() !== 'SetResponsibleGroup') {
         return;
      }

      $itemtype = $item->getType();
      if (!PluginAtribuicaointeligenteConfig::canUpdateConfig()) {
         Session::addMessageAfterRedirect(__('Você não tem permissão para executar esta ação.', 'atribuicaointeligente'), false, ERROR);
         foreach ($ids as $key => $val) {
            if ($val) {
               $ma->itemDone($itemtype, (int) $key, MassiveAction::ACTION_NORIGHT);
            }
         }
         return;
      }

      $groupsId = isset($_POST['groups_id']) ? (int) $_POST['groups_id'] : -1;
      if ($groupsId < 0) {
         Session::addMessageAfterRedirect(__('Selecione um grupo ou a opção vazia para remover.', 'atribuicaointeligente'), false, ERROR);
         foreach ($ids as $key => $val) {
            if ($val) {
               $ma->itemDone($itemtype, (int) $key, MassiveAction::ACTION_KO);
            }
         }
         return;
      }

      $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
      foreach ($ids as $key => $val) {
         if (!$val) {
            continue;
         }

         $assignment = new self();
         if (!$assignment->getFromDB((int) $key)) {
            $ma->itemDone($itemtype, (int) $key, MassiveAction::ACTION_KO);
            continue;
         }

         $itilcategoriesId = (int) ($assignment->fields['itilcategories_id'] ?? 0);
         if ($itilcategoriesId > 0 && $entity->updateCategoryGroup($itilcategoriesId, $groupsId)) {
            $ma->itemDone($itemtype, (int) $key, MassiveAction::ACTION_OK);
         } else {
            $ma->itemDone($itemtype, (int) $key, MassiveAction::ACTION_KO);
         }
      }
   }

   public static function massiveActionsFieldsDisplay(array $options = []): bool {
      if (($options['itemtype'] ?? '') !== self::class) {
         return false;
      }

      $opt = $options['options'] ?? [];
      if (($opt['table'] ?? '') !== self::getTable() || ($opt['field'] ?? '') !== 'is_active') {
         return false;
      }

      Dropdown::showFromArray('is_active', [
         0 => __('Não', 'atribuicaointeligente'),
         1 => __('Sim', 'atribuicaointeligente'),
      ], ['display' => true, 'value' => -1]);

      return true;
   }

   public static function giveItem($itemtype, $ID, $data, $num) {
      if ($itemtype !== self::class) {
         return false;
      }

      $rawVal = self::extractRawValue((int) $ID, (array) $data, $num);

      switch ((int) $ID) {
         case 1:
            return (string) ($rawVal ?? ($data['id'] ?? '-'));

         case 2:
            $catId = self::extractItilCategoryId((array) $data);
            $label = $rawVal ?: ($catId > 0 ? Dropdown::getDropdownName('glpi_itilcategories', $catId) : '-');
            $label = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
            if ($catId > 0) {
               global $CFG_GLPI;
               $url = rtrim((string) ($CFG_GLPI['root_doc'] ?? ''), '/') . '/front/itilcategory.form.php?id=' . $catId;
               return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
            }
            return $label;

         case 80:
            if ($rawVal === null || $rawVal === '') {
               return '-';
            }
            $name = (string) $rawVal;
            if (strpos($name, '$#$') !== false) {
               $parts = explode('$#$', $name, 2);
               $name = (string) ($parts[0] ?? '');
            } elseif (is_numeric($rawVal)) {
               $name = Dropdown::getDropdownName('glpi_entities', (int) $rawVal);
            }
            return htmlspecialchars($name !== '' ? $name : '-', ENT_QUOTES, 'UTF-8');

         case 4:
            if ($rawVal === null || $rawVal === '') {
               return '<em class="text-muted">' . __('Sem grupo', 'atribuicaointeligente') . '</em>';
            }
            return is_numeric($rawVal)
               ? htmlspecialchars(Dropdown::getDropdownName('glpi_groups', (int) $rawVal), ENT_QUOTES, 'UTF-8')
               : htmlspecialchars((string) $rawVal, ENT_QUOTES, 'UTF-8');

         case 5:
            return (int) $rawVal === 1
               ? '<span class="badge bg-success text-white">' . __('Sim', 'atribuicaointeligente') . '</span>'
               : '<span class="badge bg-secondary text-white">' . __('Não', 'atribuicaointeligente') . '</span>';
      }

      return false;
   }

   private static function extractRawValue(int $ID, array $data, $num) {
      if (isset($data['raw']) && is_array($data['raw'])) {
         foreach ($data['raw'] as $key => $value) {
            if (strpos((string) $key, (string) $ID) !== false || $key === 'ITEM_' . $ID) {
               return $value;
            }
         }
      }

      if (isset($data[$num]) && is_array($data[$num]) && isset($data[$num][0])) {
         return $data[$num][0]['name']
            ?? $data[$num][0]['completename']
            ?? $data[$num][0]['id']
            ?? $data[$num][0]['is_active']
            ?? null;
      }

      return null;
   }

   private static function extractItilCategoryId(array $data): int {
      if (isset($data['itilcategories_id']) && is_numeric($data['itilcategories_id'])) {
         return (int) $data['itilcategories_id'];
      }

      if (isset($data['raw']) && is_array($data['raw'])) {
         foreach ($data['raw'] as $key => $value) {
            if (!is_numeric($value)) {
               continue;
            }
            $key = strtolower((string) $key);
            if (strpos($key, 'itilcategories_id') !== false || strpos($key, 'glpi_itilcategories.id') !== false) {
               return (int) $value;
            }
         }
      }

      $assignmentId = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : 0;
      if ($assignmentId <= 0) {
         return 0;
      }

      global $DB;
      $iterator = $DB->request([
         'SELECT' => ['itilcategories_id'],
         'FROM'   => self::getTable(),
         'WHERE'  => ['id' => $assignmentId],
         'LIMIT'  => 1,
      ]);
      $row = $iterator->current();
      return $row ? (int) $row['itilcategories_id'] : 0;
   }
}
