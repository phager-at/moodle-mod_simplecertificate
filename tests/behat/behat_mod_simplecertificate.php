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
 * Basic simplecertificate steps definitions.
 *
 * @package mod_simplecertificate
 * @category test
 * @copyright 2016 © Carlos Alexandre S. da Fonseca
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use Behat\Mink\Exception\ElementException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\Exception;
use Behat\Mink\Exception\ExpectationException;

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_mod_simplecertificate extends behat_base {
    // Just a memo note.
    // ... $this->execute('behat_forms::i_set_the_field_to', array('Username', $this->escape($username)));.
    // ... $this->execute('behat_forms::i_set_the_field_to', array('Password', $this->escape($username)));.

    /**
     * Verify if an new windows is created
     * @Then /^I should see a pop-up window$/
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $button
     */
    public function should_see_popup() {
        $windowames = $this->getSession()->getWindowNames();
        if (count($windowames) < 2) {
            throw new ElementNotFoundException();
        }
    }

    /**
     * For some reason I set don't work when i try to enable access restringtion
     * using xpath to slove the problem
     * @Given /^I select "(?P<option_string>(?:[^"]|\\")*)" option from the "(?P<select_string>(?:[^"]|\\")*)"$/
     *
     * @param unknown $option
     * @param unknown $select
     */
    public function select_option_from($option, $select) {
        $xpath = '//select[@name="' . $this->escape($select) . '"]';
        $selectedelement = $this->find('xpath', $xpath);
        try {
            $selectedelement->selectOption($this->escape($option));
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException($e->getMessage() . "\nField Options = " . $selectedelement->getText(),
                                        $this->getSession()->getDriver(), $e);
        }

    }

    /**
     * Add grade restriction to an activity
     * @Given /^I put a grade restrinction to "(?P<activity_string>(?:[^"]|\\")*)" with "(?P<grade_activity_string>(?:[^"]|\\")*)" min grade "(?P<mingrade_string>(?:[^"]|\\")*)"$/
     *
     * @param unknown $activity
     * @param unknown $mingrade
     */
    public function add_grade_restriction($activity, $gradeactivity, $mingrade) {
        $this->execute('behat_general::click_link', array($this->escape($activity)));
        $this->execute('behat_navigation::i_navigate_to_node_in', array("Edit settings", "Certificate administration"));
        $this->execute('behat_forms::i_expand_all_fieldsets');
        $this->execute('behat_general::i_click_on', array("Add restriction...", "button"));
        $this->execute('behat_general::i_click_on_in_the', array("Grade", "button", "Add restriction...", "dialogue"));
        $this->execute('behat_general::i_click_on', array("min", "checkbox"));
        $this->select_option_from($this->escape($gradeactivity), 'id');
        $this->execute('behat_forms::i_set_the_field_to', array('minval', $this->escape($mingrade)));
        $this->execute('behat_forms::press_button', array("Save and return to course"));
    }

    /**
     * Redirets to a page
     *
     * @Given /^I am on "(?P<page_name_string>(?:[^"]|\\")*)"$/
     * @param unknown $pagename
     */
    public function i_am_on($pagename) {
        switch ($pagename) {
            case "certificate verification page":
                $this->getSession()->visit($this->locate_path('mod/simplecertificate/verify.php'));
            break;

            case "homepage":
                $this->getSession()->visit($this->locate_path('/'));
            break;
            default:
          throw new Exception("Page not found: $pagename");
            break;
        }
    }

    private function get_issue_certificate_by_simplecertificate_name($certificatename, $user) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

        if ($certificateinstanceid = $DB->get_record("simplecertificate", array('name' => $certificatename), 'id')) {
            $certificateinstanceid = $certificateinstanceid->id;
        } else {
            throw new Exception("Can't find certificate with name: $certificatename");
        }

        $cm = get_coursemodule_from_instance('simplecertificate', $certificateinstanceid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $simplecertificate = new simplecertificate($context, null, null);

        $issuecert = $simplecertificate->get_issue($user);
        if (empty($issuecert->has_change)) {
            $simplecertificate->get_issue_file($issuecert);
        }
        return $issuecert;

    }
    /**
     *
     * @Given /^I issue a "(?P<certificate_name_string>(?:[^"]|\\")*)" to "(?P<user_name_string>(?:[^"]|\\")*)"$/
     *
     * @param unknown $certificatename
     * @param unknown $username
     */
    public function issue_certificate_to($certificatename, $username) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/simplecertificate/locallib.php');

        if (!$user = $DB->get_record("user", array('username' => $username))) {
            throw new Exception("Can't find User with username: $username");
        }

        if (!$issuecert = $this->get_issue_certificate_by_simplecertificate_name($certificatename, $user)) {
            throw new Exception("Can't find certificate with name: $certificatename");
        }

    }


    /**
     *
     * @Given /^I set "(?P<user_name_string>(?:[^"]|\\")*)" certificate "(?P<certificate_name_string>(?:[^"]|\\")*)" code$/
     * @param unknown $certificatename
     * @param unknown $username
     */
    public function set_certificate_code($username, $certificatename) {
        global $DB;

        if (!$user = $DB->get_record("user", array('username' => $username))) {
            throw new Exception("Can't find User with username: $username");
        }

        if (!$issuecert = $this->get_issue_certificate_by_simplecertificate_name($certificatename, $user)) {
            throw new Exception("Can't find certificate with name: $certificatename");
        }
        $code = $issuecert->code;
        $this->execute('behat_forms::i_set_the_field_to', array('code', $this->escape($code)));

    }


    /**
     *
     * @Given /^I should see the "(?P<user_name_string>(?:[^"]|\\")*)" certificate "(?P<certificate_name_string>(?:[^"]|\\")*)" code$/
     * @param unknown $certificatename
     * @param unknown $username
     */
    public function verify_certificate_code($username, $certificatename) {
        global $DB;

        if (!$user = $DB->get_record("user", array('username' => $username))) {
            throw new Exception("Can't find User with username: $username");
        }

        if (!$issuecert = $this->get_issue_certificate_by_simplecertificate_name($certificatename, $user)) {
            throw new Exception("Can't find certificate with name: $certificatename");
        }
        $code = $issuecert->code;
        $this->execute('behat_general::assert_page_contains_text', $code);

    }

    /**
     * @Given I check :name on list
     */
    public function i_check_on_list($name) {
        $xpath = "//tr[contains(normalize-space(.), '$name')]//input[@type='checkbox']";
        $node = $this->find('xpath', $xpath);
        $this->ensure_node_is_visible($node);
        $node->click();

    }

    /**
     * @Then :certname certificate status should be :status
     */
    public function certificate_status_should_be($certname, $status) {
        throw new PendingException();
    }

}
