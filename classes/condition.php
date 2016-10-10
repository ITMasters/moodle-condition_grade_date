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
 * Condition on grades of current user.
 *
 * @package availability_grade_date
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_grade_date;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition on grades of current user.
 *
 * @package availability_grade_date
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int Grade item id */
    private $gradeitemid;

    /** @var float|null Min grade (must be >= this) or null if none */
    private $min;

    /** @var float|null Max grade (must be < this) or null if none */
    private $max;

    /** @var string Availabile only from specified date. */
    const DIRECTION_FROM = '>=';

    /** @var string Availabile only until specified date. */
    const DIRECTION_UNTIL = '<';

    /** @var string One of the DIRECTION_xx constants. */
    private $direction;

    /** @var int Time (Unix epoch seconds) for condition. */
    private $time;

    /** @var int Forced current time (for unit tests) or 0 for normal. */
    private static $forcecurrenttime = 0;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get grade item id.
        if (isset($structure->id) && is_int($structure->id)) {
            $this->gradeitemid = $structure->id;
        } else {
            throw new \coding_exception('Missing or invalid ->id for grade condition');
        }

        // Get min and max.
        if (!property_exists($structure, 'min')) {
            $this->min = null;
        } else if (is_float($structure->min) || is_int($structure->min)) {
            $this->min = $structure->min;
        } else {
            throw new \coding_exception('Missing or invalid ->min for grade condition');
        }
        if (!property_exists($structure, 'max')) {
            $this->max = null;
        } else if (is_float($structure->max) || is_int($structure->max)) {
            $this->max = $structure->max;
        } else {
            throw new \coding_exception('Missing or invalid ->max for grade condition');
        }

        // Get direction.
        if (isset($structure->d) && in_array($structure->d,
                array(self::DIRECTION_FROM, self::DIRECTION_UNTIL))) {
            $this->direction = $structure->d;
        } else {
            throw new \coding_exception('Missing or invalid ->d for date condition');
        }

        // Get time.
        if (isset($structure->t) && is_int($structure->t)) {
            $this->time = $structure->t;
        } else {
            throw new \coding_exception('Missing or invalid ->t for date condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'grade_date', 'id' => $this->gradeitemid);
        if (!is_null($this->min)) {
            $result->min = $this->min;
        }
        if (!is_null($this->max)) {
            $result->max = $this->max;
        }
        $result->d = $this->direction;
        $result->t = $this->time;
        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $gradeitemid Grade item id
     * @param number|null $min Min grade (or null if no min)
     * @param number|null $max Max grade (or null if no max)
     * @param string $direction DIRECTION_xx constant
     * @param int $time Time in epoch seconds
     * @return stdClass Object representing condition
     */
    public static function get_json($gradeitemid, $min = null, $max = null, $direction, $time) {
        $result = (object)array('type' => 'grade_date', 'id' => (int)$gradeitemid);
        if (!is_null($min)) {
            $result->min = $min;
        }
        if (!is_null($max)) {
            $result->max = $max;
        }
        $result->d = $direction;
        $result->t = $time;
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $course = $info->get_course();
        $score = $this->get_cached_grade_score($this->gradeitemid, $course->id, $grabthelot, $userid);
        $allow = $score !== false &&
                (is_null($this->min) || $score >= $this->min) &&
                (is_null($this->max) || $score < $this->max);
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

     /**
     * Obtains the actual direction of checking based on the $not value.
     *
     * @param bool $not True if condition is negated
     * @return string Direction constant
     * @throws \coding_exception
     */
    protected function get_logical_direction($not) {
        switch ($this->direction) {
            case self::DIRECTION_FROM:
                return $not ? self::DIRECTION_UNTIL : self::DIRECTION_FROM;
            case self::DIRECTION_UNTIL:
                return $not ? self::DIRECTION_FROM : self::DIRECTION_UNTIL;
            default:
                throw new \coding_exception('Unexpected direction');
        }
    }

    public function get_description($full, $not, \core_availability\info $info) {
        $course = $info->get_course();
        // String depends on type of requirement. We are coy about
        // the actual numbers, in case grades aren't released to
        // students.
        if (is_null($this->min) && is_null($this->max)) {
            $string = 'any';
        } else if (is_null($this->max)) {
            $string = 'min';
        } else if (is_null($this->min)) {
            $string = 'max';
        } else {
            $string = 'range';
        }
        if ($not) {
            // The specific strings don't make as much sense with 'not'.
            if ($string === 'any') {
                $string = 'notany';
            } else {
                $string = 'notgeneral';
            }
        }
        $name = self::get_cached_grade_name($course->id, $this->gradeitemid);
        return get_string('requires_' . $string, 'availability_grade_date', $name) . ' ' . $this->get_either_description($not, true);
    }

    public function get_standalone_description(
            $full, $not, \core_availability\info $info) {
        $course = $info->get_course();
        // String depends on type of requirement. We are coy about
        // the actual numbers, in case grades aren't released to
        // students.
        if (is_null($this->min) && is_null($this->max)) {
            $string = 'any';
        } else if (is_null($this->max)) {
            $string = 'min';
        } else if (is_null($this->min)) {
            $string = 'max';
        } else {
            $string = 'range';
        }
        if ($not) {
            // The specific strings don't make as much sense with 'not'.
            if ($string === 'any') {
                $string = 'notany';
            } else {
                $string = 'notgeneral';
            }
        }
        $name = self::get_cached_grade_name($course->id, $this->gradeitemid);
        return get_string('requires_' . $string, 'availability_grade_date', $name) . ' ' . $this->get_either_description($not, true);
    }

    /**
     * Shows the description using the different lang strings for the standalone
     * version or the full one.
     *
     * @param bool $not True if NOT is in force
     * @param bool $standalone True to use standalone lang strings
     */
    protected function get_either_description($not, $standalone) {
        $direction = $this->get_logical_direction($not);
        $midnight = self::is_midnight($this->time);
        $midnighttag = $midnight ? '_date' : '';
        $satag = $standalone ? 'short_' : 'full_';
        switch ($direction) {
            case self::DIRECTION_FROM:
                return get_string($satag . 'from' . $midnighttag, 'availability_grade_date',
                        self::show_time($this->time, $midnight, false));
            case self::DIRECTION_UNTIL:
                return get_string($satag . 'until' . $midnighttag, 'availability_grade_date',
                        self::show_time($this->time, $midnight, true));
        }
    }

    protected function get_debug_string() {
        $out = '#' . $this->gradeitemid;
        if (!is_null($this->min)) {
            $out .= ' >= ' . sprintf('%.5f', $this->min);
        }
        if (!is_null($this->max)) {
            if (!is_null($this->min)) {
                $out .= ',';
            }
            $out .= ' < ' . sprintf('%.5f', $this->max);
        }
        $out .= $this->direction . ' ' . gmdate('Y-m-d H:i:s', $this->time);
        return $out;
    }

    /**
     * Gets time. This function is implemented here rather than calling time()
     * so that it can be overridden in unit tests. (Would really be nice if
     * Moodle had a generic way of doing that, but it doesn't.)
     *
     * @return int Current time (seconds since epoch)
     */
    protected static function get_time() {
        if (self::$forcecurrenttime) {
            return self::$forcecurrenttime;
        } else {
            return time();
        }
    }

    /**
     * Forces the current time for unit tests.
     *
     * @param int $forcetime Time to return from the get_time function
     */
    public static function set_current_time_for_test($forcetime = 0) {
        self::$forcecurrenttime = $forcetime;
    }

    /**
     * Shows a time either as a date or a full date and time, according to
     * user's timezone.
     *
     * @param int $time Time
     * @param bool $dateonly If true, uses date only
     * @param bool $until If true, and if using date only, shows previous date
     * @return string Date
     */
    protected function show_time($time, $dateonly, $until = false) {
        // For 'until' dates that are at midnight, e.g. midnight 5 March, it
        // is better to word the text as 'until end 4 March'.
        $daybefore = false;
        if ($until && $dateonly) {
            $daybefore = true;
            $time = strtotime('-1 day', $time);
        }
        return userdate($time,
                get_string($dateonly ? 'strftimedate' : 'strftimedatetime', 'langconfig'));
    }

    /**
     * Checks whether a given time refers exactly to midnight (in current user
     * timezone).
     *
     * @param int $time Time
     * @return bool True if time refers to midnight, false otherwise
     */
    protected static function is_midnight($time) {
        return usergetmidnight($time) == $time;
    }
    /**
     * Obtains the name of a grade item, also checking that it exists. Uses a
     * cache. The name returned is suitable for display.
     *
     * @param int $courseid Course id
     * @param int $gradeitemid Grade item id
     * @return string Grade name or empty string if no grade with that id
     */
    private static function get_cached_grade_name($courseid, $gradeitemid) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // Get all grade item names from cache, or using db query.
        $cache = \cache::make('availability_grade_date', 'items');
        if (($cacheditems = $cache->get($courseid)) === false) {
            // We cache the whole items table not the name; the format_string
            // call for the name might depend on current user (e.g. multilang)
            // and this is a shared cache.
            $cacheditems = $DB->get_records('grade_items', array('courseid' => $courseid));
            $cache->set($courseid, $cacheditems);
        }

        // Return name from cached item or a lang string.
        if (!array_key_exists($gradeitemid, $cacheditems)) {
            return get_string('missing', 'availability_grade_date');
        }
        $gradeitemobj = $cacheditems[$gradeitemid];
        $item = new \grade_item;
        \grade_object::set_properties($item, $gradeitemobj);
        return $item->get_name();
    }

    /**
     * Obtains a grade score. Note that this score should not be displayed to
     * the user, because gradebook rules might prohibit that. It may be a
     * non-final score subject to adjustment later.
     *
     * @param int $gradeitemid Grade item ID we're interested in
     * @param int $courseid Course id
     * @param bool $grabthelot If true, grabs all scores for current user on
     *   this course, so that later ones come from cache
     * @param int $userid Set if requesting grade for a different user (does
     *   not use cache)
     * @return float Grade score as a percentage in range 0-100 (e.g. 100.0
     *   or 37.21), or false if user does not have a grade yet
     */
    protected function get_cached_grade_score($gradeitemid, $courseid, $grabthelot=false, $userid=0) {
        global $USER, $DB;
        if (!$userid) {
            $userid = $USER->id;
        }
        $cache = \cache::make('availability_grade_date', 'scores');
        if (($cachedgrades = $cache->get($userid)) === false) {
            $cachedgrades = array();
        }
        if (!array_key_exists($gradeitemid, $cachedgrades)) {
            if ($grabthelot) {
                // Get all grades for the current course.
                $rs = $DB->get_recordset_sql('
                        SELECT
                            gi.id,gg.timecreated,gg.finalgrade,gg.rawgrademin,gg.rawgrademax
                        FROM
                            {grade_items} gi
                            LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid=?
                        WHERE
                            gi.courseid = ?', array($userid, $courseid));
                foreach ($rs as $record) {
                    // This function produces division by zero error warnings when rawgrademax and rawgrademin
                    // are equal. Below change does not affect function behavior, just avoids the warning.
                    if (is_null($record->finalgrade) || $record->rawgrademax == $record->rawgrademin) {
                        // No grade = false.
                        $cachedgrades[$record->id] = false;
                    } else {
                        switch ($this->direction) {
                            case self::DIRECTION_FROM:
                                $allow = $record->timecreated >= $this->time;
                                break;
                            case self::DIRECTION_UNTIL:
                                $allow = $record->timecreated < $this->time;
                                break;
                            default:
                                throw new \coding_exception('Unexpected direction');
                        }
                        // Otherwise convert grade to percentage.
                        if($allow) {
                            $cachedgrades[$record->id] =
                                (($record->finalgrade - $record->rawgrademin) * 100) /
                                ($record->rawgrademax - $record->rawgrademin);
                        }
                    }

                }
                $rs->close();
                // And if it's still not set, well it doesn't exist (eg
                // maybe the user set it as a condition, then deleted the
                // grade item) so we call it false.
                if (!array_key_exists($gradeitemid, $cachedgrades)) {
                    $cachedgrades[$gradeitemid] = false;
                }
            } else {
                // Just get current grade.
                $record = $DB->get_record('grade_grades', array(
                    'userid' => $userid, 'itemid' => $gradeitemid));
                // This function produces division by zero error warnings when rawgrademax and rawgrademin
                // are equal. Below change does not affect function behavior, just avoids the warning.
                if ($record && !is_null($record->finalgrade) && $record->rawgrademax != $record->rawgrademin) {
                    switch ($this->direction) {
                        case self::DIRECTION_FROM:
                            $allow = $record->timecreated >= $this->time;
                            break;
                        case self::DIRECTION_UNTIL:
                            $allow = $record->timecreated < $this->time;
                            break;
                        default:
                            throw new \coding_exception('Unexpected direction');
                    }
                    if($allow) {
                        $score = (($record->finalgrade - $record->rawgrademin) * 100) /
                            ($record->rawgrademax - $record->rawgrademin);
                    } else {
                        $score = false;
                    }
                } else {
                    // Treat the case where row exists but is null, same as
                    // case where row doesn't exist.
                    $score = false;
                }
                $cachedgrades[$gradeitemid] = $score;
            }
            $cache->set($userid, $cachedgrades);
        }
        return $cachedgrades[$gradeitemid];
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'grade_item', $this->gradeitemid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('grade_items',
                    array('id' => $this->gradeitemid, 'courseid' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->gradeitemid = 0;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on grade that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->gradeitemid = (int)$rec->newitemid;
        }
        return true;
    }

    public function update_dependency_id($table, $oldid, $newid) {
        if ($table === 'grade_items' && (int)$this->gradeitemid === (int)$oldid) {
            $this->gradeitemid = $newid;
            return true;
        } else {
            return false;
        }
    }
}
