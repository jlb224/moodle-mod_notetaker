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
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

class addnote_form extends \moodleform
{
    public function definition() {

        $mform = $this->_form;

        $addintro = '<p>' . get_string('addintro', 'mod_notetaker') . '</p>';

        // Intoduction.
        $mform->addElement('html', $addintro);

        // Store the cmid.
        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        // Store the noteid.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        // Store the publicposts setting from mod_form.
        $mform->addElement('hidden', 'publicposts', $this->_customdata['publicposts']);
        $mform->setType('id', PARAM_BOOL);

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'mod_notetaker'), array('size' => '64'));
        $mform->setType('name', PARAM_CLEANHTML);

        // Adding the editor.
        $editoroptions = $this->_customdata['editoroptions'];
        $mform->addElement('editor', 'notefield_editor', get_string('notecontent', 'mod_notetaker'), null, $editoroptions);
        $mform->setType('notefield_editor', PARAM_RAW);

        // Adding the tag selector.
        $mform->addElement('tags', 'tags', get_string('tags'), array('component' => 'mod_notetaker', 'itemtype' => 'notetaker_notes'));

        // Adding the "make post public" field.
        $mform->addElement('selectyesno', 'publicpost', get_string('publicpost', 'mod_notetaker'));
        // Hide unless feature is enabled in instance settings.
        $mform->hideIf('publicpost', 'publicposts', 'eq', 0);
        $mform->setType('publicpost', PARAM_INT);
        $mform->setType('publicposts', PARAM_INT);
        $mform->addHelpButton('publicpost', 'publicpost', 'mod_notetaker');

        // Action buttons.
        $this->add_action_buttons();
    }
}