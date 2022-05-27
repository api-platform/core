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

    ###Cursor connection for DummyFriend.###
    type DummyFriendCursorConnection {
      edges: [DummyFriendEdge!]!
      pageInfo: DummyFriendPageInfo!
      totalCount: Int!
    }

    ###Edge of DummyFriend.###
    type DummyFriendEdge {
      node: DummyFriend!
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

  Scenario: Export the GraphQL schema in SDL with comment descriptions
    When I run the command "api:graphql:export" with options:
      | --comment-descriptions | true |
    Then the command output should contain:
    """
    # Dummy Friend.
    type DummyFriend implements Node {
      id: ID!

      # The id
      _id: Int!

      # The dummy name
      name: String!
    }

    # Cursor connection for DummyFriend.
    type DummyFriendCursorConnection {
      edges: [DummyFriendEdge!]!
      pageInfo: DummyFriendPageInfo!
      totalCount: Int!
    }

    # Edge of DummyFriend.
    type DummyFriendEdge {
      node: DummyFriend!
      cursor: String!
    }

    # Information about the current page.
    type DummyFriendPageInfo {
      endCursor: String
      startCursor: String
      hasNextPage: Boolean!
      hasPreviousPage: Boolean!
    }
    """

  Scenario: Export the GraphQL schema in SDL
    When I run the command "api:graphql:export"
    Then the command output should contain:
    """
    ###FooDummy.###
    type FooDummy implements Node {
      id: ID!

      ###The id###
      _id: Int!

      ###The foo name###
      name: String!

      ###The foo dummy###
      dummy: Dummy
    }

    ###Page connection for FooDummy.###
    type FooDummyPageConnection {
      collection: [FooDummy!]!
      paginationInfo: FooDummyPaginationInfo!
    }

    ###Information about the pagination.###
    type FooDummyPaginationInfo {
      itemsPerPage: Int!
      lastPage: Int!
      totalCount: Int!
    }
    """

  Scenario: Export the GraphQL schema in SDL with comment descriptions
    When I run the command "api:graphql:export" with options:
      | --comment-descriptions | true |
    Then the command output should contain:
    """
    # FooDummy.
    type FooDummy implements Node {
      id: ID!

      # The id
      _id: Int!

      # The foo name
      name: String!

      # The foo dummy
      dummy: Dummy
    }

    # Page connection for FooDummy.
    type FooDummyPageConnection {
      collection: [FooDummy!]!
      paginationInfo: FooDummyPaginationInfo!
    }

    # Information about the pagination.
    type FooDummyPaginationInfo {
      itemsPerPage: Int!
      lastPage: Int!
      totalCount: Int!
    }
    """
