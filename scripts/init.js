function WpTimeSlotExtended() {
    var self = this;

    var slotRef, filterRef, resultRef;
    var timerFilter = null;

    var tapedTwice = false;

    var state = {
        slot: '',
        filter: ''
    };

    var tapHandler = (event) => {
        if (!tapedTwice) {
            tapedTwice = true;
            setTimeout(function () { tapedTwice = false; }, 300);
            return false;
        }
        event.preventDefault();
        event.target.select();
    }


    this.filter = () => {
        var args = { action: 'wp_time_slots_extended_dashboard_post' };

        Object.assign(args, state);

        self.resultRef.innerHTML = '<div class="loading">' + wp_time_slots_extended.lang_loading + '</div>';

        jQuery.post(wp_time_slots_extended.ajaxurl, args)
            .done(function (result) {
                if (!result.data) {
                    self.resultRef.innerHTML = '<div class="loading">' + wp_time_slots_extended.lang_nodata + '</div>'
                    return;
                }
                self.resultRef.innerHTML = result.data;
            });
    };

    var onItemChanged = (e) => {
        state[e.target.name] = e.target.value;
    }

    (function () {
        self.slotRef = document.getElementById('wp_time_slots_extended_dashboard_time');

        self.slotRef.addEventListener('change', e => {
            onItemChanged(e);
            self.filter();
        });

        self.filterRef = document.getElementById('wp_time_slots_extended_dashboard_filter');
        self.filterRef.addEventListener("touchstart", tapHandler);
        self.filterRef.addEventListener('keyup', e => {
            onItemChanged(e);
            if (self.timerFilter) clearTimeout(self.timerFilter);
            self.timerFilter = setTimeout(() => self.filter(), 700);
        });

        self.resultRef = document.getElementById('wp_time_slots_extended_dashboard_result');
    })();
}

document.addEventListener("DOMContentLoaded", function (event) {
    new WpTimeSlotExtended();
});
