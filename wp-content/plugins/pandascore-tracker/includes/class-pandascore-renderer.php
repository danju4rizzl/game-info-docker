<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Renderer {
    private $plugin_file;
    private $router;

    public function __construct($plugin_file, $router) {
        $this->plugin_file = $plugin_file;
        $this->router = $router;
    }

    public function render_date_filters() {
        $dates = [];
        $now = current_time('timestamp');
        for ($i = 0; $i < 7; $i++) {
            $ts = $now + DAY_IN_SECONDS * $i;
            $dates[] = [
                'label' => $i === 0 ? __('Today', 'pandascore-tracker') : date_i18n('M j', $ts),
                'iso'   => date_i18n('Y-m-d', $ts),
            ];
        }

        $html = '<div class="pandascore-date-filters">';
        foreach ($dates as $d) {
            $html .= '<div class="pandascore-date-filter" data-date-iso="' . esc_attr($d['iso']) . '">';
            $html .= esc_html($d['label']);
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_league_filters() {
        // The $leagueFilters is used to display the league filters  
        $leagueFilters = ['Worlds', 'LCK', 'LPL', 'LEC', 'LTA'];
        $html = '<div class="pandascore-league-filters">';

        foreach ($leagueFilters as $league_name) {
            $filename = str_replace(' ', '-', strtoupper($league_name)) . '-logo.png';
            $image_url = plugins_url('images/' . $filename, $this->plugin_file);

            $html .= '<div class="pandascore-league-filter" data-league-name="' . esc_attr($league_name) . '" title="' . esc_attr($league_name) . '">';
            $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($league_name) . '">';
            $html .= '</div>';
        }

        $other_leagues_filename = 'OTHERS-LEAGUES-logo.png';
        $other_leagues_image = plugins_url('images/' . $other_leagues_filename, $this->plugin_file);
        $html .= '<div class="pandascore-league-filter" data-league-name="OTHER LEAGUES" title="OTHER LEAGUES">';
        $html .= '<img src="' . esc_url($other_leagues_image) . '" alt="OTHER LEAGUES">';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    public function get_team_logo_html($logo_url, $team_name, $acronym) {
        if ($logo_url) {
            return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($team_name) . '" class="pandascore-team-logo">';
        }
        // Debug: Log when logo is missing
        echo '<script>console.log("Missing logo for team: ' . esc_js($team_name) . ' (' . esc_js($acronym) . '), logo_url: ' . esc_js($logo_url) . '");</script>';
        $fallback_letter = strtoupper(($acronym && $acronym !== 'TBD' && $acronym !== 'N/A') ? $acronym[0] : (($team_name && $team_name !== 'TBD' && $team_name !== 'N/A') ? $team_name[0] : '?'));
        return '<div class="pandascore-team-logo-placeholder" title="Unknown Team">' . esc_html($fallback_letter) . '</div>';
    }

    public function render_team($logo_url, $name, $acronym, $score = null, $opponent_id = null) {
        $html = '<div class="pandascore-team' . ($score !== null ? ' with-score' : '') . '">';
        $html .= '<div class="pandascore-team-info">';
        $html .= $this->get_team_logo_html($logo_url, $name, $acronym);
        $html .= '<span class="pandascore-team-name" title="' . esc_attr($name) . '">' . esc_html($acronym) . '</span>';
        $html .= '</div>';
        if ($score !== null) {
            $html .= '<div class="pandascore-score" data-opponent-id="' . esc_attr($opponent_id ?? '') . '">' . intval($score) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_match($match, $is_live = false) {
        $opponents = ['TBD', 'TBD'];
        $acronyms = ['TBD', 'TBD'];
        $logos = ['', ''];
        $scores = [0, 0];
        $opponent_ids = [null, null];

        // Handle tournament matches with teams in tournament data
        if (isset($match['opponents']) && is_array($match['opponents']) && !empty($match['opponents'])) {
            foreach ($match['opponents'] as $i => $o) {
                if ($i < 2 && isset($o['opponent'])) {
                    $opponent = $o['opponent'];
                    $opponents[$i] = $opponent['name'] ?? 'TBD';
                    $acronyms[$i] = $opponent['acronym'] ?? $opponents[$i];
                    $logos[$i] = $opponent['image_url'] ?? '';
                    $opponent_ids[$i] = $opponent['id'] ?? null;
                    
                    // Debug: Log team data to see what's available
                    if (empty($logos[$i])) {
                        echo '<script>console.log("Team ' . $i . ' data:", ' . json_encode($opponent) . ');</script>';
                    }
                }
            }
        } else {
            // Extract team names from match name for TBD matches
            $match_name = $match['name'] ?? '';
            if (preg_match('/([A-Z0-9.]+)\s+vs\s+([A-Z0-9.]+)/', $match_name, $matches_found)) {
                $acronyms[0] = $matches_found[1];
                $acronyms[1] = $matches_found[2];
                $opponents[0] = $matches_found[1];
                $opponents[1] = $matches_found[2];
            }
        }

        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $i => $r) {
                if ($i < 2) $scores[$i] = intval($r['score'] ?? 0);
            }
        }

        $league_name = esc_html($match['league']['name'] ?? 'Unknown League');
        $league_logo = esc_url($match['league']['image_url'] ?? '');
        $league_id = esc_attr($match['league']['id'] ?? '');
        $scheduled_at = $match['scheduled_at'] ?? '';
        $is_upcoming = !$is_live && $scheduled_at;
        $match_url = $this->router->get_match_url($match);

        $html = '<div class="pandascore-match" data-league-id="' . $league_id . '" data-match-id="' . esc_attr($match['id'] ?? '') . ($is_upcoming ? '" data-scheduled-at="' . esc_attr($scheduled_at) : '') . '">';
        $html .= '<a class="pandascore-match-link" href="' . esc_url($match_url) . '">';
        $html .= '<div class="pandascore-league-container">';
        $html .= $league_logo ? '<div class="pandascore-league-logo"><img src="' . $league_logo . '" alt="' . $league_name . '" title="' . $league_name . '"></div>'
                             : '<div class="pandascore-league-placeholder" title="' . $league_name . '">' . ($league_name ? $league_name[0] : 'L') . '</div>';
        $html .= '</div>';

        $html .= '<div class="pandascore-match-content' . ($is_live ? ' live-layout' : '') . '">';
        $html .= '<div class="pandascore-teams-container">';
        $html .= $this->render_team($logos[0], $opponents[0], $acronyms[0], $is_live ? $scores[0] : null, $opponent_ids[0]);
        $html .= $this->render_team($logos[1], $opponents[1], $acronyms[1], $is_live ? $scores[1] : null, $opponent_ids[1]);
        $html .= '</div>';

        if ($is_upcoming) {
            $html .= '<div class="pandascore-time-container"><div class="pandascore-time-badge"><div class="pandascore-time">Loading...</div><div class="pandascore-time-day">Loading...</div></div></div>';
        }
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>';
        return $html;
    }

    public function render_matches($api, $game, $limit, $is_live, &$live_match_ids) {
        $matches = $api->get_all_tournament_matches($is_live);
        if (is_wp_error($matches)) {
            return '<div class="pandascore-error">Error: ' . esc_html($matches->get_error_message()) . '</div>';
        }
        if (empty($matches)) {
            return $is_live ? '' : '<div class="pandascore-no-matches">No matches found.</div>';
        }

        // Filter matches by status
        $filtered_matches = [];
        foreach ($matches as $match) {
            $status = $match['status'] ?? '';
            if ($is_live) {
                // Only show running matches for live
                if ($status === 'running') {
                    $filtered_matches[] = $match;
                }
            } else {
                // Only show not_started matches for upcoming
                if ($status === 'not_started') {
                    $filtered_matches[] = $match;
                }
            }
        }
        
        // Add live match IDs for WebSocket tracking
        foreach ($filtered_matches as $match) {
            if ($is_live && isset($match['id'])) {
                $live_match_ids[] = $match['id'];
            }
        }

        // Log API results for debugging
        $leagues_from_api = [];
        foreach ($filtered_matches as $match) {
            if (isset($match['league']['name'])) {
                $leagues_from_api[] = $match['league']['name'];
            }
        }
        $unique_leagues = array_unique($leagues_from_api);
        sort($unique_leagues);
        
        echo '<script>console.log("🔥 Tournament-based ' . ($is_live ? 'LIVE' : 'UPCOMING') . ' leagues:", ' . json_encode($unique_leagues) . ');</script>';
        echo '<script>console.log("📊 Total ' . ($is_live ? 'LIVE' : 'UPCOMING') . ' matches:", ' . count($filtered_matches) . ');</script>';
        
        // Debug: Log all available tournaments
        $all_tournaments = $api->get_tournaments_for_leagues($is_live);
        $all_league_names = [];
        foreach ($all_tournaments as $tournament) {
            $league_name = $tournament['league']['name'] ?? 'Unknown';
            $league_slug = $tournament['league']['slug'] ?? 'unknown';
            $all_league_names[] = $league_name . ' (' . $league_slug . ')';
        }
        echo '<script>console.log("🎮 All available tournaments:", ' . json_encode(array_unique($all_league_names)) . ');</script>';

        $html = '<div class="pandascore-section-header">' . ($is_live ? '<span class="pandascore-live-indicator"></span>LIVE' : 'UPCOMING') . '</div>';
        $html .= '<div class="pandascore-matches-container">';
        
        // Apply limit to matches
        $display_matches = array_slice($filtered_matches, 0, $limit);
        foreach ($display_matches as $match) {
            $html .= $this->render_match($match, $is_live);
        }
        $html .= '</div>';
        return $html;
    }

    public function get_match_details($api, $match_id) {
        return $api->make_api_call("/matches/{$match_id}");
    }
}