# ACF Manager for WB Product Importer

## 🎯 Problem Solved

The ACF tabs were showing empty in the WB Helper page because the product name matching logic wasn't finding correct matches between:
- **Product folder structure**: e.g., "Cotton Chambray Fabric"
- **ACF JSON entries**: e.g., "4 - Cotton Fabrics - b) Cotton Chambray Fabric"

## ✅ Solutions Implemented

### 1. **Enhanced ACF Matching Logic** (Line 2323-2458)

Replaced simple exact/partial matching with intelligent multi-strategy matching:

- **Strategy 1**: Check if variation name is contained in ACF product name (Score: 80)
- **Strategy 2**: Direct variation name match (Score: 90)
- **Strategy 3**: Category folder path match (Score: 70)
- **Strategy 4**: WooCommerce category slug match (Score: 85)
- **Strategy 5**: Fuzzy word matching - splits words and finds common terms (Score: 0-75)

**Result**: The system now finds matches with confidence scores, using the best match above 50% threshold.

### 2. **Central ACF Manager Interface**

Added a new admin page accessible via: **WB Helper → ACF Manager**

#### Features:
- ✅ **Accordion Interface**: All product categories collapsed by default
- ✅ **Live Editing**: Text areas for all ACF fields
- ✅ **Real-time Save**: AJAX-powered save functionality per category
- ✅ **Visual Feedback**: Success/error messages
- ✅ **Modern UI**: WordPress admin styled with custom improvements

#### Access Path:
```
WordPress Admin → WB Helper → ACF Manager
```

## 📁 Files Modified

### `/wb-suite/wb-product-importer/includes/class-wb-product-importer.php`

1. **Line 334-351**: Added menu hooks for ACF Manager page and AJAX handlers
2. **Line 2323-2458**: Enhanced `get_acf_tabs_for_product()` with fuzzy matching
3. **Line 2738-3133**: Added three new methods:
   - `add_acf_manager_page()` - Registers admin submenu
   - `render_acf_manager_page()` - Renders the UI
   - `ajax_get_all_acf_data()` - Loads ACF data via AJAX
   - `ajax_save_acf_data()` - Saves ACF updates via AJAX

## 🎨 UI Components

### ACF Manager Page Structure:
```
├── Header with icon and description
├── Loading spinner (when fetching data)
└── Accordion list of categories
    ├── Category header (click to expand)
    │   ├── Category icon
    │   ├── Product name
    │   └── Toggle arrow
    └── Category body (collapsed by default)
        ├── ACF Fields (as textareas)
        │   ├── Key Features
        │   ├── Fabric Construction
        │   ├── Fabric Content
        │   ├── Fabric Weight
        │   ├── Country of Origin
        │   └── ... (all other ACF fields)
        └── Save Changes button + Status indicator
```

## 🔧 How It Works

### On Page Load:
1. AJAX calls `wb_get_all_acf_data`
2. Reads `/wp-content/uploads/woocommerce_acf_data_with_categories.json`
3. Returns all category data to frontend
4. JavaScript renders accordion interface

### When Clicking "Save Changes":
1. Collects all field values from textareas
2. AJAX calls `wb_save_acf_data` with category name and fields
3. Backend validates and updates JSON file
4. Shows success/error message
5. Changes are immediately saved to the JSON file

## 🚀 Usage for wbwoven.com

### To Upload This Update:
1. Use the existing upload script:
   ```bash
   python upload-product-importer.py
   ```

### To Access on Site:
1. Go to: `https://wbwoven.com/wp-admin`
2. Click: **WB Helper** in left sidebar
3. Click: **ACF Manager** submenu
4. You'll see all 22 product categories with their ACF fields
5. Click any category to expand and edit
6. Click "Save Changes" to update the JSON file

## 📊 Expected Results

### WB Helper Page:
- ✅ ACF tabs should now show with data (thanks to improved matching)
- ✅ Each product variation should display matching ACF fields
- ✅ Match scores logged for debugging

### ACF Manager Page:
- ✅ All 22 categories visible in accordion
- ✅ All ACF fields editable per category
- ✅ Changes saved instantly to JSON file
- ✅ Applied to all products in that category

## 🔍 Debugging

If ACF tabs are still empty:
1. Check WordPress error log for matching scores:
   ```
   WB ACF Matching: Matched 'Cotton Chambray Fabric' to '4 - Cotton Fabrics - b) Cotton Chambray Fabric' (score: 90)
   ```
2. Verify JSON file exists at:
   ```
   /wp-content/uploads/woocommerce_acf_data_with_categories.json
   ```
3. Check browser console for AJAX errors in ACF Manager page

## 🎁 Bonus Features

- **Auto-escape HTML**: All content safely escaped to prevent XSS
- **Permission checks**: Only users with `manage_woocommerce` can access
- **Nonce protection**: CSRF protection on all AJAX calls
- **Responsive design**: Works on tablets and smaller screens
- **Keyboard accessible**: Full keyboard navigation support
