Feature: A client requests an authorization code with the Code Response Type

  Scenario: A client sends an authorization requests with the Authorization Code Response Type and a Code Challenge, but the method is not supported.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code Response Type and a Code Challenge, but the method is not supported
    When the Resource Owner accepts the authorization request
    Then the client should be redirected
    And the redirection ends with "#_=_"
    And the redirect query should contain parameter "error" with value "invalid_request"
    And the redirect query should contain parameter "error_description" with value "The challenge method 'foo' is not supported."
    And the redirection Uri query should contain a parameter "state" with value "123456789"

  Scenario: A client sends an authorization requests with the Authorization Code Response Type and the Resource Owner accepts it.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code Response Type
    When the Resource Owner accepts the authorization request
    Then the client should be redirected
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "code"
    And the redirection Uri query should contain a parameter "state" with value "123456789"
    And an authorization code creation event is thrown

  Scenario: A client sends an authorization requests with the Authorization Code Response Type and code verifier and the Resource Owner accepts it.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code Response Type and a code verifier
    When the Resource Owner accepts the authorization request
    Then the client should be redirected
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "code"
    And the redirection Uri query should contain a parameter "state" with value "123456789"
    And an authorization code creation event is thrown

  Scenario: A client sends an authorization requests with the Authorization Code Response Type and the Resource Owner rejects it.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code Response Type
    When the Resource Owner rejects the authorization request
    Then the client should be redirected
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection ends with "#_=_"
    And the redirect query should contain parameter "error" with value "access_denied"
    And the redirect query should contain parameter "error_description" with value "The resource owner denied access to your client."
    And the redirection Uri query should contain a parameter "state" with value "123456789"
    And no authorization code creation event is thrown

  Scenario: A client receives an authorization code and use it to get an access token.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code Response Type, a code verifier and the scope "openid"
    When the Resource Owner accepts the authorization request
    And the client exchanges the authorization for an access token
    Then the response code is 200
    And the response contains an access token
    And an access token creation event is thrown
    And a refresh token creation event is thrown
