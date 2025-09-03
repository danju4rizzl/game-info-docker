<?php
/**
 * Template for upcoming matches section
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 * 
 * Available variables:
 * @var array $matches Array of upcoming matches
 * @var array $args Display arguments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="<?php echo esc_attr( $args['container_class'] ); ?>">
    
    <?php if ( $args['show_upcoming_indicator'] ) : ?>
    <!-- Upcoming Indicator -->
    <div class="pandascore-section-header pandascore-upcoming-header">
        <span class="pandascore-section-title"><?php esc_html_e( 'UPCOMING', 'pandascore-tracker' ); ?></span>
    </div>
    <?php endif; ?>

    <!-- Matches Container -->
    <div class="pandascore-matches-container">
        <?php foreach ( $matches as $match ) : ?>
            <?php
            // Use the renderer to render each match card
            $renderer = new PandaScore_Renderer();
            echo $renderer->render_match_card( $match, array(
                'card_class' => 'pandascore-match-card pandascore-upcoming-match',
            ) );
            ?>
        <?php endforeach; ?>
    </div>
    
</div>
