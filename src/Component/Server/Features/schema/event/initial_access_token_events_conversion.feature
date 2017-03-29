Feature: Event Objects Conversion
  Event objects can be converted into Json Object.
  From a Json Object, I can get an Event Object.

  Scenario: Initial Access Token Created Event can be converted and recovered
    Given I have a valid Initial Access Token Created Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenCreatedEvent"

  Scenario: Initial Access Token Revoked Event can be converted and recovered
    Given I have a valid Initial Access Token Revoked Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\InitialAccessToken\InitialAccessTokenRevokedEvent"
