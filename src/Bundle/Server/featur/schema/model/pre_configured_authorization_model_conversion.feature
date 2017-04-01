Feature: Models Conversion
  Model can be converted into Json Object.
  From a Json Object, I can get an Model.

  Scenario: Pre-Configured Authorization Object can be converted and recovered
    Given I have a Pre-Configured Authorization Object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorization"
