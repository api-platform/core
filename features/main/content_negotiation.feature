Feature: Content Negotiation support
  In order to make the API supporting several input and output formats
  As an API developer
  I need to be able to specify the format I want to use

  @createSchema
  Scenario: Post an XML body
    When I add "Accept" header equal to "application/xml"
    And I add "Content-Type" header equal to "application/xml"
    And I send a "POST" request to "/dummies" with body:
    """
    <root>
        <name>XML!</name>
    </root>
    """
    Then the response status code should be 201
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><description/><dummy/><dummyBoolean/><dummyDate/><dummyFloat/><dummyPrice/><relatedDummy/><relatedDummies/><jsonData/><arrayData/><name_converted/><relatedOwnedDummy/><relatedOwningDummy/><id>1</id><name>XML!</name><alias/><foo/></response>
    """

  Scenario:  Retrieve a collection in XML
    When I add "Accept" header equal to "text/xml"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><item key="0"><description/><dummy/><dummyBoolean/><dummyDate/><dummyFloat/><dummyPrice/><relatedDummy/><relatedDummies/><jsonData/><arrayData/><name_converted/><relatedOwnedDummy/><relatedOwningDummy/><id>1</id><name>XML!</name><alias/><foo/></item></response>
    """

  Scenario:  Retrieve a collection in XML using the .xml URL
    When I send a "GET" request to "/dummies.xml"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><item key="0"><description/><dummy/><dummyBoolean/><dummyDate/><dummyFloat/><dummyPrice/><relatedDummy/><relatedDummies/><jsonData/><arrayData/><name_converted/><relatedOwnedDummy/><relatedOwningDummy/><id>1</id><name>XML!</name><alias/><foo/></item></response>
    """

  Scenario:  Retrieve a collection in JSON
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
      {
        "description": null,
        "dummy": null,
        "dummyBoolean": null,
        "dummyDate": null,
        "dummyFloat": null,
        "dummyPrice": null,
        "relatedDummy": null,
        "relatedDummies": [],
        "jsonData": [],
        "arrayData": [],
        "name_converted": null,
	      "relatedOwnedDummy": null,
	      "relatedOwningDummy": null,
        "id": 1,
        "name": "XML!",
        "alias": null,
        "foo": null
      }
    ]
    """

  Scenario: Post a JSON document and retrieve an XML body
    When I add "Accept" header equal to "application/xml"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/dummies" with body:
    """
    {"name": "Sent in JSON"}
    """
    Then the response status code should be 201
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><description/><dummy/><dummyBoolean/><dummyDate/><dummyFloat/><dummyPrice/><relatedDummy/><relatedDummies/><jsonData/><arrayData/><name_converted/><relatedOwnedDummy/><relatedOwningDummy/><id>2</id><name>Sent in JSON</name><alias/><foo/></response>
    """

  Scenario: Requesting the same format in the Accept header and in the URL should work
    When I add "Accept" header equal to "text/xml"
    And I send a "GET" request to "/dummies/1.xml"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"

  Scenario: Requesting any format in the Accept header should default to the first configured format
    When I add "Accept" header equal to "*/*"
    And I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Requesting any format in the Accept header should default to the format passed in the URL
    When I add "Accept" header equal to "text/plain; charset=utf-8, */*"
    And I send a "GET" request to "/dummies/1.xml"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"

  Scenario: Requesting an unknown format should throw an error
    When I add "Accept" header equal to "text/plain"
    And I send a "GET" request to "/dummies/1"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  Scenario: If the request format is HTML, the error should be in HTML
    When I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/dummies/666"
    Then the response status code should be 404
    And the header "Content-Type" should be equal to "text/html; charset=utf-8"

  Scenario: Retrieve a collection in JSON should not be possible if the format has been removed at resource level
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/dummy_custom_formats"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  Scenario: Post an CSV body allowed on a single resource
    When I add "Accept" header equal to "application/xml"
    And I add "Content-Type" header equal to "text/csv"
    And I send a "POST" request to "/dummy_custom_formats" with body:
    """
    name
    Kevin
    """
    Then the response status code should be 201
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><id>1</id><name>Kevin</name></response>
    """

  Scenario: Retrieve a collection in CSV should be possible if the format is at resource level
    When I add "Accept" header equal to "text/csv"
    And I send a "GET" request to "/dummy_custom_formats"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "text/csv; charset=utf-8"
    And the response should be equal to
    """
    id,name
    1,Kevin
    """
