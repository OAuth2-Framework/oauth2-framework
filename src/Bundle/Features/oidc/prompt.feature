Feature: A client can set a "prompt" parameter for each authorization request

  Scenario: A client tries to send an authorization request with prompt none but the resource owner is not logged in
    Given A client sends an authorization request with "prompt=none" parameter but the user has to authenticate again
    Then the response code is 302
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "error" with value "login_required"
    And the redirection Uri query should contain a parameter "error_description" with value "The resource owner is not logged in."

  Scenario: A client tries to send an authorization request with prompt none but the resource owner is not logged in
    Given The user "john.1" is logged in and but not fully authenticated
    And A client sends an authorization request with "prompt=none" parameter but the user has to authenticate again
    Then the response code is 302
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "error" with value "interaction_required"
    And the redirection Uri query should contain a parameter "error_description" with value "The resource owner interaction is required."

  Scenario: A client tries to send an authorization request with prompt none associated with another prompt value
    Given The user "john.1" is logged in and but not fully authenticated
    And A client sends an authorization request with "prompt=none consent" parameter
    Then the response code is 302
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "error" with value "invalid_request"
    And the redirection Uri query should contain a parameter "error_description" with value "Invalid parameter 'prompt'. Prompt value 'none' must be used alone."

  Scenario: A client tries to send an authorization request but it is not fully authenticated and the "prompt=login" parameter is set
    Given The user "john.1" is logged in and but not fully authenticated
    And A client sends an authorization request with "prompt=login" parameter
    Then the response code is 302
    And I should be on the login screen

  Scenario: A client tries to send an authorization request with "prompt=login" parameter and the resource owner is fully authenticated
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization request with "prompt=login" parameter
    Then the response code is 200
    And I should be on the consent screen

  Scenario: A client tries to send an authorization request that the user already accepted
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization request that was already accepted and saved by the resource owner
    Then the client should be redirected
    And the redirection Uri starts with "https://example.com/redirection/callback"
    And the redirection Uri query should contain a parameter "code"
    And the redirection Uri query should contain a parameter "state" with value "123456789"

  Scenario: A client tries to send an authorization request that the user already accepted with "prompt=consent"
    Given The user "john.1" is logged in and fully authenticated
    And A client sends an authorization request that was already accepted and saved by the resource owner with "prompt=consent"
    Then the response code is 200
    And I should be on the consent screen
