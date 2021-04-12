<?php

class Ole1986_AppointmentHourBookingExtendedDashboard extends Ole1986_SlotBase
{
    private $data;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'load_scripts']);

        add_action('wp_ajax_wp_time_slots_extended_dashboard_post', [$this, 'post']);
        add_action('wp_dashboard_setup', [$this, 'widget']);
    }

    public function load_scripts($hook)
    {
        if ($hook == 'index.php') {
            wp_enqueue_style('wp_time_slots_extended', plugins_url('styles/backend.css', __FILE__), null, WP_TIME_SLOTS_EXTENDED_VERSION);
            wp_enqueue_script('wp_time_slots_extended', plugins_url('scripts/init.js', __FILE__), null, WP_TIME_SLOTS_EXTENDED_VERSION);
            wp_localize_script('wp_time_slots_extended', 'wp_time_slots_extended', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'lang_loading' => __('Loading...', 'wp-time-slots-extended')
            ]);    
        }
    }

    public function widget()
    {
        add_meta_box('dashboard_wp_time_slots_extended_dashboard_widget', __('Slot Booking Overview', 'wp-time-slots-extended'), [$this, 'content'], 'dashboard', 'normal', 'high');
    }

    public function post()
    {
        ob_start();

        $this->output();
        $output_string = ob_get_contents();

        ob_end_clean();

        wp_send_json_success(  $output_string );
    }
    public function fetchData()
    {
        global $wpdb;

        if ($this->data) return;

        $curDate = date('Y-m-d');
        $this->data = $wpdb->get_results('SELECT posted_data FROM '. $wpdb->prefix . $this->table_messages . ' WHERE posted_data LIKE \'%s:4:"date";s:10:"' . $curDate . '"%\'');

        $this->data = array_map(function ($v) {
            return unserialize($v->posted_data);
        }, $this->data);

    }
    
    public function output()
    {
        $this->fetchData();
        $slot = $_POST['slot'] ?? '';

        foreach ($this->data as $item) {
            if (!empty($slot) && $item['apps'][0]['slot'] != $slot) continue;
            ?>
            <div>
                <div class="headline">
                    <div><?php echo $item['email'] ?></div>
                    <div><?php echo $item['formname'] ?></div>
                </div>
                <div class="detail">
                    <?php echo $item['fieldname1'] ?>
                </div>
            </div>
            <?php
        }
    }

    public function content()
    {
        $this->fetchData();

        $slots = [];

        if (!empty($this->data)) {
            $apps = array_merge(...array_map(function ($v) {
                return $v['apps'];
            }, $this->data));
    
            $slots = array_unique(array_column($apps, 'slot'));
        }
        
        ?>
        <div id="wp_time_slots_extended_dashboard" class="custom-dash-box">
            <div style="display: flex; justify-content: space-between">
                <div>
                    <h3><?php _e('Slot Bookings for today', 'wp-time-slots-extended') ?></h3>
                    <a href="admin.php?page=cp_apphourbooking"><?php _e('Switch to calendar list', 'wp-time-slots-extended') ?></a>
                </div>
                <div>
                    <select id="wp_time_slots_extended_dashboard_time">
                        <option value=""><?php _e('All periods', 'wp-time-slots-extended') ?></option>
                <?php foreach ($slots as $item) {
                        echo "<option>$item</option>";
                }
                ?>
                    </select>
                </div>
            </div>
            <div id="wp_time_slots_extended_dashboard_result">
            <?php $this->output() ?>
            </div>
        </div>
        <?php
    }
}