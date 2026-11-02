<?php
/**
 * Template Name: Audiobook Catalog
 *
 * The full catalog: search box, left filter rail (age, modifiers,
 * languages), a 4-per-row tile grid, sorting and pagination.
 * Filters are plain GET params, so every view is a shareable URL.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

get_header();

/* ---- Read and sanitize the filter parameters --------------------------- */
$ab_q    = isset( $_GET['ab_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ab_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ab_age  = isset( $_GET['ab_age'] ) ? sanitize_title( wp_unslash( $_GET['ab_age'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ab_cat  = isset( $_GET['ab_cat'] ) ? sanitize_title( wp_unslash( $_GET['ab_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ab_lang = isset( $_GET['ab_lang'] ) ? sanitize_title( wp_unslash( $_GET['ab_lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ab_tags = isset( $_GET['ab_tags'] ) ? sanitize_text_field( wp_unslash( $_GET['ab_tags'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ab_sort = isset( $_GET['ab_sort'] ) ? sanitize_key( wp_unslash( $_GET['ab_sort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$ab_tag_slugs = array_filter( array_map( 'sanitize_title', explode( ',', $ab_tags ) ) );

/* Every catalog search feeds the "Hot searches" sidebar. */
if ( $ab_q ) {
	audioshelf_log_search( $ab_q );
}

/* ---- Build the query ---------------------------------------------------- */
$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

$ab_query_args = array(
	'post_type'           => 'audiobook',
	'post_status'         => 'publish',
	'posts_per_page'      => 12, // 4 tiles per row x 3 rows
	'paged'               => $paged,
	'ignore_sticky_posts' => true,
);

if ( $ab_q ) {
	$ab_query_args['s'] = $ab_q; // title, content and (via functions.php) narrator.
}

$ab_tax_query = array();
if ( $ab_age ) {
	$ab_tax_query[] = array(
		'taxonomy' => 'ab_age',
		'field'    => 'slug',
		'terms'    => $ab_age,
	);
}
if ( $ab_cat ) {
	$ab_tax_query[] = array(
		'taxonomy' => 'ab_category',
		'field'    => 'slug',
		'terms'    => $ab_cat,
	);
}
if ( $ab_lang ) {
	$ab_tax_query[] = array(
		'taxonomy' => 'ab_language',
		'field'    => 'slug',
		'terms'    => $ab_lang,
	);
}
if ( $ab_tag_slugs ) {
	$ab_tax_query[] = array(
		'taxonomy' => 'ab_tag',
		'field'    => 'slug',
		'terms'    => $ab_tag_slugs,
		'operator' => 'AND', // a tile must carry every selected modifier.
	);
}
if ( $ab_tax_query ) {
	$ab_query_args['tax_query'] = $ab_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
}

/* Sorting */
switch ( $ab_sort ) {
	case 'popular':
		$ab_query_args['meta_key'] = 'ab_popularity'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$ab_query_args['orderby']  = 'meta_value_num';
		$ab_query_args['order']    = 'DESC';
		break;
	case 'rating':
		$ab_query_args['meta_key'] = 'ab_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$ab_query_args['orderby']  = 'meta_value_num';
		$ab_query_args['order']    = 'DESC';
		break;
	case 'title':
		$ab_query_args['orderby'] = 'title';
		$ab_query_args['order']   = 'ASC';
		break;
	case 'shortest':
		$ab_query_args['meta_key'] = 'ab_duration'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$ab_query_args['orderby']  = 'meta_value_num';
		$ab_query_args['order']    = 'ASC';
		break;
	case 'newest':
	default:
		$ab_query_args['orderby'] = 'date';
		$ab_query_args['order']   = 'DESC';
		break;
}

$ab_catalog_query = new WP_Query( $ab_query_args );
?>

<main class="ab-catalog" id="content">
	<div class="ab-container">

		<header class="ab-page-head">
			<h1 class="ab-page-title"><?php the_title(); ?></h1>
			<p class="ab-page-sub"><?php esc_html_e( 'Every audiobook on the shelf — search, filter and sort to find your next listen.', 'audioshelf' ); ?></p>
		</header>

		<form class="ab-search" role="search" method="get" action="<?php echo esc_url( audioshelf_catalog_url() ); ?>">
			<input type="search" name="ab_q" value="<?php echo esc_attr( $ab_q ); ?>"
				placeholder="<?php esc_attr_e( 'Search by title, author, narrator, genre, tag…', 'audioshelf' ); ?>"
				aria-label="<?php esc_attr_e( 'Search the catalog', 'audioshelf' ); ?>" />
			<?php
			/* Keep active taxonomy filters when searching. */
			foreach ( array( 'ab_age' => $ab_age, 'ab_cat' => $ab_cat, 'ab_lang' => $ab_lang, 'ab_tags' => $ab_tags, 'ab_sort' => $ab_sort ) as $hidden_key => $hidden_val ) {
				if ( $hidden_val ) {
					echo '<input type="hidden" name="' . esc_attr( $hidden_key ) . '" value="' . esc_attr( $hidden_val ) . '" />';
				}
			}
			?>
			<button type="submit"><?php esc_html_e( 'Search', 'audioshelf' ); ?></button>
		</form>

		<div class="ab-catalog-layout">

			<!-- Filter rail -->
			<aside class="ab-catalog-filters" aria-label="<?php esc_attr_e( 'Catalog filters', 'audioshelf' ); ?>">
				<div class="ab-filter-head">
					<span><?php esc_html_e( 'Filters', 'audioshelf' ); ?></span>
					<a href="<?php echo esc_url( audioshelf_catalog_url() ); ?>"><?php esc_html_e( 'Clear all', 'audioshelf' ); ?></a>
				</div>

				<section class="ab-sidebar-box">
					<h2 class="ab-sidebar-title"><?php esc_html_e( 'Age category', 'audioshelf' ); ?></h2>
					<ul class="ab-filter-list">
						<?php
						$ab_all_ages = get_terms( array( 'taxonomy' => 'ab_age', 'hide_empty' => false ) );
						if ( ! is_wp_error( $ab_all_ages ) ) :
							foreach ( $ab_all_ages as $ab_term ) :
								$ab_base = array_filter( array( 'ab_cat' => $ab_cat, 'ab_lang' => $ab_lang, 'ab_tags' => $ab_tags, 'ab_sort' => 'newest' !== $ab_sort ? $ab_sort : '', 'ab_q' => $ab_q ) );
								$ab_link = $ab_term->slug === $ab_age ? audioshelf_catalog_link( $ab_base ) : audioshelf_catalog_link( array_merge( $ab_base, array( 'ab_age' => $ab_term->slug ) ) );
								?>
								<li>
									<a class="<?php echo $ab_term->slug === $ab_age ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ab_link ); ?>">
										<span><?php echo esc_html( $ab_term->name ); ?></span>
										<span class="ab-count"><?php echo esc_html( $ab_term->count ); ?></span>
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
						$ab_all_tags = get_terms( array( 'taxonomy' => 'ab_tag', 'hide_empty' => false, 'number' => 14, 'orderby' => 'count', 'order' => 'DESC' ) );
						if ( ! is_wp_error( $ab_all_tags ) ) :
							foreach ( $ab_all_tags as $ab_term ) :
								$ab_selected = in_array( $ab_term->slug, $ab_tag_slugs, true );
								$ab_next     = $ab_selected ? array_diff( $ab_tag_slugs, array( $ab_term->slug ) ) : array_merge( $ab_tag_slugs, array( $ab_term->slug ) );
								$ab_base     = array_filter( array( 'ab_age' => $ab_age, 'ab_cat' => $ab_cat, 'ab_lang' => $ab_lang, 'ab_q' => $ab_q ) );
								if ( $ab_next ) {
									$ab_base['ab_tags'] = implode( ',', $ab_next );
								}
								?>
								<a class="ab-chip<?php echo $ab_selected ? ' is-active' : ''; ?>"
									href="<?php echo esc_url( audioshelf_catalog_link( $ab_base ) ); ?>">
									<?php echo esc_html( $ab_term->name ); ?> (<?php echo esc_html( $ab_term->count ); ?>)
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
						$ab_all_langs = get_terms( array( 'taxonomy' => 'ab_language', 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC' ) );
						if ( ! is_wp_error( $ab_all_langs ) ) :
							foreach ( $ab_all_langs as $ab_term ) :
								$ab_base = array_filter( array( 'ab_age' => $ab_age, 'ab_cat' => $ab_cat, 'ab_tags' => $ab_tags, 'ab_q' => $ab_q ) );
								$ab_link = $ab_term->slug === $ab_lang ? audioshelf_catalog_link( $ab_base ) : audioshelf_catalog_link( array_merge( $ab_base, array( 'ab_lang' => $ab_term->slug ) ) );
								?>
								<li>
									<a class="<?php echo $ab_term->slug === $ab_lang ? 'is-active' : ''; ?>" href="<?php echo esc_url( $ab_link ); ?>">
										<span><?php echo esc_html( $ab_term->name ); ?></span>
										<span class="ab-count"><?php echo esc_html( $ab_term->count ); ?></span>
									</a>
								</li>
								<?php
							endforeach;
						endif;
						?>
					</ul>
				</section>
			</aside>

			<!-- Results -->
			<div class="ab-catalog-results">

				<div class="ab-toolbar">
					<p class="ab-result-count" aria-live="polite">
						<?php
						printf(
							/* translators: %s: number of results */
							esc_html( _n( '%s audiobook found', '%s audiobooks found', $ab_catalog_query->found_posts, 'audioshelf' ) ),
							'<strong>' . esc_html( number_format_i18n( $ab_catalog_query->found_posts ) ) . '</strong>'
						);
						?>
					</p>
					<form method="get" action="<?php echo esc_url( audioshelf_catalog_url() ); ?>" class="ab-sort">
						<label for="ab-sort"><?php esc_html_e( 'Sort by', 'audioshelf' ); ?></label>
						<select id="ab-sort" name="ab_sort" onchange="this.form.submit()">
							<option value="newest" <?php selected( $ab_sort, 'newest' ); ?>><?php esc_html_e( 'Newest first', 'audioshelf' ); ?></option>
							<option value="popular" <?php selected( $ab_sort, 'popular' ); ?>><?php esc_html_e( 'Most popular', 'audioshelf' ); ?></option>
							<option value="rating" <?php selected( $ab_sort, 'rating' ); ?>><?php esc_html_e( 'Highest rated', 'audioshelf' ); ?></option>
							<option value="title" <?php selected( $ab_sort, 'title' ); ?>><?php esc_html_e( 'Title A–Z', 'audioshelf' ); ?></option>
							<option value="shortest" <?php selected( $ab_sort, 'shortest' ); ?>><?php esc_html_e( 'Shortest first', 'audioshelf' ); ?></option>
						</select>
						<?php
						foreach ( array( 'ab_q' => $ab_q, 'ab_age' => $ab_age, 'ab_cat' => $ab_cat, 'ab_lang' => $ab_lang, 'ab_tags' => $ab_tags ) as $hidden_key => $hidden_val ) {
							if ( $hidden_val ) {
								echo '<input type="hidden" name="' . esc_attr( $hidden_key ) . '" value="' . esc_attr( $hidden_val ) . '" />';
							}
						}
						?>
						<noscript><button type="submit"><?php esc_html_e( 'Apply', 'audioshelf' ); ?></button></noscript>
					</form>
				</div>

				<?php if ( $ab_catalog_query->have_posts() ) : ?>

					<div class="ab-tile-grid">
						<?php
						while ( $ab_catalog_query->have_posts() ) :
							$ab_catalog_query->the_post();
							get_template_part( 'template-parts/tile-audiobook' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>

					<?php
					$ab_pagination = paginate_links(
						array(
							'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
							'format'    => '?paged=%#%',
							'current'   => $paged,
							'total'     => $ab_catalog_query->max_num_pages,
							'type'      => 'list',
							'prev_text' => __( '« Prev', 'audioshelf' ),
							'next_text' => __( 'Next »', 'audioshelf' ),
						)
					);
					if ( $ab_pagination ) :
						?>
						<nav class="ab-pagination" aria-label="<?php esc_attr_e( 'Catalog pagination', 'audioshelf' ); ?>">
							<?php echo wp_kses_post( $ab_pagination ); ?>
						</nav>
					<?php endif; ?>

				<?php else : ?>

					<div class="ab-empty">
						<h2><?php esc_html_e( 'No audiobooks match your filters', 'audioshelf' ); ?></h2>
						<p><?php esc_html_e( 'Try a different search term, or clear some filters to widen the results.', 'audioshelf' ); ?></p>
						<a class="ab-btn" href="<?php echo esc_url( audioshelf_catalog_url() ); ?>"><?php esc_html_e( 'Clear all filters', 'audioshelf' ); ?></a>
					</div>

				<?php endif; ?>
			</div>
		</div>
	</div>
</main>

<?php
get_footer();
