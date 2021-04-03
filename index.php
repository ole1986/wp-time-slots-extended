<?php
/*
Plugin Name: WP Time Slots Extended
Plugin URI: https://wptimeslot.dwbooster.com/
Description: Time Slots / Extended Functions
Version: 1.0.0
Author: ole1986
License: MIT
Text Domain: wp-time-slots-extended
*/

require_once __DIR__ .'/../wp-time-slots-booking-form/classes/cp-base-class.inc.php';

class Ole1986_WpTimeSlotExtended extends CP_TSLOTSBOOK_BaseClass {
    /**
     * The unique instance of the plugin.
     *
     * @var Ole1986_WpTimeSlotExtended
     */
    private static $instance;

    /**
     * Gets an instance of our plugin.
     *
     * @return Ole1986_WpTimeSlotExtended
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    private $menu_parameter = 'cp_timeslotsbooking';

    public $table_items = "cptslotsbk_forms";
    public $table_messages = "cptslotsbk_messages";

    public function __construct() {
        load_plugin_textdomain('wp-time-slots-extended', false, dirname(plugin_basename(__FILE__)) . '/lang/');

        add_action('cptslotsb_update_status', [$this, 'onUpdateStatus'], 10, 2);

        add_action('admin_menu',  [$this, 'extend_menu']);
    }

    public function extend_menu() {
        add_submenu_page( $this->menu_parameter, 'Extended Settings', 'Extended Settings', 'edit_pages', $this->menu_parameter."_extended_settings", array($this, 'settings_page') );
    }

    public function settings_page() {

        if (!empty($_POST)) {
            if ($_POST['submit'] == "Reset") {
                delete_option('cp_cptslotextended_subject_approved');
                delete_option('cp_cptslotextended_body_approved');
                delete_option('cp_cptslotextended_subject_canceled');
                delete_option('cp_cptslotextended_body_canceled');
            } else {
                update_option('cp_cptslotextended_subject_approved', sanitize_text_field($_POST['cp_cptslotextended_subject_approved']));
                update_option('cp_cptslotextended_body_approved', sanitize_textarea_field($_POST['cp_cptslotextended_body_approved']));
                update_option('cp_cptslotextended_subject_canceled', sanitize_text_field($_POST['cp_cptslotextended_subject_canceled']));
                update_option('cp_cptslotextended_body_canceled', sanitize_textarea_field($_POST['cp_cptslotextended_body_canceled']));
            }
        }

        ?>
        <h1>WP Time Slots Extended - <?php _e('General Settings','wp-time-slots-booking-form'); ?></h1>
        <form name="updatesettings" action="" method="post">
            <h3>Email notification on Approval</h3>
            <div>
                <label>Subject</label><br />
                <input type="text" name="cp_cptslotextended_subject_approved" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_subject_approved', 'Your Slot has been approved')); ?>" /><br />
            </div>

            <div>
                <label>Body</label><br />
                <textarea name="cp_cptslotextended_body_approved" rows="10" cols="70"><?php echo esc_attr(get_option('cp_cptslotextended_body_approved', "Dear %email%,\r\n\r\nWe have just confirmed your slot on %formname% / %fieldname1%")); ?></textarea><br />
            </div>
            <div>&nbsp;</div>
            <h3>Email notification on Cancelation</h3>
            <div>
                <label>Subject</label><br />
                <input type="text" name="cp_cptslotextended_subject_canceled" size="70" value="<?php echo esc_attr(get_option('cp_cptslotextended_subject_canceled', 'Your Slot has been CANCELED')); ?>" /><br />
            </div>
            <div>
                <label>Body</label><br />
                <textarea name="cp_cptslotextended_body_canceled" rows="10" cols="70"><?php echo esc_attr(get_option('cp_cptslotextended_body_canceled', "Dear %email%,\r\n\r\nwe deeply regret that we had to cance your slot on %formname% / %fieldname1%")); ?></textarea><br />
            </div>
            <div>&nbsp;</div>
            <input type="submit" name="submit" class="button button-primary" value="Update" />
            <input type="submit" name="submit" class="button" value="Reset" />
        </form>
        <?php
    }

    public function onUpdateStatus($id, $status) {
        global $wpdb;

        define('CP_TSLOTSBOOK_DEFAULT_fp_from_email', get_the_author_meta('user_email', get_current_user_id()) );
        
        $from = $this->get_option('fp_from_email', @CP_TSLOTSBOOK_DEFAULT_fp_from_email);

        $events = $wpdb->get_results( $wpdb->prepare('SELECT * FROM `'.$wpdb->prefix.$this->table_messages.'` WHERE id=%d', $id) );
        $posted_data = unserialize($events[0]->posted_data);

        $status = strtolower($status);

        switch($status) {
            default:
                $subject = get_option('cp_cptslotextended_subject_approved', 'Your Slot has been approved');
                $body = get_option('cp_cptslotextended_body_approved',  "Dear %email%,\r\n\r\nWe have just confirmed your slot on %formname% / %fieldname1%");
                break;
            case 'canceled':
            case 'cancelled':
                $subject = get_option('cp_cptslotextended_subject_canceled', 'Your Slot has been CANCELED');
                $body = get_option('cp_cptslotextended_body_canceled',  "Dear %email%,\r\n\r\nwe deeply regret that we had to cance your slot on %formname% / %fieldname1%");
                break;
        }

        foreach ($posted_data as $item => $value) {
            $body = str_replace('%'.$item.'%',(is_array($value)?(implode(", ",$value)):($value)),$body);
        }
    
        wp_mail(trim($posted_data['email']), $subject . ' / ' . $posted_data['formname'], $body,
                    "From: $from <".$from.">\r\n".
                    'text/plain'.
                    "X-Mailer: PHP/" . phpversion());
    }
}

Ole1986_WpTimeSlotExtended::get_instance();