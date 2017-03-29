Feature: A protected resource needs information about a token
  In order get information about a token
  A protected resource should send a request to the introspection endpoint
  and, if authorized, retrieves information

  Scenario: An unauthenticated protected resource tries to get information about a token
    Given An unauthenticated protected resource tries to get information about a token
    Then the response contains an error with code 401
    And the error is "invalid_resource_server"
    And the error description is "Resource Server authentication failed."

  Scenario: A protected resource sends an invalid introspection request
    Given A protected resource sends an invalid introspection request
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'token' is missing."

  Scenario: A protected resource tries to get information of a token that owns another protected resource
    Given A protected resource tries to get information of a token that owns another protected resource
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'token' is invalid."

  Scenario: A protected resource tries to get information of a token
    Given A protected resource tries to get information of a token
    Then the response code is 200
    And the response contains something like '/{"active":true,"client_id":"client1","resource_owner":"john.1","expires_in":\d+,"token_type":"Bearer"}/'

  Scenario: A protected resource tries to get information of a revoked token
    Given A protected resource tries to get information of a revoked token
    Then the response code is 200
    And the response contains
    """
    {"active":false}
    """
