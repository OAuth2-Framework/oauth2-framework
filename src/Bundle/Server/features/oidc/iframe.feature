Feature: The resource server provides an iframe for session management

  Scenario: The server has an OP iframe endpoint
    When A client sends a request to get the Session Management iFrame
    Then the response code is 200
    And the content type of the response is "text/html; charset=UTF-8"
