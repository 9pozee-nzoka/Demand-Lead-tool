# OpenAI Integration Added

## Overview
OpenAI has been added as an integration provider to power AI features like intent analysis, keyword clustering, opportunity explanations, and landing page content generation.

## Changes Made

### 1. Controllers Updated

#### Web IntegrationController (`app/Http/Controllers/Web/IntegrationController.php`)
- ✅ Added `'openai'` to `PROVIDER_TYPES` array
- ✅ Added `testOpenAI()` method to test OpenAI API connectivity
- ✅ Updated `test()` method to include OpenAI testing
- ✅ Added OpenAI to `getAvailableProviders()` array with icon and description

#### API IntegrationController (`app/Http/Controllers/Api/IntegrationController.php`)
- ✅ Added `'openai'` to `PROVIDER_TYPES` array
- ✅ Added `testOpenAI()` method to test OpenAI API connectivity
- ✅ Updated `test()` method to include OpenAI testing
- ✅ Added OpenAI to `availableProviders()` array

### 2. Views Updated

#### Create Integration View (`resources/views/integrations/create.blade.php`)
- ✅ Added OpenAI credentials form section with:
  - API Key input field
  - Model selector (gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo)
  - Help text with link to OpenAI platform
  - Info banner explaining OpenAI features

#### Edit Integration View (`resources/views/integrations/edit.blade.php`)
- ✅ Added OpenAI credentials form section
- ✅ Added 🤖 emoji icon for OpenAI
- ✅ Shows current model selection
- ✅ Indicates when API key is configured with ✓ indicator

### 3. Provider Configuration

**OpenAI Provider Details:**
- **Type**: `openai`
- **Label**: OpenAI
- **Icon**: 🤖 (psychology icon)
- **Color**: indigo
- **Description**: AI-powered intent analysis, clustering, and content generation

### 4. Credentials Structure

OpenAI integration stores the following credentials:
```json
{
  "api_key": "sk-...",
  "model": "gpt-4o"
}
```

**Fields:**
- `api_key` (required) - OpenAI API key from platform.openai.com
- `model` (optional) - Defaults to `gpt-4o` if not specified

**Available Models:**
- `gpt-4o` - Recommended, latest multimodal model
- `gpt-4o-mini` - Faster and cheaper variant
- `gpt-4-turbo` - Previous generation high-performance model
- `gpt-3.5-turbo` - Legacy model for compatibility

### 5. Connection Testing

The `testOpenAI()` method performs a lightweight connectivity check:
- Fetches the list of available models from OpenAI API
- Uses the API key from credentials or falls back to `OPENAI_API_KEY` env variable
- Returns success/error status with descriptive message
- Updates the integration status and sync_meta accordingly

**Test endpoint:**
```
GET https://api.openai.com/v1/models
```

## Usage

### Creating an OpenAI Integration

1. Navigate to Integrations page
2. Click "Add Integration"
3. Select "OpenAI" from provider dropdown
4. Enter integration name (e.g., "Main AI Engine")
5. Paste your OpenAI API key
6. Optionally select a different model (defaults to gpt-4o)
7. Click "Create Integration"

### Testing the Integration

Once created, you can test the connection:
- Via UI: Click "Test Connection" button on integration detail page
- Via API: `POST /api/v1/integrations/{id}/test`

### Environment Variable Fallback

If no API key is stored in the integration credentials, the system will fall back to the `OPENAI_API_KEY` environment variable. This is useful for:
- Development/testing
- Shared organization-wide API key
- Before configuring per-integration keys

Add to `.env`:
```bash
OPENAI_API_KEY=sk-your-key-here
```

## Integration with Existing Services

OpenAI integration is designed to work with:

### Intelligence Services (`app/Services/Intelligence/`)
- Intent analysis for keywords
- Semantic clustering of demand signals
- Content generation for landing pages
- Opportunity explanation generation

### Jobs
- `GenerateOpportunityExplanation` - Uses OpenAI to explain why an opportunity scored high
- Future: Lead qualification, content generation, sentiment analysis

### Models
- Credentials are encrypted in `DataSource` model using `Crypt::encryptString()`
- Never exposed in API responses (only `has_credentials` flag shown)
- Audit logs track integration creation/updates

## Security Notes

✅ **Secure Storage**: API keys are encrypted at rest using Laravel's encryption
✅ **Hidden from API**: Credentials never returned in API responses
✅ **Audit Trail**: All integration changes are logged in `audit_logs` table
✅ **Multi-tenancy**: Each organization can have their own OpenAI integration
✅ **Environment Fallback**: Can use shared key from .env as fallback

## Next Steps

To fully utilize OpenAI integration:

1. **Update AI Service** (`app/Services/AI/OpenAIService.php`):
   - Modify to fetch API key from DataSource instead of only env variable
   - Support model selection from integration config

2. **Create AI-Powered Jobs**:
   - Lead qualification automation
   - Landing page content generation
   - Email/SMS content optimization
   - Market analysis and insights

3. **Add Usage Tracking**:
   - Track tokens used per integration
   - Monitor costs per organization
   - Add usage limits based on subscription plan

4. **Dashboard Metrics**:
   - Show AI usage stats on integration detail page
   - Display cost estimates
   - Track success/error rates for AI calls

## Testing

Test the OpenAI integration:

```bash
# Create an integration via UI or API
POST /api/v1/integrations
{
  "name": "Main AI Engine",
  "type": "openai",
  "credentials": {
    "api_key": "sk-...",
    "model": "gpt-4o"
  }
}

# Test connection
POST /api/v1/integrations/{id}/test

# Expected response on success:
{
  "ok": true,
  "message": "OpenAI API connected successfully."
}
```

## Troubleshooting

**"OpenAI API key not configured"**
- Ensure API key is provided in credentials OR set in .env as `OPENAI_API_KEY`

**"Cannot reach OpenAI API"**
- Check network connectivity
- Verify API key is valid at platform.openai.com
- Check firewall/proxy settings

**"OpenAI API returned HTTP 401"**
- API key is invalid or expired
- Generate a new key at platform.openai.com/api-keys

**"OpenAI API returned HTTP 429"**
- Rate limit exceeded
- Upgrade OpenAI plan or reduce request frequency
- Implement request queuing/throttling

---

**Status**: ✅ Complete
**Last Updated**: 2026-08-31
