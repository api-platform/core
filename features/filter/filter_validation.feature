Feature: Validate filters based upon filter description

  @createSchema
  Scenario: Required filter should not throw an error if set
    When I am on "/filter_validators?required=foo"
    Then the response status code should be 200

    When I am on "/filter_validators?required="
    Then the response status code should be 200

  Scenario: Required filter should throw an error if not set
    When I am on "/filter_validators"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "required" is required'

  Scenario: Required filter should not throw an error if set
    When I am on "/array_filter_validators?arrayRequired[]=foo&indexedArrayRequired[foo]=foo"
    Then the response status code should be 200

  Scenario: Required filter should throw an error if not set
    When I am on "/array_filter_validators"
    Then the response status code should be 400
    And the JSON node "detail" should match '/^Query parameter "arrayRequired\[\]" is required\nQuery parameter "indexedArrayRequired\[foo\]" is required$/'

    When I am on "/array_filter_validators?arrayRequired=foo&indexedArrayRequired[foo]=foo"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "arrayRequired[]" is required'

    When I am on "/array_filter_validators?arrayRequired[foo]=foo"
    Then the response status code should be 400
    And the JSON node "detail" should match '/^Query parameter "arrayRequired\[\]" is required\nQuery parameter "indexedArrayRequired\[foo\]" is required$/'

    When I am on "/array_filter_validators?arrayRequired[]=foo"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "indexedArrayRequired[foo]" is required'

    When I am on "/array_filter_validators?arrayRequired[]=foo&indexedArrayRequired[bar]=bar"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "indexedArrayRequired[foo]" is required'
