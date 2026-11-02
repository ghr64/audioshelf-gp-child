<?php
/**
 * AudioShelf – GeneratePress child theme functions.
 *
 * Requires the free GeneratePress parent theme to be installed and active.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

define( 'AUDIOSHELF_VERSION', '1.0.0' );

/* -------------------------------------------------------------------------
 * 1. Styles
 * ---------------------------------------------------------------------- */

/**
 * Enqueue parent + child styles and the custom AudioShelf stylesheet.
 */
function audioshelf_enqueue_styles() {
	wp_enqueue_style(
		'generate-child',
		get_stylesheet_uri(),
		array( 'generate-style' ),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'audioshelf',
		get_stylesheet_directory_uri() . '/assets/css/audioshelf.css',
		array( 'generate-child' ),
		AUDIOSHELF_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'audioshelf_enqueue_styles' );

/* -------------------------------------------------------------------------
 * 2. Audiobook post type, taxonomies and meta
 * ---------------------------------------------------------------------- */

require get_stylesheet_directory() . '/inc/audiobook-post-type.php';

/* -------------------------------------------------------------------------
 * 3. Template helpers (catalog URL, hot searches, fallback sidebars)
 * ---------------------------------------------------------------------- */

require get_stylesheet_directory() . '/inc/helpers.php';

/* -------------------------------------------------------------------------
 * 4. Search: let the WordPress search find narrators (custom field)
 *    and always include audiobooks in main search results.
 * ---------------------------------------------------------------------- */

/**
 * Extend audiobook search SQL so a term also matches the narrator meta field.
 *
 * @param string     $search The current WHERE search clause.
 * @param WP_Query   $query  The query being run.
 * @return string
 */
function audioshelf_search_narrator( $search, $query ) {
	if ( is_admin() || empty( $search ) ) {
		return $search;
	}
	if ( 'audiobook' !== (string) $query->get( 'post_type' ) ) {
		return $search;
	}

	global $wpdb;
	$term = $query->get( 's' );
	if ( ! $term ) {
		return $search;
	}

	$like  = '%' . $wpdb->esc_like( $term ) . '%';
	$extra = $wpdb->prepare(
		" OR EXISTS (
			SELECT pm.post_id FROM {$wpdb->postmeta} pm
			WHERE pm.post_id = {$wpdb->posts}.ID
			  AND pm.meta_key = 'ab_narrator'
			  AND pm.meta_value LIKE %s
		)",
		$like
	);

	// Inject before the final closing parenthesis of the search clause.
	$search = preg_replace( '/\)\)$/', $extra . '))', $search );
	return $search;
}
add_filter( 'posts_search', 'audioshelf_search_narrator', 10, 2 );

/* -------------------------------------------------------------------------
 * 5. Log catalog searches so the "Hot searches" sidebar stays fresh
 * ---------------------------------------------------------------------- */

/**
 * Record a search term (called from the catalog template).
 *
 * @param string $term Raw search term.
 */
function audioshelf_log_search( $term ) {
	$term = trim( wp_strip_all_tags( $term ) );
	if ( $term === '' || mb_strlen( $term ) < 2 || mb_strlen( $term ) > 60 ) {
		return;
	}

	$terms = get_option( 'ab_hot_searches', array() );
	if ( ! is_array( $terms ) ) {
		$terms = array();
	}

	// Case-insensitive match against existing entries.
	$found = false;
	foreach ( $terms as $existing => $count ) {
		if ( 0 === strcasecmp( $existing, $term ) ) {
			unset( $terms[ $existing ] );
			$term  = $existing;
			$terms[ $term ] = (int) $count + 1;
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		$terms[ $term ] = 1;
	}

	arsort( $terms );
	update_option( 'ab_hot_searches', array_slice( $terms, 0, 10, true ), false );
}

/* -------------------------------------------------------------------------
 * 6. Small theme niceties
 * ---------------------------------------------------------------------- */

/** Square cover crop used by the tile grid. */
add_action( 'after_setup_theme', function () {
	add_image_size( 'ab-cover', 480, 480, true );
} );

/** Make main-search results readable: strip the GP sidebar on search pages. */
add_filter( 'generate_sidebar_layout', function ( $layout ) {
	if ( is_search() ) {
		return 'no-sidebar';
	}
	return $layout;
} );

/** Allow shortcode-free excerpt on the audiobook CPT. */
add_filter( 'excerpt_length', function ( $length ) {
	return is_post_type_archive( 'audiobook' ) ? 24 : $length;
}, 999 );
