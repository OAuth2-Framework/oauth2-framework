Feature: A client requests an authorization
  In order to be sure the user is not connected for a long time
  A client can set the "max_age" query parameter to force user to login again if necessary

  Scenario: A client send an authorization request with a max_age parameter. The user has to authenticate again
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization request with max_age parameter but the user has to authenticate again
    Then the response code is 302
    And I should be on the login screen

  Scenario: A client send an authorization request with a max_age parameter. The user does not need to authenticate again
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization request with max_age parameter
    Then the response code is 200
    And I should be on the consent screen
