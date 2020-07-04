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
 * Local lib - main library of static functions.
 *
 * @package     mod_notetaker
 * @copyright   2020 Jo Beaver <myemail@example.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_notetaker\lib;

use core_tag_tag;
use DOMDocument;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/tag/lib.php');

class local {

    /**
     * Gets the notes associated with a module instance from the database.
     * Converts time to human readable format.
     * @param $cmid ID of the module instance.
     * @param $context current context.
     */
    public static function get_notes($cmid, $context) {
        global $DB;

        $results = $DB->get_records('notetaker_notes', ['modid' => $cmid]);

        foreach ($results as $result) {
            if ($result->timemodified != null) {
                $result->timemodified = userdate($result->timemodified, '%d %B %Y');
            } else {
                $result->timecreated = userdate($result->timecreated, '%d %B %Y');
            }

            // Process urls and convert card text to teaser length (150 characters).
            if (isset($result->notefield)) {
                $result->notefield = file_rewrite_pluginfile_urls($result->notefield, 'pluginfile.php', $context->id, 'mod_notetaker', 'notefield', $result->id);

                // Extract content from the editor to display in the card.
                $str = $result->notefield;
                $htmldom = new DOMDocument;
                libxml_use_internal_errors(true); // Required for HTML5.
                $htmldom->loadHTML($str);
                libxml_clear_errors(); // Required for HTML5.

                // Extract the images.
                $imagetags = $htmldom->getElementsByTagName('img');
                $extractedimages = [];
                foreach ($imagetags as $imagetag) {
                    $imgsrc = $imagetag->getAttribute('src');
                    $extractedimages[] = $imgsrc;
                }
                $result->extractedimages = $extractedimages;

                // Extract the text.
                $ptags = $htmldom->getElementsByTagName('p'); // Problem here is that Atto sometimes saves text in divs and not p tags.
                $cardtext = "";
                foreach ($ptags as $ptag) {
                    $cardtext = $cardtext . $ptag->textContent . " ";
                    $cardtext = strlen($cardtext) > 150 ? substr($cardtext, 0, 147).'...' : $cardtext;
                    $cardtext = format_text($cardtext, FORMAT_HTML);
                }
                $result->cardtext = $cardtext;
            }

            // Get the tags for this notetaker instance.
            $result->tags = core_tag_tag::get_item_tags_array('mod_notetaker', 'notetaker_notes', $result->id);
        }
        return $results;
    }

    /**
     * Deletes a note from the database.
     * @param $cmid ID of the module instance.
     * @param $noteid ID of the note.
     */
    public static function delete($cmid, $noteid) {
        global $DB;
        $DB->delete_records('notetaker_notes', ['modid' => $cmid, 'id' => $noteid]);
    }
}
