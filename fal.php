<?php
/******************************************
 * get latest post
 * use in loop if ( is_latest() ) { stuff; }
 ******************************************/	
function is_latest() {
    global $post;
    $loop = get_posts( 'numberposts=1' );
    $latest = $loop[0]->ID; 
    return ( $post->ID == $latest ) ? true : false;
}	
	
/******************************************
 * get first post
 * use in loop if ( is_first() ) { stuff; }
 ******************************************/	
function is_first() {
    global $post;
    $loop = get_posts( 'numberposts=1&order=ASC' );
    $first = $loop[0]->ID; 
    return ( $post->ID == $first ) ? true : false;
}	
?>
