<?php 
// Custom loop for featured items in the slider on the front page. 
// Slider will show posts in the 'Featured' category

// setup the custom fields that are for the Title and Image fields that are used to override the default ones if the user wants to add them. They show up in the Edit screen for the posts/page etc. I created them using the Advanced Custom Fields plugin and then exported the PHP code so you don't need to have that Plugin enabled.


if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_featured-posts-slides',
		'title' => 'Featured posts slides',
		'fields' => array (
			array (
				'key' => 'field_5270201a54e4a',
				'label' => 'Image',
				'name' => 'carousel-image',
				'type' => 'image',
				'instructions' => 'Upload an image here if you don\'t want to use the Featured Image',
				'save_format' => 'id',
				'preview_size' => 'home-slider',
				'library' => 'all',
			),
			array (
				'key' => 'field_527020aa54e4b',
				'label' => 'Title',
				'name' => 'carousel-title',
				'type' => 'text',
				'instructions' => 'Put a short title here if you don\'t want the image slider to use the Post/Page Title',
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'formatting' => 'none',
				'maxlength' => 83,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'post',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'page',
					'order_no' => 0,
					'group_no' => 1,
				),
			),
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'event',
					'order_no' => 0,
					'group_no' => 2,
				),
			),
			// If you want to enable this functionality for other content types then copy and paste an array above and change the value and group_no accordignly
		),
		'options' => array (
			'position' => 'side',
			'layout' => 'default',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
	));
}



//Add a WordPress image size if the slider image size isn't already registered in your WordPress setup. Change the size accordingly here. Mine is set to 750x410 and will autocrop.

add_image_size( 'home-slider', '750', '410', true );

//Wrap the whole lot in a shortcode so you can just type [carousel] wherever you want it - in a Text Widget for example

function carousel_shortcode(){

$number = 0; 
$q =  query_posts( array ( 'category_name' => 'featured', 'posts_per_page' => 6, 'post_type' => array ( 'post', 'page','event') ) );
// Support for posts and pages and events - add others if necessary if you added more custom post types above, and change max number of slides to show if you want
 if(have_posts()):
 	 	
 	//IF THERE IS NO THUMBNAIL NOR CAROUSEL-IMAGE THEN LEAVE IT OUT OF THE LOOP COMPLETELY - OR ELSE IT WILL SHOW EMPTY SLIDES
 	//IF CAROUSEL-IMAGE EXISTS, THEN USE IT, OTHERWISE USE THUMBNAIL('home-slider).
 	
 	//IF CAROUSEL-TITLE EXISTS, USE THAT AS THE TITLE, OTHERWISE USE the_title()
?>
<div id="myCarousel" class="carousel slide">
  <ol class="carousel-indicators">
    <?php while(have_posts()): the_post(); ?>
     <?php
	  $postIdl = get_the_ID();
	  $has_fetured_imagel =  has_post_thumbnail( $postIdl );	 
	  $carousel_imagel = get_field('carousel-image');	
	  $carousel_image_urll = $carousel_imagel['url'];
	  if($carousel_image_urll != '' || $has_fetured_imagel) 
	  {
     ?>
    <li data-target="#myCarousel" data-slide-to="<?php echo $number++; ?>"></li>
    <?php } endwhile; ?>
  </ol>

  <!-- Carousel items -->
  <div class="carousel-inner">
    <?php while(have_posts()): the_post(); ?>
     <?php
	  $slider_image = '';
	  $slider_title = '';
	  $postId = get_the_ID();
	  $has_fetured_image =  has_post_thumbnail( $postId );
	  $carousel_title = get_post_meta($postId, 'carousel-title', $single = true);
	  $carousel_image = get_field('carousel-image');	
	  $carousel_image_url = $carousel_image['url'];
	  if($carousel_title != '')
	  {
	  	$slider_title = $carousel_title;
	  }
	  else
	  {
	     $slider_title = get_the_title();
	  }
	   if($carousel_image_url != '')
	  {
	  	$attachment_id = get_field('carousel-image');
$size = 'home-slider'; 
 

		$thumb = wp_get_attachment_image_src( get_field('carousel-image'), 'home-slider' );
		$url = $thumb['0'];
		$slider_image = '<img src="'.$url.'">';
	  }
	  else
	  {
	     $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'home-slider' );
$url = $thumb['0'];  
		$slider_image = '<img src="'.$url.'">';
	  }
	  if($carousel_image_url != '' || $has_fetured_image) 
	  {
     ?>   
    <div class="item">
      <img src="<?php echo $url; ?>">
      <a href="<?php the_permalink(); ?>"><div class="carousel-caption">
        <h4><?php echo $slider_title; ?></h4>
        
      </div></a>
    </div>
    <?php  } ?>
    <?php endwhile; ?>
  </div>

  <!-- Carousel nav - these chevrons require the Font Awesome library to be loaded. It often is with Bootstrap themes -->
  
  <a class="carousel-control left" href="#myCarousel" data-slide="prev"><i class="icon-chevron-left icon-2x"></i></a>
  <a class="carousel-control right" href="#myCarousel" data-slide="next"><i class="icon-chevron-right icon-2x"></i></a>
</div>
<?php endif; wp_reset_query();
}

add_shortcode('carousel', 'carousel_shortcode');  



//Add js to footer. Change the interval to alter how long the slides display - 4000 equals 4 seconds.
function biscuits_carousel() { ?>

<script>
jQuery(document).ready(function($){
  $("#myCarousel .carousel-indicators li:first").addClass("active");
  $("#myCarousel .carousel-inner .item:first").addClass("active");
   $("#myCarousel").carousel({
  interval: 4000
  })
});

</script>
<?php
}
	add_action('wp_footer', 'biscuits_carousel');
