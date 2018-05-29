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
# TODO: add after OneToMany fix
#        "inspections": [
#            {
#                "accepted": true,
#                "performed": "2018-03-14 00:00:00"
#            }
#        ],
    Then the response status code should be 201
    And the JSON should be equal to:
    """
     {
         "@context": "/contexts/VoDummyCar",
         "@id": "/vo_dummy_cars/1",
         "@type": "VoDummyCar",
         "mileage": 1500,
         "bodyType": "suv",
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
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {
          "type": "string",
          "format": "^/contexts/Error$"
        },
        "type": {
          "type": "string",
          "format": "^hydra:Error$"
        },
        "hydra:title": {
          "type": "string",
          "format": "^An error occurred$"
        },
        "hydra:description": {
          "type": "string",
          "format": "^Cannot create an instance of ApiPlatform\\Core\\Tests\\Fixtures\\TestBundle\\Entity\\VoDummyCar from serialized data because its constructor requires parameter \"drivers\" to be present.$"
        }
      }
    }
    """
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

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
