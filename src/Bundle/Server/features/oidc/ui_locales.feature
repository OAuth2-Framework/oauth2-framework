Feature: A client requests an authorization
  In order to display an authorization page
  translated in the language selected by the user
  a parameter can be set in the query string

  Scenario: A client send an authorization request with a ui_locales parameter.
    Given The user "john.1" is logged in and but not fully authenticated
    And A client sends an authorization request with ui_locales parameter and at least one locale is supported
    Then the consent screen should be translated

  Scenario: A client send an authorization request with a ui_locales parameter but none of them is supported.
    Given The user "john.1" is logged in and but not fully authenticated
    And A client sends an authorization request with ui_locales parameter and none of them is supported
    Then the consent screen should not be translated
