<?php
/**
 * Single audiobook view: big cover, mock player, details and related titles.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
        the_post();

        $ab_id       = get_the_ID();
        $ab_author   = get_post_meta( $ab_id, 'ab_author', true );
        $ab_narrator = get_post_meta( $ab_id, 'ab_narrator', true );
        $ab_duration = get_post_meta( $ab_id, 'ab_duration', true );
        $ab_rating   = (float) get_post_meta( $ab_id, 'ab_rating', true );
        $ab_pop      = get_post_meta( $ab_id, 'ab_popularity', true );
        ?>

        <main class="ab-single" id="content">
                <div class="ab-container">

                        <nav class="ab-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'audioshelf' ); ?>">
                                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'audioshelf' ); ?></a> ›
                                <a href="<?php echo esc_url( audioshelf_catalog_url() ); ?>"><?php esc_html_e( 'Catalog', 'audioshelf' ); ?></a> ›
                                <span><?php the_title(); ?></span>
                        </nav>

                        <div class="ab-single-layout">

                                <div class="ab-single-cover-col">
                                        <div class="ab-single-cover">
                                                <?php if ( has_post_thumbnail() ) : ?>
                                                        <?php the_post_thumbnail( 'large', array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
                                                <?php else : ?>
                                                        <div class="ab-tile-cover-fallback ab-tile-cover-fallback-lg"><?php the_title(); ?></div>
                                                <?php endif; ?>
                                        </div>

                                        <div class="ab-player" role="group" aria-label="<?php esc_attr_e( 'Audio player placeholder', 'audioshelf' ); ?>">
                                                <button type="button" class="ab-player-btn" aria-label="<?php esc_attr_e( 'Play', 'audioshelf' ); ?>">▶</button>
                                                <div class="ab-player-track">
                                                        <span class="ab-player-bar"><span style="width:0%"></span></span>
                                                        <span class="ab-player-time">
                                                                <span>0:00</span>
                                                                <span><?php echo esc_html( audioshelf_format_duration( $ab_duration ) ); ?></span>
                                                        </span>
                                                </div>
                                        </div>
                                        <p class="ab-player-note"><?php esc_html_e( 'Hook your audio files or a streaming service up to this button — the markup is ready.', 'audioshelf' ); ?></p>
                                </div>

                                <div class="ab-single-info">
                                        <div class="ab-badge-row">
                                                <?php
                                                $ab_cats = get_the_term_list( $ab_id, 'ab_category', '', ' ', '' );
                                                if ( $ab_cats && ! is_wp_error( $ab_cats ) ) {
                                                        echo wp_kses_post( $ab_cats );
                                                }
                                                ?>
                                        </div>

                                        <h1 class="ab-single-title"><?php the_title(); ?></h1>

                                        <?php if ( $ab_author ) : ?>
                                                <p class="ab-single-author">
                                                        <?php
                                                        /* translators: %s: book author */
                                                        printf( esc_html__( 'by %s', 'audioshelf' ), '<strong>' . esc_html( $ab_author ) . '</strong>' );
                                                        ?>
                                                </p>
                                        <?php endif; ?>

                                        <?php if ( $ab_narrator ) : ?>
                                                <p class="ab-single-narrator">
                                                        <?php
                                                        /* translators: %s: narrator name */
                                                        printf( esc_html__( 'Narrated by %s', 'audioshelf' ), esc_html( $ab_narrator ) );
                                                        ?>
                                                </p>
                                        <?php endif; ?>

                                        <p class="ab-single-stats">
                                                <?php if ( $ab_rating > 0 ) : ?>
                                                        <span class="ab-stars" aria-hidden="true">★★★★★</span>
                                                        <strong><?php echo esc_html( number_format_i18n( $ab_rating, 1 ) ); ?></strong> ·
                                                <?php endif; ?>
                                                <?php if ( $ab_pop ) : ?>
                                                        <span><?php echo esc_html( audioshelf_format_count( $ab_pop ) ); ?> <?php esc_html_e( 'listens', 'audioshelf' ); ?></span> ·
                                                <?php endif; ?>
                                                <span><?php echo esc_html( audioshelf_format_duration( $ab_duration ) ); ?></span>
                                        </p>

                                        <div class="ab-single-desc">
                                                <?php the_content(); ?>
                                        </div>

                                        <?php
                                        $ab_terms = get_the_term_list( $ab_id, 'ab_tag', '', ' ', '' );
                                        if ( $ab_terms && ! is_wp_error( $ab_terms ) ) :
                                                ?>
                                                <div class="ab-badge-row ab-badge-tags"><?php echo wp_kses_post( $ab_terms ); ?></div>
                                        <?php endif; ?>

                                        <?php
                                        $ab_related = new WP_Query(
                                                array(
                                                        'post_type'      => 'audiobook',
                                                        'posts_per_page' => 4,
                                                        'post__not_in'   => array( $ab_id ),
                                                        'no_found_rows'  => true,
                                                        'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                                                                array(
                                                                        'taxonomy' => 'ab_category',
                                                                        'field'    => 'term_id',
                                                                        'terms'    => wp_get_post_terms( $ab_id, 'ab_category', array( 'fields' => 'ids' ) ),
                                                                ),
                                                        ),
                                                        'orderby'        => 'rand',
                                                )
                                        );
                                        if ( $ab_related->have_posts() ) :
                                                ?>
                                                <section class="ab-related">
                                                        <h2><?php esc_html_e( 'More like this', 'audioshelf' ); ?></h2>
                                                        <div class="ab-tile-grid">
                                                                <?php
                                                                while ( $ab_related->have_posts() ) :
                                                                        $ab_related->the_post();
                                                                        get_template_part( 'template-parts/tile-audiobook' );
                                                                endwhile;
                                                                wp_reset_postdata();
                                                                ?>
                                                        </div>
                                                </section>
                                        <?php endif; ?>
                                </div>

                        </div>
                </div>
        </main>

        <?php
endwhile;

get_footer();
