<?php

/**
 * A custom contact search that returns one row for each contact with a partial or full match on any of its emails or phone numbers
 */
class CRM_Anyteloremail_Form_Search_anyTelOrEmail extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Email(s)') => 'email',
      ts('Phone Number(s)') => 'number',
      ts('City (Cities)') => 'city',
      ts('State(s)') => 'state',
    );
  }

  function buildForm(&$form) {
    $form->add(
      'text',
      'number',
      ts('Phone Number')
    );
    $form->add(
      'text',
      'email',
      ts('Email address')
    );
    $form->add(
      'checkbox',
      'regexp',
      ts('Search email address via regular expression (slower)')
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Any Telephone or Email Search');

    $form->addElement('header', "howdy", 'QuickForm tutorial example');
    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('number', 'email', 'howdy', 'regexp'));
  }

  function summary() {
    $summary = array();
    return $summary;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $partialSelect = "contact_a.id AS contact_id, contact_a.sort_name as sort_name, ".
     "(SELECT GROUP_CONCAT(DISTINCT email SEPARATOR ', ') FROM civicrm_email WHERE contact_id = contact_a.id) AS  email, ".
     "(SELECT GROUP_CONCAT(DISTINCT CONCAT(phone,IF(phone_ext IS NULL,'',CONCAT(' ',phone_ext))) SEPARATOR ', ') FROM civicrm_phone WHERE contact_id = contact_a.id) AS number, ".
     "GROUP_CONCAT(DISTINCT city SEPARATOR ', ') AS city, ".
     "GROUP_CONCAT(DISTINCT sp.name SEPARATOR ', ') AS state ";
    $groupBy = $this->groupBy();

    $all = $this->sql($partialSelect,
      $offset, $rowcount, $sort,
      $includeContactIDs, $groupBy
    );

    return $all;
  }

  function from() {
    return "FROM civicrm_contact contact_a LEFT JOIN ".
      "civicrm_phone phone ON ".
      "(phone.contact_id = contact_a.id) ".
      "LEFT JOIN civicrm_address address ON ".
      "(address.contact_id = contact_a.id) ".
      "LEFT JOIN civicrm_state_province sp ON ".
      "(address.state_province_id = sp.id) ".
      "LEFT JOIN civicrm_email email ON ".
      "(contact_a.id = email.contact_id) ";
  }

  function where($includeContactIDs = FALSE) {

    $params = array();
    $where = array();

    $phone = CRM_Utils_Array::value('number', $this->_formValues);
    $phoneregex = $this->convertNumberToRegex($phone);
    $email = CRM_Utils_Array::value('email', $this->_formValues);
    $email .= CRM_Utils_Array::value('regexp', $this->_formValues) ? '' : '%';
    $operator = CRM_Utils_Array::value('regexp', $this->_formValues) ? 'REGEXP' : 'LIKE';

    if(!empty($phone) && !empty($email)) {
      $where = "(contact_a.is_deleted = 0 AND phone.phone REGEXP %1) AND (contact_a.is_deleted = 0 AND email.email $operator %2) ";
      $params[1] = array($phoneregex, 'String');
      $params[2] = array($email, 'String');
    } elseif(!empty($email)) { // $phone is empty
      $where = "(contact_a.is_deleted = 0 AND email.email $operator %1) ";
      $params[1] = array($email, 'String');
    } elseif (!empty($phone)) { // $email is empty
      $where = "(contact_a.is_deleted = 0 AND phone.phone REGEXP %1) ";
      $params[1] = array($phoneregex, 'String');
    } // else both are empty
    else {
      $where = "1 ";
    }

    $whereClause = $this->whereClause($where, $params);

    return $whereClause;
  }

  function convertNumberToRegex($number) {
    // search for North American phone numbers of form (416) 444-4444
    // and allow any form of extension to follow it
    // the following are all valid
    // 4444444444
    // 444 444-4444
    // (444)444-4444
    // (444) 444-4444
    // 444.444.4444
    // (444) 444-4444 x123
    // (444) 444-4444 ext. 123

    // First remove everything that is not a number
    $number = preg_replace('/[^0-9]/', '', $number);

    // now break it down
    $area_code = substr($number,0,3);
    $prefix = substr($number,3,3);
    $last = substr($number,6,4);

    // now build it up
    $nnstr = "[^0-9]*"; // non-numeric string of 0 to n characters
    // The ^ forces match to start at beginning of string; anything can follow the number eg extensions of various forms
    $str = '^' . $nnstr . $area_code . $nnstr . $prefix . $nnstr . $last;

    return $str;

  }

  function groupBy() {
    $groupBy = "GROUP BY contact_id ";
    return $groupBy;
  }

  function setDefaultValues() {
    return array();
  }

  function templateFile() {
    return 'CRM/Anyteloremail/Form/Search/anyTelOrEmail.tpl';
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }
}
