Feature: A client requests an access token using the Authorization Code Grant Type

  Scenario: A client sends a token request using a valid authorization code, but the code parameter is missing
    Given A client sends a Authorization Code Grant Type request but the code parameter is missing
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'code' is missing."
    And no access token creation event is thrown

  Scenario: A client sends a token request using a valid authorization code, but the redirect parameter is missing
    Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter is missing
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'redirect_uri' is missing."
    And no access token creation event is thrown

  Scenario: A client sends a token request using a valid authorization code, but the redirect parameter is invalid
    Given A client sends a Authorization Code Grant Type request but the redirection Uri parameter mismatch
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'redirect_uri' is invalid."
    And no access token creation event is thrown

  Scenario: A client has a valid authorization code and use it to get an access token
    Given A client sends a valid Authorization Code Grant Type request
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown
    And an authorization code used event is thrown

  Scenario: A client has a valid authorization code, with reduced scope
    Given A client sends a valid Authorization Code Grant Type request with reduced scope
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown
    And an authorization code used event is thrown

  Scenario: A client has a valid authorization code, but requested scope are not authorized
    Given A client sends a Authorization Code Grant Type request but a scope is not allowed
    Then the response contains an error with code 400
    And the error is "invalid_scope"
    And the error description is "An unsupported scope was requested. Available scopes are openid, email, phone, address."
    And no access token creation event is thrown

  Scenario: A client has a valid authorization code, but associated client_id is not valid
    Given A client sends a Authorization Code Grant Type request but a authorization code is for another client
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "Code does not exist or is invalid for the client."
    And no access token creation event is thrown

  Scenario: A client has an expired authorization code
    Given A client sends a Authorization Code Grant Type request but the authorization code expired
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "The authorization code has expired."
    And no access token creation event is thrown

  Scenario: A client has a revoked authorization code
    Given A client sends a Authorization Code Grant Type request but the authorization code is revoked
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "The parameter 'code' is invalid."
    And no access token creation event is thrown

  Scenario: A client has a used authorization code
    Given A client sends a Authorization Code Grant Type request but the authorization code is used
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "The parameter 'code' is invalid."
    And no access token creation event is thrown

  Scenario: A client has an authorization code issued using PKCE, but that parameter "code_verifier" is missing
    Given A client sends a Authorization Code Grant Type request but the authorization code requires a code_verifier parameter
    Then the response contains an error with code 400
    And the error is "invalid_request"
    And the error description is "The parameter 'code_verifier' is missing."
    And no access token creation event is thrown

  Scenario: A client has an authorization code issued using PKCE, but that parameter "code_verifier" is invalid
    Given A client sends a Authorization Code Grant Type request but the code_verifier parameter of the authorization code is invalid
    Then the response contains an error with code 400
    And the error is "invalid_grant"
    And the error description is "The parameter 'code_verifier' is invalid."
    And no access token creation event is thrown

  Scenario: A client has an authorization code with a code_verifier parameter (plain)
    Given A client sends a valid Authorization Code Grant Type request with code verifier that uses plain method
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown
    And an authorization code used event is thrown

  Scenario: A client has an authorization code with a code_verifier parameter (S256)
    Given A client sends a valid Authorization Code Grant Type request with code verifier that uses S256 method
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown
    And an authorization code used event is thrown
