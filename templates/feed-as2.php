<?php
/**
 * Activity Streams 2 Feed Template for displaying AS2 Posts feed.
 */

$json = new stdClass();

$json->{'@context'} = 'http://www.w3.org/ns/activitystreams';
$json->id = get_feed_link( 'as2' );
$json->type = 'Collection';
$json->name = esc_attr( sprintf( __( '%1$s - posts', 'activitystram-extension' ), get_bloginfo( 'name' ) ) );
$json->totalItems = (int) get_option( 'posts_per_rss' );

$json->items = array();

header( 'Content-Type: ' . feed_content_type( 'as2' ), true );

/*
 * The JSONP callback function to add to the JSON feed
 *
 * @param string $callback The JSONP callback function name
 */
$callback = apply_filters( 'as2_feed_callback', get_query_var( 'callback' ) );

if ( ! empty( $callback ) && ! apply_filters( 'json_jsonp_enabled', true ) ) {
	status_header( 400 );
	echo json_encode( array(
		'code'  => 'json_callback_disabled',
		'message' => 'JSONP support is disabled on this site.',
	) );
	exit;
}

if ( preg_match( '/\W/', $callback ) ) {
	status_header( 400 );
	echo json_encode( array(
		'code'  => 'json_callback_invalid',
		'message' => 'The JSONP callback function is invalid.',
	) );
	exit;
}

/*
 * Action triggerd prior to the AS2 feed being created and sent to the client
 */
do_action( 'as2_feed_pre' );

while ( have_posts() ) {
	the_post();

	/*
	 * The object type of the current post in the Activity Streams 1 feed
	 *
	 * @param Object get_post() The current post
	 */
	$object_type = apply_filters( 'as2_object_type', 'Article', get_post() );

	$item = array(
		'published' => get_post_modified_time( 'Y-m-d\TH:i:s\Z', true ),
		'generator' => 'http://wordpress.org/?v=' . get_bloginfo_rss( 'version' ),
		'id' => get_post_comments_feed_link( get_the_ID(), 'as2' ),
		'type' => 'Create',
		'name' => esc_attr( sprintf( __( '%1$s created a new post', 'activitystram-extension' ), get_the_author() ) ),
		'target' => (object) array(
			'id' => get_bloginfo( 'url' ),
			'type' => 'http://schema.org/Blog',
			'url' => get_bloginfo( 'url' ),
			'name' => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
		),
		'object' => (object) array(
			'id' => get_the_guid(),
			'type' => $object_type,
			'name' => get_the_title(),
			'summary' => get_the_excerpt(),
			'url' => get_permalink(),
			'content' => get_the_content(),
		),
		'actor' => (object) array(
			'id' => get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'nicename' ) ),
			'type' => 'Person',
			'name' => get_the_author(),
			'url' => get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'nicename' ) ),
			'image' => (object) array(
				'type' => 'Link',
				'width'  => 96,
				'height' => 96,
				'href' => get_avatar_url( get_the_author_meta( 'email' ), array( 'size' => 96 ) ),
			),
		),
	);

	/*
	 * The item to be added to the Activity Streams 1 feed
	 *
	 * @param object $item The Activity Streams 1 item
	 */
	$item = apply_filters( 'as2_feed_item', $item );

	$json->items[] = $item;
}

/*
 * The array of data to be sent to the user as JSON
 *
 * @param object $json The JSON data object
 */
$json = apply_filters( 'as2_feed', $json );

if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
	// json_encode() options added in PHP 5.3
	$json_str = json_encode( $json );
} else {
	$options = 0;
	// JSON_PRETTY_PRINT added in PHP 5.4
	if ( get_query_var( 'pretty' ) && version_compare( phpversion(), '5.4.0', '>=' ) ) {
		$options |= JSON_PRETTY_PRINT;
	}

	/*
	 * Options to be passed to json_encode()
	 *
	 * @param int $options The current options flags
	 */
	$options = apply_filters( 'as2_feed_options', $options );

	$json_str = json_encode( $json, $options );
}

echo $json_str;

/*
 * Action triggerd after the AS2 feed has been created and sent to the client
 */
do_action( 'as2_feed_post' );
