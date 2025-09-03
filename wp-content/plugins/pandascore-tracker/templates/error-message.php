<?php
/**
 * Template for error messages
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 * 
 * Available variables:
 * @var string $message Error message
 * @var string $type Error type (error, warning, info)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="pandascore-message pandascore-message-<?php echo esc_attr( $type ); ?>">
    <div class="pandascore-message-content">
        <p><?php echo esc_html( $message ); ?></p>
    </div>
</div>
