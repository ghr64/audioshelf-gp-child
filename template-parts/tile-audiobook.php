<?php
/**
 * One audiobook tile — used by the homepage and the catalog grid.
 * Expects the Loop's current post.
 *
 * @package audioshelf
 */

defined( 'ABSPATH' ) || exit;

$ab_id         = get_the_ID();
$ab_author     = get_post_meta( $ab_id, 'ab_author', true );
$ab_duration   = get_post_meta( $ab_id, 'ab_duration', true );
$ab_rating     = get_post_meta( $ab_id, 'ab_rating', true );
$ab_lang_names = wp_strip_all_tags( get_the_term_list( $ab_id, 'ab_language', '', ', ', '' ) );
?>
<a class="ab-tile" href="<?php the_permalink(); ?>">
        <span class="ab-tile-cover">
                <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'ab-cover', array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
                <?php else : ?>
                        <span class="ab-tile-cover-fallback"><?php echo esc_html( wp_trim_words( get_the_title(), 3 ) ); ?></span>
                <?php endif; ?>
                <?php echo audioshelf_rating_badge( $ab_rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </span>
        <span class="ab-tile-title"><?php the_title(); ?></span>
        <?php if ( $ab_author ) : ?>
                <span class="ab-tile-author"><?php echo esc_html( $ab_author ); ?></span>
        <?php endif; ?>
        <span class="ab-tile-meta">
                <?php if ( $ab_duration ) : ?>
                        <span class="ab-tile-duration"><?php echo esc_html( audioshelf_format_duration( $ab_duration ) ); ?></span>
                <?php endif; ?>
                <?php if ( $ab_lang_names ) : ?>
                        <span class="ab-tile-language"><?php echo esc_html( $ab_lang_names ); ?></span>
                <?php endif; ?>
        </span>
</a>
