<?php
/**
 * Template helpers used by the AudioShelf page templates.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

/**
 * Find the URL of the page that uses the "Audiobook Catalog" template.
 *
 * @return string Permalink or home URL as fallback.
 */
function audioshelf_catalog_url() {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => 'template-catalog.php',
			'number'     => 1,
		)
	);

	if ( ! empty( $pages ) ) {
		return get_permalink( $pages[0]->ID );
	}
	return home_url( '/' );
}

/**
 * Build a catalog link with query args appended.
 *
 * @param array $args Query args, e.g. array( 'ab_age' => 'teen' ).
 * @return string
 */
function audioshelf_catalog_link( $args = array() ) {
	$base = audioshelf_catalog_url();
	if ( empty( $args ) ) {
		return $base;
	}
	return add_query_arg( array_map( 'rawurlencode', $args ), $base );
}

/**
 * Top searched terms recorded by audioshelf_log_search().
 *
 * @param int $limit How many terms to return.
 * @return array term => count
 */
function audioshelf_get_hot_searches( $limit = 10 ) {
	$terms = get_option( 'ab_hot_searches', array() );
	if ( ! is_array( $terms ) ) {
		return array();
	}
	arsort( $terms );
	return array_slice( $terms, 0, $limit, true );
}

/**
 * "660" → "11h", "505" → "8h 25m".
 *
 * @param int $minutes Duration in minutes.
 * @return string
 */
function audioshelf_format_duration( $minutes ) {
	$minutes = (int) $minutes;
	if ( $minutes <= 0 ) {
		return '';
	}
	$h = (int) floor( $minutes / 60 );
	$m = $minutes % 60;
	if ( 0 === $h ) {
		return sprintf( '%dm', $m );
	}
	if ( 0 === $m ) {
		return sprintf( '%dh', $h );
	}
	return sprintf( '%dh %dm', $h, $m );
}

/**
 * "9800" → "9.8k".
 *
 * @param int $number Number to shorten.
 * @return string
 */
function audioshelf_format_count( $number ) {
	$number = (int) $number;
	if ( $number >= 1000 ) {
		$k = $number / 1000;
		return ( $k >= 10 ? round( $k ) : round( $k, 1 ) ) . 'k';
	}
	return (string) $number;
}

/**
 * Star rating markup for a tile.
 *
 * @param string|float $rating Rating value.
 * @return string
 */
function audioshelf_rating_badge( $rating ) {
	$rating = (float) $rating;
	if ( $rating <= 0 ) {
		return '';
	}
	return '<span class="ab-tile-rating"><span aria-hidden="true">★</span> ' . esc_html( number_format_i18n( $rating, 1 ) ) . '</span>';
}

/**
 * Default content for the LEFT sidebar when no widgets are set:
 * age categories, category modifiers, popular languages, hot searches.
 */
