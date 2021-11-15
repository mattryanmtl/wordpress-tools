<?php
/**
* Plugin Name: WordPress Export to JSON
* Plugin URI: https://program-fuse.ca
* Description: Export all WordPress posts, pages, comments, tags, commments and users to a JSON file.
* Author: Program Fuse
* Author URI: https://program-fuse.ca
* Version: 1.0
**/


/*
*	Usage:
*		1. Create a new page or post in your WordPress site.
*		2. Add the shortcode [wordpress_export_to_json] to the page.
*		3. Run the page.
*		4. Check your "/wp-content" folder for a file "export.json".
*		If the file did not create, try first creating a blank file "export.json"
*		(your site may not have permission to create file in the directory, but it may be able to update).
*
*	You will now have a JSON formatted file with all the important contents of your core WordPress site.
*/
add_shortcode('wordpress_export_to_json', 'wordpress_export_to_json_handler');





/**
 * The main plug-in handler. Processing starts here.
 */
function wordpress_export_to_json_handler($atts = [], $content = null)
{
	$results = [];

	echo "<p>Starting export to JSON.</p>";



	//-- Posts
	$posts = get_posts_by_type("post", "publish");
	$results["posts_publish"] = add_tags_and_categories_to_posts($posts);

	$posts = get_posts_by_type("post", "private");
	$results["posts_private"] = add_tags_and_categories_to_posts($posts);

	$posts = get_posts_by_type("post", "draft");
	$results["posts_draft"] = add_tags_and_categories_to_posts($posts);



	//-- Pages
	$posts = get_posts_by_type("page", "publish");
	$results["pages_publish"] = add_tags_and_categories_to_posts($posts);

	$posts = get_posts_by_type("page", "private");
	$results["pages_private"] = add_tags_and_categories_to_posts($posts);

	$posts = get_posts_by_type("page", "draft");
	$results["pages_draft"] = add_tags_and_categories_to_posts($posts);



	//-- Attachments (media)
	$posts = get_posts_by_type("attachment", "inherit");
	$results["attachments"] = add_tags_and_categories_to_posts($posts);



	//-- Comments (only approved)
	$comments = get_all_comments();
	$results["comments"] = $comments;



	//-- Categories
	$categories = get_categories();
	$category_results = [];
	foreach ($categories as $category)
	{
		$cat["id"] = $category->term_id;
		$cat["name"] = $category->name;
		$cat["slug"] = $category->slug;

		array_push($category_results, $cat);
	}
	$results["categories"] = $category_results;



	//-- Tags
	$tags = get_tags();
	$tag_results = [];
	foreach ($tags as $tag)
	{
		$t["id"] = $tag->term_id;
		$t["name"] = $tag->name;
		$t["slug"] = $tag->slug;

		array_push($tag_results, $t);
	}
	$results["categories"] = $tag_results;



	//-- Users
	$users = get_all_users();
	$results["users"] = $users;



	//-- Convert the results array to JSON string.
	$json = wp_json_encode($results);



	//-- Write to /wp-content/export.json
	write_to_json_file($json);



	echo "<p>Export to JSON complete.</p>";
}





/**
 * Helper function. Add full Tag and Category information to each post in a set.
 * Note: Array keys are prefixed with cc_ to avoid any key naming collisions.
 * 
 * @param array $posts Array of posts to attach category and tag information to.
 * 
 * @return array The array of posts with tags and categories included.
 */
function add_tags_and_categories_to_posts($posts)
{
	$result = [];

	foreach ($posts as $post)
	{
		$post_categories = wp_get_post_categories($post["ID"], ['fields' => 'all_with_object_id']);
		$post["cc_categories"] = $post_categories;
		
		$post_tags = wp_get_post_tags($post["ID"]);
		$post["cc_tags"] = $post_tags;

		array_push($result, $post);
	}

	return $result;
}





/**
 * Return all posts by type and optionally status.
 * 
 * @param string $post_type The 'post_type' to return.
 * @param string $post_status The 'post_status' to return. I think it defaults to 'publish' if left blank.
 * 
 * @return array Array of WP_Post objects converted to associative arrays.
 */
function get_posts_by_type($post_type, $post_status = '')
{
    $args = array(
        'post_type'			=> $post_type,
        'orderby'    		=> 'ID',
        'post_status' 		=> $post_status,
        'order'    			=> 'DESC',
        'posts_per_page'	 => -1 // this will retrive all the post that is published 
    );
	$query_results = new \WP_Query( $args );

	$posts = $query_results->posts;

	$results = [];
	foreach ($posts as $post)
	{
		array_push($results, $post->to_array());
	}

	return $results;
}





/**
 * Return all 'approved' comments (from what I can tell from implementation).
 * 
 * @return array Array of WP_Comment objects converted to associative arrays.
 */
function get_all_comments()
{
	$comments = get_comments( );

	$results = [];
	foreach ($comments as $comment)
	{
		array_push($results, $comment->to_array());
	}

	return $results;
}





/**
 * Return all users.
 * 
 * @return array Array of WP_User objects converted to associative arrays.
 */
function get_all_users()
{
	$users = get_users();

	$results = [];
	foreach ($users as $user)
	{
		array_push($results, $user->to_array());
	}

	return $results;
}





/**
 * Case-insensitive check if a value is in the array.
 * (Source: https://gist.github.com/sepehr/6351397)
 * 
 * @return bool True if the $check_value is found in $array, otherwise False.
 */
function in_array_case_insensitive($array, $check_value)
{
	return in_array(strtolower($check_value), array_map('strtolower', $array));
}





/**
 * Write the export.json file to the wp-content directory.
 * File will be created if it does not exist.
 * Content will be replaced if the file already exists.
 * 
 * @param string $json The JSON formatted string to write into the file.
 * 
 * @return void
 */
function write_to_json_file( $json )
{
    $myfile = fopen(WP_CONTENT_DIR . "/export.json", "w+");
        
    fwrite($myfile, $json);
    fclose($myfile);
}
?>
