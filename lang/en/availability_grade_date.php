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
 * Language strings.
 *
 * @package availability_grade_date
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cachedef_items'] = 'Grade items cached for evaluating conditional availability';
$string['cachedef_scores'] = 'User grades cached for evaluating conditional availability';
$string['error_backwardrange'] = 'When specifying a grade range, the minimum must be lower than the maximum.';
$string['error_invalidnumber'] = 'Grade ranges must be specified with valid percentages.';
$string['error_selectgradeid'] = 'You must select a grade item for the grade condition.';
$string['label_min'] = 'Minimum grade percentage (inclusive)';
$string['label_max'] = 'Maximum grade percentage (exclusive)';
$string['option_min'] = 'must be &#x2265;';
$string['option_max'] = 'must be <';
$string['requires_any'] = 'You have a grade in <strong>{$a}</strong>';
$string['requires_max'] = 'You get an appropriate score in <strong>{$a}</strong>';
$string['requires_min'] = 'You achieve a required score in <strong>{$a}</strong>';
$string['requires_notany'] = 'You do not have a grade in <strong>{$a}</strong>';
$string['requires_notgeneral'] = 'You do not get certain scores in <strong>{$a}</strong>';
$string['requires_range'] = 'You get a particular score in <strong>{$a}</strong>';
$string['missing'] = '(missing activity)';
$string['ajaxerror'] = 'Error contacting server to convert times';
$string['direction_before'] = 'Date';
$string['direction_from'] = 'from';
$string['direction_label'] = 'Direction';
$string['direction_until'] = 'until';
$string['description'] = 'Require students to achieve a specified grade before a specified date and time.';
$string['full_from'] = 'It is after <strong>{$a}</strong>';
$string['full_from_date'] = 'It is on or after <strong>{$a}</strong>';
$string['full_until'] = 'It is before <strong>{$a}</strong>';
$string['full_until_date'] = 'It is before end of <strong>{$a}</strong>';
$string['pluginname'] = 'Restriction by grade by date';
$string['short_from'] = 'obtained after <strong>{$a}</strong>';
$string['short_from_date'] = 'obtained after <strong>{$a}</strong>';
$string['short_until'] = 'obtained before <strong>{$a}</strong>';
$string['short_until_date'] = 'obtained before the end of <strong>{$a}</strong>';
$string['title'] = 'Grade by Date';