Feature: Validate filters based upon filter description

  Background:
    Given I add "Accept" header equal to "application/json"

  @createSchema
  Scenario: Required filter should not throw an error if set
    When I am on "/filter_validators?required=foo&required-allow-empty=&arrayRequired[foo]="
    Then the response status code should be 200

  Scenario: Required filter that does not allow empty value should throw an error if empty
    When I am on "/filter_validators?required=&required-allow-empty=&arrayRequired[foo]="
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'required: This value should not be blank.'

  Scenario: Required filter should throw an error if not set
    When I am on "/filter_validators"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'required: This value should not be blank.\nrequired-allow-empty: This value should not be null.'

  Scenario: Required filter should not throw an error if set
    When I am on "/array_filter_validators?arrayRequired[]=foo&indexedArrayRequired[foo]=foo"
    Then the response status code should be 200

  Scenario: Required filter should throw an error if not set
    When I am on "/array_filter_validators"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'arrayRequired[]: This value should not be blank.\nindexedArrayRequired[foo]: This value should not be blank.'

    When I am on "/array_filter_validators?arrayRequired[foo]=foo"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'indexedArrayRequired[foo]: This value should not be blank.'

    When I am on "/array_filter_validators?arrayRequired[]=foo"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'indexedArrayRequired[foo]: This value should not be blank.'

  Scenario: Test filter bounds: maximum
    When I am on "/filter_validators?required=foo&required-allow-empty&maximum=10"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&maximum=11"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'maximum: This value should be less than or equal to 10.'

  Scenario: Test filter bounds: exclusiveMaximum
    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMaximum=9"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMaximum=10"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'exclusiveMaximum: This value should be less than 10.'

  Scenario: Test filter bounds: minimum
    When I am on "/filter_validators?required=foo&required-allow-empty&minimum=5"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&minimum=0"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'minimum: This value should be greater than or equal to 5.'

  Scenario: Test filter bounds: exclusiveMinimum
    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMinimum=6"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&exclusiveMinimum=5"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'exclusiveMinimum: This value should be greater than 5.'

  Scenario: Test filter bounds: max length
    When I am on "/filter_validators?required=foo&required-allow-empty&max-length-3=123"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&max-length-3=1234"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'max-length-3: This value is too long. It should have 3 characters or less.'

  Scenario: Test filter bounds: min length
    When I am on "/filter_validators?required=foo&required-allow-empty&min-length-3=123"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&min-length-3=12"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'min-length-3: This value is too short. It should have 3 characters or more.'

  Scenario: Test filter pattern
    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=pattern"
    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=nrettap"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&pattern=not-pattern"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'pattern: This value is not valid.'

  Scenario: Test filter enum
    When I am on "/filter_validators?required=foo&required-allow-empty&enum=in-enum"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&enum=not-in-enum"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'enum: The value you selected is not a valid choice.'

  Scenario: Test filter multipleOf
    When I am on "/filter_validators?required=foo&required-allow-empty&multiple-of=4"
    Then the response status code should be 200

    When I am on "/filter_validators?required=foo&required-allow-empty&multiple-of=3"
    Then the response status code should be 422
    And the JSON node "detail" should be equal to 'multiple-of: This value should be a multiple of 2.'
