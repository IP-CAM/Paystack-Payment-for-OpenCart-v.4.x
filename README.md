## Paystack Payment Gateway for OpenCart 4

This Paystack payment gateway extension is designed specifically for **OpenCart 4.x**.  
The official Paystack plugin supports up to **OpenCart 3.0.3.2** and is not compatible with newer versions like **4.0.2.3**  
This extension is based on the official Paystack integration guide, but updated for compatibility with OpenCart's new directory structure and powered by **Paystack API v2**.

### Features

- ✅ Fully compatible with OpenCart 4.x
- 🚀 Uses Paystack API v2 for improved flexibility
- 💱 Supports multiple Paystack-approved currencies
- 🔄 Performs automatic currency conversion using store exchange rates
- 🧩 Follows OpenCart 4 extension structure

### Installation Guide

> This extension is only compatible with OpenCart 4.x. If you're using OpenCart 3.x, refer to the [official Paystack extension](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=25767) instead.

> This extension follows similar installation steps as described in the [official Paystack documentation](https://support.paystack.com/en/articles/2126082).  
> However, the extracted content should not reside in the root folder but in **extension/opencart/** folder

1. Extract the downloaded zip file which contains `admin` and `catalog` folder.
2. Merge the Paystack plugin folders with the folders in `/extension/opencart/` 
3. Go to `Extensions > Extensions > Payments` in your admin dashboard.
4. Locate **Paystack** and click **Install**.
5. Click the **Edit button** to configure the necessary Paystack settings
6. Enable the extension and save your settings.

### Next

1. Navigate to `System > Localisation > Currencies.`
2. Ensure that a supported currency is added. E.g NGN, GHS etc
3. Enable the currency and ensure it can be selected in store front

### Currency Support Differences from Official Plugin

The Paystack v1 extension (which uses API v1) only works if **NGN** (or GHS) is the store’s default currency.  
This modern version leverages Paystack API v2 and offering much greater flexibility:

- Works as long as any Paystack-supported currency is selected in your store.
- Automatically converts prices from your store's base currency to the selected Paystack currency using OpenCart’s currency rates.

For example:

- Store base currency: USD
- Customer selects: NGN
- Paystack processes payment in NGN, converting from USD using your store's exchange rate settings.

### Compatibility

| Feature                | Supported |
| ---------------------- | --------- |
| OpenCart 4.x           | Yes     |
| OpenCart 3.x           | No      |
| Paystack API v2        | Yes     |
| NGN only (forced)      | No      |
| Multi-currency support | Yes     |


### Help &amp; Support

This extension is independently maintained by [Ucscode](https://ucscode.com).  
Feel free to suggest features or improvements via GitHub pull request or reach out directly.
For general inquiries about Paystack, reach out to techsupport@paystack.com or visit the [Paystack Help Center](https://support.paystack.com/).
