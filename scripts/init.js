jQuery(function() {
    jQuery('#wp_time_slots_extended_dashboard_time').change(function() {
        var val = jQuery(this).val();
        jQuery('#wp_time_slots_extended_dashboard_result').html('<div class="loading">' + wp_time_slots_extended.lang_loading + '</div>');

        jQuery.post(wp_time_slots_extended.ajaxurl, { action: 'wp_time_slots_extended_dashboard_post', slot: val})
            .done(function(result) {
                jQuery('#wp_time_slots_extended_dashboard_result').html(result.data);
            });
    })
});