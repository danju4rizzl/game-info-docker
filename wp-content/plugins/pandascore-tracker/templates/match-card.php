<?php
/**
 * Template for individual match card
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 * 
 * Available variables:
 * @var array $match Processed match data
 * @var array $args Display arguments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_live = $match['is_live'] ?? false;
$is_upcoming = ! $is_live && ! empty( $match['display_time'] );
?>

<div class="<?php echo esc_attr( $args['card_class'] ); ?>" 
     data-match-id="<?php echo esc_attr( $match['id'] ); ?>"
     data-match-status="<?php echo $is_live ? 'live' : 'upcoming'; ?>">
     
    <div class="pandascore-match-inner">
        
        <?php if ( $args['show_league_logo'] && ! empty( $match['league_name'] ) ) : ?>
        <!-- League Logo Section -->
        <div class="pandascore-league-section">
            <?php if ( ! empty( $match['league_logo'] ) ) : ?>
                <div class="pandascore-league-logo">
                    <img src="<?php echo esc_url( $match['league_logo'] ); ?>" 
                         alt="<?php echo esc_attr( $match['league_name'] ); ?>"
                         loading="lazy">
                </div>
            <?php else : ?>
                <div class="pandascore-league-placeholder">
                    <?php echo esc_html( $match['league_initial'] ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Match Content -->
        <div class="pandascore-match-content">
            
            <?php if ( $is_upcoming ) : ?>
                <!-- Upcoming Match Layout -->
                <div class="pandascore-teams-section">
                    <!-- Team 1 -->
                    <div class="pandascore-team">
                        <?php if ( $args['show_team_logos'] && ! empty( $match['team1']['logo'] ) ) : ?>
                            <img src="<?php echo esc_url( $match['team1']['logo'] ); ?>" 
                                 alt="<?php echo esc_attr( $match['team1']['name'] ); ?>"
                                 class="pandascore-team-logo"
                                 loading="lazy">
                        <?php endif; ?>
                        <span class="pandascore-team-name">
                            <?php echo esc_html( $match['team1']['acronym'] ); ?>
                        </span>
                    </div>

                    <!-- Team 2 -->
                    <div class="pandascore-team">
                        <?php if ( $args['show_team_logos'] && ! empty( $match['team2']['logo'] ) ) : ?>
                            <img src="<?php echo esc_url( $match['team2']['logo'] ); ?>" 
                                 alt="<?php echo esc_attr( $match['team2']['name'] ); ?>"
                                 class="pandascore-team-logo"
                                 loading="lazy">
                        <?php endif; ?>
                        <span class="pandascore-team-name">
                            <?php echo esc_html( $match['team2']['acronym'] ); ?>
                        </span>
                    </div>
                </div>

                <!-- Match Time -->
                <div class="pandascore-match-time">
                    <div class="pandascore-time-display">
                        <div class="pandascore-time"><?php echo esc_html( $match['display_time'] ); ?></div>
                        <?php if ( ! empty( $match['display_day'] ) ) : ?>
                            <div class="pandascore-day"><?php echo esc_html( $match['display_day'] ); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else : ?>
                <!-- Live/Completed Match Layout -->
                <div class="pandascore-teams-with-scores">
                    <!-- Team 1 with Score -->
                    <div class="pandascore-team-row <?php echo $match['winner'] === 1 ? 'winner' : ( $match['winner'] === 2 ? 'loser' : '' ); ?>">
                        <div class="pandascore-team-info">
                            <?php if ( $args['show_team_logos'] && ! empty( $match['team1']['logo'] ) ) : ?>
                                <img src="<?php echo esc_url( $match['team1']['logo'] ); ?>" 
                                     alt="<?php echo esc_attr( $match['team1']['name'] ); ?>"
                                     class="pandascore-team-logo"
                                     loading="lazy">
                            <?php endif; ?>
                            <span class="pandascore-team-name">
                                <?php echo esc_html( $match['team1']['acronym'] ); ?>
                            </span>
                        </div>
                        <?php if ( $args['show_scores'] ) : ?>
                            <div class="pandascore-score" 
                                 data-opponent-id="<?php echo esc_attr( $match['team1']['id'] ); ?>">
                                <?php echo esc_html( $match['score1'] ); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Team 2 with Score -->
                    <div class="pandascore-team-row <?php echo $match['winner'] === 2 ? 'winner' : ( $match['winner'] === 1 ? 'loser' : '' ); ?>">
                        <div class="pandascore-team-info">
                            <?php if ( $args['show_team_logos'] && ! empty( $match['team2']['logo'] ) ) : ?>
                                <img src="<?php echo esc_url( $match['team2']['logo'] ); ?>" 
                                     alt="<?php echo esc_attr( $match['team2']['name'] ); ?>"
                                     class="pandascore-team-logo"
                                     loading="lazy">
                            <?php endif; ?>
                            <span class="pandascore-team-name">
                                <?php echo esc_html( $match['team2']['acronym'] ); ?>
                            </span>
                        </div>
                        <?php if ( $args['show_scores'] ) : ?>
                            <div class="pandascore-score" 
                                 data-opponent-id="<?php echo esc_attr( $match['team2']['id'] ); ?>">
                                <?php echo esc_html( $match['score2'] ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>
