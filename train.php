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
 * Attendance report
 *
 * @package    mod_attendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new att_train_page_params();

$id                     = required_param('id', PARAM_INT);
$from                   = optional_param('from', null, PARAM_ACTION);
$pageparams->view       = optional_param('view', null, PARAM_INT);
$pageparams->curdate    = optional_param('curdate', null, PARAM_INT);
$pageparams->group      = optional_param('group', null, PARAM_INT);
$pageparams->sort       = optional_param('sort', null, PARAM_INT);
$pageparams->page       = optional_param('page', 1, PARAM_INT);
$pageparams->perpage    = get_config('attendance', 'resultsperpage');

$cm             = get_coursemodule_from_id('attendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$attrecord = $DB->get_record('attendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$pageparams->init($cm);
$att = new attendance($attrecord, $cm, $course, $PAGE->context, $pageparams);

$att->perm->require_view_reports_capability();

if (($formdata = data_submitted()) && confirm_sesskey()) {
    if(isset($_POST["tatu"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            //echo "File is an imageeee WOW - " . $check["mime"] . ".";
            $image_file = fopen($_FILES["fileToUpload"]['tmp_name'], 'rb');
            $image = fread($image_file, 20000000);
            if (!$image) {
              echo "Image is too big";
            } else {
              $coursecontext = context_course::instance($course->id);
              $contextmodule = context_module::instance($cm->id);
              $fs = get_file_storage();
               
              $group_img_name = sha1($image);
               
              // Prepare file record object
              $fileinfo = array(
                  'contextid' => $coursecontext->id, // ID of context
                  'component' => 'mod_attendance',     // usually = table name
                  'filearea' => 'myarea',     // usually = table name
                  'itemid' => 0,               // usually = ID of row in table
                  'filepath' => '/',           // any path beginning and ending in /
                  'filename' => $group_img_name); // any filename
               

              if (!$fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
                $fs->create_file_from_string($fileinfo, $image);
              }
              $faces = PICATTENDANCE_find_faces(dirname(__FILE__)."/lbpcascade_frontalface.xml", $image);
              foreach ($faces as $face) {
                $face_img_name = sha1($face["face"]);
                // Prepare file record object
                $fileinfo = array(
                    'contextid' => $coursecontext->id, // ID of context
                    'component' => 'mod_attendance',     // usually = table name
                    'filearea' => 'myarea',     // usually = table name
                    'itemid' => 0,               // usually = ID of row in table
                    'filepath' => '/',           // any path beginning and ending in /
                    'filename' => $face_img_name); // any filename
                if (!$fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
                  $fs->create_file_from_string($fileinfo, $face["face"]);
                  // $face["rectangle"]["x"]
                  // $face["rectangle"]["y"]
                  // $face["rectangle"]["width"]
                  // $face["rectangle"]["height"]
                  $fs_record = new stdClass();
                  
                  $fs_record->groupimg = $group_img_name;
                  $fs_record->faceimg = $face_img_name;
                  $fs_record->approved = 0;
                  $fs_record->tag = 0;
                  $fs_record->detected = 1;
                  $fs_record->x = $face["rectangle"]["x"];
                  $fs_record->y = $face["rectangle"]["y"];
                  $fs_record->width = $face["rectangle"]["width"];
                  $fs_record->length = $face["rectangle"]["height"];
                  // append
                  $lastinsertid = $DB->insert_record('attendance_images', $fs_record, false);
                  
                  $sess_record = new stdClass();
                  $sess_record->groupimg = $group_img_name;
                  // Imagens para training tem sessionid = 0
                  $lastinsertid = $DB->insert_record('attendance_session_images', $sess_record, false);
                }
              }
            }
            $uploadOk = 1;
            // COLOCAR: redirect($att->url_manage(), get_string('sessionupdated', 'attendance'));
        } else {
            echo "File is not an image.";
            // print_error('sessionsnotfound', 'attendance', $att->url_manage());
            $uploadOk = 0;
        }
    }
}

$PAGE->set_url($att->url_train());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'attendance'));
// Add get_string('train', 'attendance');
$PAGE->navbar->add('TRAIN');

$output = $PAGE->get_renderer('mod_attendance');
$tabs = new attendance_tabs($att, attendance_tabs::TAB_TRAIN);
$button = new attendance_train_data($att);

// Output starts here.

echo $output->header();
echo $output->heading(get_string('attendanceforthecourse', 'attendance').' :: ' .$course->fullname);
echo $output->render($tabs);
echo $output->render($button);


// echo $output->render($filtercontrols);
// echo $output->render($reportdata);

echo $output->footer();
