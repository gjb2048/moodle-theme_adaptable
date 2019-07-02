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
 * myprofile edit profile.
 *
 * @package    theme_adaptable
 * @copyright  &copy; 2019 - Coventry University
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_adaptable\output\core_user\myprofile;

defined('MOODLE_INTERNAL') || die;

/**
 * myprofile editprofile.
 */
class editprofile {
    static function process_edit_profile() {
        global $CFG, $DB, $PAGE, $SITE, $USER;
        $userid = optional_param('id', 0, PARAM_INT);
        $userid = $userid ? $userid : $USER->id;
        $user = \core_user::get_user($userid);

        $courseid = optional_param('course', SITEID, PARAM_INT); // Course id (defaults to Site).
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        if ($user->id !== -1) {
            $usercontext = \context_user::instance($user->id);
            $editoroptions = array(
                'maxfiles'   => EDITOR_UNLIMITED_FILES,
                'maxbytes'   => $CFG->maxbytes,
                'trusttext'  => false,
                'forcehttps' => false,
                'context'    => $usercontext
            );
            $user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
        } else {
            $usercontext = null;
            // This is a new user, we don't want to add files here.
            $editoroptions = array(
                'maxfiles' => 0,
                'maxbytes' => 0,
                'trusttext' => false,
                'forcehttps' => false,
                'context' => $coursecontext
            );
        }
        // Prepare filemanager draft area.
        $draftitemid = 0;
        $filemanagercontext = $editoroptions['context'];
        $filemanageroptions = array(
            'maxbytes'       => $CFG->maxbytes,
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => 'web_image');
        \file_prepare_draft_area($draftitemid, $filemanagercontext->id, 'user', 'newicon', 0, $filemanageroptions);
        $user->imagefile = $draftitemid;

        // Deciding where to send the user back in most cases.
        //if ($returnto === 'profile') {
            if ($course->id != SITEID) {
                $returnurl = new \moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id));
            } else {
                $returnurl = new \moodle_url('/user/profile.php', array('id' => $user->id));
            }
        /*} else {
            $returnurl = new \moodle_url('/user/preferences.php', array('userid' => $user->id));
        }*/

        $editprofileform = new editprofile_form(
            new \moodle_url($PAGE->url),
            array(
                'editoroptions' => $editoroptions,
                'filemanageroptions' => $filemanageroptions,
                'user' => $user)
            );

        if ($editprofileform->is_cancelled()) {
            redirect($returnurl);
        } else if ($usernew = $editprofileform->get_data()) {
            $usercreated = false;
            if (empty($usernew->auth)) {
                // User editing self.
                $authplugin = get_auth_plugin($user->auth);
                unset($usernew->auth); // Can not change/remove.
            } else {
                $authplugin = get_auth_plugin($usernew->auth);
            }

            $usernew->timemodified = time();
            $createpassword = false;

            if ($usernew->id == -1) {
                unset($usernew->id);
                $createpassword = !empty($usernew->createpassword);
                unset($usernew->createpassword);
                $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, null, 'user', 'profile', null);
                $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
                $usernew->confirmed  = 1;
                $usernew->timecreated = time();
                if ($authplugin->is_internal()) {
                    if ($createpassword or empty($usernew->newpassword)) {
                        $usernew->password = '';
                    } else {
                        $usernew->password = hash_internal_user_password($usernew->newpassword);
                    }
                } else {
                    $usernew->password = AUTH_PASSWORD_NOT_CACHED;
                }
                $usernew->id = user_create_user($usernew, false, false);

                if (!$authplugin->is_internal() and $authplugin->can_change_password() and !empty($usernew->newpassword)) {
                    if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                        // Do not stop here, we need to finish user creation.
                        debugging(get_string('cannotupdatepasswordonextauth', '', '', $usernew->auth), DEBUG_NONE);
                    }
                }
                $usercreated = true;
            } else {
                $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
                // Pass a true old $user here.
                if (!$authplugin->user_update($user, $usernew)) {
                    // Auth update failed.
                    print_error('cannotupdateuseronexauth', '', '', $user->auth);
                }
                user_update_user($usernew, false, false);

                // Set new password if specified.
                if (!empty($usernew->newpassword)) {
                    if ($authplugin->can_change_password()) {
                        if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                            print_error('cannotupdatepasswordonextauth', '', '', $usernew->auth);
                        }
                        unset_user_preference('create_password', $usernew); // Prevent cron from generating the password.

                        if (!empty($CFG->passwordchangelogout)) {
                            // We can use SID of other user safely here because they are unique,
                            // the problem here is we do not want to logout admin here when changing own password.
                            \core\session\manager::kill_user_sessions($usernew->id, session_id());
                        }
                        if (!empty($usernew->signoutofotherservices)) {
                            webservice::delete_user_ws_tokens($usernew->id);
                        }
                    }
                }

                // Force logout if user just suspended.
                if (isset($usernew->suspended) and $usernew->suspended and !$user->suspended) {
                    \core\session\manager::kill_user_sessions($user->id);
                }
            }

            $usercontext = \context_user::instance($usernew->id);

            // Update preferences.
            useredit_update_user_preference($usernew);

            // Update tags.
            if (empty($USER->newadminuser) && isset($usernew->interests)) {
                useredit_update_interests($usernew, $usernew->interests);
            }

            // Update user picture.
            if (empty($USER->newadminuser)) {
                \core_user::update_picture($usernew, $filemanageroptions);
            }

            // Update mail bounces.
            useredit_update_bounces($user, $usernew);

            // Update forum track preference.
            useredit_update_trackforums($user, $usernew);

            // Save custom profile fields data.
            profile_save_data($usernew);

            // Reload from db.
            $usernew = $DB->get_record('user', array('id' => $usernew->id));

            if ($createpassword) {
                setnew_password_and_mail($usernew);
                unset_user_preference('create_password', $usernew);
                set_user_preference('auth_forcepasswordchange', 1, $usernew);
            }

            // Trigger update/create event, after all fields are stored.
            if ($usercreated) {
                \core\event\user_created::create_from_userid($usernew->id)->trigger();
            } else {
                \core\event\user_updated::create_from_userid($usernew->id)->trigger();
            }

            if ($user->id == $USER->id) {
                // Override old $USER session variable.
                foreach ((array)$usernew as $variable => $value) {
                    if ($variable === 'description' or $variable === 'password') {
                        // These are not set for security nad perf reasons.
                        continue;
                    }
                    $USER->$variable = $value;
                }
                // Preload custom fields.
                profile_load_custom_fields($USER);

                if (!empty($USER->newadminuser)) {
                    unset($USER->newadminuser);
                    // Apply defaults again - some of them might depend on admin user info, backup, roles, etc.
                    admin_apply_default_settings(null, false);
                    // Admin account is fully configured - set flag here in case the redirect does not work.
                    unset_config('adminsetuppending');
                    // Redirect to admin/ to continue with installation.
                    redirect("$CFG->wwwroot/$CFG->admin/");
                } else if (empty($SITE->fullname)) {
                    // Somebody double clicked when editing admin user during install.
                    redirect("$CFG->wwwroot/$CFG->admin/");
                } else {
                    redirect($returnurl);
                }
            } else {
                \core\session\manager::gc(); // Remove stale sessions.
                redirect("$CFG->wwwroot/$CFG->admin/user.php");
            }
            // Never reached..
        }
    }
}
