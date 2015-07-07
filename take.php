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
 * Take Attendance
 *
 * @package    mod_attendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new att_take_page_params();

$id                     = required_param('id', PARAM_INT);
$pageparams->sessionid  = required_param('sessionid', PARAM_INT);
$pageparams->grouptype  = required_param('grouptype', PARAM_INT);
$pageparams->sort       = optional_param('sort', null, PARAM_INT);
$pageparams->copyfrom   = optional_param('copyfrom', null, PARAM_INT);
$pageparams->viewmode   = optional_param('viewmode', null, PARAM_INT);
$pageparams->gridcols   = optional_param('gridcols', null, PARAM_INT);
$pageparams->page       = optional_param('page', 1, PARAM_INT);
$pageparams->perpage    = optional_param('perpage', get_config('attendance', 'resultsperpage'), PARAM_INT);

$cm             = get_coursemodule_from_id('attendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att            = $DB->get_record('attendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$pageparams->group = groups_get_activity_group($cm, true);

$pageparams->init($course->id);
$att = new attendance($att, $cm, $course, $PAGE->context, $pageparams);

if (!$att->perm->can_take_session($pageparams->grouptype)) {
    $group = groups_get_group($pageparams->grouptype);
    throw new moodle_exception('cannottakeforgroup', 'attendance', '', $group->name);
}
if (($formdata = data_submitted()) && confirm_sesskey()) {
    if(isset($_POST["tatu"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "File is an imageeee - " . $check["mime"] . ".";
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
              
              $select = "approved = '1' AND studentid <> '0'";
              $results = $DB->get_records_select('attendance_images', $select, null, 'faceimg, studentid');
   
              $labeled_faces = array();
              foreach($results as $result) {
                // Prepare file record object
                $face_img_name = $result->faceimg;

                $fileinfo = array(
                      'contextid' => $coursecontext->id, // ID of context
                      'component' => 'mod_attendance',     // usually = table name
                      'filearea' => 'myarea',     // usually = table name
                      'itemid' => 0,               // usually = ID of row in table
                      'filepath' => '/',           // any path beginning and ending in /
                      'filename' => $face_img_name); // any filename
                 
                // Get file
                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                                      $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                 
                  $contents = $file->get_content();
                  $labeled_face = array('face' => $contents, 'label' => $result->studentid);
                  $labeled_faces[] = $labeled_face; 
              } 
  
              //////////////////////////////////////////////////////////////////
              // TODO: preencher a variável $labeled_faces para conter todas as faces
              // do banco de dados que estejam relacionadas a algum aluno (ja aprovadas).
              // Essa variável deve ser
              // um array. Cada entrada desse array deve ser um array associativo
              // com duas entradas, uma com indice "face" e outra com indice "label".
              // a entrada no indice "face" deve ser uma string contendo o conteúdo do arquivo
              // da face. a entrad "label" deve ser um inteiro (NAO UMA STRING!!) com o id do aluno
              // é muito importante que nenhuma dessas imagens tenha id 0 associado, já que 0 é usado como curinga
              //////////////////////////////////////////////////////////////////
              $faces = PICATTENDANCE_recognize_faces(dirname(__FILE__)."/lbpcascade_frontalface.xml", $image, $labeled_faces);
              
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
                  // $face["label"]
                  
                  //////////////////////////////////////////////////////////////
                  // TODO: Usar o valor da $face["label"] para escrever no BD
                  /// observe que $face["label"] é 0 para nao deteccao
                  //////////////////////////////////////////////////////////////
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
                  $fs_record->studentid = $face["label"];
                  // append
                  $lastinsertid = $DB->insert_record('attendance_images', $fs_record, false);
                  
                  $sess_record = new stdClass();
                  $sess_record->groupimg = $group_img_name;
                  $sess_record->sessionid = $pageparams->sessionid;
                  $lastinsertid = $DB->insert_record('attendance_session_images', $sess_record, false);
                }
              } 
            }
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    } else {
        $att->take_from_form_data($formdata);
    }
}

$PAGE->set_url($att->url_take());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'attendance'));
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_attendance');
$tabs = new attendance_tabs($att);
$sesstable = new attendance_take_data($att);

// Output starts here.

echo $output->header();
echo $output->heading(get_string('attendanceforthecourse', 'attendance').' :: ' .$course->fullname);
echo $output->render($tabs);
echo $output->render($sesstable);

echo $output->footer();
