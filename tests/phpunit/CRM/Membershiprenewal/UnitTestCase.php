<?php
/**
 *  @file 
 *  File for the UnitTestCase class
 *
 */

/**
 *  Include class definitions
 */
require_once 'api/api.php';
require_once 'api/v3/utils.php';
define('API_LATEST_VERSION', 3);


/**
 *  Base class for BTO unit tests
 *
 *  Common functions for unit tests
 * @package CiviCRM
 */
class CRM_Membershiprenewal_UnitTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Api version - easier to override than just a define
   */
  protected $_apiversion = API_LATEST_VERSION;

  /**
   * wrap api functions.
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param mixed $checkAgainst
   *   Optional value to check result against, implemented for getvalue,.
   *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
   *   for getsingle the array is compared against an array passed in - the id is not compared (for
   *   better or worse )
   *
   * @return array|int
   */
  public function callAPISuccess($entity, $action, $params, $checkAgainst = NULL) {
    $params = array_merge(array(
        'version' => $this->_apiversion,
        'debug' => 1,
      ),
      $params
    );
    switch (strtolower($action)) {
      case 'getvalue':
        return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);

      case 'getsingle':
        return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);

      case 'getcount':
        return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
    }
    $result = $this->civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result, "Failure in api call for $entity $action");
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   *
   * @param string $entity
   * @param array $params
   * @param string $type
   *   Per http://php.net/manual/en/function.gettype.php possible types.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @return array|int
   */
  public function callAPISuccessGetValue($entity, $params, $type = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getvalue', $params);
    if ($type) {
      if ($type == 'integer') {
        // api seems to return integers as strings
        $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
      }
      else {
        $this->assertType($type, $result, "returned result should have been of type $type but was ");
      }
    }
    return $result;
  }

  /**
   * This function exists to wrap api getsingle function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param array $params
   * @param array $checkAgainst
   *   Array to compare result against.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @throws Exception
   * @return array|int
   */
  public function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getsingle', $params);
    if (!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getsingle result' . print_r($result, TRUE));
    }
    if ($checkAgainst) {
      // @todo - have gone with the fn that unsets id? should we check id?
      $this->checkArrayEquals($result, $checkAgainst);
    }
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   * @param string $entity
   * @param array $params
   * @param null $count
   * @throws Exception
   * @return array|int
   */
  public function callAPISuccessGetCount($entity, $params, $count = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = $this->civicrm_api($entity, 'getcount', $params);
    if (!is_int($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getcount result : ' . print_r($result, TRUE) . " type :" . gettype($result));
    }
    if (is_int($count)) {
      $this->assertEquals($count, $result, "incorrect count returned from $entity getcount");
    }
    return $result;
  }

  /**
   * A stub for the API interface. This can be overriden by subclasses to change how the API is called.
   *
   * @param $entity
   * @param $action
   * @param array $params
   * @return array|int
   */
  public function civicrm_api($entity, $action, $params) {
    return civicrm_api($entity, $action, $params);
  }

  /**
   * Check that api returned 'is_error' => 0.
   *
   * @param array $apiResult
   *   Api result.
   * @param string $prefix
   *   Extra test to add to message.
   */
  public function assertAPISuccess($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $errorMessage = empty($apiResult['error_message']) ? '' : " " . $apiResult['error_message'];

    if (!empty($apiResult['debug_information'])) {
      $errorMessage .= "\n " . print_r($apiResult['debug_information'], TRUE);
    }
    if (!empty($apiResult['trace'])) {
      $errorMessage .= "\n" . print_r($apiResult['trace'], TRUE);
    }
    $this->assertEquals(0, $apiResult['is_error'], $prefix . $errorMessage);
  }

  /**
   * Assert that a SQL query returns a given value.
   *
   * The first argument is an expected value. The remaining arguments are passed
   * to CRM_Core_DAO::singleValueQuery
   *
   * Example: $this->assertSql(2, 'select count(*) from foo where foo.bar like "%1"',
   * array(1 => array("Whiz", "String")));
   * @param $expected
   * @param $query
   * @param array $params
   * @param string $message
   */
  public function assertDBQuery($expected, $query, $params = array(), $message = '') {
    if ($message) {
      $message .= ': ';
    }
    $actual = CRM_Core_DAO::singleValueQuery($query, $params);
    $this->assertEquals($expected, $actual,
      sprintf('%sexpected=[%s] actual=[%s] query=[%s]',
        $message, $expected, $actual, CRM_Core_DAO::composeQuery($query, $params, FALSE)
      )
    );
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if a record is found.                                                                                                                         
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   *
   * @return null|string
   * @throws PHPUnit_Framework_AssertionFailedError                                                                                                                                                                 
   */
  public function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {                                                                                                                 
    if (empty($searchValue)) {
      $this->fail("empty value passed to assertDBNotNull");                                                                                                                                                         
    }
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);                                                                                                               
    $this->assertNotNull($value, $message);                                                                                                                                                                         

    return $value;
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.                                                                                                                
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   */
  public function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) { 
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);                                                                                                               
    $this->assertNull($value, $message);                                                                                                                                                                            
  }

  /**
   * Private helper function for calling civicrm_contact_add.
   *
   * @param array $params
   *   For civicrm_contact_add api function call.
   *
   * @throws Exception
   *
   * @return int
   *   id of Household created
   */
  private function _contactCreate($params) {
    $result = $this->callAPISuccess('contact', 'create', $params);
    if (!empty($result['is_error']) || empty($result['id'])) {
      throw new Exception('Could not create test contact, with message: ' . CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . CRM_Utils_Array::value('trace', $result));
    }
    return $result['id'];
  }
}
