Feature: Value object as ApiResource
  In order to keep ApiResource immutable
  As a client software developer
  I need to be able to use class without setters as ApiResource

  @createSchema
  Scenario: Create Value object resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/vo_dummy_cars" with body:
    """
    {
        "mileage": 1500,
        "bodyType": "suv",
        "make": "CustomCar",
        "insuranceCompany": {
            "name": "Safe Drive Company"
        },
        "drivers": [
            {
                "firstName": "John",
                "lastName": "Doe"
            }
        ]
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/VoDummyCar",
        "@id": "/vo_dummy_cars/1",
        "@type": "VoDummyCar",
        "mileage": 1500,
        "bodyType": "suv",
        "inspections": [],
        "make": "CustomCar",
        "insuranceCompany": {
            "@id": "/vo_dummy_insurance_companies/1",
            "@type": "VoDummyInsuranceCompany",
            "name": "Safe Drive Company"
        },
        "drivers": [
            {
                "@id": "/vo_dummy_drivers/1",
                "@type": "VoDummyDriver",
                "firstName": "John",
                "lastName": "Doe"
            }
        ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Create Value object with IRI and nullable parameter
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/vo_dummy_inspections" with body:
    """
    {
        "accepted": true,
        "car": "/vo_dummy_cars/1"
    }
    """
    Then the response status code should be 201
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "required": ["accepted", "performed", "car"],
      "properties": {
        "accepted": {
          "enum":[true]
        },
        "performed": {
          "format": "date-time"
        },
        "car": {
          "enum": ["/vo_dummy_cars/1"]
        }
      }
    }
    """

  Scenario: Update Value object with writable and non writable property
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/vo_dummy_inspections/1" with body:
    """
    {
        "performed": "2018-08-24 00:00:00",
        "accepted": false
    }
    """
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/VoDummyInspection",
        "@id": "/vo_dummy_inspections/1",
        "@type": "VoDummyInspection",
        "accepted": true,
        "car": "/vo_dummy_cars/1",
        "performed": "2018-08-24T00:00:00+00:00"
    }
    """

  @createSchema
  Scenario: Create Value object without required params
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/vo_dummy_cars" with body:
    """
    {
        "mileage": 1500,
        "make": "CustomCar",
        "insuranceCompany": {
            "name": "Safe Drive Company"
        }
    }
    """
    Then the response status code should be 400
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {
          "type": "string",
          "pattern": "^/contexts/Error$"
        },
        "@type": {
          "type": "string",
          "pattern": "^hydra:Error$"
        },
        "hydra:title": {
          "type": "string",
          "pattern": "^An error occurred$"
        },
        "hydra:description": {
          "pattern": "^Cannot create an instance of ApiPlatform\\\\Core\\\\Tests\\\\Fixtures\\\\TestBundle\\\\(Document|Entity)\\\\VoDummyCar from serialized data because its constructor requires parameter \"drivers\" to be present.$"
        }
      },
      "required": [
        "@context",
        "@type",
        "hydra:title",
        "hydra:description"
      ]
    }
    """

  @createSchema
  Scenario: Create Value object without default param
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/vo_dummy_cars" with body:
    """
    {
        "mileage": 1500,
        "make": "CustomCar",
        "insuranceCompany": {
            "name": "Safe Drive Company"
        },
        "drivers": [
            {
                "firstName": "John",
                "lastName": "Doe"
            }
        ]
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
        "@context": "/contexts/VoDummyCar",
        "@id": "/vo_dummy_cars/1",
        "@type": "VoDummyCar",
        "mileage": 1500,
        "bodyType": "coupe",
        "inspections": [],
        "make": "CustomCar",
        "insuranceCompany": {
            "@id": "/vo_dummy_insurance_companies/1",
            "@type": "VoDummyInsuranceCompany",
            "name": "Safe Drive Company"
        },
        "drivers": [
            {
                "@id": "/vo_dummy_drivers/1",
                "@type": "VoDummyDriver",
                "firstName": "John",
                "lastName": "Doe"
            }
        ]
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
