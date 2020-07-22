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
 * The mod_notetaker search note form.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

class searchnotes_form extends \moodleform
{
    public function definition() {

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        // Store the cmid.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', '<span class="input-group">');

        $mform->addElement('text', 'q');
        $mform->setType('q', PARAM_TEXT);

        $mform->addElement('html', '<span class="input-group-append">');
        $this->add_action_buttons(false, get_string('search', 'search'));
        $mform->addElement('html', '<button type="submit" id="clearsearch" class="btn btn-link" name="clear">'. get_string('clear'). '</button>');
        $mform->addElement('html', '</a>');
        $mform->addElement('html', '</span>');
        $mform->addElement('html', '</span>');
    }
}



