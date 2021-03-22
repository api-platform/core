Feature: XML Deserialization
  In order to use the API with XML
  As a client software developer
  I need to be able to deserialize XML data

  Background:
    Given I add "Accept" header equal to "application/xml"
    And I add "Content-Type" header equal to "application/xml"

  @createSchema
  Scenario: Posting an XML resource with a string value
    When I send a "POST" request to "/resource_with_strings" with body:
    """
    <?xml version="1.0" encoding="UTF-8"?>
    <ResourceWithString>
      <myStringField>string</myStringField>
    </ResourceWithString>
    """
    Then the response status code should be 201
    And the response should be in XML
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"

  Scenario Outline: Posting an XML resource with a boolean value
    When I send a "POST" request to "/resource_with_booleans" with body:
    """
    <?xml version="1.0" encoding="UTF-8"?>
    <ResourceWithBoolean>
      <myBooleanField><value></myBooleanField>
    </ResourceWithBoolean>
    """
    Then the response status code should be 201
    And the response should be in XML
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
  Examples:
    | value |
    | true  |
    | false |
    | 1     |
    | 0     |

  Scenario Outline: Posting an XML resource with an integer value
    When I send a "POST" request to "/resource_with_integers" with body:
    """
    <?xml version="1.0" encoding="UTF-8"?>
    <ResourceWithInteger>
      <myIntegerField><value></myIntegerField>
    </ResourceWithInteger>
    """
    Then the response status code should be 201
    And the response should be in XML
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
  Examples:
    | value |
    | 42    |
    | -6    |
    | 1     |
    | 0     |

  @!mysql
  Scenario Outline: Posting an XML resource with a float value
    When I send a "POST" request to "/resource_with_floats" with body:
    """
    <?xml version="1.0" encoding="UTF-8"?>
    <ResourceWithFloat>
      <myFloatField><value></myFloatField>
    </ResourceWithFloat>
    """
    Then the response status code should be 201
    And the response should be in XML
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
  Examples:
    | value |
    | 3.14  |
    | NaN   |
    | INF   |
    | -INF  |

  Scenario: Posting an XML resource with a collection with only one element
    When I send a "POST" request to "/dummy_properties" with body:
    """
    <?xml version="1.0" encoding="UTF-8"?>
    <DummyProperty>
      <groups>
        <DummyGroup>
          <foo>bar</foo>
        </DummyGroup>
      </groups>
    </DummyProperty>
    """
    Then the response status code should be 201
    And the response should be in XML
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
