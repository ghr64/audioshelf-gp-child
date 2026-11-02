<?php
/**
 * Template Name: Audiobook Homepage
 *
 * Three-column browsing:
 *   left   – age categories, category modifiers, popular languages, hot searches
 *   center – the latest audiobooks in a 4-per-row tile grid
 *   right  – recent audiobooks + most popular audiobooks
 *
 * Both sidebars are real widget areas (falling back to built-in lists),
 * so you can rearrange them in Appearance → Widgets without touching code.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="ab-home" id="content">
	<div class="ab-container">
		<div class="ab-home-layout">

			<!-- Left sidebar -->
			<aside class="ab-home-left" aria-label="<?php esc_attr_e( 'Browse filters', 'audioshelf' ); ?>">
				<?php
				if ( is_active_sidebar( 'ab_left_sidebar' ) ) {
					dynamic_sidebar( 'ab_left_sidebar' );
				} else {
					audioshelf_default_left_sidebar();
				}
				?>
			</aside>

			<!-- Center column -->
			<div class="ab-home-center">
				<header class="ab-page-head ab-row-head">
					<div>
						<h1 class="ab-page-title"><?php esc_html_e( 'Latest audiobooks', 'audioshelf' ); ?></h1>
						<p class="ab-page-sub"><?php esc_html_e( 'Fresh additions to the shelf.', 'audioshelf' ); ?></p>
					</div>
					<a class="ab-btn ab-btn-outline" href="<?php echo esc_url( audioshelf_catalog_url() ); ?>">
						<?php esc_html_e( 'Browse catalog', 'audioshelf' ); ?> →
					</a>
				</header>

				<?php
				$ab_latest = new WP_Query(
					array(
						'post_type'           => 'audiobook',
						'post_status'         => 'publish',
						'posts_per_page'      => 8,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				if ( $ab_latest->have_posts() ) :
					?>
					<div class="ab-tile-grid">
						<?php
						while ( $ab_latest->have_posts() ) :
							$ab_latest->the_post();
							get_template_part( 'template-parts/tile-audiobook' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php else : ?>
					<div class="ab-empty">
						<h2><?php esc_html_e( 'No audiobooks yet', 'audioshelf' ); ?></h2>
						<p><?php esc_html_e( 'Head to Audiobooks → Add New in your dashboard and publish your first title.', 'audioshelf' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right sidebar -->
			<aside class="ab-home-right" aria-label="<?php esc_attr_e( 'Recent and popular', 'audioshelf' ); ?>">
				<?php
				if ( is_active_sidebar( 'ab_right_sidebar' ) ) {
					dynamic_sidebar( 'ab_right_sidebar' );
				} else {
					audioshelf_default_right_sidebar();
				}
				?>
			</aside>

		</div>
	</div>
</main>

<?php
get_footer();
