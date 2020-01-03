# BigCommerce App Integration with SilverStripe
## Developed by IQnection

Provides an interface for creating a BigCommerce App.

### Requires a developer's API Client ID and Client Secret

- Create your BigCommerce account, then go to https://devtools.bigcommerce.com/my/apps
- Login to the CMS and go to Settings, select the ?Developer? tab, then BigCommerce tab
- Take note of the URLs provided, you'll need to enter these when creating your BigCommerce App
- Click "Create an App" and fill in all of your information, including the URLs provided from SilverStripe

### Once your app is created, you'll be able to retrieve your Client ID
- create a config file /app/_config/bigcommerce.yml and add the following credentials:
```
IQnection\BigCommerceApp\Client:
  client_id: '{your_client_id}'
  client_secret: '{your_client_secret}'
  debug: true
```
- Run a dev/build

... more to come