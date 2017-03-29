Feature: Event Objects Conversion
  Event objects can be converted into Json Object.
  From a Json Object, I can get an Event Object.

  Scenario: Pre-Configured Authorization Created Event can be converted and recovered
    Given I have a valid Pre-Configured Authorization Created Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization\PreConfiguredAuthorizationCreatedEvent"

  Scenario: Pre-Configured Authorization Revoked Event can be converted and recovered
    Given I have a valid Pre-Configured Authorization Revoked Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization\PreConfiguredAuthorizationRevokedEvent"
