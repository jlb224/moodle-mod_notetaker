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
 * Library of interface functions and constants.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function notetaker_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_notetaker into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_notetaker_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function notetaker_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('notetaker', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_notetaker in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_notetaker_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function notetaker_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('notetaker', $moduleinstance);
}

/**
 * Removes an instance of the mod_notetaker from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function notetaker_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('notetaker', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('notetaker', array('id' => $id));

    return true;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @package     mod_notetaker
 * @return array
 */
function notetaker_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @package     mod_notetaker
 * @return array
 */
function notetaker_get_post_actions() {
    return array('update', 'add');
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @package     mod_notetaker
 * @param  stdClass $notetaker   notetaker object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function notetaker_view ($notetaker, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $event = \mod_notetaker\event\course_module_viewed::create(array(
    'objectid' => $notetaker->id,
    'context' => $context
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('notetaker', $notetaker);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Serves files.
 *
 * @package     mod_notetaker
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not to force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function notetaker_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'notefield' || $filearea !== 'intro') {
        return false;
    }

    require_course_login($course, true, $cm);

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/'; // Here $args is empty => the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // Here $args contains elements of the filepath.
    }

    $fs = get_file_storage();

    $file = $fs->get_file($context->id, 'mod_notetaker', 'notefield', $itemid, $filepath, $filename);

    if (!$file) {
        return false; // The file does not exist.
    }

    // Send the file.
    send_stored_file($file, 86400, 0, true, $options);
}
