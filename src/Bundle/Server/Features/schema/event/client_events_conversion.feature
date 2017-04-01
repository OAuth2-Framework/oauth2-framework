Feature: Event Objects Conversion
  Event objects can be converted into Json Object.
  From a Json Object, I can get an Event Object.

  Scenario: Client Created Event can be converted and recovered
    Given I have a valid Client Created Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\Client\ClientCreatedEvent"

  Scenario: Client Owner Changed Event can be converted and recovered
    Given I have a valid Client Owner Changed Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\Client\ClientOwnerChangedEvent"

  Scenario: Client Parameters Updated Event can be converted and recovered
    Given I have a valid Client Parameters Updated Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\Client\ClientParametersUpdatedEvent"

  Scenario: Client Deleted Event can be converted and recovered
    Given I have a valid Client Deleted Event object
    When I convert the Domain Object into a Json Object
    Then I can recover the event from the Json Object and its class is "OAuth2Framework\Component\Server\Event\Client\ClientDeletedEvent"
