Feature: The client can use the ID Token

  Scenario: A client sends a valid authorization request with a valid "id_token_hint" parameter
    When A client sends a valid authorization request with a valid id_token_hint parameter
    Then the response code is 200
    And I should be on the consent screen

  Scenario: A client sends a valid authorization request with a valid "id_token_hint" parameter but the current user does not correspond
    Given The user "john.2" is logged in and fully authenticated
    When A client sends a valid authorization request with a valid id_token_hint parameter but the current user does not correspond
    Then the response code is 302
    And I should be on the login screen

  Scenario: A client sends a valid authorization request with an invalid "id_token_hint" parameter
    When A client sends a valid authorization request with an invalid id_token_hint parameter
    Then the client should be redirected
    And the redirect query should contain parameter "error" with value "invalid_request"
    And the redirect query should contain parameter "error_description" with value "The parameter 'id_token_hint' does not contain a valid ID Token."
    And the redirection Uri query should contain a parameter "state" with value "123456789"

  Scenario: A client sends a valid authorization request with a valid "id_token_hint" parameter but signed with an unsupported algorithm
    When A client sends a valid authorization request with a valid id_token_hint parameter but signed with an unsupported algorithm
    Then the client should be redirected
    And the redirect query should contain parameter "error" with value "invalid_request"
    And the redirect query should contain parameter "error_description" with value "The parameter 'id_token_hint' does not contain a valid ID Token."
    And the redirection Uri query should contain a parameter "state" with value "123456789"
