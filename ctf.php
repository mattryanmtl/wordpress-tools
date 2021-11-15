<?php
/**
* Plugin Name: WordPress Export to JSON
* Plugin URI: https://program-fuse.ca
* Description: Export all WordPress posts, pages, comments, tags, commments and users to a JSON file.
* Author: Program Fuse
* Author URI: https://program-fuse.ca
* Version: 1.0
 */

/* 
After installing and activating this plugin, you will see 'Products' in the Admin side menu, with its own 'Product Type' (taxonomy) settings.
The Product add/edit forms should have custom Price and Technical Specifiations (meta box) fields.
The Products page list columns includes the price and a list of custom product types assigned to them.
The Products will be exposed to the REST API at ; /wp-json/wp/v2/acme_product and will contain the data from the extra fields.
*/

// Create our new post type: 'acme_product'
add_action( 'init', 'create_product_post_type' );

function create_product_post_type() {
  register_post_type( 'acme_product',
    array(
        'labels' => array(
        'name' => __( 'Products' ),
        'singular_name' => __( 'Product' ),
        'add_new' => 'Add Product',
        'all_items' => 'All Products',
        'add_new_item' => 'Add Product',
        'edit_item' => 'Edit Product',
        'new_item' => 'New Product',
        'view_item' => 'View Product',
        'search_items' => 'Search Products',
        'not_found' => 'No products found',
        'not_found_in_trash' => 'No products found in trash'
      ),
      'public' => true,
      'has_archive' => true,
      'query_var' => true,
      'rewrite' => true,
      'hierarchical' => false,
      'supports' => array( 'title', 'editor', 'thumbnail' )
    )
  );

  // Adds ability to create some taxonomies for our products
  /* eg: Toys, Games, etc. */
  register_taxonomy('product_type', 
    array('acme_product'), 
      array(
        'labels' => array(
      'name' => __( 'Product Types' ),
      'singular_name' => __( 'Product Type' ),
      'add_new' => 'Add Product Type',
      'all_items' => 'All Product Types',
      'add_new_item' => 'Add Product Type',
      'edit_item' => 'Edit Product Type',
      'new_item' => 'New Product Type',
      'view_item' => 'View Product Type',
      'search_items' => 'Search Product Types',
      'not_found' => 'No products types found',
      'not_found_in_trash' => 'No product types found in trash'
      ),
        'singular_label' => 'Product Type', 
        'add_new' => 'Add New Product Type',
        'hierarchical' => true, 
        'rewrite' => true
      )
  );

}



// Add our custom post type products to the REST API
add_action( 'init', 'add_product_rest_support', 25 );

function add_product_rest_support() {
  global $wp_post_types;
 
  $post_type_name = 'acme_product';
  if( isset( $wp_post_types[ $post_type_name ] ) ) {
    $wp_post_types[$post_type_name]->show_in_rest = true;
    $wp_post_types[$post_type_name]->rest_base = $post_type_name;
    $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
  }
}



// Add custom fields and meta boxes - eg: Price, Spec, etc.
add_action('admin_init', 'add_product_meta_boxes'); 
function add_product_meta_boxes(){
  // Show on side of admin add/edit form
  add_meta_box('product_price-meta', 'Product Price', 'meta_box_product_price', 'acme_product', 'side', 'low');
  // Show below normal admin add/edit form
  add_meta_box('product_spec-meta', 'Technical Specifications', 'meta_box_product_spec', 'acme_product', 'normal', 'low');

}

// callbacks to create the meta boxes
function meta_box_product_price(){
  global $post;
  $custom = get_post_custom($post->ID);
  $product_price = $custom['product_price'][0];
  ?>
  <input name='product_price' value='<?php echo $product_price; ?>' />
  <?php
}

function meta_box_product_spec(){
  global $post;
  $custom = get_post_custom($post->ID);
  $product_spec = $custom['product_spec'][0];
  ?>
  <p><textarea style='width:99%;' cols='500' rows='3' name='product_spec'><?php echo $product_spec; ?></textarea></p>
  <?php
}

// Saves/updates data from our new custom meta boxes
add_action('save_post', 'save_product_info');

function save_product_info(){
  global $post;
  update_post_meta($post->ID, 'product_price', $_POST['product_price']);  // Sanitisation recommended!!
  update_post_meta($post->ID, 'product_spec', $_POST['product_spec']);    // Sanitisation recommended!!
}


// Now expose the custom post types to the REST API
add_action('rest_api_init', 'register_product_rest');

function register_product_rest() {
  register_rest_field( 'acme_product',
    'product_price',
    array(
      'get_callback'    => 'get_product_cb',
      'schema' => array(
          'description' => __( 'The product price' ),
          'type' => 'integer',
          'context' => array('view', 'edit')
      )
    )
  );
  register_rest_field( 'acme_product',
    'product_spec',
    array(
       'get_callback'    => 'get_product_cb',
       'schema' => array(
          'description' => __( 'The product technical specifications' ),
          'type' => 'string',
          'context' => array('view', 'edit')
      )
    )
  );
}
function get_product_cb( $object, $field_name, $request ) {
  return get_post_meta( $object[ 'id' ], $field_name )[0];
}

// Finally, let's add some extra columns to the admin page for our Products list

// NB: Uses: manage_$post_type_posts_columns  - So replace $post_type with 'acme_product'
add_filter('manage_acme_product_posts_columns', 'product_columns');
function product_columns($columns){
  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => 'Product Name',
    'product_price' => 'Price',
    'product_type' => 'Type',
  );
 
  return $columns;
}

// Use: manage_{$post_type}_posts_custom_column 
add_action('manage_acme_product_posts_custom_column',  'products_custom_columns');

function products_custom_columns($column){
  global $post;
 
  switch ($column) {
    case 'product_price':
      $custom = get_post_custom();
      echo $custom['product_price'][0];
      break;
    case 'product_type':
      echo get_the_term_list($post->ID, 'product_type', '', ', ','');
      break;
  }
}

?>
