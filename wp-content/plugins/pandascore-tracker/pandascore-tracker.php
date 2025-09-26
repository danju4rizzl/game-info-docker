<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.5 
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Tracker_Plugin {
    private $option_key = 'pandascore_tracker_options';
    private $live_match_ids = [];
    private $preloaded_live_matches = [];

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_pandascore_clear_cache', [$this, 'handle_clear_cache']);
        add_shortcode('pandascore_tracker', [$this, 'shortcode_handler']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function enqueue_assets(): void {
        wp_register_style('pandascore-tracker-style', plugins_url('css/index.css', __FILE__), [], '1.5');
        wp_register_style('pandascore-match-details-style', plugins_url('css/match-details.css', __FILE__), [], '1.0');
        wp_register_script('pandascore-live-tracker-js', plugins_url('js/live-tracker.js', __FILE__), [], '1.5', true);
        wp_register_script('pandascore-timezone-js', plugins_url('js/timezone-converter.js', __FILE__), [], '1.0', true);
        wp_register_script('pandascore-league-filter-js', plugins_url('js/league-filter.js', __FILE__), [], '1.0', true);
        wp_register_script('pandascore-date-filter-js', plugins_url('js/date-filter.js', __FILE__), [], '1.0', true);
        wp_register_script('pandascore-match-details-js', plugins_url('js/match-details.js', __FILE__), [], '1.0', true);
    }

    public function admin_menu(): void {
        add_options_page('PandaScore Tracker', 'PandaScore Tracker', 'manage_options', 'pandascore-tracker', [$this, 'settings_page']);
    }

    private function defaults(): array {
        return [
            'api_key' => '',
            'enable_cache' => 1,
            'cache_version' => 1,
            // TTLs (seconds)
            'ttl_running' => 15,
            'stale_running' => 120,
            'ttl_upcoming' => 600,
            'stale_upcoming' => 1800,
            'ttl_tournaments' => 60,
            'stale_tournaments' => 300,
            'ttl_match' => 15,
            'stale_match' => 120,
        ];
    }

    public function register_settings(): void {
        register_setting($this->option_key, $this->option_key, [
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);

        add_settings_section('pandascore_main', 'PandaScore Settings', null, 'pandascore-tracker');
        add_settings_field('api_key', 'API Key', [$this, 'field_api_key'], 'pandascore-tracker', 'pandascore_main');
        add_settings_field('enable_cache', 'Enable Caching', [$this, 'field_enable_cache'], 'pandascore-tracker', 'pandascore_main');

        add_settings_section('pandascore_cache', __('Caching TTLs (seconds)', 'pandascore-tracker'), [$this, 'section_cache_info'], 'pandascore-tracker');
        add_settings_field('ttl_running', 'Live list TTL', [$this, 'field_ttl_running'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('stale_running', 'Live list Stale TTL', [$this, 'field_stale_running'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('ttl_upcoming', 'Upcoming list TTL', [$this, 'field_ttl_upcoming'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('stale_upcoming', 'Upcoming list Stale TTL', [$this, 'field_stale_upcoming'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('ttl_tournaments', 'Tournaments TTL', [$this, 'field_ttl_tournaments'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('stale_tournaments', 'Tournaments Stale TTL', [$this, 'field_stale_tournaments'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('ttl_match', 'Match detail TTL', [$this, 'field_ttl_match'], 'pandascore-tracker', 'pandascore_cache');
        add_settings_field('stale_match', 'Match detail Stale TTL', [$this, 'field_stale_match'], 'pandascore-tracker', 'pandascore_cache');
    }

    public function section_cache_info(): void {
        echo wp_kses_post(
            '<p class="description">These settings control how long data is cached by the plugin and how long stale data may be served if PandaScore is rate-limited or unavailable.</p>' .
            '<ul class="description" style="margin-left:1.2em;list-style:disc;">' .
            '<li><strong>TTL</strong>: Time To Live — how long fresh data is kept before the server fetches it again.</li>' .
            '<li><strong>Stale TTL</strong>: How long previously cached data may be served if a new fetch fails (e.g., 429 or 5xx). This prevents empty widgets during outages.</li>' .
            '<li><strong>Recommendations</strong>: Live 10–20s; Upcoming 300–600s; Tournaments 60s; Match details 10–20s.</li>' .
            '</ul>'
        );
    }

    public function sanitize_options($input): array {
        $defaults = $this->defaults();
        $existing = get_option($this->option_key, []);
        $output = array_merge($defaults, is_array($existing) ? $existing : []);

        // API key
        if (isset($input['api_key'])) {
            $output['api_key'] = sanitize_text_field($input['api_key']);
        }

        // Enable cache checkbox: if field missing, it's unchecked
        $output['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;

        // TTLs
        foreach (['ttl_running','stale_running','ttl_upcoming','stale_upcoming','ttl_tournaments','stale_tournaments','ttl_match','stale_match'] as $k) {
            if (isset($input[$k]) && $input[$k] !== '') {
                $v = intval($input[$k]);
                if ($v < 0) $v = 0;
                $output[$k] = $v;
            }
        }

        // Preserve cache_version unless explicitly changed elsewhere
        if (isset($existing['cache_version'])) {
            $output['cache_version'] = intval($existing['cache_version']);
            if ($output['cache_version'] <= 0) $output['cache_version'] = 1;
        }

        return $output;
    }

    public function field_api_key(): void {
        $opts = $this->get_options();
        $val = esc_attr($opts['api_key']);
        echo '<input type="text" name="' . $this->option_key . '[api_key]" value="' . $val . '" class="pandascore-api-key-input" style="width: 420px;">';
    }

    public function field_enable_cache(): void {
        $opts = $this->get_options();
        $enabled = (bool)$opts['enable_cache'];
        echo '<label><input type="checkbox" name="' . $this->option_key . '[enable_cache]" value="1" ' . checked(true, $enabled, false) . '> Use WordPress caching (transients/object cache) for API responses</label>';
    }

    private function ttl_number_field(string $key, string $placeholder, string $desc = ''): void {
        $opts = $this->get_options();
        $val = isset($opts[$key]) ? intval($opts[$key]) : '';
        echo '<input type="number" min="0" step="1" name="' . $this->option_key . '[' . esc_attr($key) . ']" value="' . esc_attr($val) . '" placeholder="' . esc_attr($placeholder) . '" style="width: 140px;">';
        if ($desc !== '') {
            echo '<p class="description" style="max-width:720px;">' . esc_html($desc) . '</p>';
        }
    }

    public function field_ttl_running(): void {
        $this->ttl_number_field(
            'ttl_running',
            'e.g. 15',
            __('How long the list of currently running matches is cached before refreshing from PandaScore. Lower = fresher, higher = fewer API calls. Example: 15 seconds.', 'pandascore-tracker')
        );
    }
    public function field_stale_running(): void {
        $this->ttl_number_field(
            'stale_running',
            'e.g. 120',
            __('How long we may serve slightly out-of-date live list data if PandaScore is rate-limited or down. Example: 120 seconds.', 'pandascore-tracker')
        );
    }
    public function field_ttl_upcoming(): void {
        $this->ttl_number_field(
            'ttl_upcoming',
            'e.g. 600',
            __('How long upcoming matches are cached. These change less often, so 300–600 seconds is typical. Example: 600 seconds.', 'pandascore-tracker')
        );
    }
    public function field_stale_upcoming(): void {
        $this->ttl_number_field(
            'stale_upcoming',
            'e.g. 1800',
            __('How long we may serve stale upcoming data when errors occur. Example: 1800 seconds (30 minutes).', 'pandascore-tracker')
        );
    }
    public function field_ttl_tournaments(): void {
        $this->ttl_number_field(
            'ttl_tournaments',
            'e.g. 60',
            __('How often to refresh running tournaments used to discover additional live matches. Example: 60 seconds.', 'pandascore-tracker')
        );
    }
    public function field_stale_tournaments(): void {
        $this->ttl_number_field(
            'stale_tournaments',
            'e.g. 300',
            __('How long to serve stale tournament data if fetching fails. Example: 300 seconds.', 'pandascore-tracker')
        );
    }
    public function field_ttl_match(): void {
        $this->ttl_number_field(
            'ttl_match',
            'e.g. 15',
            __('How often to refresh individual match details during live. Example: 15 seconds.', 'pandascore-tracker')
        );
    }
    public function field_stale_match(): void {
        $this->ttl_number_field(
            'stale_match',
            'e.g. 120',
            __('How long we may serve stale match details when new data cannot be fetched (e.g., 429). Example: 120 seconds.', 'pandascore-tracker')
        );
    }

    public function settings_page(): void {
        wp_enqueue_style('pandascore-tracker-style');
        $clear_url = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1>PandaScore Tracker</h1>
            <form method="post" action="options.php" style="margin-bottom: 20px;">
                <?php
                settings_fields($this->option_key);
                do_settings_sections('pandascore-tracker');
                submit_button('Save Settings');
                ?>
            </form>

            <form method="post" action="<?php echo esc_url($clear_url); ?>">
                <?php wp_nonce_field('pandascore_clear_cache', 'pandascore_clear_cache_nonce'); ?>
                <input type="hidden" name="action" value="pandascore_clear_cache">
                <?php submit_button('Clear Plugin Cache', 'secondary'); ?>
            </form>

            <h3>Shortcode Usage</h3>
            <p><strong>Basic usage:</strong> <code>[pandascore_tracker]</code></p>
            <p><strong>Live matches:</strong> <code>[pandascore_tracker type="live"]</code></p>
            <p><strong>Mixed (live + upcoming):</strong> <code>[pandascore_tracker type="mixed" game="lol"]</code></p>
            <h4>Parameters:</h4>
            <ul>
                <li><strong>game:</strong> Game type (valorant, lol, csgo, dota2, etc.)</li>
                <li><strong>type:</strong> Match type - "upcoming" (default), "live", or "mixed"</li>
            </ul>
        </div>
        <?php
    }

    public function handle_clear_cache(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'pandascore-tracker'));
        }
        check_admin_referer('pandascore_clear_cache', 'pandascore_clear_cache_nonce');

        $opts = $this->get_options();
        $opts['cache_version'] = isset($opts['cache_version']) ? (intval($opts['cache_version']) + 1) : 2;
        update_option($this->option_key, $opts);

        // Best effort: attempt to flush group if supported (some object caches)
        if (function_exists('wp_cache_flush_group')) {
            @wp_cache_flush_group($this->cache_namespace());
        }

        wp_redirect(add_query_arg(['page' => 'pandascore-tracker', 'cleared' => '1'], admin_url('options-general.php')));
        exit;
    }

    private function get_options(): array {
        $opts = get_option($this->option_key, []);
        return array_merge($this->defaults(), is_array($opts) ? $opts : []);
    }

    private function get_api_key(): string {
        $opts = $this->get_options();
        return isset($opts['api_key']) ? trim($opts['api_key']) : '';
    }

    private function is_cache_enabled(): bool {
        $opts = $this->get_options();
        return !empty($opts['enable_cache']);
    }

    private function get_cache_version(): int {
        $opts = $this->get_options();
        $v = isset($opts['cache_version']) ? intval($opts['cache_version']) : 1;
        return $v > 0 ? $v : 1;
    }

    private function ttl(string $name, int $default): int {
        $opts = $this->get_options();
        $val = isset($opts[$name]) ? intval($opts[$name]) : $default;
        if ($val < 0) $val = 0;
        return $val;
    }

    private function cache_namespace(): string {
        return 'pandascore';
    }

    private function cache_key(string $key): string {
        // Include version so clearing cache is simply bumping the version
        return 'pandascore_v' . $this->get_cache_version() . '_' . $key;
    }

    private function cache_get(string $key) {
        $nsKey = $this->cache_key($key);
        if (wp_using_ext_object_cache()) {
            return wp_cache_get($nsKey, $this->cache_namespace());
        }
        return get_transient($nsKey);
    }

    private function cache_set(string $key, $value, int $ttl): bool {
        $nsKey = $this->cache_key($key);
        if (wp_using_ext_object_cache()) {
            return wp_cache_set($nsKey, $value, $this->cache_namespace(), $ttl);
        }
        return set_transient($nsKey, $value, $ttl);
    }

    private function cache_delete(string $key): void {
        $nsKey = $this->cache_key($key);
        if (wp_using_ext_object_cache()) {
            wp_cache_delete($nsKey, $this->cache_namespace());
        } else {
            delete_transient($nsKey);
        }
    }

    /**
     * Cached GET with stale-on-error and light stampede protection
     */
    private function cached_json_get(string $url, array $headers, int $ttl, int $stale_ttl = 300) {
        // If caching disabled, do a direct fetch
        if (!$this->is_cache_enabled()) {
            $response = wp_remote_get($url, [
                'timeout' => 15,
                'headers' => $headers
            ]);
            if (is_wp_error($response)) return $response;
            if (wp_remote_retrieve_response_code($response) !== 200) {
                return new WP_Error('api_error', 'PandaScore API returned code ' . wp_remote_retrieve_response_code($response));
            }
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (json_last_error() !== JSON_ERROR_NONE) return new WP_Error('json_error', 'Invalid JSON from API');
            return $data;
        }

        $hash = md5($url);
        $freshKey = 'fresh_' . $hash;
        $staleKey = 'stale_' . $hash;
        $lockKey  = 'lock_' . $hash;

        // Fast path: fresh cache
        $fresh = $this->cache_get($freshKey);
        if ($fresh !== false) {
            return $fresh;
        }

        // Try to acquire a short lock
        $lockAcquired = false;
        $lockStoreKey = $this->cache_key($lockKey);
        if (wp_using_ext_object_cache()) {
            $lockAcquired = wp_cache_add($lockStoreKey, 1, $this->cache_namespace(), 20);
        } else {
            $lockAcquired = (get_transient($lockStoreKey) === false) && set_transient($lockStoreKey, 1, 20);
        }

        // If lock not acquired, serve stale if available
        if (!$lockAcquired) {
            $stale = $this->cache_get($staleKey);
            if ($stale !== false) return $stale;
            // No stale, continue to fetch (one more worker may still fetch)
        }

        // Fetch upstream
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => $headers
        ]);

        // Release lock
        if (wp_using_ext_object_cache()) {
            wp_cache_delete($lockStoreKey, $this->cache_namespace());
        } else {
            delete_transient($lockStoreKey);
        }

        if (is_wp_error($response)) {
            $stale = $this->cache_get($staleKey);
            if ($stale !== false) return $stale;
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 200) {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $stale = $this->cache_get($staleKey);
                if ($stale !== false) return $stale;
                return new WP_Error('json_error', 'Invalid JSON from PandaScore');
            }
            $this->cache_set($freshKey, $data, $ttl);
            $this->cache_set($staleKey, $data, $stale_ttl);
            return $data;
        }

        if (in_array($code, [429, 500, 502, 503, 504], true)) {
            $stale = $this->cache_get($staleKey);
            if ($stale !== false) return $stale;
            return new WP_Error('api_error', 'PandaScore error ' . $code);
        }

        return new WP_Error('api_error', 'PandaScore error ' . $code);
    }

    private function render_date_filters(): string {
        $dates = [];
        $now = current_time('timestamp'); // WP localized timestamp
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

    private function render_league_filters(): string {
        $leagues = ['LCK', 'LPL', 'LEC', 'LTA'];

        $html = '<div class="pandascore-league-filters">';

        foreach ($leagues as $league_name) {
            $filename = str_replace(' ', '-', strtoupper($league_name)) . '-logo.png';
            $image_url = plugins_url('images/' . $filename, __FILE__);

            $html .= '<div class="pandascore-league-filter" data-league-name="' . esc_attr($league_name) . '" title="' . esc_attr($league_name) . '">';
            $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($league_name) . '">';
            $html .= '</div>';
        }

        $other_leagues_filename = 'OTHERS-LEAGUES-logo.png';
        $other_leagues_image = plugins_url('images/' . $other_leagues_filename, __FILE__);
        $html .= '<div class="pandascore-league-filter" data-league-name="OTHER LEAGUES" title="OTHER LEAGUES">';
        $html .= '<img src="' . esc_url($other_leagues_image) . '" alt="OTHER LEAGUES">';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    private function make_api_call($game, $limit, $endpoint): mixed {
        $api_key = $this->get_api_key();
        if (!$api_key) return new WP_Error('no_api_key', 'PandaScore API key not set');

        $query_args = ['page[size]' => intval($limit)];
        $url = add_query_arg($query_args, "https://api.pandascore.co/{$game}/matches/{$endpoint}");

        $is_live = ($endpoint === 'running');
        // Pull TTLs from settings (still filterable)
        $ttl_setting = $is_live ? $this->ttl('ttl_running', 15) : $this->ttl('ttl_upcoming', 600);
        $stale_setting = $is_live ? $this->ttl('stale_running', 120) : $this->ttl('stale_upcoming', 1800);
        $ttl = (int) apply_filters('pandascore_cache_ttl', $ttl_setting, $endpoint, $game);
        $stale_ttl = (int) apply_filters('pandascore_cache_stale_ttl', $stale_setting, $endpoint, $game);

        $data = $this->cached_json_get($url, [
            'Authorization' => 'Bearer ' . $api_key
        ], $ttl, $stale_ttl);

        return $data;
    }

    /**
     * Enhanced function to detect and collect live matches from tournaments
     */
    private function get_live_matches_from_tournaments($game): array {
        $api_key = $this->get_api_key();
        if (!$api_key) return [];

        $tournaments_url = "https://api.pandascore.co/{$game}/tournaments/running?status=not_started";

        $ttl_setting = $this->ttl('ttl_tournaments', 60);
        $stale_setting = $this->ttl('stale_tournaments', 300);
        $ttl = (int) apply_filters('pandascore_cache_ttl_tournaments', $ttl_setting, $game);
        $stale_ttl = (int) apply_filters('pandascore_cache_stale_ttl_tournaments', $stale_setting, $game);

        $response_data = $this->cached_json_get($tournaments_url, [
            'Authorization' => 'Bearer ' . $api_key
        ], $ttl, $stale_ttl);

        if (is_wp_error($response_data)) {
            error_log('[PandaScore] Failed to fetch tournaments: ' . $response_data->get_error_message());
            return [];
        }

        if (!is_array($response_data)) return [];

        $live_matches = [];

        // Process each tournament to find live-supported matches
        foreach ($response_data as $tournament) {
            if (!isset($tournament['matches']) || !is_array($tournament['matches'])) continue;

            foreach ($tournament['matches'] as $match) {
                // Check if match has live support
                if (isset($match['live']['supported']) && $match['live']['supported'] === true) {
                    $match_data = [
                        'match_id' => $match['id'] ?? null,
                        'status' => $match['status'] ?? 'unknown',
                        'live_url' => $match['live']['url'] ?? null,
                        'opens_at' => $match['live']['opens_at'] ?? null
                    ];

                    // Only include matches that are running or about to start
                    if (in_array($match_data['status'], ['running', 'not_started'], true)) {
                        if ($match_data['match_id']) {
                            $live_matches[] = $match_data;
                            error_log("[PandaScore] Found live-supported match: {$match_data['match_id']} (status: {$match_data['status']})");
                        }
                    }
                }
            }
        }

        return $live_matches;
    }

    /**
     * Build WebSocket matches data for JavaScript
     */
    private function get_ws_matches_payload($matchIds = []): array {
        $matchIds = array_values(array_unique(array_map('intval', (array) $matchIds)));
        if (empty($matchIds)) return [];

        $payload = [];
        foreach ($matchIds as $id) {
            $payload[] = ['match_id' => $id];
        }
        return $payload;
    }

    private function get_team_logo_html($logo_url, $team_name, $acronym): string {
        if ($logo_url) {
            return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($team_name) . '" class="pandascore-team-logo">';
        }
        $fallback_letter = strtoupper(($acronym && $acronym !== 'TBD' && $acronym !== 'N/A') ? $acronym[0] : (($team_name && $team_name !== 'TBD' && $team_name !== 'N/A') ? $team_name[0] : '?'));
        return '<div class="pandascore-team-logo-placeholder" title="Unknown Team">' . esc_html($fallback_letter) . '</div>';
    }

    private function render_team($logo_url, $name, $acronym, $score = null, $opponent_id = null): string {
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

    private function render_match($match, $is_live = false): string {
        $opponents = ['TBD', 'TBD'];
        $acronyms = ['TBD', 'TBD'];
        $logos = ['', ''];
        $scores = [0, 0];
        $opponent_ids = [null, null];

        if (isset($match['opponents']) && is_array($match['opponents'])) {
            foreach ($match['opponents'] as $i => $o) {
                if ($i < 2) {
                    $opponents[$i] = isset($o['opponent']['name']) ? esc_html($o['opponent']['name']) : 'TBD';
                    $acronyms[$i] = !empty($o['opponent']['acronym']) ? esc_html($o['opponent']['acronym']) : $opponents[$i];
                    $logos[$i] = $o['opponent']['image_url'] ?? '';
                    $opponent_ids[$i] = $o['opponent']['id'] ?? null;
                }
            }
        }

        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $i => $r) {
                if ($i < 2) $scores[$i] = intval($r['score'] ?? 0);
            }
        }

        $league_name = esc_html($match['league']['name'] ?? '');
        $league_logo = esc_url($match['league']['image_url'] ?? '');
        $league_id = esc_attr($match['league']['id'] ?? '');
        $scheduled_at = $match['scheduled_at'] ?? '';
        $is_upcoming = !$is_live && $scheduled_at;

        $html = '<div class="pandascore-match" data-league-id="' . $league_id . '" data-match-id="' . esc_attr($match['id'] ?? '') . ($is_upcoming ? '" data-scheduled-at="' . esc_attr($scheduled_at) : '') . '">';
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
        $html .= '</div></div>';
        return $html;
    }

    private function render_matches($game, $limit, $is_live): string {
        $matches = $this->make_api_call($game, $limit, $is_live ? 'running' : 'upcoming');
        if (is_wp_error($matches)) {
            return '<div class="pandascore-error">Error: ' . esc_html($matches->get_error_message()) . '</div>';
        }
        if (empty($matches)) {
            return $is_live ? '' : '<div class="pandascore-no-matches">No upcoming matches found.</div>';
        }

        $html = '<div class="pandascore-section-header">' . ($is_live ? '<span class="pandascore-live-indicator"></span>LIVE' : 'UPCOMING') . '</div>';
        $html .= '<div class="pandascore-matches-container">';
        foreach ($matches as $match) {
            if ($is_live && isset($match['id'])) {
                $this->live_match_ids[] = $match['id'];
                $this->preloaded_live_matches[$match['id']] = [
                    'id' => $match['id'],
                    'status' => $match['status'] ?? null,
                    'results' => $match['results'] ?? [],
                ];
            }
            $html .= $this->render_match($match, $is_live);
        }
        $html .= '</div>';
        return $html;
    }

    public function shortcode_handler($atts): string {
        // Check if we should show match details instead
        if (isset($_GET['view']) && $_GET['view'] === 'match-details' && isset($_GET['match_id'])) {
            return $this->render_match_details(intval($_GET['match_id']));
        }

        $atts = shortcode_atts(['game' => 'lol', 'limit' => 100, 'align' => 'center', 'type' => 'mixed'], $atts, 'pandascore_tracker');
        wp_enqueue_style('pandascore-tracker-style');
        wp_enqueue_script('pandascore-timezone-js');
        wp_enqueue_script('pandascore-league-filter-js');
        wp_enqueue_script('pandascore-date-filter-js');
        wp_enqueue_script('pandascore-match-details-js');

        $this->live_match_ids = [];
        $this->preloaded_live_matches = [];

        $html = '<div class="pandascore-tracker align-' . esc_attr($atts['align']) . '">';

        $html .= $this->render_date_filters();
        $html .= $this->render_league_filters();

        $html .= '<div class="pandascore-matches-wrapper">';
        if (in_array($atts['type'], ['live', 'mixed'], true)) {
            $live_content = $this->render_matches($atts['game'], $atts['limit'], true);
            if (!empty($live_content)) {
                $html .= '<div class="pandascore-live-container">';
                $html .= $live_content;
                $html .= '</div>';
            }
        }
        if (in_array($atts['type'], ['upcoming', 'mixed'], true)) {
            $upcoming_content = $this->render_matches($atts['game'], $atts['limit'], false);
            if (!empty($upcoming_content)) {
                $html .= '<div class="pandascore-upcoming-container">';
                $html .= $upcoming_content;
                $html .= '</div>';
            }
        }
        $html .= '</div>';

        // Live match detection (includes tournaments)
        if (in_array($atts['type'], ['live', 'mixed'], true)) {
            $tournament_live_matches = $this->get_live_matches_from_tournaments($atts['game']);
            $all_live_match_ids = array_unique(array_merge(
                $this->live_match_ids,
                array_column($tournament_live_matches, 'match_id')
            ));

            if (!empty($all_live_match_ids)) {
                wp_enqueue_script('pandascore-live-tracker-js');
                $wsMatches = $this->get_ws_matches_payload($all_live_match_ids);

                // JS uses REST proxy and preloaded data (no API key exposure)
                wp_localize_script('pandascore-live-tracker-js', 'pandaScoreLiveTracker', [
                    'wsMatches' => $wsMatches,
                    'restBase' => esc_url_raw( rest_url('pandascore/v1') ),
                    'preloadedMatches' => array_values($this->preloaded_live_matches),
                ]);

                error_log('[PandaScore] Initialized tracking for ' . count($wsMatches) . ' matches');
            }
        }

        $html .= '</div>';
        return $html;
    }

    public function register_rest_routes(): void {
        register_rest_route('pandascore/v1', '/match/(?P<id>\d+)', [
            'methods'  => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function (WP_REST_Request $req) {
                $match_id = (int) $req['id'];
                $api_key = $this->get_api_key();
                if (!$api_key) {
                    return new WP_Error('no_api_key', 'PandaScore API key missing', ['status' => 500]);
                }
                $url = "https://api.pandascore.co/matches/{$match_id}";

                // TTLs for match detail (from settings, filterable)
                $ttl_setting = $this->ttl('ttl_match', 15);
                $stale_setting = $this->ttl('stale_match', 120);
                $ttl = (int) apply_filters('pandascore_cache_ttl_match_detail', $ttl_setting, $match_id);
                $stale_ttl = (int) apply_filters('pandascore_cache_stale_ttl_match_detail', $stale_setting, $match_id);

                $data = $this->cached_json_get($url, [
                    'Authorization' => 'Bearer ' . $api_key
                ], $ttl, $stale_ttl);

                if (is_wp_error($data)) return $data;

                // Lightweight cache hints for edges/CDN; does not affect page caches
                header('Cache-Control: public, max-age=10, stale-while-revalidate=60');

                return rest_ensure_response($data);
            }
        ]);

        // (Optional future) list endpoints can be added for running/upcoming per game
    }

    /**
     * Render match details page
     */
    private function render_match_details($match_id): string {
        wp_enqueue_style('pandascore-tracker-style');
        wp_enqueue_style('pandascore-match-details-style');
        
        require_once plugin_dir_path(__FILE__) . 'templates/match-details.php';
        
        $details = new PandaScore_Match_Details($this, $match_id);
        return $details->render();
    }

    /**
     * Public method to access API key for match details template
     */
    public function get_public_api_key(): string {
        return $this->get_api_key();
    }

    /**
     * Public method to access cached JSON get for match details template
     */
    public function public_cached_json_get(string $url, array $headers, int $ttl, int $stale_ttl = 300) {
        return $this->cached_json_get($url, $headers, $ttl, $stale_ttl);
    }
}

new PandaScore_Tracker_Plugin();
