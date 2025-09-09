<?php
/**
 * Error Handler Class for PandaScore Tracker Plugin
 *
 * Provides comprehensive error handling and logging functionality
 * following WordPress best practices.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Error Handler Class
 *
 * Centralizes error handling and logging for the plugin
 */
class PandaScore_Error_Handler extends PandaScore_Base_Component {

    /**
     * Error log file path
     *
     * @var string
     */
    private $log_file;

    /**
     * Maximum log file size in bytes (5MB)
     *
     * @var int
     */
    private $max_log_size = 5242880;

    /**
     * Error levels
     *
     * @var array
     */
    private $error_levels = array(
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    );

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log_file = WP_CONTENT_DIR . '/pandascore-tracker-errors.log';
    }

    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param string $level Error level (emergency, alert, critical, error, warning, notice, info, debug)
     * @param array  $context Additional context data
     * @param string $component Component that generated the error
     */
    public function log_error( $message, $level = 'error', $context = array(), $component = '' ) {
        // Only log if WP_DEBUG is enabled or error level is critical
        if ( ! $this->should_log_error( $level ) ) {
            return;
        }

        $log_entry = $this->format_log_entry( $message, $level, $context, $component );
        
        // Write to WordPress error log
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( $log_entry );
        }

        // Write to plugin-specific log file
        $this->write_to_log_file( $log_entry );

        // Send admin notification for critical errors
        if ( in_array( $level, array( 'emergency', 'alert', 'critical' ), true ) ) {
            $this->send_admin_notification( $message, $level, $context, $component );
        }
    }

    /**
     * Handle API errors specifically
     *
     * @param WP_Error $error WordPress error object
     * @param string   $endpoint API endpoint that failed
     * @param array    $request_data Request data
     */
    public function handle_api_error( $error, $endpoint = '', $request_data = array() ) {
        if ( ! is_wp_error( $error ) ) {
            return;
        }

        $context = array(
            'endpoint' => $endpoint,
            'request_data' => $request_data,
            'error_code' => $error->get_error_code(),
            'error_data' => $error->get_error_data()
        );

        $this->log_error(
            'API Error: ' . $error->get_error_message(),
            'error',
            $context,
            'API_Handler'
        );
    }

    /**
     * Handle cache errors
     *
     * @param string $operation Cache operation that failed
     * @param string $cache_key Cache key involved
     * @param mixed  $error_details Error details
     */
    public function handle_cache_error( $operation, $cache_key = '', $error_details = null ) {
        $context = array(
            'operation' => $operation,
            'cache_key' => $cache_key,
            'error_details' => $error_details
        );

        $this->log_error(
            'Cache Error: ' . $operation . ' operation failed',
            'warning',
            $context,
            'Cache_Manager'
        );
    }

    /**
     * Handle template errors
     *
     * @param string $template_name Template that failed to load
     * @param string $template_path Template path
     */
    public function handle_template_error( $template_name, $template_path = '' ) {
        $context = array(
            'template_name' => $template_name,
            'template_path' => $template_path
        );

        $this->log_error(
            'Template Error: Failed to load template ' . $template_name,
            'warning',
            $context,
            'Match_Renderer'
        );
    }

    /**
     * Get recent error logs
     *
     * @param int $limit Number of recent entries to retrieve
     * @return array Recent error log entries
     */
    public function get_recent_errors( $limit = 50 ) {
        if ( ! file_exists( $this->log_file ) ) {
            return array();
        }

        $lines = file( $this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( false === $lines ) {
            return array();
        }

        // Get the last $limit lines
        $recent_lines = array_slice( $lines, -$limit );
        
        $errors = array();
        foreach ( $recent_lines as $line ) {
            $parsed = $this->parse_log_entry( $line );
            if ( $parsed ) {
                $errors[] = $parsed;
            }
        }

        return array_reverse( $errors ); // Most recent first
    }

    /**
     * Clear error logs
     *
     * @return bool True on success, false on failure
     */
    public function clear_error_logs() {
        if ( file_exists( $this->log_file ) ) {
            return unlink( $this->log_file );
        }
        return true;
    }

    /**
     * Get error statistics
     *
     * @return array Error statistics
     */
    public function get_error_stats() {
        $errors = $this->get_recent_errors( 1000 ); // Get more for stats
        
        $stats = array(
            'total_errors' => count( $errors ),
            'by_level' => array(),
            'by_component' => array(),
            'recent_24h' => 0
        );

        $yesterday = time() - DAY_IN_SECONDS;

        foreach ( $errors as $error ) {
            // Count by level
            $level = $error['level'] ?? 'unknown';
            $stats['by_level'][ $level ] = ( $stats['by_level'][ $level ] ?? 0 ) + 1;

            // Count by component
            $component = $error['component'] ?? 'unknown';
            $stats['by_component'][ $component ] = ( $stats['by_component'][ $component ] ?? 0 ) + 1;

            // Count recent errors
            if ( isset( $error['timestamp'] ) && $error['timestamp'] > $yesterday ) {
                $stats['recent_24h']++;
            }
        }

        return $stats;
    }

    /**
     * Check if error should be logged based on level and settings
     *
     * @param string $level Error level
     * @return bool True if should log, false otherwise
     */
    private function should_log_error( $level ) {
        // Always log critical errors
        if ( in_array( $level, array( 'emergency', 'alert', 'critical' ), true ) ) {
            return true;
        }

        // Log other errors only if WP_DEBUG is enabled
        return defined( 'WP_DEBUG' ) && WP_DEBUG;
    }

    /**
     * Format log entry
     *
     * @param string $message Error message
     * @param string $level Error level
     * @param array  $context Context data
     * @param string $component Component name
     * @return string Formatted log entry
     */
    private function format_log_entry( $message, $level, $context, $component ) {
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $level = strtoupper( $level );
        
        $entry = sprintf(
            '[%s] %s %s: %s',
            $timestamp,
            $level,
            $component ? "[$component]" : '',
            $message
        );

        if ( ! empty( $context ) ) {
            $entry .= ' | Context: ' . wp_json_encode( $context );
        }

        return $entry;
    }

    /**
     * Write to plugin log file
     *
     * @param string $log_entry Formatted log entry
     */
    private function write_to_log_file( $log_entry ) {
        // Check file size and rotate if necessary
        if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > $this->max_log_size ) {
            $this->rotate_log_file();
        }

        // Write to log file
        file_put_contents( $this->log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX );
    }

    /**
     * Rotate log file when it gets too large
     */
    private function rotate_log_file() {
        $backup_file = $this->log_file . '.old';
        
        if ( file_exists( $backup_file ) ) {
            unlink( $backup_file );
        }
        
        rename( $this->log_file, $backup_file );
    }

    /**
     * Send admin notification for critical errors
     *
     * @param string $message Error message
     * @param string $level Error level
     * @param array  $context Context data
     * @param string $component Component name
     */
    private function send_admin_notification( $message, $level, $context, $component ) {
        // Store notification in transient for admin display
        $notification = array(
            'message' => $message,
            'level' => $level,
            'component' => $component,
            'timestamp' => time(),
            'context' => $context
        );

        set_transient( 'pandascore_critical_error', $notification, HOUR_IN_SECONDS );
    }

    /**
     * Parse log entry back into components
     *
     * @param string $log_line Log line to parse
     * @return array|null Parsed log entry or null if parsing failed
     */
    private function parse_log_entry( $log_line ) {
        // Simple regex to parse log format
        $pattern = '/^\[([^\]]+)\] (\w+) (?:\[([^\]]+)\])?: (.+)$/';
        
        if ( preg_match( $pattern, $log_line, $matches ) ) {
            return array(
                'timestamp' => strtotime( $matches[1] ),
                'level' => strtolower( $matches[2] ),
                'component' => $matches[3] ?? '',
                'message' => $matches[4] ?? ''
            );
        }

        return null;
    }
}
