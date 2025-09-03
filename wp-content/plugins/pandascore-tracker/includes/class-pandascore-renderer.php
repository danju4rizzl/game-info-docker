<?php
/**
 * Renderer Component for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles all rendering and template functionality
 * 
 * Provides methods for rendering matches, sections, and managing templates
 * with theme override support.
 */
class PandaScore_Renderer extends PandaScore_Base_Component {

    /**
     * Template directory path
     *
     * @var string
     */
    private $template_path;

    /**
     * Theme template override path
     *
     * @var string
     */
    private $theme_template_path;

    /**
     * Constructor
     */
    public function __construct() {
        $this->template_path = dirname( dirname( __FILE__ ) ) . '/templates/';
        $this->theme_template_path = get_template_directory() . '/pandascore-tracker/';
    }

    /**
     * Render live matches section
     *
     * @param array $matches Array of live matches
     * @param array $args Display arguments
     * @return string HTML output
     */
    public function render_live_matches_section( $matches, $args = array() ) {
        if ( empty( $matches ) ) {
            return '';
        }

        $defaults = array(
            'show_live_indicator' => true,
            'container_class'     => 'pandascore-live-matches',
        );
        $args = wp_parse_args( $args, $defaults );

        $template_data = array(
            'matches' => $matches,
            'args'    => $args,
        );

        return $this->load_template( 'live-matches-section', $template_data );
    }

    /**
     * Render upcoming matches section
     *
     * @param array $matches Array of upcoming matches
     * @param array $args Display arguments
     * @return string HTML output
     */
    public function render_upcoming_matches_section( $matches, $args = array() ) {
        if ( empty( $matches ) ) {
            return $this->render_no_matches_message( __( 'No upcoming matches found.', 'pandascore-tracker' ) );
        }

        $defaults = array(
            'show_upcoming_indicator' => true,
            'container_class'         => 'pandascore-upcoming-matches',
        );
        $args = wp_parse_args( $args, $defaults );

        $template_data = array(
            'matches' => $matches,
            'args'    => $args,
        );

        return $this->load_template( 'upcoming-matches-section', $template_data );
    }

