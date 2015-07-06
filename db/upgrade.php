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
 * upgrade processes for this module.
 *
 * @package   mod_attendance
 * @copyright 2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * upgrade this attendance instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the attendance module
 * @return bool
 */
function xmldb_attendance_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    $result = true;

    // PicAttendance
    if ($oldversion < 2015070602) {

        // Define field detected to be added to attendance_images.
        $table = new xmldb_table('attendance_images');
        $field = new xmldb_field('detected', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null, 'tag');

        // Conditionally launch add field detected.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'width');

        // Conditionally launch add field studentid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('studentid', XMLDB_INDEX_NOTUNIQUE, array('studentid'));

        // Conditionally launch add index studentid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('groupimg', XMLDB_INDEX_NOTUNIQUE, array('groupimg'));

        // Conditionally launch add index groupimg.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        // Define table attendance_session_images to be created.
        $table = new xmldb_table('attendance_session_images');

        // Adding fields to table attendance_session_images.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupimg', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table attendance_session_images.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table attendance_session_images.
        $table->add_index('sessionid', XMLDB_INDEX_NOTUNIQUE, array('sessionid'));

        // Conditionally launch create table for attendance_session_images.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Attendance savepoint reached.
        upgrade_mod_savepoint(true, 2015070602, 'attendance');
    }
    
    if ($oldversion < 2015070601) {

        // Define table attendance_images to be created.
        $table = new xmldb_table('attendance_images');

        // Adding fields to table attendance_images.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupimg', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('faceimg', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('approved', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('tag', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('x', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('y', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('length', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('width', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table attendance_images.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for attendance_images.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Attendance savepoint reached.
        upgrade_mod_savepoint(true, 2015070601, 'attendance');
    }

    if ($oldversion < 2014112000) {
        $table = new xmldb_table('attendance_sessions');

        $field = new xmldb_field('studentscanmark');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_mod_savepoint($result, 2014112000, 'attendance');
    }

    if ($oldversion < 2014112001) {
        // Replace values that reference old module "attforblock" to "attendance".
        $sql = "UPDATE {grade_items}
                   SET itemmodule = 'attendance'
                 WHERE itemmodule = 'attforblock'";

        $DB->execute($sql);

        $sql = "UPDATE {grade_items_history}
                   SET itemmodule = 'attendance'
                 WHERE itemmodule = 'attforblock'";

        $DB->execute($sql);

        /*
         * The user's custom capabilities need to be preserved due to the module renaming.
         * Capabilities with a modifierid = 0 value are installed by default.
         * Only update the user's custom capabilities where modifierid is not zero.
         */
        $sql = $DB->sql_like('capability', '?').' AND modifierid <> 0';
        $rs = $DB->get_recordset_select('role_capabilities', $sql, array('%mod/attforblock%'));
        foreach ($rs as $cap) {
            $renamedcapability = str_replace('mod/attforblock', 'mod/attendance', $cap->capability);
            $exists = $DB->record_exists('role_capabilities', array('roleid' => $cap->roleid, 'capability' => $renamedcapability));
            if (!$exists) {
                $DB->update_record('role_capabilities', array('id' => $cap->id, 'capability' => $renamedcapability));
            }
        }

        // Delete old role capabilities.
        $sql = $DB->sql_like('capability', '?');
        $DB->delete_records_select('role_capabilities', $sql, array('%mod/attforblock%'));

        // Delete old capabilities.
        $DB->delete_records_select('capabilities', 'component = ?', array('mod_attforblock'));

        upgrade_plugin_savepoint($result, 2014112001, 'mod', 'attendance');
    }

    return $result;
}
