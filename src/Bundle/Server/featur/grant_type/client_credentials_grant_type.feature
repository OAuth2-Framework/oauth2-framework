Feature: A client requests an access token using the Client Credentials Grant Type

  Scenario: A client sends a Client Credentials Grant Type request but client is not authenticated
    Given An unauthenticated client sends a Client Credentials Grant Type request
    Then the response contains an error with code 401
    And the error is "invalid_client"
    And the error description is "Client authentication failed."
    And no access token creation event is thrown

  Scenario: A client sends a Client Credentials Grant Type request but it is a public client
    Given An public client sends a Client Credentials Grant Type request
    Then the response contains an error with code 400
    And the error is "invalid_client"
    And the error description is "The client is not a confidential client."
    And no access token creation event is thrown

  Scenario: A client sends a Client Credentials Grant Type request but credentials expired
    Given A client sends a Client Credentials Grant Type request but credentials expired
    Then the response contains an error with code 401
    And the error is "invalid_client"
    And the error description is "Client authentication failed."
    And no access token creation event is thrown

  Scenario: A client sends a valid Client Credentials Grant Type request
    Given A client sends a valid Client Credentials Grant Type request
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown

  Scenario: A client sends a valid Client Credentials Grant Type request
    Given A client authenticated with a JWT assertion sends a valid Client Credentials Grant Type request
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown

  Scenario: A client sends a valid Client Credentials Grant Type request but this grant type is not allowed to the client
    Given A client sends a valid Client Credentials Grant Type request but the grant type is not allowed
    Then the response contains an error with code 400
    And the error is "unauthorized_client"
    And the error description is "The grant type 'client_credentials' is unauthorized for this client."
    And no access token creation event is thrown

  Scenario: A deleted client sends a Client Credentials Grant Type request
    Given A deleted client sends a Client Credentials Grant Type request
    Then the response contains an error with code 401
    And the error is "invalid_client"
    And the error description is "Client authentication failed."
    And no access token creation event is thrown
