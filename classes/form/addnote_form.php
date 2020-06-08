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
 * The mod_notetaker add note form.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver <myemail@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

class addnote_form extends moodleform 
{
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $addintro = '<p>' . get_string('addintro', 'mod_notetaker') . '</p>';
        
        // Intoduction.
        $mform->addElement('html', $addintro);

        // Store the ID.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('notetakername', 'mod_notetaker'), array('size' => '64'));
        $mform->setType('name', PARAM_CLEANHTML);
        
        // Adding the editor.
        $mform->addElement('editor', 'notecontent', get_string('', 'mod_notetaker'));
        $mform->setType('notecontent', PARAM_RAW);
        
        // Action buttons.
        $this->add_action_buttons();
    } 
}