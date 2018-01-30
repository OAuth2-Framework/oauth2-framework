Feature: Event Objects Conversion
  Event objects can be converted into Json Object.
  From a Json Object, I can get an Event Object.

  Scenario: Authorization Code Created Event can be converted and recovered
    Given I have a valid Authorization Code Created Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Event\AuthCode\AuthCodeCreatedEvent"

  Scenario: Authorization Code Marked As Used Event can be converted and recovered
    Given I have a valid Authorization Code Marked As Used Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Event\AuthCode\AuthCodeMarkedAsUsedEvent"

  Scenario: Authorization Code Revoked Event can be converted and recovered
    Given I have a valid Authorization Code Revoked Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Event\AuthCode\AuthCodeRevokedEvent"
