<?php
/**
 * Aba de permissoes do plugin em Perfis.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteProfile extends Profile {

   public static $rightname = 'profile';

   public static function getTable($classname = null) {
      return 'glpi_profiles';
   }

   public static function getAllRights(): array {
      return [
         [
            'itemtype' => PluginAtribuicaointeligenteConfig::class,
            'label'    => __('Administrar Atribuição Inteligente', 'atribuicaointeligente'),
            'field'    => PluginAtribuicaointeligenteConfig::RIGHT_CONFIG,
         ],
      ];
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item instanceof Profile && $item->getID()) {
         return self::createTabEntry(__('Atribuição Inteligente', 'atribuicaointeligente'), 0, $item::getType(), 'ti ti-user-check');
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item instanceof Profile) {
         if (Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
            self::addDefaultProfileInfos($item->getID(), [
               PluginAtribuicaointeligenteConfig::RIGHT_CONFIG => 0,
            ]);
         }

         $profile = new self();
         $profile->showFormAtribuicaoInteligente((int) $item->getID());
      }
      return true;
   }

   public static function addDefaultProfileInfos($profiles_id, array $rights, bool $upgrade = false): void {
      global $DB;

      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'rights'],
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => [
               'profiles_id' => $profiles_id,
               'name'        => $right,
            ],
            'LIMIT'  => 1,
         ]);
         $row = $iterator->current();
         $sessionValue = (int) $value;

         if (!$row) {
            $profileRight->add([
               'profiles_id' => $profiles_id,
               'name'        => $right,
               'rights'      => $value,
            ]);
         } elseif ($upgrade) {
            $currentRights = (int) ($row['rights'] ?? 0);
            $newRights = $currentRights | (int) $value;
            if ($newRights !== $currentRights) {
               $DB->update('glpi_profilerights', ['rights' => $newRights], [
                  'id' => (int) $row['id'],
               ]);
            }
            $sessionValue = $newRights;
         } else {
            $sessionValue = (int) ($row['rights'] ?? 0);
         }

         if (isset($_SESSION['glpiactiveprofile']['id'])
            && (int) $_SESSION['glpiactiveprofile']['id'] === (int) $profiles_id
         ) {
            $_SESSION['glpiactiveprofile'][$right] = $sessionValue;
            unset($_SESSION['glpimenu']);
         }
      }
   }

   public static function grantCurrentProfileAccess(): void {
      if (!isset($_SESSION['glpiactiveprofile']['id'])) {
         return;
      }

      self::addDefaultProfileInfos((int) $_SESSION['glpiactiveprofile']['id'], [
         PluginAtribuicaointeligenteConfig::RIGHT_CONFIG => ALLSTANDARDRIGHT,
      ], true);
   }

   public static function createFirstAccess($profiles_id): void {
      self::addDefaultProfileInfos($profiles_id, [
         PluginAtribuicaointeligenteConfig::RIGHT_CONFIG => ALLSTANDARDRIGHT,
      ], true);
   }

   public function showFormAtribuicaoInteligente(int $profiles_id): bool {
      if (!$this->can($profiles_id, READ)) {
         return false;
      }

      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

      echo "<div class='firstbloc'>";
      if ($canedit) {
         $profile = new Profile();
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $this->displayRightsChoiceMatrix(self::getAllRights(), [
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
         'title'         => __('Permissões', 'atribuicaointeligente'),
      ]);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";
      return true;
   }
}
