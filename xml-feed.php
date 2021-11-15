<?php
/*
* Plugin Name: WordPress XML Feed
* Plugin URI: https://program-fuse.ca
* Description: A custom mobile feed plugin for WordPress 
* Author: Program Fuse
* Author URI: https://program-fuse.ca
* Version: 1.0
*/

class UddMobileFeed{
	private static $instance;
	private function __construct(){
		$this->setupActions();
	}
	public static function getInstance(){
		if ( !isset(self::$instance) ){
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}
	public function __clone(){
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}
	private function setupActions(){
		add_action('init', array($this, 'addFeed'));
		add_action('init', array($this, 'registerImageSizes'));
	}
	public function registerImageSizes(){
		add_image_size('mobile-small', 120, 80, true);
		add_image_size('mobile-normal', 300, 200, true);
	}
	public function displayNews(){
		header('Content-Type: application/xml; charset=utf-8');
		$xml = new DOMDocument();
		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;
		$xml->encoding = 'utf-8';
		$root = $xml->appendChild( $xml->createElement('calendario') );
		$news = new WP_Query(array(
			'posts_per_page' => 30,
			'orderby' => 'date',
			'order' => 'DESC',
			'tax_query' => array(
				array( 'taxonomy' => 'post_tag', 'terms' => array('alumnos-ccp', 'alumnos-scl', 'exalumnos', 'investigacion-2', 'newsletters'), 'field' => 'slug', 'operator' => 'NOT IN' )
			)
		));
		if ( $news->have_posts() ) {
			global $post;
			add_filter('the_content_feed', array($this, 'theContentFilter'));
			while ( $news->have_posts() ) {
				$news->the_post();
				$entry = $root->appendChild( $xml->createElement('noticia') );
				$entry->appendChild( $this->createTextElement($xml, 'titulo', $this->sanitizeText( $post->post_title) ) );
				$entry->appendchild( $xml->createElement('fecha', $post->post_date) );
				$entry->appendChild( $xml->createElement('fotochica', $this->getThumbUrl('mobile-small') ) );
				$entry->appendChild( $xml->createElement('fotogrande', $this->getThumbUrl('mobile-normal') ) );
				$entry->appendChild( $this->createTextElement($xml, 'resumen', $this->sanitizeText( get_the_excerpt() ) ) );
				$entry->appendChild( $this->createTextElement($xml, 'texto', get_the_content_feed()) );
			}
		}
		echo $xml->saveXML();
		exit;
	}
	private function createTextElement( $document, $key, $value ){
		$element = $document->createElement( $key );
		$element->appendChild( $document->createCDATASection( $value ) );
		return $element;
	}
	public function displayEvents(){
		header('Content-Type: application/xml; charset=utf-8');
		$xml = new DOMDocument();
		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;
		$xml->encoding = 'utf-8';
		$root = $xml->appendChild( $xml->createElement('calendar') );
		$entries = new udd_event(array(
			'posts_per_page' => 30
		));
		if ( $entries->get()->have_posts() ) {
			global $post;
			add_filter('the_content_feed', array($this, 'theContentFilter'));
			foreach ( $entries->query->posts as $post ) {
				$entry = $root->appendChild( $xml->createElement('event') );
				$entry->appendChild( $this->createTextElement($xml, 'summary', $this->sanitizeText( $post->post_title) ) );
				$entry->appendChild( $xml->createElement('dtstart', $post->post_modified ) );
				$entry->appendChild( $xml->createElement('dtend', $post->post_modified_gmt ) );
				$entry->appendChild( $this->createTextElement($xml, 'description', $this->sanitizeText( $post->post_content)) );
				$entry->appendChild( $this->createTextElement($xml, 'location', $this->sanitizeText( $post->post_excerpt) ) );
				$entry->appendchild( $xml->createElement('pubdate', $post->post_date) );
			}
		}
		echo $xml->saveXML();
		exit;
	}
	public function getThumbUrl( $size ){
		global $post;
		$thumb = get_post_thumbnail_id( $post->ID );
		if ( ! $thumb )
			return '';
		$img = wp_get_attachment_image_src( $thumb, $size );
		return $img[0];
	}
	public function sanitizeText( $text ){
		$text = str_replace('&nbsp;', '', $text);
		$text = html_entity_decode( $text );
		$text = strip_shortcodes( $text );
		$text = strip_tags( $text );
		$text = trim( $text );
		// $text = utf8_encode( $text );
		return $text;
	}
	public function theContentFilter( $text ){
		return $this->sanitizeText( $text );
	}
	public function addFeed(){
		add_feed('mobile-news', array($this, 'displayNews'));
		add_feed('mobile-events', array($this, 'displayEvents'));
	}
	public function rewriteRules( $wp_rewrite ){
		$new_rules = array(
			'feed/(.+)' => 'index.php?feed='. $wp_rewrite->preg_index( 1 )
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}
	public function installationHook(){
		if ( function_exists('add_feed') )
			$this->addFeed();
		flush_rewrite_rules();
	}
}
// Instantiate the class object

$UddMobileFeed = UddMobileFeed::getInstance();
register_activation_hook( __FILE__, array($UddMobileFeed, 'installationHook') );

add_action('init', 'mobile_feed_test');
function mobile_feed_test(){
	if ( ! isset($_GET['action']) )
		return;
	if ( $_GET['action'] !== 'mobile_feed_test' )
		return;
	$feed_url = site_url( 'feed/mobile-news' );
	$feed = wp_remote_get( $feed_url );
	$f = simplexml_load_string( $feed['body'] );
	header('Content-Type: text/plain; charset=UTF-8');
	foreach ( $f->children() as $child ) {
		$title = utf8_decode( $child->titulo );
		echo $title ."\n";
		echo str_repeat('-', strlen( $title )) ."\n";
		echo utf8_decode( $child->texto ) ."\n";
		echo '==='."\n";
	}
	exit;
}
