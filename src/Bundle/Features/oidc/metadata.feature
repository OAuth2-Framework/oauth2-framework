Feature: A client want to know the authorization server configuration

  Scenario: A client sends a request to the metadata endpoint.
    Given A client sends a request to the metadata endpoint
    Then the response code is 200
    And the content type of the response is "application/json; charset=UTF-8"
