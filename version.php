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
 * Version details
 *
 * @package   theme_adaptable
 * @copyright 2015-2019 Jeremy Hopkins (Coventry University)
 * @copyright 2015-2019 Fernando Acedo (3-bits.com)
 * @copyright 2017-2019 Manoj Solanki (Coventry University)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'theme_adaptable';

// Adaptable version date.
$plugin->version   = 2019021600;

// Moodle required version (3.6.2 or higher).
$plugin->requires  = 2018120302.09;

// Adaptable version.
$plugin->release = '2.0.0';

// Adaptable maturity (do not use ALPHA or BETA versions in production sites).
$plugin->maturity = MATURITY_ALPHA;

// Adaptable dependencies.
$plugin->dependencies = array(
    'theme_classic'  => 2018120700,
);
