# IndexerGraphQl

Provides GraphQl queries to access indexes information.

# GraphQL Query
```graphql
query($indexes: [String!]) {
  indexerState(indexer: $indexes ) {
    total_count,
    items {
      id,
      title,
      status,
      update_on,
      schedule_status,
      updated
    }
  }
}
```

