@!mongodb
Feature: Eager Loading
  In order to have better performance
  As a client software developer
  The eager loading should be enabled

  @createSchema
  Scenario: Eager loading for a relation
    Given there is a RelatedDummy with 2 friends
    When I send a "GET" request to "/related_dummies/1"
    And the response status code should be 200
    And the DQL should be equal to:
    """
    SELECT o, thirdLevel_a1, relatedToDummyFriend_a2, dummyFriend_a3
    FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
        LEFT JOIN o.thirdLevel thirdLevel_a1
        LEFT JOIN o.relatedToDummyFriend relatedToDummyFriend_a2
        LEFT JOIN relatedToDummyFriend_a2.dummyFriend dummyFriend_a3
    WHERE o.id = :id_id
    """

  Scenario: Eager loading for the search filter
    Given there is a dummy object with a fourth level relation
    When I send a "GET" request to "/dummies?relatedDummy.thirdLevel.level=3"
    Then the response status code should be 200
    And the DQL should be equal to:
    """
    SELECT o
    FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy o
        INNER JOIN o.relatedDummy relatedDummy_a1
        INNER JOIN relatedDummy_a1.thirdLevel thirdLevel_a2
    WHERE o IN(
            SELECT o_a3
            FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy o_a3
                INNER JOIN o_a3.relatedDummy relatedDummy_a4
                INNER JOIN relatedDummy_a4.thirdLevel thirdLevel_a5
            WHERE thirdLevel_a5.level = :level_p1
        )
    ORDER BY o.id ASC
    """

  Scenario: Eager loading for a relation and a search filter
    Given there is a RelatedDummy with 2 friends
    When I send a "GET" request to "/related_dummies?relatedToDummyFriend.dummyFriend=2"
    And the response status code should be 200
    And the DQL should be equal to:
    """
    SELECT o, thirdLevel_a4, relatedToDummyFriend_a1, dummyFriend_a5
    FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
        INNER JOIN o.relatedToDummyFriend relatedToDummyFriend_a1
        LEFT JOIN o.thirdLevel thirdLevel_a4
        INNER JOIN relatedToDummyFriend_a1.dummyFriend dummyFriend_a5
    WHERE o IN(
            SELECT o_a2
            FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy o_a2
                INNER JOIN o_a2.relatedToDummyFriend relatedToDummyFriend_a3
            WHERE relatedToDummyFriend_a3.dummyFriend = :dummyFriend_p1
        )
    ORDER BY o.id ASC
    """

  Scenario: Eager loading for a relation with complex sub-query filter
    Given there is a RelatedDummy with 2 friends
    When I send a "GET" request to "/related_dummies?complex_sub_query_filter=1"
    Then the response status code should be 200
    And the DQL should be equal to:
    """
    SELECT o, thirdLevel_a3, relatedToDummyFriend_a4, dummyFriend_a5
    FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy o
        LEFT JOIN o.thirdLevel thirdLevel_a3
        LEFT JOIN o.relatedToDummyFriend relatedToDummyFriend_a4
        LEFT JOIN relatedToDummyFriend_a4.dummyFriend dummyFriend_a5
    WHERE o.id IN (
            SELECT related_dummy_a1.id
            FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy related_dummy_a1
            INNER JOIN related_dummy_a1.relatedToDummyFriend related_to_dummy_friend_a2
            WITH related_to_dummy_friend_a2.name = :name_p1
        )
    ORDER BY o.id ASC
    """
