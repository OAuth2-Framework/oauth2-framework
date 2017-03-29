Feature: A client requests an access token using the Refresh Token Grant Type

  Scenario: A client sends a Refresh Token Grant Type request but the refresh_token parameter is missing
    Given A client sends a Refresh Token Grant Type request without refresh_token parameter
    Then the response contains an error with code 400
    And the error description is "The parameter 'refresh_token' is missing."
    And no access token creation event is thrown

  Scenario: A client sends a Refresh Token Grant Type request but the refresh token expired
    Given a client sends a Refresh Token Grant Type request with an expired refresh token
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "Refresh token has expired."
    And no access token creation event is thrown

  Scenario: A client sends a Refresh Token Grant Type request but the refresh token is revoked
    Given a client sends a Refresh Token Grant Type request with a revoked refresh token
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "The parameter 'refresh_token' is invalid."
    And no access token creation event is thrown

  Scenario: A client sends a valid Refresh Token Grant Type request
    Given A client sends a valid Refresh Token Grant Type request
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown

  Scenario: A client sends a valid Refresh Token Grant Type request but this grant type is not allowed to the client
    Given A client sends a valid Refresh Token Grant Type request but the grant type is not allowed
    Then the response contains an error with code 400
    And the error is "unauthorized_client"
    And the error description is "The grant type 'refresh_token' is unauthorized for this client."
    And no access token creation event is thrown
