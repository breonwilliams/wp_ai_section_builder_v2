# AI Settings Setup Instructions

## Setting Up Encryption for API Keys

To ensure your API keys are stored securely, add the following constants to your `wp-config.php` file:

```php
// Add these lines to wp-config.php (above the "That's all, stop editing!" line)

// AI Section Builder Encryption Keys
// Generate unique keys for your installation - DO NOT use these example values in production!
define('AISB_ENCRYPTION_KEY', 'your-32-character-encryption-key-here-change-me');
define('AISB_ENCRYPTION_SALT', 'your-16-char-salt');
```

### Generating Secure Keys

You can generate secure random strings using one of these methods:

1. **Using PHP:**
   ```php
   // For encryption key (32 characters)
   echo bin2hex(random_bytes(16)); 
   
   // For salt (16 characters)
   echo bin2hex(random_bytes(8));
   ```

2. **Using WordPress Salt Generator:**
   Visit https://api.wordpress.org/secret-key/1.1/salt/ and use parts of the generated keys

3. **Using Command Line:**
   ```bash
   # For encryption key
   openssl rand -hex 16
   
   # For salt
   openssl rand -hex 8
   ```

## Important Security Notes

1. **Never commit these keys to version control** - Keep them in your local `wp-config.php` only
2. **Use different keys for each environment** (development, staging, production)
3. **Back up your keys securely** - You'll need them to decrypt stored API keys
4. **If keys are not defined**, API keys will be stored with basic encoding only (not recommended for production)

## Setting Up AI Provider Access

### OpenAI
1. Go to https://platform.openai.com/api-keys
2. Create a new API key
3. Copy the key immediately (it won't be shown again)
4. Enter it in the AI Settings page

### Anthropic (Claude)
1. Go to https://console.anthropic.com/api-keys
2. Create a new API key
3. Copy the key immediately
4. Enter it in the AI Settings page

## Using the AI Settings Page

1. Navigate to **WordPress Admin → AI Section Builder → AI Settings**
2. Select your AI provider (OpenAI or Anthropic)
3. Enter your API key
4. Select the model you want to use
5. Click "Test Connection" to verify your setup
6. Click "Save Settings" to store your configuration

## Troubleshooting

### "Security check failed" error
- Clear your browser cache and cookies
- Log out and log back into WordPress

### "Invalid API key" error
- Verify you're using the correct key for the selected provider
- Check that the key hasn't been revoked or expired
- Ensure you've copied the complete key without spaces

### Connection timeout
- Check your server's firewall settings
- Verify your server can make outbound HTTPS requests
- Try increasing the timeout in the connection test functions

## Support

For issues or questions, please check the plugin documentation or contact support.