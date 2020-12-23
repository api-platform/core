Feature: Validate filters based upon filter description

  @createSchema
  Scenario: Required filter should not throw an error if set
    When I am on "/filter_validators?required=foo&required-allow-empty=&arrayRequired[foo]="
    Then the response status code should be 200

  Scenario: Required filter that does not allow empty value should throw an error if empty
    When I am on "/filter_validators?required=&required-allow-empty=&arrayRequired[foo]="
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "required" does not allow empty value'

  Scenario: Required filter should throw an error if not set
    When I am on "/filter_validators"
    Then the response status code should be 400
    Then the JSON node "detail" should match '/^Query parameter "required" is required\nQuery parameter "required-allow-empty" is required$/'

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

  Scenario: Test filter bounds: maximum
    When I am on "/filter_validators?required=foo&required-allow-empty&maximum=10"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&maximum=11"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "maximum" must be less than or equal to 10'

  Scenario: Test filter bounds: exclusiveMaximum
    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMaximum=9"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMaximum=10"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "exclusiveMaximum" must be less than 10'

  Scenario: Test filter bounds: minimum
    When I am on "/filter_validators?required=foo&required-allow-empty&minimum=5"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&minimum=0"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "minimum" must be greater than or equal to 5'

  Scenario: Test filter bounds: exclusiveMinimum
    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMinimum=6"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMinimum=5"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "exclusiveMinimum" must be greater than 5'

  Scenario: Test filter bounds: max length
    When I am on "/filter_validators?required=foo&required-allow-empty&max-length-3=123"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&max-length-3=1234"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "max-length-3" length must be lower than or equal to 3'

  Scenario: Do not throw an error if value is not an array
    When I am on "/filter_validators?required=foo&required-allow-empty&max-length-3[]=12345"
    Then the response status code should be 200

  Scenario: Test filter bounds: min length
    When I am on "/filter_validators?required=foo&required-allow-empty&min-length-3=123"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&min-length-3=12"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "min-length-3" length must be greater than or equal to 3'

  Scenario: Test filter pattern
    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=pattern"
    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=nrettap"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=not-pattern"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "pattern" must match pattern /^(pattern|nrettap)$/'

  Scenario: Test filter enum
    When I am on "/filter_validators?required=foo&required-allow-empty&enum=in-enum"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&enum=not-in-enum"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "enum" must be one of "in-enum, mune-ni"'

  Scenario: Test filter multipleOf
    When I am on "/filter_validators?required=foo&required-allow-empty&multiple-of=4"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&multiple-of=3"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "multiple-of" must multiple of 2'

  Scenario: Test filter array items csv format minItems
    When I am on "/filter_validators?required=foo&required-allow-empty&csv-min-2=a,b"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&csv-min-2=a"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "csv-min-2" must contain more than 2 values'

  Scenario: Test filter array items csv format maxItems
    When I am on "/filter_validators?required=foo&required-allow-empty&csv-max-3=a,b,c"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&csv-max-3=a,b,c,d"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "csv-max-3" must contain less than 3 values'

  Scenario: Test filter array items tsv format minItems
    When I am on "/filter_validators?required=foo&required-allow-empty&tsv-min-2=a\tb"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&tsv-min-2=a,b"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "tsv-min-2" must contain more than 2 values'

  Scenario: Test filter array items pipes format minItems
    When I am on "/filter_validators?required=foo&required-allow-empty&pipes-min-2=a|b"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&pipes-min-2=a,b"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "pipes-min-2" must contain more than 2 values'

  Scenario: Test filter array items ssv format minItems
    When I am on "/filter_validators?required=foo&required-allow-empty&ssv-min-2=a b"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&ssv-min-2=a,b"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "ssv-min-2" must contain more than 2 values'

  @dropSchema
  Scenario: Test filter array items unique items
    When I am on "/filter_validators?required=foo&required-allow-empty&csv-uniques=a,b"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&csv-uniques=a,a"
    Then the response status code should be 400
    And the JSON node "detail" should be equal to 'Query parameter "csv-uniques" must contain unique values'
