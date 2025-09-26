<?php
/**
 * Match Details Template
 * Displays detailed information about a specific match
 */

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Match_Details {
    private $plugin;
    private $match_id;
    private $match_data;

    public function __construct($plugin, $match_id) {
        $this->plugin = $plugin;
        $this->match_id = intval($match_id);
        $this->load_match_data();
    }

    private function load_match_data() {
        $api_key = $this->plugin->get_public_api_key();
        if (!$api_key) {
            $this->match_data = new WP_Error('no_api_key', 'API key not configured');
            return;
        }

        $url = "https://api.pandascore.co/matches/{$this->match_id}";
        $this->match_data = $this->plugin->public_cached_json_get($url, [
            'Authorization' => 'Bearer ' . $api_key
        ], 60, 300);
    }

    public function render() {
        if (is_wp_error($this->match_data)) {
            return $this->render_error($this->match_data->get_error_message());
        }

        if (empty($this->match_data)) {
            return $this->render_error('Match not found');
        }

        $match = $this->match_data;
        
        ob_start();
        ?>
        <div class="pandascore-match-details">
            <?php echo $this->render_header($match); ?>
            <?php echo $this->render_teams_section($match); ?>
            <?php echo $this->render_match_info($match); ?>
            <?php echo $this->render_games_section($match); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_error($message) {
        return '<div class="pandascore-error">Error: ' . esc_html($message) . '</div>';
    }

    private function render_header($match) {
        $league_name = esc_html($match['league']['name'] ?? 'Unknown League');
        $league_logo = esc_url($match['league']['image_url'] ?? '');
        $tournament_name = esc_html($match['tournament']['name'] ?? '');
        $status = esc_html($match['status'] ?? 'unknown');
        $scheduled_at = $match['scheduled_at'] ?? '';

        ob_start();
        ?>
        <div class="match-details-header">
            <div class="match-details-back">
                <button onclick="history.back()" class="back-button">← Back to Matches</button>
            </div>
            <div class="match-details-league">
                <?php if ($league_logo): ?>
                    <img src="<?php echo $league_logo; ?>" alt="<?php echo $league_name; ?>" class="league-logo-large">
                <?php endif; ?>
                <div class="league-info">
                    <h2><?php echo $league_name; ?></h2>
                    <?php if ($tournament_name): ?>
                        <p class="tournament-name"><?php echo $tournament_name; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="match-status status-<?php echo $status; ?>">
                <?php echo ucfirst($status); ?>
                <?php if ($scheduled_at && $status === 'not_started'): ?>
                    <div class="scheduled-time" data-scheduled-at="<?php echo esc_attr($scheduled_at); ?>">
                        <?php echo date('M j, Y H:i', strtotime($scheduled_at)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teams_section($match) {
        $opponents = $match['opponents'] ?? [];
        $results = $match['results'] ?? [];
        
        ob_start();
        ?>
        <div class="match-details-teams">
            <?php foreach ($opponents as $index => $opponent): ?>
                <?php
                $team = $opponent['opponent'] ?? [];
                $team_name = esc_html($team['name'] ?? 'TBD');
                $team_acronym = esc_html($team['acronym'] ?? $team_name);
                $team_logo = esc_url($team['image_url'] ?? '');
                $score = isset($results[$index]) ? intval($results[$index]['score'] ?? 0) : 0;
                ?>
                <div class="team-details">
                    <div class="team-logo-container">
                        <?php if ($team_logo): ?>
                            <img src="<?php echo $team_logo; ?>" alt="<?php echo $team_name; ?>" class="team-logo-large">
                        <?php else: ?>
                            <div class="team-logo-placeholder-large">
                                <?php echo strtoupper($team_acronym[0] ?? '?'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="team-info">
                        <h3 class="team-name"><?php echo $team_name; ?></h3>
                        <p class="team-acronym"><?php echo $team_acronym; ?></p>
                    </div>
                    <div class="team-score">
                        <?php echo $score; ?>
                    </div>
                </div>
                <?php if ($index === 0): ?>
                    <div class="vs-separator">VS</div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_match_info($match) {
        $match_type = esc_html($match['match_type'] ?? 'Unknown');
        $number_of_games = intval($match['number_of_games'] ?? 0);
        $begin_at = $match['begin_at'] ?? '';
        $end_at = $match['end_at'] ?? '';
        
        ob_start();
        ?>
        <div class="match-details-info">
            <h4>Match Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Format:</span>
                    <span class="info-value"><?php echo $match_type; ?></span>
                </div>
                <?php if ($number_of_games > 0): ?>
                <div class="info-item">
                    <span class="info-label">Best of:</span>
                    <span class="info-value"><?php echo $number_of_games; ?></span>
                </div>
                <?php endif; ?>
                <?php if ($begin_at): ?>
                <div class="info-item">
                    <span class="info-label">Started:</span>
                    <span class="info-value"><?php echo date('M j, Y H:i', strtotime($begin_at)); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($end_at): ?>
                <div class="info-item">
                    <span class="info-label">Ended:</span>
                    <span class="info-value"><?php echo date('M j, Y H:i', strtotime($end_at)); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_games_section($match) {
        $games = $match['games'] ?? [];
        
        if (empty($games)) {
            return '<div class="no-games">No game details available</div>';
        }

        ob_start();
        ?>
        <div class="match-details-games">
            <h4>Games</h4>
            <div class="games-list">
                <?php foreach ($games as $index => $game): ?>
                    <div class="game-item">
                        <div class="game-header">
                            <span class="game-number">Game <?php echo $index + 1; ?></span>
                            <span class="game-status status-<?php echo esc_attr($game['status'] ?? 'unknown'); ?>">
                                <?php echo ucfirst($game['status'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                        <?php if (!empty($game['teams'])): ?>
                            <div class="game-teams">
                                <?php foreach ($game['teams'] as $team): ?>
                                    <div class="game-team">
                                        <span class="team-name"><?php echo esc_html($team['name'] ?? 'Unknown'); ?></span>
                                        <span class="team-score"><?php echo intval($team['score'] ?? 0); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>