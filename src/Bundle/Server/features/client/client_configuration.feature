Feature: A client can be retrieved, updated or deleted

  Scenario: A valid GET request is received
    Given a valid client configuration GET request is received
    And the response code is 200
    And the response contains a client

  Scenario: A GET request is received but no Registration Token is set
    Given a client configuration GET request is received but no Registration Token is set
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Invalid client or invalid registration access token."

  Scenario: A GET request is received but the Registration Token is invalid
    Given a client configuration GET request is received but the Registration Token is invalid
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Invalid client or invalid registration access token."

  Scenario: A valid DELETE request is received
    Given a valid client configuration DELETE request is received
    Then the response code is 204
    And a client deleted event should be recorded

  Scenario: A DELETE request is received but no Registration Token is set
    Given a client configuration DELETE request is received but no Registration Token is set
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Invalid client or invalid registration access token."
    And no client deleted event should be recorded

  Scenario: A PUT request is received but no Registration Token is set
    Given a client configuration PUT request is received but no Registration Token is set
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Invalid client or invalid registration access token."
    And no client updated event should be recorded

  Scenario: A valid GET request is received
    Given a valid client configuration PUT request is received
    And the response code is 200
    And a client updated event should be recorded
    And the response contains the updated client

  Scenario: A valid GET request is received and a software statement is set in the parameters
    Given a valid client configuration PUT request with software statement is received
    And the response code is 200
    And a client updated event should be recorded
    And the response contains the updated client
    And the software statement parameters are in the client parameters

  Scenario: A valid GET request is received and a software statement is set in the parameters but the algorithm is not supported
    Given a valid client configuration PUT request with software statement is received but the algorithm is not supported
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Invalid Software Statement."
