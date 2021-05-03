<?php
/*
Plugin Name: Appointment Hour Booking Extended
Plugin URI: https://github.com/ole1986/wp-time-slots-extended
Description: Appointment Hour Booking Extended Functions
Version: 1.0.11
Author: ole1986
License: MIT
Text Domain: wp-time-slots-extended
*/

defined('ABSPATH') or die('No script kiddies please!');

define('WP_TIME_SLOTS_EXTENDED_VERSION', '1.0.11');

require_once ABSPATH.'wp-admin/includes/plugin.php';

if (is_plugin_active('appointment-hour-booking/app-booking-plugin.php')) {
    include_once __DIR__ .'/../appointment-hour-booking/classes/cp-base-class.inc.php';
} else if (is_plugin_active('wp-time-slots-booking-form/wp-time-slots-booking-plugin.php')) {
    include_once __DIR__ .'/../wp-time-slots-booking-form/classes/cp-base-class.inc.php';
}

require_once 'Base.php';
require_once 'Dashboard.php';

class Ole1986_AppointmentHourBookingExtended extends Ole1986_SlotBase
{
    /**
     * The unique instance of the plugin.
     *
     * @var Ole1986_AppointmentHourBookingExtended
     */
    private static $instance;

    /**
     * Gets an instance of our plugin.
     *
     * @return Ole1986_AppointmentHourBookingExtended
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        load_plugin_textdomain('wp-time-slots-extended', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        if (empty($this->menu_parameter)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning"><p>The <strong>Appointment Hour Booking Extended</strong> plugin requires either the "Appointment Hour Booking" or "WP Time Slots Booking Form" plugin</p></div>';
            });
            return;
        }
        
        register_activation_hook(__FILE__, [$this, 'plugin_activate']);
        register_deactivation_hook(__FILE__,  [$this, 'plugin_deactivate']);
        
        // appointment-hour-booking
        add_action('cpappb_update_status', [$this, 'onUpdateStatus'], 10, 2);
        // wp-time-slots-booking-form
        add_action('cptslotsb_update_status', [$this, 'onUpdateStatus'], 10, 2);

        add_action('admin_menu',  [$this, 'extend_menu']);

        // appointment-hour-booking
        add_action('cpappb_process_data_before_insert', [$this, 'check_single_insert']);
        // wp-time-slots-booking-form
        add_action('cptslotsb_process_data_before_insert', [$this, 'check_single_insert']);

        add_action('wp_head', [$this, 'scripts']);
        add_action('admin_head', [$this, 'scripts']);

        new Ole1986_AppointmentHourBookingExtendedDashboard();
    }

    public function plugin_activate()
    {
        $ahb_module = __DIR__ . '/../appointment-hour-booking/js/fields-public/36_fbuilder.fapp.getCurrentSlots.js';
        @copy(__DIR__ . '/scripts/36_fbuilder.fapp.getCurrentSlots.js', $ahb_module);
    }

    public function plugin_deactivate()
    {
        $ahb_module = __DIR__ . '/../appointment-hour-booking/js/fields-public/36_fbuilder.fapp.getCurrentSlots.js';

        if (file_exists($ahb_module)) {
            unlink($ahb_module);
        }
    }

    public function scripts()
    {
        ?>
        <style>
            .ahb-section-container form > nobr {
                display: inline-block;
                margin-top: 1em;
            }
            .ahb-section-container form > nobr:nth-of-type(1) > input {
                width: 300px;
            }
            .ahb-section-container form > nobr:nth-of-type(2) > input, .ahb-section-container form > nobr:nth-of-type(3) > input {
                width: 105px;
            }
            .ahb-section-container form > div:last-child {
                margin-top: 1em;
            }
        </style>
        <script>
            /* workaroung to display available slots */
            var cp_hourbk_cmpublic = true;
        </script>
        <?php
    }

    public function extend_menu()
    {
        add_submenu_page($this->menu_parameter, 'Extended Settings', __('Extended Settings', 'wp-time-slots-extended'), 'edit_pages', $this->menu_parameter."_extended_settings", [$this, 'settings_page'], 2);
    }

    public function settings_page()
    {
        if (!empty($_POST)) {
            if ($_POST['submit'] == "Reset") {
                delete_option('cp_cptslotextended_dashboard_title');

                delete_option('cp_cptslotextended_approved');
                delete_option('cp_cptslotextended_subject_approved');
                delete_option('cp_cptslotextended_body_approved');

                delete_option('cp_cptslotextended_canceled');
                delete_option('cp_cptslotextended_subject_canceled');
                delete_option('cp_cptslotextended_body_canceled');

                delete_option('cp_cptslotextended_max_registration');
                delete_option('cp_cptslotextended_max_registration_url');
            } else {
                update_option('cp_cptslotextended_dashboard_title', sanitize_text_field($_POST['cp_cptslotextended_dashboard_title']));

                update_option('cp_cptslotextended_approved', sanitize_key($_POST['cp_cptslotextended_approved']));
                update_option('cp_cptslotextended_subject_approved', sanitize_text_field($_POST['cp_cptslotextended_subject_approved']));
                update_option('cp_cptslotextended_body_approved', sanitize_textarea_field($_POST['cp_cptslotextended_body_approved']));

                update_option('cp_cptslotextended_canceled', sanitize_key($_POST['cp_cptslotextended_canceled']));
                update_option('cp_cptslotextended_subject_canceled', sanitize_text_field($_POST['cp_cptslotextended_subject_canceled']));
                update_option('cp_cptslotextended_body_canceled', sanitize_textarea_field($_POST['cp_cptslotextended_body_canceled']));

                update_option('cp_cptslotextended_max_registration', sanitize_key($_POST['cp_cptslotextended_max_registration']));
                update_option('cp_cptslotextended_max_registration_url', sanitize_text_field($_POST['cp_cptslotextended_max_registration_url']));
            }
        }

        ?>
        <h1>WP Time Slots Extended</h1>
        <form name="updatesettings" action="" method="post">
            <h3><?php _e('Dashboard Widget', 'wp-time-slots-extended') ?></h3>
            <p>
                <label><?php _e('Choose a field name (availble in the form) to display as title - always fallback to %email% if unavailable', 'wp-time-slots-extended') ?></label><br />
                <input type="text" name="cp_cptslotextended_dashboard_title" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_dashboard_title', '%email%')); ?>" /><br />
            </p>
            <h3><?php _e('Email notification on Approval', 'wp-time-slots-extended') ?></h3>
            <p>
                <label><input type="checkbox" name="cp_cptslotextended_approved" value="1" <?php echo (get_option('cp_cptslotextended_approved', 0) ? 'checked' : '') ?>>
                    <?php _e('Notify the user when its booked slot status has been changed to approved', 'wp-time-slots-extended') ?>
                </label>
            </p>
            <div>
                <label><?php _e('Subject', 'wp-time-slots-extended') ?></label><br />
                <input type="text" name="cp_cptslotextended_subject_approved" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_subject_approved', 'Your Slot has been approved')); ?>" /><br />
            </div>

            <div>
                <label><?php _e('Message', 'wp-time-slots-extended') ?></label><br />
                <textarea name="cp_cptslotextended_body_approved" rows="10" cols="70"><?php echo esc_attr(get_option('cp_cptslotextended_body_approved', "Dear %email%,\r\n\r\nWe have just confirmed your slot on %formname% / %fieldname1%")); ?></textarea><br />
            </div>
            <div>&nbsp;</div>
            <h3><?php _e('Email notification on Cancelation', 'wp-time-slots-extended') ?></h3>
            <p>
                <label>
                    <input type="checkbox" name="cp_cptslotextended_canceled" value="1" <?php echo (get_option('cp_cptslotextended_canceled', 0) ? 'checked' : '') ?>>
                    <?php _e('Notify the user when its booked slot status has been changed to canceled', 'wp-time-slots-extended') ?>
                </label>
            </p>
            <div>
            <label><?php _e('Subject', 'wp-time-slots-extended') ?></label><br />
                <input type="text" name="cp_cptslotextended_subject_canceled" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_subject_canceled', 'Your Slot has been CANCELED')); ?>" /><br />
            </div>
            <div>
                <label><?php _e('Message', 'wp-time-slots-extended') ?></label><br />
                <textarea name="cp_cptslotextended_body_canceled" rows="10" cols="70"><?php echo esc_attr(get_option('cp_cptslotextended_body_canceled', "Dear %email%,\r\n\r\nwe deeply regret that we had to cance your slot on %formname% / %fieldname1%")); ?></textarea><br />
            </div>
            <div>&nbsp;</div>
            <h3><?php _e('Registration limits', 'wp-time-slots-extended') ?></h3>
            <div>
                <label><?php _e('Maximum allowed registrations per user and day', 'wp-time-slots-extended') ?></label><br />
                <input type="number" name="cp_cptslotextended_max_registration" value="<?php echo esc_attr(get_option('cp_cptslotextended_max_registration', '')); ?>" /><br />
            </div>
            <div>
                <label><?php _e('Redirect URL when max registrations reached', 'wp-time-slots-extended') ?></label><br />
                <input type="text" name="cp_cptslotextended_max_registration_url" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_max_registration_url', '/slot-register-limit')); ?>" /><br />
            </div>
            <div>&nbsp;</div>
            <input type="submit" name="submit" class="button button-primary" value="Update" />
            <input type="submit" name="submit" class="button" value="Reset" />
        </form>
        <?php
    }

    public function check_single_insert($params)
    {
        global $wpdb;

        // allow adding additionals, with the same email from admin area
        if (is_admin()) { return;
        }

        $selectedDate = $params['apps'][0]['date'];

        $maxAllowedRegistrations = intval(get_option('cp_cptslotextended_max_registration', ''));

        // skip max allowed registration check
        if (empty($maxAllowedRegistrations)) { return;
        }
        
        $countResult = $wpdb->get_col($wpdb->prepare('SELECT COUNT(*) FROM `'.$wpdb->prefix.$this->table_messages.'` WHERE DATE(time) = CURDATE() AND formid = %d AND notifyto = %s', $params['formid'], $params['email']));

        $c = intval(array_pop($countResult));

        if ($maxAllowedRegistrations > 0 && ($c + 1) > $maxAllowedRegistrations) {
            $url = get_option('cp_cptslotextended_max_registration_url', '/slot-register-limit');
            header("Location: ". $url);
            exit(); 
        }
    }

    public function onUpdateStatus($id, $status)
    {
        global $wpdb;

        if (is_subclass_of($this, 'CP_APPBOOK_BaseClass')) {
            define('CP_APPBOOK_DEFAULT_fp_from_email', get_the_author_meta('user_email', get_current_user_id()));
            $from = $this->get_option('fp_from_email', @CP_APPBOOK_DEFAULT_fp_from_email);
        } else {
            define('CP_TSLOTSBOOK_DEFAULT_fp_from_email', get_the_author_meta('user_email', get_current_user_id()));
            $from = $this->get_option('fp_from_email', @CP_TSLOTSBOOK_DEFAULT_fp_from_email);
        }

        $events = $wpdb->get_results($wpdb->prepare('SELECT * FROM `'.$wpdb->prefix.$this->table_messages.'` WHERE id=%d', $id));
        $posted_data = unserialize($events[0]->posted_data);

        $status = strtolower($status);

        switch($status) {
        case '':
            if (empty(get_option('cp_cptslotextended_approved'))) { return;
            }
            $subject = get_option('cp_cptslotextended_subject_approved', 'Your Slot has been approved');
            $body = get_option('cp_cptslotextended_body_approved',  "Dear %email%,\r\n\r\nWe have just confirmed your slot on %formname% / %fieldname1%");
            break;
        case 'rejected':
        case 'canceled':
        case 'cancelled':
            if (empty(get_option('cp_cptslotextended_canceled'))) { return;
            }
            $subject = get_option('cp_cptslotextended_subject_canceled', 'Your Slot has been CANCELED');
            $body = get_option('cp_cptslotextended_body_canceled',  "Dear %email%,\r\n\r\nwe deeply regret that we had to cance your slot on %formname% / %fieldname1%");
            break;
        }

        foreach ($posted_data as $item => $value) {
            $body = str_replace('%'.$item.'%', (is_array($value)?(implode(", ", $value)):($value)), $body);
        }
    
        wp_mail(trim($posted_data['email']), $subject . ' / ' . $posted_data['formname'], $body,
            "From: $from <".$from.">\r\n".
                    'text/plain'.
        "X-Mailer: PHP/" . phpversion());
    }
}

Ole1986_AppointmentHourBookingExtended::get_instance();