<?php

if (class_exists('CP_APPBOOK_BaseClass')) {
    class Ole1986_SlotBase extends CP_APPBOOK_BaseClass {
        protected $menu_parameter = 'cp_apphourbooking';
        public $table_items = "cpappbk_forms";
        public $table_messages = "cpappbk_messages";
    }
} else if (class_exists('CP_TSLOTSBOOK_BaseClass')) {
    class Ole1986_SlotBase extends CP_TSLOTSBOOK_BaseClass {
        protected $menu_parameter = 'cp_timeslotsbooking';
        public $table_items = "cptslotsbk_forms";
        public $table_messages = "cptslotsbk_messages";
    }
} else {
    class Ole1986_SlotBase { }
}