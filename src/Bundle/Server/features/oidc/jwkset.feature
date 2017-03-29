Feature: Public keys are available through an endpoint

  Scenario: The public keys are available through an endpoint
    When A client sends a request to get the keys used by this authorization server
    Then the response code is 200
    And the content type of the response is "application/jwk-set+json; charset=UTF-8"