function audioshelf_default_left_sidebar() {
	?>
	<div class="ab-sidebar">
		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Age category', 'audioshelf' ); ?></h2>
			<ul class="ab-filter-list">
				<?php
				$ages = get_terms(
					array(
						'taxonomy'   => 'ab_age',
						'hide_empty' => true,
					)
				);
				if ( ! is_wp_error( $ages ) ) :
					foreach ( $ages as $term ) :
						?>
						<li>
							<a href="<?php echo esc_url( audioshelf_catalog_link( array( 'ab_age' => $term->slug ) ) ); ?>">
								<span><?php echo esc_html( $term->name ); ?></span>
								<span class="ab-count"><?php echo esc_html( $term->count ); ?></span>
							</a>
						</li>
						<?php
					endforeach;
				endif;
				?>
			</ul>
		</section>

		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Category modifiers', 'audioshelf' ); ?></h2>
			<div class="ab-chip-wrap">
				<?php
				$tags = get_terms(
					array(
						'taxonomy'   => 'ab_tag',
						'hide_empty' => true,
						'number'     => 14,
						'orderby'    => 'count',
						'order'      => 'DESC',
					)
				);
				if ( ! is_wp_error( $tags ) ) :
					foreach ( $tags as $term ) :
						?>
						<a class="ab-chip" href="<?php echo esc_url( audioshelf_catalog_link( array( 'ab_tags' => $term->slug ) ) ); ?>">
							<?php echo esc_html( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
						</a>
						<?php
					endforeach;
				endif;
				?>
			</div>
		</section>

		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Popular languages', 'audioshelf' ); ?></h2>
			<ul class="ab-filter-list">
				<?php
				$langs = get_terms(
					array(
						'taxonomy'   => 'ab_language',
						'hide_empty' => true,
						'orderby'    => 'count',
						'order'      => 'DESC',
					)
				);
				if ( ! is_wp_error( $langs ) ) :
					foreach ( $langs as $term ) :
						?>
						<li>
							<a href="<?php echo esc_url( audioshelf_catalog_link( array( 'ab_lang' => $term->slug ) ) ); ?>">
								<span><?php echo esc_html( $term->name ); ?></span>
								<span class="ab-count"><?php echo esc_html( $term->count ); ?></span>
							</a>
						</li>
						<?php
					endforeach;
				endif;
				?>
			</ul>
		</section>

		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Hot searches', 'audioshelf' ); ?></h2>
			<div class="ab-chip-wrap">
				<?php
				$hot = audioshelf_get_hot_searches();
				if ( empty( $hot ) ) :
					?>
					<p class="ab-muted"><?php esc_html_e( 'Search for something to start the trends…', 'audioshelf' ); ?></p>
				<?php else : ?>
					<?php foreach ( $hot as $term => $count ) : ?>
						<a class="ab-chip<?php echo $count >= 5 ? ' ab-chip-hot' : ''; ?>" href="<?php echo esc_url( audioshelf_catalog_link( array( 'ab_q' => $term ) ) ); ?>">
							<?php echo esc_html( $term ); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>
	</div>
	<?php
}

/**
 * Default content for the RIGHT sidebar when no widgets are set:
 * recent audiobooks + most popular audiobooks.
 */
function audioshelf_default_right_sidebar() {
	?>
	<div class="ab-sidebar">
		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Recent audiobooks', 'audioshelf' ); ?></h2>
			<div class="ab-mini-list">
				<?php
				$recent = new WP_Query(
					array(
						'post_type'      => 'audiobook',
						'posts_per_page' => 5,
						'no_found_rows'  => true,
					)
				);
				while ( $recent->have_posts() ) :
					$recent->the_post();
					$duration = get_post_meta( get_the_ID(), 'ab_duration', true );
					?>
					<a class="ab-mini-row" href="<?php the_permalink(); ?>">
						<span class="ab-mini-thumb"><?php the_post_thumbnail( 'ab-cover' ); ?></span>
						<span class="ab-mini-text">
							<span class="ab-mini-title"><?php the_title(); ?></span>
							<span class="ab-mini-meta">
								<?php
								$author = get_post_meta( get_the_ID(), 'ab_author', true );
								echo esc_html( $author ? $author : '' );
								if ( $author && $duration ) {
									echo ' · ';
								}
								echo esc_html( audioshelf_format_duration( $duration ) );
								?>
							</span>
						</span>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>

		<section class="ab-sidebar-box">
			<h2 class="ab-sidebar-title"><?php esc_html_e( 'Most popular', 'audioshelf' ); ?></h2>
			<div class="ab-mini-list">
				<?php
				$popular = new WP_Query(
					array(
						'post_type'      => 'audiobook',
						'posts_per_page' => 5,
						'no_found_rows'  => true,
						'meta_key'       => 'ab_popularity',
						'orderby'        => 'meta_value_num',
						'order'          => 'DESC',
					)
				);
				$rank = 0;
				while ( $popular->have_posts() ) :
					$popular->the_post();
					$rank++;
					$pop = get_post_meta( get_the_ID(), 'ab_popularity', true );
					?>
					<a class="ab-mini-row" href="<?php the_permalink(); ?>">
						<span class="ab-rank"><?php echo esc_html( $rank ); ?></span>
						<span class="ab-mini-thumb"><?php the_post_thumbnail( 'ab-cover' ); ?></span>
						<span class="ab-mini-text">
							<span class="ab-mini-title"><?php the_title(); ?></span>
							<span class="ab-mini-meta"><?php echo esc_html( audioshelf_format_count( $pop ) ); ?> <?php esc_html_e( 'listens', 'audioshelf' ); ?></span>
						</span>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
	</div>
	<?php
}
