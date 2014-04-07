<?php

/**
 * A custom contact search
 */
class CRM_Anyteloremail_Form_Search_anyTelOrEmail extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Email') => 'email',
      ts('Phone Number') => 'number',
      ts('Phone Extension') => 'extension',
      ts('City') => 'city',
      ts('State') => 'state',
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
    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Partial Phone Number and Email Search');

    $form->addElement('header', "howdy", 'QuickForm tutorial example');
    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('number', 'email', 'howdy'));
  }

  function summary() {
    $summary = array();
    return $summary;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $selectClause = "contact_a.id AS contact_id, contact_a.sort_name as sort_name, ".
     "(SELECT GROUP_CONCAT(email SEPARATOR ', ') FROM civicrm_email WHERE contact_id = contact_a.id) AS email, ".
     "(SELECT GROUP_CONCAT(phone SEPARATOR ', ') FROM civicrm_phone WHERE contact_id = contact_a.id) AS number, ".
     "phone_ext AS extension, city AS city, sp.name AS state ";
    $all = $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
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
      "(contact_a.id = email.contact_id)";
  }

  function where($includeContactIDs = FALSE) {
    $params = array();

    $phone = CRM_Utils_Array::value('number',
      $this->_formValues
    );
    $email = CRM_Utils_Array::value('email',
      $this->_formValues
    );

    $where = array();
    if(!empty($phone)) {
      $phone = $this->convertNumberToRegex($phone);
      $where[] = "(contact_a.is_deleted = 0 AND phone.phone REGEXP %1)";
      $params[1] = array($phone, 'String');
    }
    if(!empty($email)) {
      $where[] = "(contact_a.is_deleted = 0 AND email.email REGEXP %2)";
      $params[2] = array('.*' . $email . '.*', 'String');
    }
    if (!empty($phone) && !empty($email)) {
      $where = implode(' AND ', $where);
    }
    else {
      $where = implode(' OR ', $where);
    }
    return $this->whereClause($where, $params);
  }

  function convertNumberToRegex($number) {
    // We assume US style number with 10 digits

    // First remove everything that is not a number
    $number = preg_replace('/[^0-9]/', '', $number);

    // now break it down
    $area_code = substr($number,0,3);
    $prefix = substr($number,3,3);
    $last = substr($number,6,4);

    return ".*$area_code.*$prefix.*$last.*";

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
