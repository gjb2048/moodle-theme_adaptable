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
 * Category header settings
 *
 * @package    theme_adaptable
 * @copyright  &copy; 2019 - TBD
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Category headers heading.
$temp = new admin_settingpage('theme_adaptable_categoryheaders', get_string('categoryheaderssettings', 'theme_adaptable'));
if ($ADMIN->fulltree) {
    $temp->add(new admin_setting_heading('theme_adaptable_categoryheaders', get_string('categoryheaderssettingsheading', 'theme_adaptable'),
        format_text(get_string('categoryheaderssettingsdesc', 'theme_adaptable'), FORMAT_MARKDOWN)));

    // Category headers to use.
    $coursecatsoptions = \theme_adaptable\toolbox::get_top_level_categories();
    $name = 'theme_adaptable/categoryhavecustomheader';
    $title = get_string('categoryhavecustomheader', 'theme_adaptable');
    $description = get_string('categoryhavecustomheaderdesc', 'theme_adaptable');
    $default = array();
    $setting = new admin_setting_configmultiselect($name, $title, $description, $default, $coursecatsoptions);
    $temp->add($setting);

    $tohavecustomheader = get_config('theme_adaptable', 'categoryhavecustomheader');
    if (!empty($tohavecustomheader)) {
        $customheaderids = explode(',', $tohavecustomheader);
        $topcats = \theme_adaptable\toolbox::get_top_categories_with_children();
        foreach ($customheaderids as $customheaderid) {
            $catinfo = $topcats[$customheaderid];
            if (empty($catinfo['children'])) {
                $headdesc = get_string('categoryheaderheaderdesc', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name']));
            } else {
                $childrentext = '';
                $first = true;
                foreach ($catinfo['children'] as $catchildid => $catchild) {
                    if ($first) {
                        $first = false;
                    } else {
                        $childrentext .= ', ';
                    }
                    $childrentext .= $catchild.'('.$catchildid.')';
                }
                $headdesc = get_string('categoryheaderheaderdescchildren', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name'], 'children' => $childrentext));
            }
            $temp->add(new admin_setting_heading('theme_adaptable_categoryheader'.$customheaderid, get_string('categoryheaderheader', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name'])),
                format_text($headdesc, FORMAT_MARKDOWN)));

            $name = 'theme_adaptable/categoryheaderbgimage'.$customheaderid;
            $title = get_string('categoryheaderbgimage', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name']));
            if (empty($catinfo['children'])) {
                $description = get_string('categoryheaderbgimagedesc', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name']));
            } else {
                $description = get_string('categoryheaderbgimagedescchildren', 'theme_adaptable', array('id' => $customheaderid, 'name' => $catinfo['name'], 'children' => $childrentext));
            }
            $setting = new admin_setting_configstoredfile($name, $title, $description, 'categoryheaderbgimage'.$customheaderid);
            $temp->add($setting);
        }
    }
}

$ADMIN->add('theme_adaptable', $temp);
