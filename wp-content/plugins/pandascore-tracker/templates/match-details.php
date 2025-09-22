<?php
if (!defined('ABSPATH')) { exit; }

get_header();

// Enqueue plugin styles and optional timezone script
wp_enqueue_style('pandascore-tracker-style');
wp_enqueue_script('pandascore-timezone-js');

$match_id = intval(get_query_var('game_match_id'));
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

function pandascore_team_block($team) {
    $name = isset($team['name']) ? esc_html($team['name']) : 'TBD';
    $acronym = !empty($team['acronym']) ? esc_html($team['acronym']) : $name;
    $logo = isset($team['image_url']) ? esc_url($team['image_url']) : '';
    $html = '<div class="pandascore-team">';
    $html .= '<div class="pandascore-team-info">';
    if ($logo) {
        $html .= '<img src="' . $logo . '" alt="' . $name . '" class="pandascore-team-logo">';
    } else {
        $html .= '<div class="pandascore-team-logo-placeholder" title="Unknown Team">' . esc_html(mb_substr($name, 0, 1)) . '</div>';
    }
    $html .= '<span class="pandascore-team-name" title="' . $name . '">' . $acronym . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

?>
<div class="pandascore-match-details-container" style="max-width: 980px; margin: 32px auto; padding: 0 16px;">
    <a href="javascript:history.back()" style="display:inline-block;margin-bottom:16px;">&larr; Back</a>

    <?php if ($error): ?>
        <div class="pandascore-error"><?php echo esc_html($error); ?></div>
    <?php elseif ($match): ?>
        <?php
            $league_name = isset($match['league']['name']) ? esc_html($match['league']['name']) : '';
            $league_logo = isset($match['league']['image_url']) ? esc_url($match['league']['image_url']) : '';
            $status = isset($match['status']) ? esc_html($match['status']) : '';
            $scheduled_at = !empty($match['scheduled_at']) ? esc_html($match['scheduled_at']) : '';
            $begin_at = !empty($match['begin_at']) ? esc_html($match['begin_at']) : '';
            $live_supported = isset($match['live']['supported']) ? (bool)$match['live']['supported'] : false;
            $results = isset($match['results']) && is_array($match['results']) ? $match['results'] : [];
            $opponents = isset($match['opponents']) && is_array($match['opponents']) ? $match['opponents'] : [];
        ?>

        <div class="pandascore-match-details-card" style="background:#0f1320;border-radius:12px;padding:16px 16px 24px;">
            <div class="pandascore-league-container" style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <?php if ($league_logo): ?>
                    <div class="pandascore-league-logo" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                        <img src="<?php echo $league_logo; ?>" alt="<?php echo $league_name; ?>" title="<?php echo $league_name; ?>" style="width:100%;height:100%;object-fit:contain;" />
                    </div>
                <?php endif; ?>
                <div style="font-weight:600;"><?php echo $league_name; ?></div>
                <div style="margin-left:auto;font-size:12px;opacity:0.8;">Status: <?php echo $status; ?><?php echo $live_supported ? ' • Live supported' : ''; ?></div>
            </div>

            <div class="pandascore-teams-container" style="display:flex;gap:12px;align-items:center;justify-content:space-between;">
                <?php
                    $teamA = isset($opponents[0]['opponent']) ? $opponents[0]['opponent'] : ['name' => 'TBD'];
                    $teamB = isset($opponents[1]['opponent']) ? $opponents[1]['opponent'] : ['name' => 'TBD'];
                    $scoreA = isset($results[0]['score']) ? intval($results[0]['score']) : null;
                    $scoreB = isset($results[1]['score']) ? intval($results[1]['score']) : null;
                ?>
                <div style="flex:1;display:flex;justify-content:flex-start;">
                    <?php echo pandascore_team_block($teamA); ?>
                </div>
                <div class="pandascore-scoreboard" style="min-width:80px;text-align:center;font-weight:700;font-size:20px;">
                    <?php echo $scoreA !== null && $scoreB !== null ? intval($scoreA) . ' : ' . intval($scoreB) : 'vs'; ?>
                </div>
                <div style="flex:1;display:flex;justify-content:flex-end;">
                    <?php echo pandascore_team_block($teamB); ?>
                </div>
            </div>

            <div class="pandascore-schedule" style="margin-top:16px;font-size:14px;opacity:0.85;">
                <?php if ($scheduled_at): ?>
                    <div>Scheduled: <span class="pandascore-time" data-iso="<?php echo esc_attr($scheduled_at); ?>"><?php echo $scheduled_at; ?></span></div>
                <?php endif; ?>
                <?php if ($begin_at): ?>
                    <div>Begin at: <span class="pandascore-time" data-iso="<?php echo esc_attr($begin_at); ?>"><?php echo $begin_at; ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:16px;font-size:12px;opacity:0.7;">
            Match ID: <?php echo intval($match_id); ?>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