    /**
     * Render individual match card
     *
     * @param array $match Match data
     * @param array $args Display arguments
     * @return string HTML output
     */
    public function render_match_card( $match, $args = array() ) {
        if ( empty( $match ) || empty( $match['id'] ) ) {
            return '';
        }

        $defaults = array(
            'show_league_logo' => true,
            'show_team_logos'  => true,
            'show_scores'      => true,
            'card_class'       => 'pandascore-match-card',
        );
        $args = wp_parse_args( $args, $defaults );

        // Process match data for display
        $processed_match = $this->process_match_for_display( $match );

        $template_data = array(
            'match' => $processed_match,
            'args'  => $args,
        );

        return $this->load_template( 'match-card', $template_data );
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @param string $type Error type (error, warning, info)
     * @return string HTML output
     */
    public function render_error_message( $message, $type = 'error' ) {
        $template_data = array(
            'message' => esc_html( $message ),
            'type'    => sanitize_html_class( $type ),
        );

        return $this->load_template( 'error-message', $template_data );
    }

    /**
     * Render no matches message
     *
     * @param string $message Message to display
     * @return string HTML output
     */
    public function render_no_matches_message( $message ) {
        $template_data = array(
            'message' => esc_html( $message ),
        );

        return $this->load_template( 'no-matches', $template_data );
    }

    /**
     * Render loading placeholder
     *
     * @param array $args Display arguments
     * @return string HTML output
     */
    public function render_loading_placeholder( $args = array() ) {
        $defaults = array(
            'count' => 3,
            'type'  => 'matches',
        );
        $args = wp_parse_args( $args, $defaults );

        $template_data = array(
            'args' => $args,
        );

        return $this->load_template( 'loading-placeholder', $template_data );
    }

    /**
     * Load template file with data
     *
     * @param string $template_name Template name (without .php extension)
     * @param array  $template_data Data to pass to template
     * @return string Template output
     */
    private function load_template( $template_name, $template_data = array() ) {
        // Check for theme override first
        $theme_template = $this->theme_template_path . $template_name . '.php';
        $plugin_template = $this->template_path . $template_name . '.php';

        $template_file = '';
        if ( file_exists( $theme_template ) ) {
            $template_file = $theme_template;
        } elseif ( file_exists( $plugin_template ) ) {
            $template_file = $plugin_template;
        }

        if ( empty( $template_file ) ) {
            $this->log_error( 'Template Not Found', array(
                'template' => $template_name,
                'paths'    => array( $theme_template, $plugin_template ),
            ) );
            return $this->render_fallback_content( $template_name, $template_data );
        }

        // Extract data for template
        extract( $template_data );

        // Start output buffering
        ob_start();
        
        // Include template
        include $template_file;
        
        // Get output and clean buffer
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Process match data for display
     *
     * @param array $match Raw match data
     * @return array Processed match data
     */
    private function process_match_for_display( $match ) {
        $processed = $match;

        // Format time
        if ( ! empty( $match['scheduled_at'] ) ) {
            $time_data = $this->format_match_time( $match['scheduled_at'] );
            $processed['display_time'] = $time_data['time'];
            $processed['display_day'] = $time_data['day'];
        } else {
            $processed['display_time'] = '';
            $processed['display_day'] = '';
        }

        // Process opponents
        $processed['team1'] = $this->get_team_data( $match, 0 );
        $processed['team2'] = $this->get_team_data( $match, 1 );

        // Process scores
        $processed['score1'] = $this->get_team_score( $match, 0 );
        $processed['score2'] = $this->get_team_score( $match, 1 );

        // Determine winner
        if ( $processed['score1'] > $processed['score2'] ) {
            $processed['winner'] = 1;
        } elseif ( $processed['score2'] > $processed['score1'] ) {
            $processed['winner'] = 2;
        } else {
            $processed['winner'] = 0; // Tie or no scores
        }

        // League data
        $processed['league_name'] = isset( $match['league']['name'] ) ? $match['league']['name'] : '';
        $processed['league_logo'] = isset( $match['league']['image_url'] ) ? $match['league']['image_url'] : '';
        $processed['league_initial'] = ! empty( $processed['league_name'] ) ? substr( $processed['league_name'], 0, 1 ) : '?';

        return $processed;
    }

    /**
     * Get team data by index
     *
     * @param array $match Match data
     * @param int   $index Team index (0 or 1)
     * @return array Team data
     */
    private function get_team_data( $match, $index ) {
        $default_team = array(
            'id'       => 0,
            'name'     => __( 'Unknown Team', 'pandascore-tracker' ),
            'acronym'  => __( 'TBD', 'pandascore-tracker' ),
            'logo'     => '',
        );

        if ( ! isset( $match['opponents'][$index] ) ) {
            return $default_team;
        }

        $opponent = $match['opponents'][$index];
        
        return array(
            'id'      => $opponent['id'] ?? 0,
            'name'    => $opponent['name'] ?? $default_team['name'],
            'acronym' => ! empty( $opponent['acronym'] ) ? $opponent['acronym'] : $opponent['name'] ?? $default_team['acronym'],
            'logo'    => $opponent['image_url'] ?? '',
        );
    }

    /**
     * Get team score by index
     *
     * @param array $match Match data
     * @param int   $index Team index (0 or 1)
     * @return int Team score
     */
    private function get_team_score( $match, $index ) {
        if ( ! isset( $match['results'][$index] ) ) {
            return 0;
        }

        return intval( $match['results'][$index]['score'] ?? 0 );
    }

    /**
     * Render fallback content when template is missing
     *
     * @param string $template_name Template name
     * @param array  $template_data Template data
     * @return string Fallback HTML
     */
    private function render_fallback_content( $template_name, $template_data ) {
        switch ( $template_name ) {
            case 'error-message':
                return sprintf(
                    '<div class="pandascore-error pandascore-error-%s"><p>%s</p></div>',
                    esc_attr( $template_data['type'] ?? 'error' ),
                    esc_html( $template_data['message'] ?? __( 'An error occurred.', 'pandascore-tracker' ) )
                );

            case 'no-matches':
                return sprintf(
                    '<div class="pandascore-no-matches"><p>%s</p></div>',
                    esc_html( $template_data['message'] ?? __( 'No matches found.', 'pandascore-tracker' ) )
                );

            default:
                return sprintf(
                    '<div class="pandascore-template-missing">%s</div>',
                    esc_html( sprintf( __( 'Template "%s" not found.', 'pandascore-tracker' ), $template_name ) )
                );
        }
    }

    /**
     * Get available template files
     *
     * @return array Array of available templates
     */
    public function get_available_templates() {
        $templates = array();
        
        // Scan plugin templates
        $plugin_templates = glob( $this->template_path . '*.php' );
        foreach ( $plugin_templates as $template ) {
            $name = basename( $template, '.php' );
            $templates[$name] = array(
                'name'   => $name,
                'path'   => $template,
                'source' => 'plugin',
            );
        }

        // Scan theme overrides
        if ( is_dir( $this->theme_template_path ) ) {
            $theme_templates = glob( $this->theme_template_path . '*.php' );
            foreach ( $theme_templates as $template ) {
                $name = basename( $template, '.php' );
                $templates[$name] = array(
                    'name'   => $name,
                    'path'   => $template,
                    'source' => 'theme',
                );
            }
        }

        return $templates;
    }
}
