Feature: GraphQL schema-related features

  @createSchema
  Scenario: Export the GraphQL schema in SDL
    When I run the command "api:graphql:export"
    Then the command output should contain:
    """
    ###Dummy Friend.###
    type DummyFriend implements Node {
      id: ID!

      ###The id###
      _id: Int!

      ###The dummy name###
      name: String!
    }
    """
    And the command output should contain:
    """
    ###Cursor connection for DummyFriend.###
    type DummyFriendCursorConnection {
      edges: [DummyFriendEdge]
      pageInfo: DummyFriendPageInfo!
      totalCount: Int!
    }

    ###Edge of DummyFriend.###
    type DummyFriendEdge {
      node: DummyFriend
      cursor: String!
    }

    ###Information about the current page.###
    type DummyFriendPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
    }
    """
    And the command output should contain:
    """
      ###Updates a DummyFriend.###
      updateDummyFriend(input: updateDummyFriendInput!): updateDummyFriendPayload

      ###Deletes a DummyFriend.###
      deleteDummyFriend(input: deleteDummyFriendInput!): deleteDummyFriendPayload

      ###Creates a DummyFriend.###
      createDummyFriend(input: createDummyFriendInput!): createDummyFriendPayload
    """
    And the command output should contain:
    """
    ###Updates a DummyFriend.###
    input updateDummyFriendInput {
      id: ID!

      ###The dummy name###
      name: String
      clientMutationId: String
    }
    """
    And the command output should contain:
    """
    ###Updates a DummyFriend.###
    type updateDummyFriendPayload {
      dummyFriend: DummyFriend
      clientMutationId: String
    }
    """
    And the command output should contain:
    """
    ###Deletes a DummyFriend.###
    input deleteDummyFriendInput {
      id: ID!
      clientMutationId: String
    }

    ###Deletes a DummyFriend.###
    type deleteDummyFriendPayload {
      dummyFriend: DummyFriend
      clientMutationId: String
    }
    """
    And the command output should contain:
    """
    ###Creates a DummyFriend.###
    input createDummyFriendInput {
      ###The dummy name###
      name: String!
      clientMutationId: String
    }

    ###Creates a DummyFriend.###
    type createDummyFriendPayload {
      dummyFriend: DummyFriend
      clientMutationId: String
    }
    """
    And the command output should contain:
    """
    "Updates a OptionalRequiredDummy."
    input updateOptionalRequiredDummyInput {
      id: ID!
      thirdLevel: updateThirdLevelNestedInput
      thirdLevelRequired: updateThirdLevelNestedInput!

      "Get relatedToDummyFriend."
      relatedToDummyFriend: [updateRelatedToDummyFriendNestedInput]
      clientMutationId: String
    }
    """
