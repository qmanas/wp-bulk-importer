# 📦 WP Bulk Importer: Enterprise Product Migration Suite

A robust WordPress plugin for bulk-importing large-scale product datasets (10k+ items) with high reliability. Originally designed for complex e-commerce migrations, this tool handles product relationships, category mapping, and media asset associations with ease.

---

## 🔥 The Problem Solved
Migrating a massive product catalog (e.g., from an old ERP or Wix) into WooCommerce or standard WordPress custom post types often results in time-outs, duplicate entries, or broken image links. Standard importers rarely handle **Hierarchical Categories** and **Custom ACF Fields** correctly for large datasets.

---

## 🛡️ The "Ghost-Proof" Win: Chunked Processing & Integrity
This plugin uses a **Batch Integrity Protocol**:
1.  **Safety Buffer**: Validates data presence (SKU, Title, Price) before attempting database injection.
2.  **Unique-ID Mapping**: Uses a `legacy_id` cross-reference logic to prevent duplicate posts during re-runs.
3.  **Media Side-loading**: Automatically downloads and attaches remote images to their respective products, handling naming collisions on-the-fly.
4.  **Meta-Data Reconciliation**: Deeply integrated with Advanced Custom Fields (ACF) to populate technical specs without manual mapping.

---

## 🛠️ Components
-   **WB-Product-Importer**: Core plugin file and UI handlers.
-   **Includes/Data-Parser**: Optimized PHP logic for reading and mapping raw JSON/CSV inputs.
-   **Includes/ACF-Manager**: Strategy for bulk-populating ACF specifications.

---

## 🚀 Usage
1.  Upload the `wb-product-importer` folder to your `/wp-content/plugins/` directory.
2.  Activate the plugin in the WordPress Dashboard.
3.  Upload your data source to the `data/` directory.
4.  Configure the mapping in the plugin settings and start the import.

---

## 💸 Technical Debt Liquidated
- **Reduced Migration Time**: What previously took days of manual work is now an automated 15-minute process.
- **Improved Data SEO**: Ensures all imported assets have proper Alt-Text and hierarchical structures from day one.

---

## 🤝 Contributing
Open for contributions to support more niche e-commerce platforms (BigCommerce, Shopify) as source feeders.

---

**Built for the high-scale WP engineer. 🛠️**
