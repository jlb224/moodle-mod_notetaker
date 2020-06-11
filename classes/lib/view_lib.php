<?php

namespace mod_notetaker\lib;

defined('MOODLE_INTERNAL') || die;

class view_lib {     
    
    public static function get_notes($notetakerid) {
        global $DB;

        $result = $DB->get_records('notetaker_notes', ['notetakerid' => $notetakerid]);
        return $result;  
    }    
}
