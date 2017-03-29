Feature: Models Conversion
  Model can be converted into Json Object.
  From a Json Object, I can get an Model.

  Scenario: Access Token Object can be converted and recovered
    Given I have an Access Token Object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Model\AccessToken\AccessToken"

  Scenario: Refresh Token Object can be converted and recovered
    Given I have an Refresh Token Object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken"
