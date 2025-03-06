# Freemius Single-Sign On 🔐 WordPress Plugin

Allow users to log into your WordPress store with their Freemius credentials. 
If a user logs in with their Freemius credentials and there was no matching user in WordPress, a new user with the same email address and password will be created in WordPress.

## Freemius account information

```php
define('FREEMIUS_SSO_STORE_ID', <Your_Freemius_Store_ID>);
define('FREEMIUS_SSO_DEVELOPER_ID', <Your_Freemius_Developer_ID>);
define('FREEMIUS_SSO_DEVELOPER_SECRET_KEY', '<Your_Freemius_Developer_Secret_Key>');
define('FREEMIUS_SSO_PUBLIC_KEY', '<Your_Freemius_Public_key>');
```

## Plugin installation

1. Download the plugin.
2. Upload and activate the plugin.
3. Add to the `wp-config.php` your Freemius developer account information.
4. Done! Users will be able to login with their Freemius credentials.
