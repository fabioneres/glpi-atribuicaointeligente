<?php
/**
 * Hooks de categorias ITIL.
 *
 * @author Fabio Neres
 * @license GPLv3+
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginAtribuicaointeligenteITILCategoryHookHandler {

   public static function itemAdded(CommonDBTM $item) {
      if ($item->getType() !== 'ITILCategory') {
         return $item;
      }

      $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
      $entity->insertItilCategory((int) $item->fields['id']);
      return $item;
   }

   public static function itemDeleted(CommonDBTM $item) {
      if ($item->getType() !== 'ITILCategory') {
         return $item;
      }

      $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
      $entity->updateIsActive((int) $item->fields['id'], 0);
      return $item;
   }

   public static function itemPurged(CommonDBTM $item) {
      if ($item->getType() !== 'ITILCategory') {
         return $item;
      }

      $entity = new PluginAtribuicaointeligenteAssignmentsEntity();
      $entity->deleteItilCategory((int) $item->fields['id']);
      return $item;
   }
}
