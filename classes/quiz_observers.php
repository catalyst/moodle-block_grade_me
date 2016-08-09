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
 * Attempts observers.
 *
 * @package    block_grade_me
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2016 Remote Learner.net Inc http://www.remote-learner.net
 */

namespace block_grade_me;

class quiz_observers {

    /**
     * A course content has been deleted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function course_content_deleted($event) {
        global $DB;
        $DB->delete_records('block_grade_me_quiz_ngrade', ['courseid' => $event->courseid]);
    }

    /**
     * A course content has been reset.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function course_reset_ended($event) {
        global $DB;
        if (!empty($event->other['reset_options']['reset_quiz_attempts'])) {
            $DB->delete_records('block_grade_me_quiz_ngrade', ['courseid' => $event->other['reset_options']['courseid']]);
        }
    }

    /**
     * A course module has been deleted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function course_module_deleted($event) {
        global $DB;
        if ($event->other['modulename'] == 'quiz') {
            $DB->delete_records('block_grade_me_quiz_ngrade', ['quizid' => $event->other['instanceid']]);
        }
    }

    /**
     * An attempt has been deleted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function attempt_deleted($event) {
        global $DB;
        $DB->delete_records('block_grade_me_quiz_ngrade', ['attemptid' => $event->objectid]);
    }

    /**
     * An attempt has been submitted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function attempt_submitted($event) {
        global $DB;
        $DB->delete_records('block_grade_me_quiz_ngrade', ['quizid' => $event->other['quizid'], 'userid' => $event->userid]);
        $sql = "INSERT INTO {block_grade_me_quiz_ngrade} ( attemptid, userid, quizid, questionattemptstepid, courseid ) SELECT qza.uniqueid, qza.userid, qza.quiz, qas.id, q.course
                  FROM {question_attempt_steps} qas
                       JOIN {question_attempts} qna ON qas.questionattemptid    = qna.id
                       JOIN {quiz_attempts} qza     ON qna.questionusageid      = qza.uniqueid
                       JOIN (SELECT questionattemptid, MAX(qas1.sequencenumber) maxseq
                          FROM {question_attempt_steps} qas1, {question_attempts} qna1
                         WHERE qas1.questionattemptid = qna1.id
                               AND qna1.questionusageid = ?
                      GROUP BY questionattemptid) maxseq ON maxseq.questionattemptid     = qna.id
                                                            AND qas.sequencenumber       = maxseq.maxseq
                       JOIN {quiz} q ON q.id = qza.quiz
                 WHERE qas.state = 'needsgrading'";
        $results = $DB->execute($sql, [$event->objectid]);
    }

    /**
     * An attempt has been manually graded.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function question_manually_graded($event) {
        global $DB;
        foreach ($records as $record) {
            $DB->delete_records('block_grade_me_quiz_ngrade', ['attemptid' => $event->other['attemptid'], 'quizid' => $event->other['quizid']]);
        }
    }
}