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
 * Base class for MCMS local tests
 *
 * @package   local_mcms
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mcms\page_utils;

/**
 * Base class for MCMS local tests
 *
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait local_mcms_test_base {

    /**
     * Upload a file in draft area
     *
     * @param context_user $usercontext
     * @param int $draftitemid
     * @param array $filenames
     * @param array $destfilenames
     * @return array files identifiers
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    protected function upload_image_draft_area($usercontext, $draftitemid, $filenames, $destfilenames) {
        global $CFG;
        $filesid = [];
        foreach ($filenames as $index => $filename) {
            $destfilename = $filename;
            if (!empty($destfilenames[$index])) {
                $destfilename = $destfilenames[$index];
            }
            $filerecord = [
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => $destfilename,
            ];
            // Create an area to upload the file.
            $fs = get_file_storage();
            // Create a file from the string that we made earlier.
            if (!($file = $fs->get_file($filerecord['contextid'],
                $filerecord['component'],
                $filerecord['filearea'],
                $filerecord['itemid'],
                $filerecord['filepath'],
                $filerecord['filename']))) {
                $file = $fs->create_file_from_pathname($filerecord,
                    $CFG->dirroot . '/local/mcms/tests/fixtures/' . $filename);
            }
            $filesid[] = $file->get_itemid();
        }
        return $filesid;
    }

    /**
     * Create a simple page
     *
     * @param \context_user $usercontext
     * @param object $pagedef
     * @param int[] $rolesid
     * @param array $contentimages
     * @param array $images
     * @return moodle_url
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    protected function create_page($usercontext, $pagedef, $rolesid, $contentimages, $images) {
        $draftdescriptionitemid = file_get_unused_draft_itemid();
        $this->upload_image_draft_area($usercontext, $draftdescriptionitemid, array_keys($contentimages),
            array_values($contentimages));
        $draftimagesitemid = file_get_unused_draft_itemid();
        $this->upload_image_draft_area($usercontext, $draftimagesitemid, array_keys($images),
            array_values($images));
        $pagedef->pageroles = $rolesid;
        return page_utils::page_add_edit($pagedef);
    }

}
