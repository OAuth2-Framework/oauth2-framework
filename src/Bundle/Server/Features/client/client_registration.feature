Feature: A client can be created

  Scenario: A valid request is received
    Given a valid client registration request is received
    And a client created event should be recorded
    And the response contains a client

  Scenario: A request with an expired initial access token
    Given a client registration request is received with an expired initial access token
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Initial Access Token expired."

  Scenario: A request with a revoked initial access token
    Given a client registration request is received with a revoked initial access token
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Initial Access Token is missing or invalid."

  Scenario: A valid request is received and a software statement is set in the parameters
    Given a valid client registration request with software statement is received
    And a client created event should be recorded
    And the response contains a client
    And the software statement parameters are in the client parameters

  Scenario: A request is received, but the Initial Access Token is missing
    Given a client registration request is received but not initial access token is set
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Initial Access Token is missing or invalid."

  Scenario: A request is received, but the Initial Access Token is invalid
    Given a client registration request is received but an invalid initial access token is set
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Initial Access Token is missing or invalid."
