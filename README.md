# Paystack Payment Gateway for OpenCart 4.x

This is a custom Paystack payment gateway extension built specifically for **OpenCart 4.x.x.x**.

> 📌 The official Paystack plugin [only supports OpenCart 3.0.3.2](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=25767) and is **not compatible** with OpenCart 4.x (e.g., 4.0.2.3).
> ✅ This version is designed for the new folder structure introduced in OpenCart 4.

---

## ⚠️ Compatibility

* ✅ Compatible with: **OpenCart 4.x.x.x**
* ❌ Not compatible with: **OpenCart 3.x** or earlier

---

## 📦 Installation Instructions

### 1. **Download and Extract**

* Download the ZIP file of this plugin.
* Extract it—you should see two folders:

  ```
  admin/
  catalog/
  ```

---

### 2. **Copy to the Correct Directory**

* In OpenCart 4, extensions should reside inside the `extension/opencart/` folder, not at the root directory.

1. Navigate to:

   ```
   your-opencart-root/extension/opencart/
   ```

2. Copy the extracted `admin/` and `catalog/` folders **into** that directory, so the structure becomes:

   ```
   extension/opencart/admin/controller/...
   extension/opencart/catalog/controller/...
   ```

✅ **Do NOT copy them into the root-level `admin/` or `catalog/` directories** — that is only valid for OpenCart 3.x.

---

### 3. **Install the Extension**

* Log into your OpenCart Admin Dashboard.
* Go to: `Extensions` → `Extensions` → Select `Payments`.
* Find **Paystack** and click **Install**.
* Then click **Edit** to configure it.

---

### 4. **Configure Paystack**

* Enter your **Live** and **Test** API keys from your [Paystack Dashboard](https://dashboard.paystack.com/#/settings/developer).
* Set **Live Mode** to `Yes` or `No` depending on your environment.
* Set **Status** to `Enabled`.
* Save your settings.

---

### 5. **Enable NGN (₦) Currency**

* Go to `System` → `Localisation` → `Currencies`.
* Add or enable **Naira (NGN)**.
* Then go to `System` → `Settings` → Edit your store → Set default currency to **NGN**.

---

## 🧪 Testing

* Set Live Mode to `No` and use test card details from Paystack to simulate payments.
* Verify that orders are correctly updated and Paystack callbacks work.

---

## 🛠 Troubleshooting

* If the payment method does not show up:

  * Confirm files are copied into `extension/opencart/`, not the root.
  * Clear modifications and cache under `Extensions → Modifications`.

* If payments don’t go through:

  * Verify API keys
  * Check store currency is set to **NGN**

---

## 📧 Support

For support with this custom plugin:

* **Developer:** \[Your Name]
* **Email:** \[[your@email.com](mailto:your@email.com)]

For Paystack-specific issues, visit [Paystack Support](https://support.paystack.com) or email [techsupport@paystack.com](mailto:techsupport@paystack.com).

---

## 📄 License

MIT License

---

Let me know if you want a downloadable ZIP or GitHub repo scaffold to go along with this!
