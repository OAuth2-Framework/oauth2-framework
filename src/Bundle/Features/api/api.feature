Feature: A client send API requests

  Scenario: A client sends a request without expired token
    When a client sends an API request without expired token
    Then the response code is 401
    And I print "www-authenticate" header

  Scenario: A client sends a request with an expired token
    When a client sends an API request using an expired token
    Then the response code is 401
    And I print "www-authenticate" header

  Scenario: A client sends a request with a revoked token
    When a client sends an API request using a revoked token
    Then the response code is 401
    And I print "www-authenticate" header

  Scenario: A client sends a request with an insufficient scope
    When a client sends an API request using an insufficient scope
    Then the response code is 403
    And the content type of the response is "application/json"
    And the response contains
    """
    {"error":"access_denied","error_description":"Insufficient scope. The scope rule is: profile openid"}
    """

  Scenario: A client sends a valid request to a resource that does not need any scope
    When a client sends a valid API request to a resource that does not need any scope
    Then the response code is 200
    And the content type of the response is "application/json"
    And the response contains
    """
    {"name":"john","message":"Hello john!"}
    """

  Scenario: A client sends a valid request using a token with sufficient scope
    When a client sends a valid API request using a token with sufficient scope
    Then the response code is 200
    And the content type of the response is "application/json"
    And the response contains
    """
    {"name":"I am protected by scope","message":"Hello!"}
    """
