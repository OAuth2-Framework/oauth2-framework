Feature: A client sends a request using Hybrid Flows as per the OpenID Connect specification

  Scenario: A client sends a authorization requests with the Authorization Code, the Id Token and the Token Response Types and the Resource Owner accepts it.
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization requests with the Authorization Code, the Id Token and the Token Response Types
    When the Resource Owner accepts the authorization request
    Then the client should be redirected
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri fragment should contain a parameter "code"
    And the redirection Uri fragment should contain a parameter "id_token"
    And the redirection Uri fragment should contain a parameter "access_token"
    And the redirection Uri fragment should contain a parameter "token_type" with value "Bearer"
    And the redirection Uri fragment should contain a parameter "expires_in"
    And the redirection Uri fragment should contain a parameter "session_state"
    And the redirection Uri fragment should contain a parameter "state" with value "123456789"
    And an authorization code creation event is thrown
