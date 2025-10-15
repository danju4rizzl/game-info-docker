<?php
if (!defined('ABSPATH')) { exit; }

get_header();
wp_enqueue_style('pandascore-tracker-style');
wp_enqueue_style('pandascore-match-details-style');
wp_enqueue_script('pandascore-timezone-js');

$match_id = intval(get_query_var('match'));

if (!$match_id) {
    echo '<div class="pandascore-error">Invalid match ID.</div>';
    get_footer();
    return;
}

$match_details = new PandaScore_Match_Details($match_id);
$match = $match_details->get_match();

if (!$match) {
    echo '<div class="pandascore-error">Match not found.</div>';
    get_footer();
    return;
}

$league_info = $match_details->get_league_info();
$teams = $match_details->get_teams();
$players = $match_details->get_sorted_players();
$main_stream = $match_details->get_main_stream();

$league_name = $league_info['name'];
$tournament_name = $league_info['tournament_name'];
$match_name = $league_info['match_name'];
$scheduled_at = $league_info['scheduled_at'];
$teamA = $teams['teamA'];
$teamB = $teams['teamB'];
$scoreA = $teams['scoreA'];
$scoreB = $teams['scoreB'];
?>

<script>
console.log('Match Details Data:', <?php echo json_encode($match, JSON_PRETTY_PRINT); ?>);
</script>

<div class="match-page">
    <div class="match-header" data-scheduled-at="<?php echo esc_attr($scheduled_at); ?>">
        <h1 class="match-league">
            <span>
                <?php echo esc_html(strtoupper($league_name));
             ?>
            </span>
            <?php if ($tournament_name): ?>
            <span>
                <?php echo esc_html($tournament_name); ?></span>
            <?php endif; ?>
            <?php if ($match_name): ?>
                <span><?php echo esc_html($match_name); ?></span>
                <?php endif; ?>        
        </h1>
        <div class="match-time">Loading...</div>
    </div>

    <div class="match-layout">
        <div class="sidebar">
            <?php 
            foreach($players['team1'] as $player): 
            ?>
            <div class="player-card">
                <img src="<?php echo esc_url($player['image_url'] ?: 'https://via.placeholder.com/40/666/fff?text=' . substr($player['name'], 0, 1)); ?>" class="player-avatar">
                <div>
                    <div class="player-name"><?php echo esc_html($player['name']); ?></div>
                    <div class="player-role"><?php echo esc_html(strtoupper($player['role'])); ?></div>
                    <div class="champion-icons">
                        <?php for($j = 0; $j < 5; $j++): ?>
                        <img src="https://via.placeholder.com/20/444/fff?text=C" class="champion-icon">
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="center-content">
            <div class="main-match">
                <div class="team-logos">
                    <div class="team-logo">
                        <img src="<?php echo esc_url($teamA['image_url'] ?? 'https://via.placeholder.com/80/ff0000/fff?text=T1'); ?>" alt="<?php echo esc_attr($teamA['name']); ?>">
                    </div>
                    <div class="vs-section">
                        <img src="<?php echo plugin_dir_url(__DIR__) . 'images/VS.png'; ?>" alt="VS">
                    </div>
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
            <?php 
            foreach($players['team2'] as $player): 
            ?>
            <div class="player-card">
                <img src="<?php echo esc_url($player['image_url'] ?: 'https://via.placeholder.com/40/666/fff?text=' . substr($player['name'], 0, 1)); ?>" class="player-avatar">
                <div>
                    <div class="player-name"><?php echo esc_html($player['name']); ?></div>
                    <div class="player-role"><?php echo esc_html(strtoupper($player['role'])); ?></div>
                    <div class="champion-icons">
                        <?php for($j = 0; $j < 5; $j++): ?>
                        <img src="https://via.placeholder.com/20/444/fff?text=C" class="champion-icon">
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

        <div class="live-stream-section">
            <div class="live-stream-container">
                <div class="stream-header">
                    <span class="live-badge">Live</span>
                    <h2>STREAM</h2>
                </div>
                <div class="stream-layout">
                    <div class="main-stream">
                        <div class="stream-player">
                            <?php if ($main_stream): ?>
                            <iframe
                                src="<?php echo esc_url($main_stream); ?>"
                                height="592px"
                                width="100%"
                                frameborder="0"
                                allow="autoplay"
                                allowfullscreen>
                            </iframe>
                            <?php else: ?>
                            <div class="play-button">
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                                    <circle cx="30" cy="30" r="30" fill="rgba(255,255,255,0.2)"/>
                                    <path d="M23 18L40 30L23 42V18Z" fill="white"/>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stream-list">
                        <?php for($i = 0; $i < 15; $i++): ?>
                        <div class="stream-item">
                            <span class="stream-name">Name of the stream here</span>
                            <div class="twitch-icon">
                               <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 300 300"><path fill-rule="evenodd" clip-rule="evenodd" fill="#65459B" d="M215.2 260.8h-58.7L117.4 300H78.3v-39.2H6.6V52.2L26.1 0h267.3v182.6l-78.2 78.2zm52.2-91.2V26.1H52.2v189.1h58.7v39.1l39.1-39.1h71.7l45.7-45.6z"/><path fill="#65459B" d="M195.6 78.3v78.3h26.1V78.3h-26.1zm-71.7 78.2H150V78.3h-26.1v78.2z"/></svg>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
    </div>

</div>

<?php get_footer(); ?>