Feature: A client can be created

  Scenario: A valid request is received
    Given a valid client registration request is received
    And a client created event should be recorded
    And the response contains a client

  Scenario: A request without redirect Uris is received
    Given a client registration request without redirect Uris is received
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'redirect_uris' is mandatory."

  Scenario: A request is received but the contact list is not an array
    Given a client registration request but the contact list is not an array
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'contacts' must be a list of e-mail addresses."

  Scenario: A request is received but the contact list contains invalid values
    Given a client registration request but the contact list contains invalid values
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'contacts' must be a list of e-mail addresses."

  Scenario: A request with redirect Uris that contain fragments is received
    Given a client registration request with redirect Uris that contain fragments is received
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'redirect_uris' must only contain URIs without fragment."

  Scenario: A request for a web application that uses the Implicit Grant Type is received with a redirect Uri that contain has localhost as host
    Given a web client registration request is received with a redirect Uri that contain has localhost as host but the client uses the Implicit Grant Type
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The host 'localhost' is not allowed for web applications that use the Implicit Grant Type."

  Scenario: A request for a web application that uses the Implicit Grant Type is received with an unsecured redirect Uri
    Given a web client registration request is received with an unsecured redirect Uri but the client uses the Implicit Grant Type
    Then no client should be created
    And the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'redirect_uris' must only contain URIs with the HTTPS scheme for web applications that use the Implicit Grant Type."

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
