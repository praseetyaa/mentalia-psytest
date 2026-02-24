<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub Plugin Updater
 * 
 * Checks for plugin updates from a GitHub repository.
 * Supports public repositories without authentication.
 */
class Mentalia_PsyTest_Updater
{
    private $slug;
    private $plugin_data;
    private $plugin_file;
    private $github_username;
    private $github_repo;
    private $github_response;
    private $access_token;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->slug = plugin_basename($plugin_file);
        $this->github_username = 'praseetyaa';
        $this->github_repo = 'mentalia-psytest';
        $this->access_token = ''; // Leave empty for public repos

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);

        // Clear update cache on plugin update check
        add_filter('upgrader_pre_download', [$this, 'clear_cache'], 10, 3);
    }

    /**
     * Get plugin data from plugin file header
     */
    private function get_plugin_data()
    {
        if (empty($this->plugin_data)) {
            $this->plugin_data = get_plugin_data($this->plugin_file);
        }
        return $this->plugin_data;
    }

    /**
     * Fetch latest release info from GitHub API
     */
    private function get_github_release()
    {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";

        $args = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ],
            'timeout' => 15,
        ];

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (empty($data) || !isset($data->tag_name)) {
            return false;
        }

        $this->github_response = $data;
        return $data;
    }

    /**
     * Normalize version string (remove 'v' prefix)
     */
    private function normalize_version($version)
    {
        return ltrim($version, 'vV');
    }

    /**
     * Check if there's an update available
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $github_release = $this->get_github_release();

        if (!$github_release) {
            return $transient;
        }

        $github_version = $this->normalize_version($github_release->tag_name);
        $current_version = $plugin_data['Version'];

        if (version_compare($github_version, $current_version, '>')) {
            // Determine download URL
            $download_url = $github_release->zipball_url;

            // Prefer uploaded asset (.zip) if available
            if (!empty($github_release->assets)) {
                foreach ($github_release->assets as $asset) {
                    if (pathinfo($asset->name, PATHINFO_EXTENSION) === 'zip') {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            if (!empty($this->access_token)) {
                $download_url = add_query_arg('access_token', $this->access_token, $download_url);
            }

            $transient->response[$this->slug] = (object)[
                'slug' => dirname($this->slug),
                'new_version' => $github_version,
                'package' => $download_url,
                'url' => "https://github.com/{$this->github_username}/{$this->github_repo}",
                'icons' => [],
                'banners' => [],
                'tested' => get_bloginfo('version'),
                'requires_php' => $plugin_data['RequiresPHP'] ?? '7.4',
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the "View Details" popup
     */
    public function plugin_info($result, $action, $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (dirname($this->slug) !== $args->slug) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();
        $github_release = $this->get_github_release();

        if (!$github_release) {
            return $result;
        }

        $github_version = $this->normalize_version($github_release->tag_name);

        $result = (object)[
            'name' => $plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => $github_version,
            'author' => $plugin_data['Author'],
            'author_profile' => $plugin_data['AuthorURI'],
            'homepage' => $plugin_data['PluginURI'],
            'requires' => $plugin_data['RequiresWP'] ?? '6.0',
            'requires_php' => $plugin_data['RequiresPHP'] ?? '7.4',
            'tested' => get_bloginfo('version'),
            'downloaded' => 0,
            'last_updated' => $github_release->published_at,
            'sections' => [
                'description' => $plugin_data['Description'],
                'changelog' => nl2br(esc_html($github_release->body ?? 'No changelog available.')),
            ],
            'download_link' => $github_release->zipball_url,
        ];

        return $result;
    }

    /**
     * After install, rename the folder to match the expected plugin directory name
     */
    public function after_install($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $result;
        }

        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        // Re-activate plugin if it was active
        if (is_plugin_active($this->slug)) {
            activate_plugin($this->slug);
        }

        return $result;
    }

    /**
     * Clear cached GitHub response before checking for updates
     */
    public function clear_cache($reply, $package, $updater)
    {
        $this->github_response = null;
        return $reply;
    }
}
