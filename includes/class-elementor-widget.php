<?php
if (!defined('ABSPATH')) {
    exit;
}

class Mentalia_PsyTest_Elementor_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'mentalia_psytest';
    }

    public function get_title()
    {
        return 'Mentalia PsyTest';
    }

    public function get_icon()
    {
        return 'eicon-form-horizontal';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['psikologi', 'tes', 'test', 'mentalia', 'quiz'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section('content_section', [
            'label' => 'Tes Psikologi',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        // Get all published tests
        global $wpdb;
        $tests = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}psytest_tests WHERE status = 'published' ORDER BY title ASC"
        );

        $options = ['0' => '— Pilih Tes —'];
        foreach ($tests as $t) {
            $options[$t->id] = $t->title;
        }

        $this->add_control('test_id', [
            'label' => 'Pilih Tes',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '0',
            'options' => $options,
            'description' => 'Pilih tes psikologi yang ingin ditampilkan.',
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $test_id = intval($settings['test_id']);

        if ($test_id <= 0) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="text-align:center;padding:40px;background:#f5f3ff;border-radius:12px;border:2px dashed #c4b5fd;">';
                echo '<p style="color:#764ba2;font-weight:600;">🧠 Mentalia PsyTest</p>';
                echo '<p style="color:#666;">Pilih tes psikologi dari panel sebelah kiri.</p>';
                echo '</div>';
            }
            return;
        }

        echo do_shortcode('[mentalia_test id="' . $test_id . '"]');
    }
}
