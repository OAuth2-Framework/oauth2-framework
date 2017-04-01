Feature: An Isuer Discovery Endpoint is available

  Scenario: A client send an Issuer Discovery request without "rel" parameter
    When a client send an Issuer Discovery request without rel parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'rel' is mandatory."

  Scenario: A client send an Issuer Discovery request with an invalid "rel" parameter
    When a client send an Issuer Discovery request with an invalid rel parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Unsupported 'rel' parameter value."

  Scenario: A client send an Issuer Discovery request without "resource" parameter
    When a client send an Issuer Discovery request without resource parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'resource' is mandatory."

  Scenario: A client send an Issuer Discovery request with an invalid resource parameter based on an XRI
    When a client send an Issuer Discovery request with an invalid resource parameter based on an XRI
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Unsupported Extensible Resource Identifier (XRI) resource value."

  Scenario: A client send an Issuer Discovery request with an invalid resource parameter based on an email
    When a client send an Issuer Discovery request with an invalid resource parameter based on an email
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Unsupported domain."

  Scenario: A client send an Issuer Discovery request with an invalid resource parameter based on an Url
    When a client send an Issuer Discovery request with an invalid resource parameter based on an Url
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "Unsupported domain."

  Scenario: A client send an Issuer Discovery request with a valid resource parameter based on an email
    When a client send an Issuer Discovery request with a valid resource parameter based on an email
    Then the response code is 200
    And the content type of the response is "application/jrd+json; charset=UTF-8"
    And the response contains
    """
    {"subject":"acct:john@my-service.com:9000","links":[{"rel":"http:\/\/openid.net\/specs\/connect\/1.0\/issuer","href":"https:\/\/server.example.com"}]}
    """

  Scenario: A client send an Issuer Discovery request with a valid resource parameter based on an Url
    When a client send an Issuer Discovery request with a valid resource parameter based on an Url
    Then the response code is 200
    And the content type of the response is "application/jrd+json; charset=UTF-8"
    And the response contains
    """
    {"subject":"https:\/\/my-service.com:9000\/+john","links":[{"rel":"http:\/\/openid.net\/specs\/connect\/1.0\/issuer","href":"https:\/\/server.example.com"}]}
    """
