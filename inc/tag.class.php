<?php
class PluginTagTag extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;
   
   const MNBSP = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

   public static function getTypeName($nb=1) {
      return _n('Tag', 'Tags', $nb, 'tag');
   }
   
   public function showForm($ID, $options = array()) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo '<table class="tab_cadre_fixe">';

      echo "<tr class='line0'><td><label for='name'>" . __('Name') . " <span class='red'>*</span></label></td>";
      echo "<td>";
      //Html::autocompletionTextField($this, "name");
      echo '<input type="text" id="name" name="name" value="'.$this->fields['name'].'" size="40" required>';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line0'><td><label for='type_de_tag'>" . _n('Tag type', 'Tag types', 1, 'tag') . "</label></td>";
      echo "<td>";
      $values = array('' => Dropdown::EMPTY_VALUE);

      $menus = Html::getMenuInfos();
      foreach ($menus as $key => $value) {
         if ($key != 'plugins' && $key != 'preference') {
            $values[$key] = $menus[$key]['title'];
         }
      }      
      Dropdown::showFromArray("type_menu", $values, array('value' => $this->fields['type_menu'],
                                                                        'width' => '50%'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td><label for='comment'>" . __('Description') . "</label></td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' cols='45' rows='2'>" . $this->fields['comment'] . "</textarea>";
      //Html::initEditorSystem('comment');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td><label>" . __('HTML color', 'tag') . "</label></td>"; 
      echo "<td>";
      //Note : create some bugs
      Html::showColorField('color', array('value' => $this->fields['color']));
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);

      return true;
   }
   
   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text collate utf8_unicode_ci,
                     `color` varchar(50) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }

      $migration->addField($table, 'type_menu', "VARCHAR(50) NOT NULL DEFAULT ''");
      $migration->addKey($table, 'type_menu');
      $migration->migrationOneTable($table);

      /*
      $query = "ALTER TABLE `$table`
                  ADD COLUMN `type_menu` VARCHAR(50) NOT NULL DEFAULT '' AFTER `color`,
                  ADD INDEX `type_menu` (`type_menu`);";
      $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      */
      
      return true;
   }

   public static function uninstall() {
      $query = "DELETE FROM glpi_logs WHERE itemtype_link='".__CLASS__."' OR itemtype = '".__CLASS__."'";
      $GLOBALS['DB']->query($query);
      
      $query = "DELETE FROM glpi_bookmarks WHERE itemtype='".__CLASS__."'";
      $GLOBALS['DB']->query($query);

      $query = "DELETE FROM glpi_bookmarks_users WHERE itemtype='".__CLASS__."'";
      $GLOBALS['DB']->query($query);
      
      $query = "DELETE FROM glpi_displaypreferences WHERE itemtype='".__CLASS__."' OR num=10500";
      $GLOBALS['DB']->query($query);
      
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   /**
    * Définition du nom de l'onglet
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      $tab = array();
      $tab[1] = __('Main');
      $tab[2] = _n('Associated item', 'Associated items', 2); //Note : can add nb_element here
      return $tab;
   }
   
   /**
    * Définition du contenu de l'onglet
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showForm($item->getID());
                  break;
               case 2 :
                  $tagitem = new PluginTagTagItem();
                  $tagitem->showForTag($item);
                  break;

            }
      }
      return true;
   }
   
   function defineTabs($options=array()) {
      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }
   
   public function cleanDBonPurge() {
      $GLOBALS['DB']->query("DELETE FROM `glpi_plugin_tag_tagitems`
                WHERE `plugin_tag_tags_id`=".$this->fields['id']);
   }

    /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
    **/
   function getLinkedItems() {
      $query = "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($GLOBALS['DB']->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      }
      return $tab;
   }
   
   // for massive actions
   function haveChildren() {
      $tagitems = new PluginTagTagItem();
      $data = $tagitems->find("plugin_tag_tags_id = ".$this->fields['id']);
      if (empty($data)) {
         return false;
      }
      return true;
   }
   
   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {
      return CommonDBTM::getSpecificMassiveActions($checkitem);
   }
   
   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
    **/
   function showSpecificMassiveActionsParameters($input=array()) {
      switch ($input['action']) {
         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }
   
   
   /**
    * @see CommonDBTM::doSpecificMassiveActions()
    **/
   function doSpecificMassiveActions($input=array()) {
      $res = array('ok'      => 0,
                  'ko'      => 0,
                  'noright' => 0);
      switch ($input['action']) {
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }
   
   function getSearchOptions() {
      $tab                       = array();
      
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['massiveaction']   = true;
      $tab[1]['datatype']        = 'itemlink';
      
      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'comment';
      $tab[2]['name']            = __('Description');
      $tab[2]['massiveaction']   = true;
      $tab[2]['datatype']        = 'string';
      
      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'id';
      $tab[3]['name']            = __('ID');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'number';

      $tab[4]['table']           = 'glpi_entities';
      $tab[4]['field']           = 'completename';
      $tab[4]['linkfield']       = 'entities_id';
      $tab[4]['name']            = __('Entity');
      $tab[4]['datatype']        = 'dropdown';
      
      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'is_recursive';
      $tab[5]['name']            = __('Child entities');
      $tab[5]['datatype']        = 'bool';
      
      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'type_menu';
      $tab[6]['searchtype']      = array('equals', 'notequals');
      $tab[6]['name']            = _n('Tag type', 'Tag types', 1, 'tag');
      $tab[6]['massiveaction']   = true;

      // For History tab (quick fix)
      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'color';
      $tab[7]['name']            = __('HTML color', 'tag');
      $tab[7]['searchtype']      = 'contains';

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      $options['value']   = $values[$field];
      switch ($field) {
         case 'type_menu':
            $tab = array('' => Dropdown::EMPTY_VALUE);

            $menus = Html::getMenuInfos();
            foreach ($menus as $key => $value) {
               if ($key != 'plugins' && $key != 'preference') {
                  $tab[$key] = $menus[$key]['title'];
               }
            }
            return Dropdown::showFromArray($name, $tab, $options);
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }
   
   /**
    * For fixed the issue #1 on Github
    */
   static function getItemtype($itemtype, $id) {
      // Specific for a webpage in GLPI
      if ($itemtype == 'rule.generic') {
         $rule = new Rule();
         $rule->getFromDB($id);
         return $rule->fields["sub_type"];
      }
      return $itemtype;
   }

   static function showMoreButton($rand) {
      global $CFG_GLPI;

      echo "&nbsp;<img title=\"".__s('Add')."\" alt=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
           "/pics/add_dropdown.png' style='cursor:pointer;margin-left:2px;'
           onClick=\"var w = window.open('".
            $CFG_GLPI['root_doc']."/plugins/tag/front/tag.form.php?popup=1&amp;rand=".$rand."', ".
            "'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
   }

   static function tagDropdownMultipleSearchKB() {

      $itemtype = 'KnowbaseItem';

      $obj = new $itemtype();

      //filter by type
      $where = "(type_menu = 'tools' OR type_menu = '') ";

      //TODO : ENTITIES
      //Session ou $_SESSION ?
      //$_SESSION['glpiactive_entity'] ET $_SESSION['glpiactive_entity_recursive']

      // restrict tag by entity if current object has entity
      //$obj->getFromDB($ID);
      //if (isset($obj->fields['entities_id'])) {
      //   $where .= getEntitiesRestrictRequest("AND", '', '', $obj->fields['entities_id'], true);
      //}

      // Propose only tags used in Knowbaseitem
      $tag_id_used = array();

      $tag_item = new PluginTagTagItem();
      foreach ($tag_item->find('itemtype = "Knowbaseitem"') as $found_item) {
         $tag_id_used[] = $found_item["plugin_tag_tags_id"];
      }

      if (!empty($tag_id_used)) {
         $where .= "AND id IN (".implode(',', $tag_id_used).") ";
      }

      $selected_id = isset($_REQUEST['_plugin_tag_tag_values']) ? explode(",", $_REQUEST['_plugin_tag_tag_values']) : array();

      //Note : code duplicated (with tagDropdownMultiple function)
      echo "<select data-placeholder='".__('Choose tags...', 'tag').self::MNBSP."' name='_plugin_tag_tag_values[]'
          id='tag_select' multiple class='chosen-select-no-results' style='width:80%;' >";

      $tag = new self();
      foreach ($tag->find($where, 'name') as $label) {
         $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
         $param .= 'data-color-option="'.$label['color'].'"';
         echo '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
      }
      echo "</select>";

      //TODO : Modifier target /glpi-090-git/glpi/front/knowbaseitem.php
   }
   
   static function tagDropdownMultiple($itemtype, $ID) {
      
      $itemtype = self::getItemtype($itemtype, $ID);
      $obj = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
        return;
      }

      $obj->getFromDB($ID);
      $sel_attr = $obj->canUpdateItem() ? '' : ' disabled ';

      $selected_id = array();
      $tag_item = new PluginTagTagItem();
      foreach ($tag_item->find('items_id='.$ID.' 
                                      AND itemtype="'.$itemtype.'"') as $found_item) {
         $selected_id[] = $found_item['plugin_tag_tags_id'];
      }

      //filter by type
      $menu_name = PluginTagTagItem::getMenuNameByItemtype($obj->getType());
      $where = ($menu_name == '') ? "1=1 " : "type_menu = '".$menu_name."' OR type_menu = '' ";

      // restrict tag by entity if current object has entity
      if (isset($obj->fields['entities_id'])) {
         $field = $obj->getType() == 'Entity' ? 'id' : 'entities_id';
         $where .= getEntitiesRestrictRequest("AND", '', '', $obj->fields[$field], true);
      }

      echo "<select data-placeholder='".__('Choose tags...', 'tag').self::MNBSP."' name='_plugin_tag_tag_values[]'
          id='tag_select' multiple class='chosen-select-no-results' ".$sel_attr." style='width:80%;' >";

      $tag = new self();
      foreach ($tag->find($where, 'name') as $label) {
         $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
         $param .= 'data-color-option="'.$label['color'].'"';
         echo '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
      }
      echo "</select>";

      if (self::canCreate()) {
         $rand = mt_rand();

         /*
         echo "<script type='text/javascript'>\n
            window.updateTagSelectResults_".$rand." = function () {
               
            }
         </script>";
         */

         // Show '+' button :
         self::showMoreButton($rand);
      }
   }
}
