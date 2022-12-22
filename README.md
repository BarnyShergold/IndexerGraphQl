# IndexerGraphQl

Provides GraphQl queries to access indexes information.

# GraphQL Configuration

## GraphQL Query

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

## GraphQL Parameters
The indexes array contains a list of the required indexes to be checked, for a full list of all indexes leave the array empty.

```json
{
  "indexes": [
    "catalog_data_exporter_product_attributes",
    "catalogrule_product"
  ]
}
```

## GraphQL Headers
**Indexer-Auth-Key** is required and needs to match the secret key that has been added to the Commerce Admin Configuration.

## GraphQL Sample Response
```json
{
  "data": {
    "indexerState": {
      "total_count": 2,
      "items": [
        {
          "id": "catalogrule_product",
          "title": "Catalog Product Rule",
          "status": "Ready",
          "update_on": "Schedule",
          "schedule_status": "idle (0 in backlog)",
          "updated": "2022-12-22 14:12:03"
        },
        {
          "id": "catalog_data_exporter_product_attributes",
          "title": "Catalog Attributes Feed",
          "status": "Ready",
          "update_on": "Schedule",
          "schedule_status": "idle (0 in backlog)",
          "updated": "2022-12-22 14:12:03"
        }
      ]
    }
  }
}
```

# Commerce Configuration
Set **Stores** > **Configuration** > **Security** > **GraphQL** > **Indexer State** > **Secret Key** this key should be password grade and send in the **Indexer-Auth-Key** header.
