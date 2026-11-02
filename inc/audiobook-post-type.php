<?php
/**
 * Audiobook post type, taxonomies and the audiobook details meta box.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Post type + taxonomies
 * ---------------------------------------------------------------------- */

function audioshelf_register_post_type() {

	register_post_type(
		'audiobook',
		array(
			'labels'        => array(
				'name'          => __( 'Audiobooks', 'audioshelf' ),
				'singular_name' => __( 'Audiobook', 'audioshelf' ),
				'add_new_item'  => __( 'Add New Audiobook', 'audioshelf' ),
				'edit_item'     => __( 'Edit Audiobook', 'audioshelf' ),
				'menu_name'     => __( 'Audiobooks', 'audioshelf' ),
			),
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-microphone',
			'menu_position' => 20,
			'rewrite'       => array(
				'slug'       => 'audiobook',
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest'  => true,
		)
	);

	register_taxonomy(
		'ab_age',
		'audiobook',
		array(
			'labels'       => array(
				'name'          => __( 'Age categories', 'audioshelf' ),
				'singular_name' => __( 'Age category', 'audioshelf' ),
				'menu_name'     => __( 'Age Categories', 'audioshelf' ),
			),
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite'      => array( 'slug' => 'age' ),
		)
	);

	register_taxonomy(
		'ab_category',
		'audiobook',
		array(
			'labels'       => array(
				'name'          => __( 'Audiobook categories', 'audioshelf' ),
				'singular_name' => __( 'Audiobook category', 'audioshelf' ),
				'menu_name'     => __( 'Categories', 'audioshelf' ),
			),
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite'      => array( 'slug' => 'audiobook-category' ),
		)
	);

	register_taxonomy(
		'ab_language',
		'audiobook',
		array(
			'labels'       => array(
				'name'          => __( 'Languages', 'audioshelf' ),
				'singular_name' => __( 'Language', 'audioshelf' ),
				'menu_name'     => __( 'Languages', 'audioshelf' ),
			),
			'hierarchical' => true,
			'show_admin_column' => true,
			'rewrite'      => array( 'slug' => 'language' ),
		)
	);

	register_taxonomy(
		'ab_tag',
		'audiobook',
		array(
			'labels'       => array(
				'name'          => __( 'Category modifiers', 'audioshelf' ),
				'singular_name' => __( 'Category modifier', 'audioshelf' ),
				'menu_name'     => __( 'Category Modifiers', 'audioshelf' ),
			),
			'hierarchical' => false,
			'show_admin_column' => true,
			'rewrite'      => array( 'slug' => 'audiobook-modifier' ),
		)
	);
}
add_action( 'init', 'audioshelf_register_post_type' );

/** Flush rewrite rules once after activation so /audiobook/ URLs work. */
function audioshelf_activate() {
	audioshelf_register_post_type();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'audioshelf_activate' );

/* -------------------------------------------------------------------------
 * Meta box: author, narrator, duration, rating, popularity
 * ---------------------------------------------------------------------- */

function audioshelf_add_meta_box() {
	add_meta_box(
		'ab_details',
		__( 'Audiobook details', 'audioshelf' ),
		'audioshelf_render_meta_box',
		'audiobook',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'audioshelf_add_meta_box' );

/**
 * Render the meta box fields.
 *
 * @param WP_Post $post Current post.
 */
function audioshelf_render_meta_box( $post ) {
	wp_nonce_field( 'ab_save_details', 'ab_details_nonce' );

	$fields = array(
		'ab_author'     => array(
			'label'       => __( 'Book author', 'audioshelf' ),
			'type'        => 'text',
			'placeholder' => __( 'e.g. J.R.R. Tolkien', 'audioshelf' ),
		),
		'ab_narrator'   => array(
			'label'       => __( 'Narrator', 'audioshelf' ),
			'type'        => 'text',
			'placeholder' => __( 'e.g. Stephen Fry', 'audioshelf' ),
		),
		'ab_duration'   => array(
			'label'       => __( 'Duration (minutes)', 'audioshelf' ),
			'type'        => 'number',
			'placeholder' => '660',
		),
		'ab_rating'     => array(
			'label'       => __( 'Rating (0–5)', 'audioshelf' ),
			'type'        => 'number',
			'placeholder' => '4.8',
			'step'        => '0.1',
		),
		'ab_popularity' => array(
			'label'       => __( 'Popularity (listen count)', 'audioshelf' ),
			'type'        => 'number',
			'placeholder' => '9200',
		),
	);

	echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">';
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		printf(
			'<p style="margin:0;"><label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px;">%2$s</label>',
			esc_attr( $key ),
			esc_html( $field['label'] )
		);
		printf(
			'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s" step="%5$s" class="widefat" /></p>',
			esc_attr( $field['type'] ),
			esc_attr( $key ),
			esc_attr( $value ),
			esc_attr( $field['placeholder'] ),
			esc_attr( isset( $field['step'] ) ? $field['step'] : '1' )
		);
	}
	echo '</div>';
	echo '<p style="margin-top:12px;color:#666;">' . esc_html__( 'Square cover art (at least 480×480) goes in the Featured Image box — the tile grid uses it as the audiobook cover.', 'audioshelf' ) . '</p>';
}

/**
 * Save the meta box fields.
 *
 * @param int $post_id Post ID.
 */
function audioshelf_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ab_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ab_details_nonce'] ), 'ab_save_details' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$keys = array( 'ab_author', 'ab_narrator', 'ab_duration', 'ab_rating', 'ab_popularity' );
	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		if ( 'ab_author' === $key || 'ab_narrator' === $key ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		} else {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}
add_action( 'save_post_audiobook', 'audioshelf_save_meta_box' );
