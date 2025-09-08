<?php
/**
 * Match Renderer Class for PandaScore Tracker Plugin
 *
 * Handles all match display logic including team logos, match cards,
 * live indicators, and HTML generation.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Match Renderer Class
 *
 * Handles all match display and HTML generation
 */
class PandaScore_Match_Renderer extends PandaScore_Base_Component {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Render a single match
     *
     * @param array $match Match data from API
     * @param bool  $is_live Whether this is a live match
     * @return string HTML for the match
     */
    public function render_match( $match, $is_live = false ) {
        if ( empty( $match ) || ! is_array( $match ) ) {
            return '';
        }

        $match_data = $this->extract_match_data( $match );
        $match_id = isset( $match['id'] ) ? $this->esc_attr_safe( $match['id'] ) : '';

        $html = '<div class="pandascore-match" data-match-id="' . $match_id . '"';

        // Add scheduled time for upcoming matches
        if ( ! $is_live && ! empty( $match_data['scheduled_at'] ) ) {
            $html .= ' data-scheduled-at="' . $this->esc_attr_safe( $match_data['scheduled_at'] ) . '"';
        }

        $html .= '>';

        // League logo container
        $html .= $this->render_league_section( $match_data['league'] );

        // Match content
        if ( $is_live ) {
            $html .= $this->render_live_match_content( $match_data );
        } else {
            $html .= $this->render_upcoming_match_content( $match_data );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render live matches section
     *
     * @param array $matches Array of live matches
     * @return string HTML for live matches section
     */
    public function render_live_matches_section( $matches ) {
        if ( empty( $matches ) ) {
            return '';
        }

        $html = '<div class="pandascore-section-header">';
        $html .= '<span class="pandascore-live-indicator"></span>LIVE</div>';
        $html .= '<div class="pandascore-matches-container">';

        foreach ( $matches as $match ) {
            $html .= $this->render_match( $match, true );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render upcoming matches section
     *
     * @param array $matches Array of upcoming matches
     * @return string HTML for upcoming matches section
     */
    public function render_upcoming_matches_section( $matches ) {
        if ( empty( $matches ) ) {
            return '<div class="pandascore-no-matches">No upcoming matches found.</div>';
        }

        $html = '<div class="pandascore-section-header">UPCOMING</div>';
        $html .= '<div class="pandascore-matches-container">';

        foreach ( $matches as $match ) {
            $html .= $this->render_match( $match, false );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render error message
     *
     * @param WP_Error $error WordPress error object
     * @return string HTML for error display
     */
    public function render_error( $error ) {
        $message = $this->format_error_message( $error );
        return '<div class="pandascore-error">' . $this->esc_html_safe( $message ) . '</div>';
    }

    /**
     * Generate team logo HTML with fallback placeholder
     *
     * @param string $logo_url Team logo URL
     * @param string $team_name Team name for alt text and fallback
     * @param string $acronym Team acronym for fallback display
     * @return string HTML for team logo or placeholder
     */
    public function get_team_logo_html( $logo_url, $team_name, $acronym ) {
        if ( ! empty( $logo_url ) && $this->is_valid_url( $logo_url ) ) {
            return sprintf(
                '<img src="%s" alt="%s" class="pandascore-team-logo">',
                esc_url( $logo_url ),
                $this->esc_attr_safe( $team_name )
            );
        }

        // Create fallback using first letter of acronym or team name
        $fallback_letter = $this->get_fallback_letter( $acronym, $team_name );

        return sprintf(
            '<div class="pandascore-team-logo-placeholder" title="%s">%s</div>',
            $this->esc_attr_safe( $team_name ?: 'Unknown Team' ),
            $this->esc_html_safe( $fallback_letter )
        );
    }

    /**
     * Extract and normalize match data
     *
     * @param array $match Raw match data from API
     * @return array Normalized match data
     */
    private function extract_match_data( $match ) {
        $data = array(
            'opponents' => array(),
            'scores' => array(),
            'opponent_ids' => array(),
            'league' => array(),
            'scheduled_at' => ''
        );

        // Extract opponent data
        if ( isset( $match['opponents'] ) && is_array( $match['opponents'] ) ) {
            foreach ( $match['opponents'] as $opponent ) {
                $data['opponents'][] = array(
                    'name' => isset( $opponent['opponent']['name'] ) ? $opponent['opponent']['name'] : 'N/A',
                    'acronym' => isset( $opponent['opponent']['acronym'] ) ? $opponent['opponent']['acronym'] : '',
                    'logo' => isset( $opponent['opponent']['image_url'] ) ? $opponent['opponent']['image_url'] : '',
                    'id' => isset( $opponent['opponent']['id'] ) ? $opponent['opponent']['id'] : null
                );
            }
        }

        // Ensure we have at least 2 opponents
        while ( count( $data['opponents'] ) < 2 ) {
            $data['opponents'][] = array(
                'name' => 'N/A',
                'acronym' => 'N/A',
                'logo' => '',
                'id' => null
            );
        }

        // Extract scores
        if ( isset( $match['results'] ) && is_array( $match['results'] ) ) {
            foreach ( $match['results'] as $result ) {
                $data['scores'][] = isset( $result['score'] ) ? intval( $result['score'] ) : 0;
            }
        }

        // Ensure we have at least 2 scores
        while ( count( $data['scores'] ) < 2 ) {
            $data['scores'][] = 0;
        }

        // Extract league data
        if ( isset( $match['league'] ) ) {
            $data['league'] = array(
                'name' => isset( $match['league']['name'] ) ? $match['league']['name'] : '',
                'logo' => isset( $match['league']['image_url'] ) ? $match['league']['image_url'] : ''
            );
        }

        // Extract scheduled time
        if ( isset( $match['scheduled_at'] ) && $match['scheduled_at'] ) {
            $data['scheduled_at'] = $match['scheduled_at'];
        }

        return $data;
    }

    /**
     * Render league section
     *
     * @param array $league League data
     * @return string HTML for league section
     */
    private function render_league_section( $league ) {
        $html = '<div class="pandascore-league-container">';

        if ( ! empty( $league['logo'] ) && $this->is_valid_url( $league['logo'] ) ) {
            $html .= sprintf(
                '<div class="pandascore-league-logo"><img src="%s" alt="%s" title="%s"></div>',
                esc_url( $league['logo'] ),
                $this->esc_attr_safe( $league['name'] ),
                $this->esc_attr_safe( $league['name'] )
            );
        } else {
            $fallback_letter = ! empty( $league['name'] ) ? substr( $league['name'], 0, 1 ) : 'L';
            $html .= sprintf(
                '<div class="pandascore-league-placeholder" title="%s">%s</div>',
                $this->esc_attr_safe( $league['name'] ),
                $this->esc_html_safe( $fallback_letter )
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render live match content
     *
     * @param array $match_data Normalized match data
     * @return string HTML for live match content
     */
    private function render_live_match_content( $match_data ) {
        $html = '<div class="pandascore-match-content live-layout">';

        // Team 1
        $html .= $this->render_team_with_score( $match_data['opponents'][0], $match_data['scores'][0] );

        // Team 2
        $html .= $this->render_team_with_score( $match_data['opponents'][1], $match_data['scores'][1] );

        $html .= '</div>';

        return $html;
    }

    /**
     * Render upcoming match content
     *
     * @param array $match_data Normalized match data
     * @return string HTML for upcoming match content
     */
    private function render_upcoming_match_content( $match_data ) {
        $html = '<div class="pandascore-match-content">';

        // Teams container
        $html .= '<div class="pandascore-teams-container">';
        $html .= $this->render_team( $match_data['opponents'][0] );
        $html .= $this->render_team( $match_data['opponents'][1] );
        $html .= '</div>';

        // Time container (populated by JavaScript)
        $html .= '<div class="pandascore-time-container">';
        $html .= '<div class="pandascore-time-badge">';
        $html .= '<div class="pandascore-time">Loading...</div>';
        $html .= '<div class="pandascore-time-day">Loading...</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render team with score (for live matches)
     *
     * @param array $team Team data
     * @param int   $score Team score
     * @return string HTML for team with score
     */
    private function render_team_with_score( $team, $score ) {
        $html = '<div class="pandascore-team with-score">';
        $html .= '<div class="pandascore-team-info">';
        $html .= $this->get_team_logo_html( $team['logo'], $team['name'], $team['acronym'] );
        $html .= sprintf(
            '<span class="pandascore-team-name" title="%s">%s</span>',
            $this->esc_attr_safe( $team['name'] ),
            $this->esc_html_safe( $team['acronym'] ?: $team['name'] )
        );
        $html .= '</div>';
        $html .= sprintf(
            '<div class="pandascore-score" data-opponent-id="%s">%d</div>',
            $this->esc_attr_safe( $team['id'] ?: '' ),
            intval( $score )
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * Render team (for upcoming matches)
     *
     * @param array $team Team data
     * @return string HTML for team
     */
    private function render_team( $team ) {
        $html = '<div class="pandascore-team">';
        $html .= '<div class="pandascore-team-info">';
        $html .= $this->get_team_logo_html( $team['logo'], $team['name'], $team['acronym'] );
        $html .= sprintf(
            '<span class="pandascore-team-name" title="%s">%s</span>',
            $this->esc_attr_safe( $team['name'] ),
            $this->esc_html_safe( $team['acronym'] ?: $team['name'] )
        );
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get fallback letter for team logo placeholder
     *
     * @param string $acronym Team acronym
     * @param string $team_name Team name
     * @return string Single character for placeholder
     */
    private function get_fallback_letter( $acronym, $team_name ) {
        if ( ! empty( $acronym ) && 'N/A' !== $acronym ) {
            return strtoupper( substr( $acronym, 0, 1 ) );
        }

        if ( ! empty( $team_name ) && 'N/A' !== $team_name ) {
            return strtoupper( substr( $team_name, 0, 1 ) );
        }

        return '?';
    }
}
