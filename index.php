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
 * Display information about all the mod_notetaker modules in the requested course.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver <myemail@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

require_once(__DIR__.'/lib.php');

// Get the current course.
$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

// Build current course page.
require_course_login($course);
// $PAGE->set_pagelayout('incourse');

$coursecontext = context_course::instance($course->id);

$event = \mod_notetaker\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

$url = new moodle_url('/mod/notetaker/index.php', array('id' => $id));
$strplural = get_string('modulenameplural', 'notetaker');

$PAGE->set_url($url);
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($coursecontext);
$PAGE->navbar->add($strplural);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'notetaker');
echo $OUTPUT->heading($modulenameplural);

// TODO only get users own. Except if public
$notetakers = get_all_instances_in_course('notetaker', $course);

if (empty($notetakers)) {
    notice(get_string('nonewmodules', 'notetaker'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// TODO move display into a mustache template.
$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($notetakers as $notetaker) {
    if (!$notetaker->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/notetaker/view.php', array('id' => $notetaker->coursemodule)),
            format_string($notetaker->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/notetaker/view.php', array('id' => $notetaker->coursemodule)),
            format_string($notetaker->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($notetaker->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);

// $data = ['name' => 'Test'];
// echo $OUTPUT->render_from_template('mod_notetaker/index', ['rows' => $data]);

echo $OUTPUT->footer();