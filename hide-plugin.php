<?php
/**
* Hide plugin from WordPress Admin.
* 
* @howto: 
* - Change plugin slug to what you want within `$hidearr` separated by comma.
* eg. $hidearr = array('seo-by-rank-math/rank-math.php', users.php );
*
* - Change `$submenu['plugin-slug'][0]` to correct plugin slug. 
*/

// Remove plugin from plugin list.
add_action( 'pre_current_active_plugins', 'my_secret_plugin' );
function my_secret_plugin() {

  global $wp_list_table;

  $hidearr = array('seo-by-rank-math/rank-math.php');

  $myplugins = $wp_list_table->items;
  foreach ($myplugins as $key => $val) {
    if (in_array($key,$hidearr)) {
      unset($wp_list_table->items[$key]);
    }
  }
}

// Remove the main menu item together with the subpages using unset
add_action( 'admin_menu', 'remove_admin_menu_items', 999 );
function remove_admin_menu_items() {

  global $submenu;

  unset($submenu['rank-math'][0]); 
  unset($submenu['rank-math'][1]); 
  unset($submenu['rank-math'][2]); 
  unset($submenu['rank-math'][3]); 
  unset($submenu['rank-math'][4]); 
  unset($submenu['rank-math'][5]); 
  unset($submenu['rank-math'][6]); 
  unset($submenu['rank-math'][7]); 
  unset($submenu['rank-math'][8]); 
  unset($submenu['rank-math'][9]); 

  /** 
     // uncomment to debug & find array keys
     print('<pre>');
     print_r($submenu);
     print('<pre>');

      // we remove everything else displayed on the screen so that see only the admin menu items array
      die();
  */

}
?>
