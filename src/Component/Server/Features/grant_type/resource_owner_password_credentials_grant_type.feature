Feature: A client requests an access token using the Resource Owner Password Credentials Grant Type

  Scenario: A client sends a ROPC Grant Type request but the username parameter is missing
    Given A client sends a Resource Owner Password Credentials Grant Type request without username parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'username' is missing."
    And no access token creation event is thrown

  Scenario: A client sends a ROPC Grant Type request but the password parameter is missing
    Given A client sends a Resource Owner Password Credentials Grant Type request without password parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'password' is missing."
    And no access token creation event is thrown

  Scenario: A client sends a ROPC Grant Type request but the user credentials are invalid
    Given A client sends a Resource Owner Password Credentials Grant Type request with invalid user credentials
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "Invalid username and password combination."
    And no access token creation event is thrown

  Scenario: A client sends a valid ROPC Grant Type request
    Given A client sends a valid Resource Owner Password Credentials Grant Type request
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown

  Scenario: A client sends a valid ROPC Grant Type request but this grant type is not allowed to the client
    Given A client sends a valid Resource Owner Password Credentials Grant Type request but the grant type is not allowed
    Then the response contains an error with code 400
    And the error is "unauthorized_client"
    And the error description is "The grant type 'password' is unauthorized for this client."
    And no access token creation event is thrown
