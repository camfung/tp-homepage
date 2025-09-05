GET /items/validate

Handled by ValidateTPKeyFunction.
use this to validate a key

Validates a key for uniqueness

Example Input GET dev.trfc.link/items/validate?tpkey=SHERRITT&domain=

Example Output
{
  "message": "Domain-key pair is unique.",
  "keystatus": "available",
  "source": "Suggested Key: abc123",
  "success": true
}


POST /items

Handled by CreateMaskedRecordFunction.

This api endpoint creates map record by authenticating user and validating the tpKey. If user is not authenticated it gives 401 error. If tpKey is invalid it returns 400 error.

Example Input (JSON body)
{
  "uid": 0,
  "tpTkn": "USER_TOKEN",
  "tpKey": "abc123",
  "domain": "trafficportal.dev",
  "destination": "<https://example.com/landing>",
  "status": "active"
}

Example Output
{
  "message": "Record Created",
  "source": {
    "mid": 42,
    "tpKey": "abc123",
    "domain": "trafficportal.dev",
    "destination": "<https://example.com/landing>",
    "status": "active"
  },
  "success": true
}
