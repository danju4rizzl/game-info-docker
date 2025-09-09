<?php
/**
 * Template for individual match card
 *
 * This template can be overridden by copying it to your theme's
 * pandascore-tracker/match-card.php file.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 * 
 * Available variables:
 * @var array $match Match data
 * @var bool $is_live Whether this is a live match
 * @var string $match_id Match ID
 * @var array $teams Team data
 * @var array $league League data
 * @var string $scheduled_at Scheduled time for upcoming matches
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$match_classes = array( 'pandascore-match' );
if ( $is_live ) {
    $match_classes[] = 'pandascore-match--live';
}
?>

<div class="<?php echo esc_attr( implode( ' ', $match_classes ) ); ?>" data-match-id="<?php echo esc_attr( $match_id ); ?>"<?php if ( ! $is_live && ! empty( $scheduled_at ) ) : ?> data-scheduled-at="<?php echo esc_attr( $scheduled_at ); ?>"<?php endif; ?>>
    
    <!-- League Section -->
    <div class="pandascore-league-container">
        <?php if ( ! empty( $league['logo'] ) ) : ?>
            <div class="pandascore-league-logo">
                <img src="<?php echo esc_url( $league['logo'] ); ?>" 
                     alt="<?php echo esc_attr( $league['name'] ); ?>" 
                     title="<?php echo esc_attr( $league['name'] ); ?>">
            </div>
        <?php else : ?>
            <div class="pandascore-league-placeholder" title="<?php echo esc_attr( $league['name'] ); ?>">
                <?php echo esc_html( ! empty( $league['name'] ) ? substr( $league['name'], 0, 1 ) : 'L' ); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Match Content -->
    <div class="pandascore-match-content<?php echo $is_live ? ' live-layout' : ''; ?>">
        
        <?php if ( ! $is_live ) : ?>
            <!-- Upcoming Match Layout -->
            <div class="pandascore-teams-container">
                <?php foreach ( $teams as $team ) : ?>
                    <div class="pandascore-team">
                        <div class="pandascore-team-info">
                            <?php if ( ! empty( $team['logo'] ) ) : ?>
                                <img src="<?php echo esc_url( $team['logo'] ); ?>" 
                                     alt="<?php echo esc_attr( $team['name'] ); ?>" 
                                     class="pandascore-team-logo">
                            <?php else : ?>
                                <div class="pandascore-team-logo-placeholder" title="<?php echo esc_attr( $team['name'] ); ?>">
                                    <?php echo esc_html( ! empty( $team['acronym'] ) && 'N/A' !== $team['acronym'] ? strtoupper( substr( $team['acronym'], 0, 1 ) ) : '?' ); ?>
                                </div>
                            <?php endif; ?>
                            <span class="pandascore-team-name" title="<?php echo esc_attr( $team['name'] ); ?>">
                                <?php echo esc_html( $team['acronym'] ?: $team['name'] ); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Time Container (populated by JavaScript) -->
            <div class="pandascore-time-container">
                <div class="pandascore-time-badge">
                    <div class="pandascore-time">Loading...</div>
                    <div class="pandascore-time-day">Loading...</div>
                </div>
            </div>
            
        <?php else : ?>
            <!-- Live Match Layout -->
            <?php foreach ( $teams as $index => $team ) : ?>
                <div class="pandascore-team with-score">
                    <div class="pandascore-team-info">
                        <?php if ( ! empty( $team['logo'] ) ) : ?>
                            <img src="<?php echo esc_url( $team['logo'] ); ?>" 
                                 alt="<?php echo esc_attr( $team['name'] ); ?>" 
                                 class="pandascore-team-logo">
                        <?php else : ?>
                            <div class="pandascore-team-logo-placeholder" title="<?php echo esc_attr( $team['name'] ); ?>">
                                <?php echo esc_html( ! empty( $team['acronym'] ) && 'N/A' !== $team['acronym'] ? strtoupper( substr( $team['acronym'], 0, 1 ) ) : '?' ); ?>
                            </div>
                        <?php endif; ?>
                        <span class="pandascore-team-name" title="<?php echo esc_attr( $team['name'] ); ?>">
                            <?php echo esc_html( $team['acronym'] ?: $team['name'] ); ?>
                        </span>
                    </div>
                    <div class="pandascore-score" data-opponent-id="<?php echo esc_attr( $team['id'] ?: '' ); ?>">
                        <?php echo intval( $team['score'] ?: 0 ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
</div>
