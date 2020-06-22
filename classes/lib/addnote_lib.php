<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of static functions used when adding or editing a note.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver <myemail@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\lib;

use core_tag_tag;

require_once($CFG->dirroot . '/tag/lib.php');

defined('MOODLE_INTERNAL') || die;

class addnote_lib {

    /**
     * Return the editor options for a note entry
     *
     * @param  stdClass $course  course object
     * @param  stdClass $context context object
     * @return array array containing the editor options
     */
    public static function get_editor_options($course, $context) {

        $editoroptions = [
        'subdirs'=> 0,
        'maxbytes'=> $course->maxbytes,
        'maxfiles'=> EDITOR_UNLIMITED_FILES,
        'changeformat'=> 0,
        'context'=> $context,
        'noclean'=> 0,
        'trusttext'=> 0,
        'enable_filemanagement' => true
        ];

        return array($editoroptions);
    }

}
