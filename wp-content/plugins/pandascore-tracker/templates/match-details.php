<?php
if (!defined('ABSPATH')) { exit; }

get_header();
wp_enqueue_style('pandascore-tracker-style');
wp_enqueue_style('pandascore-match-details-style');

$match_id = intval(get_query_var('match'));
$opts = get_option('pandascore_tracker_options');
$api_key = isset($opts['api_key']) ? trim($opts['api_key']) : '';

$match = null;
$error = '';

if (!$match_id) {
    $error = 'Invalid match ID.';
} elseif (!$api_key) {
    $error = 'PandaScore API key not set.';
} else {
    $url = 'https://api.pandascore.co/matches/' . $match_id;
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'headers' => ['Authorization' => 'Bearer ' . $api_key]
    ]);

    if (is_wp_error($response)) {
        $error = 'Failed to fetch match details.';
    } elseif (wp_remote_retrieve_response_code($response) !== 200) {
        $error = 'API error: ' . wp_remote_retrieve_response_code($response);
    } else {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            $error = 'Invalid data from API.';
        } else {
            $match = $data;
        }
    }
}

if ($error) {
    echo '<div class="pandascore-error">' . esc_html($error) . '</div>';
    get_footer();
    return;
}

$league_name = $match['league']['name'] ?? 'LCK SPLIT 3, WEEK 12';
$scheduled_at = $match['scheduled_at'] ?? '';
$opponents = $match['opponents'] ?? [];
$results = $match['results'] ?? [];
$teamA = $opponents[0]['opponent'] ?? ['name' => 'T1', 'acronym' => 'T1'];
$teamB = $opponents[1]['opponent'] ?? ['name' => 'DRX', 'acronym' => 'DRX'];
$scoreA = $results[0]['score'] ?? 3;
$scoreB = $results[1]['score'] ?? 4;
?>



<div class="match-page">
    <div class="match-header">
        <h1><?php echo esc_html(strtoupper($league_name)); ?></h1>
        <div class="match-time"><?php echo $scheduled_at ? date('H:i - jS \of F Y', strtotime($scheduled_at)) : '12:00 - 7th of August 2025'; ?></div>
    </div>

    <div class="match-layout">
        <div class="sidebar">
            <!-- <button class="stats-button">See all player stats</button> -->
            <?php for($i = 0; $i < 3; $i++): ?>
            <div class="player-card">
                <img src="https://via.placeholder.com/40/666/fff?text=D" class="player-avatar">
                <div>
                    <div class="player-name">Developer</div>
                    <div class="player-role">Most Played Champs</div>
                    <div class="champion-icons">
                        <?php for($j = 0; $j < 5; $j++): ?>
                        <img src="https://via.placeholder.com/20/444/fff?text=C" class="champion-icon">
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="center-content">
            <div class="main-match">
                <div class="team-logos">
                    <div class="team-logo">
                        <img src="<?php echo esc_url($teamA['image_url'] ?? 'https://via.placeholder.com/80/ff0000/fff?text=T1'); ?>" alt="<?php echo esc_attr($teamA['name']); ?>">
                    </div>
                    <div class="vs-section"><img src="<?php echo plugin_dir_url(__DIR__) . 'images/VS.png'; ?>" alt="VS"></div>
                    <div class="team-logo">
                        <img src="<?php echo esc_url($teamB['image_url'] ?? 'https://via.placeholder.com/80/0066ff/fff?text=DRX'); ?>" alt="<?php echo esc_attr($teamB['name']); ?>">
                    </div>
                </div>
<!-- 
                <div class="prediction-title">Who would win?</div>
                <div class="prediction-buttons">
                    <button class="pick-button">Pick</button>
                    <button class="pick-button">Pick</button>
                </div> -->
            </div>
<!-- 
            <div class="head-to-head">
                <h2>HEAD TO HEAD</h2>
                <div class="h2h-summary">
                    <div class="team-summary">
                        <div>
                            <div>NAME</div>
                            <div class="team-wins"><?php echo intval($scoreA); ?> wins</div>
                        </div>
                        <img src="<?php echo esc_url($teamA['image_url'] ?? 'https://via.placeholder.com/40/ff0000/fff?text=T1'); ?>" class="team-small-logo">
                    </div>
                    <div class="team-summary">
                        <img src="<?php echo esc_url($teamB['image_url'] ?? 'https://via.placeholder.com/40/0066ff/fff?text=DRX'); ?>" class="team-small-logo">
                        <div>
                            <div>NAME</div>
                            <div class="team-wins"><?php echo intval($scoreB); ?> wins</div>
                        </div>
                    </div>
                </div>

                <div class="match-history">
                    <div class="history-match">
                        <div class="match-date">18/08/2025</div>
                        <div>Match name here</div>
                        <div class="match-result">
                            <span class="team-score red">NAME</span>
                            <span class="score">20 - 30</span>
                            <span class="team-score blue">NAME</span>
                        </div>
                    </div>
                    <div class="history-match">
                        <div class="match-date">18/08/2025</div>
                        <div>Match name here</div>
                        <div class="match-result">
                            <span class="team-score red">NAME</span>
                            <span class="score">15 - 25</span>
                            <span class="team-score blue">NAME</span>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>

        <div class="sidebar">
            <!-- <button class="stats-button">See all player stats</button> -->
            <?php for($i = 0; $i < 3; $i++): ?>
            <div class="player-card">
                <img src="https://via.placeholder.com/40/666/fff?text=D" class="player-avatar">
                <div>
                    <div class="player-name">Deejay</div>
                    <div class="player-role">Most Played Champs</div>
                    <div class="champion-icons">
                        <?php for($j = 0; $j < 5; $j++): ?>
                        <img src="https://via.placeholder.com/20/444/fff?text=C" class="champion-icon">
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>