Feature: Models Conversion
  Model can be converted into Json Object.
  From a Json Object, I can get an Model.

  Scenario: Authorization Code Object can be converted and recovered
    Given I have an Authorization Code Object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Model\AuthCode\AuthCode"
