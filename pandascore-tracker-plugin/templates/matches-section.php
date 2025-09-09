<?php
/**
 * Template for matches section (live or upcoming)
 *
 * This template can be overridden by copying it to your theme's
 * pandascore-tracker/matches-section.php file.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 * 
 * Available variables:
 * @var string $section_type Section type ('live' or 'upcoming')
 * @var array $matches Array of match data
 * @var string $section_title Section title
 * @var bool $show_live_indicator Whether to show live indicator
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $matches ) ) {
    if ( 'upcoming' === $section_type ) {
        echo '<div class="pandascore-no-matches">No upcoming matches found.</div>';
    }
    return;
}
?>

<div class="pandascore-section-header">
    <?php if ( $show_live_indicator ) : ?>
        <span class="pandascore-live-indicator"></span>
    <?php endif; ?>
    <?php echo esc_html( $section_title ); ?>
</div>

<div class="pandascore-matches-container">
    <?php foreach ( $matches as $match ) : ?>
        <?php
        // Include the match card template
        // In a real implementation, you might want to use a template loader
        // that checks for theme overrides
        include 'match-card.php';
        ?>
    <?php endforeach; ?>
</div>
