Feature: locales support
  In order to use a hypermedia API
  As a client software developer
  I need to be able to select the locale I prefer to be used

  @createSchema
  Scenario: Get resource without header nor attribute
    Given there is a DummyWithEnabledLocales
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_with_enabled_locales/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "it,cz,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get resource with header but no attribute
    When I add "Accept" header equal to "application/ld+json"
    And I add "Accept-Language" header equal to "fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,cz;q=0.4"
    And I send a "GET" request to "/dummy_with_enabled_locales/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "cz,it,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get resource with header and locale attribute
    When I add "Accept" header equal to "application/ld+json"
    And I add "Accept-Language" header equal to "fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,ro;q=0.4"
    And I send a "GET" request to "mi/dummy_with_enabled_locales_in_request_attribute/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "mi,ro,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get resource without header nor attribute fallback
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_with_enabled_locales_fallback/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "ro,mi,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get resource for a disabled locale should fallback on enabled ones.
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/de/dummy_with_enabled_locales_in_request_attribute/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "ro,mi,en"
    And the header "Vary" should contain "Accept-Language"

#    same checks for subresources

  Scenario: Get subresource without header
    Given there is a DummyWithEnabledLocales related to a subresource
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_with_enabled_locales/2/sub_resource_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "be,ru,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get subresource with header
    When I add "Accept" header equal to "application/ld+json"
    And I add "Accept-Language" header equal to "fr-FR,fr;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,ru;q=0.4"
    And I send a "GET" request to "/dummy_with_enabled_locales/2/sub_resource_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "ru,be,en"
    And the header "Vary" should contain "Accept-Language"

  Scenario: Get subResource for a disabled locale should fallback on enabled ones.
    And I add "Accept-Language" header equal to "fr-FR,fr;q=0.9,es;q=0.5,de;q=0.4"
    And I send a "GET" request to "/dummy_with_enabled_locales/2/sub_resource_dummy"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "be,ru,en"
    And the header "Vary" should contain "Accept-Language"

# fallback on global config

  Scenario: Get resource without enabled locales on the resouroce
    Given there is a Dummy Object mapped by UUID
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/uuid_identifier_dummies/41B29566-144B-11E6-A148-3E1D05DEFE78"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Language" should be equal to "de,lu,en"
    And the header "Vary" should contain "Accept-Language"
