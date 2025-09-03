<?php
/**
 * Template for no matches message
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 * 
 * Available variables:
 * @var string $message Message to display
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="pandascore-no-matches">
    <div class="pandascore-no-matches-content">
        <p><?php echo esc_html( $message ); ?></p>
    </div>
</div>
