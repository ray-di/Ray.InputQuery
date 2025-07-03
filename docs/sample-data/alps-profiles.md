# ALPS (Application-Level Profile Semantics) Sample Data

## 1. ブログシステム ALPS プロファイル

```json
{
    "$schema": "https://alps-io.github.io/schemas/alps.json",
    "alps": {
        "version": "1.0",
        "doc": { "value": "Blog Management System API Profile" },
        "descriptor": [
            { "id": "title", "type": "semantic", "title": "Title", "def": "https://schema.org/headline" },
            { "id": "content", "type": "semantic", "title": "Content", "def": "https://schema.org/articleBody" },
            { "id": "excerpt", "type": "semantic", "title": "Excerpt", "def": "https://schema.org/abstract" },
            { "id": "slug", "type": "semantic", "title": "Slug", "def": "https://schema.org/url" },
            { "id": "publishedAt", "type": "semantic", "title": "Published At", "def": "https://schema.org/datePublished" },
            { "id": "status", "type": "semantic", "title": "Status", "def": "https://schema.org/CreativeWorkStatus" },
            { "id": "tags", "type": "semantic", "title": "Tags", "def": "https://schema.org/keywords" },
            { "id": "imageUrl", "type": "semantic", "title": "Image URL", "def": "https://schema.org/contentUrl" },
            { "id": "imageAlt", "type": "semantic", "title": "Image Alt", "def": "https://schema.org/description" },
            { "id": "imageCaption", "type": "semantic", "title": "Image Caption", "def": "https://schema.org/caption" },
            { "id": "metaTitle", "type": "semantic", "title": "Meta Title", "def": "https://schema.org/headline" },
            { "id": "metaDescription", "type": "semantic", "title": "Meta Description", "def": "https://schema.org/description" },
            { "id": "metaKeywords", "type": "semantic", "title": "Meta Keywords", "def": "https://schema.org/keywords" },
            { "id": "authorId", "type": "semantic", "title": "Author ID", "def": "https://schema.org/identifier" },
            { "id": "authorName", "type": "semantic", "title": "Author Name", "def": "https://schema.org/name" },
            { "id": "authorEmail", "type": "semantic", "title": "Author Email", "def": "https://schema.org/email" },
            { "id": "authorBio", "type": "semantic", "title": "Author Bio", "def": "https://schema.org/description" },
            { "id": "categoryId", "type": "semantic", "title": "Category ID", "def": "https://schema.org/identifier" },
            { "id": "categoryName", "type": "semantic", "title": "Category Name", "def": "https://schema.org/name" },
            { "id": "categorySlug", "type": "semantic", "title": "Category Slug", "def": "https://schema.org/url" },

            {
                "id": "BlogPost", "type": "semantic", "title": "Blog Post", "descriptor": [
                { "href": "#title" },
                { "href": "#content" },
                { "href": "#excerpt" },
                { "href": "#slug" },
                { "href": "#publishedAt" },
                { "href": "#status" },
                { "href": "#tags" },
                { "href": "#imageUrl" },
                { "href": "#imageAlt" },
                { "href": "#imageCaption" },
                { "href": "#metaTitle" },
                { "href": "#metaDescription" },
                { "href": "#metaKeywords" },
                { "href": "#authorId" },
                { "href": "#authorName" },
                { "href": "#authorEmail" },
                { "href": "#authorBio" },
                { "href": "#categoryId" },
                { "href": "#categoryName" },
                { "href": "#categorySlug" },
                { "href": "#doCreatePost" },
                { "href": "#doUpdatePost" },
                { "href": "#doDeletePost" }
            ]
            },

            {
                "id": "BlogList", "type": "semantic", "title": "Blog List", "descriptor": [
                { "href": "#BlogPost" },
                { "href": "#goBlogPost" }
            ]
            },

            {
                "id": "goBlogPost", "type": "safe", "title": "View Blog Post", "rt": "#BlogPost"
            },
            {
                "id": "doCreatePost", "type": "unsafe", "title": "Create Blog Post", "rt": "#BlogPost", "descriptor": [
                { "href": "#title" },
                { "href": "#content" }
            ]
            },
            {
                "id": "doUpdatePost", "type": "idempotent", "title": "Update Blog Post", "rt": "#BlogPost", "descriptor": [
                { "href": "#title" },
                { "href": "#content" }
            ]
            },
            {
                "id": "doDeletePost", "type": "idempotent", "title": "Delete Blog Post", "rt": "#BlogList", "descriptor": [
                { "href": "#slug" }
            ]
            }
        ]
    }
}
```

## 2. Eコマース商品管理 ALPS プロファイル

```json
{
    "$schema": "https://alps-io.github.io/schemas/alps.json",
    "alps": {
        "version": "1.0",
        "doc": { "value": "E-commerce Product Management API Profile" },
        "descriptor": [
            { "id": "productName", "type": "semantic", "title": "Product Name", "def": "https://schema.org/name" },
            { "id": "productDescription", "type": "semantic", "title": "Product Description", "def": "https://schema.org/description" },
            { "id": "productSku", "type": "semantic", "title": "Product SKU", "def": "https://schema.org/sku" },
            { "id": "brand", "type": "semantic", "title": "Brand", "def": "https://schema.org/brand" },
            { "id": "model", "type": "semantic", "title": "Model", "def": "https://schema.org/model" },
            { "id": "basePrice", "type": "semantic", "title": "Base Price", "def": "https://schema.org/price" },
            { "id": "salePrice", "type": "semantic", "title": "Sale Price", "def": "https://schema.org/salePrice" },
            { "id": "currency", "type": "semantic", "title": "Currency", "def": "https://schema.org/priceCurrency" },
            { "id": "taxRate", "type": "semantic", "title": "Tax Rate", "def": "https://schema.org/valueAddedTaxIncluded" },
            { "id": "stockQuantity", "type": "semantic", "title": "Stock Quantity", "def": "https://schema.org/amount" },
            { "id": "trackInventory", "type": "semantic", "title": "Track Inventory", "def": "https://schema.org/inStock" },
            { "id": "lowStockThreshold", "type": "semantic", "title": "Low Stock Threshold", "def": "https://schema.org/InventoryLevel" },
            { "id": "allowBackorder", "type": "semantic", "title": "Allow Backorder", "def": "https://schema.org/backorderable" },
            { "id": "categoryId", "type": "semantic", "title": "Category ID", "def": "https://schema.org/identifier" },
            { "id": "categoryName", "type": "semantic", "title": "Category Name", "def": "https://schema.org/name" },
            { "id": "isPrimaryCategory", "type": "semantic", "title": "Is Primary Category", "def": "https://schema.org/mainEntity" },
            { "id": "weight", "type": "semantic", "title": "Weight", "def": "https://schema.org/weight" },
            { "id": "length", "type": "semantic", "title": "Length", "def": "https://schema.org/width" },
            { "id": "width", "type": "semantic", "title": "Width", "def": "https://schema.org/width" },
            { "id": "height", "type": "semantic", "title": "Height", "def": "https://schema.org/height" },
            { "id": "color", "type": "semantic", "title": "Color", "def": "https://schema.org/color" },
            { "id": "size", "type": "semantic", "title": "Size", "def": "https://schema.org/size" },
            { "id": "material", "type": "semantic", "title": "Material", "def": "https://schema.org/material" },
            { "id": "imageUrl", "type": "semantic", "title": "Image URL", "def": "https://schema.org/contentUrl" },
            { "id": "imageAltText", "type": "semantic", "title": "Image Alt Text", "def": "https://schema.org/description" },
            { "id": "isPrimaryImage", "type": "semantic", "title": "Is Primary Image", "def": "https://schema.org/image" },
            { "id": "sortOrder", "type": "semantic", "title": "Sort Order", "def": "https://schema.org/position" },
            { "id": "requiresShipping", "type": "semantic", "title": "Requires Shipping", "def": "https://schema.org/shippingDetails" },
            { "id": "shippingClass", "type": "semantic", "title": "Shipping Class", "def": "https://schema.org/shippingSettingsLink" },
            { "id": "handlingTime", "type": "semantic", "title": "Handling Time", "def": "https://schema.org/handlingTime" },
            { "id": "freeShippingThreshold", "type": "semantic", "title": "Free Shipping Threshold", "def": "https://schema.org/eligibleTransactionVolume" },

            {
                "id": "Product", "type": "semantic", "title": "Product", "descriptor": [
                { "href": "#productName" },
                { "href": "#productDescription" },
                { "href": "#productSku" },
                { "href": "#brand" },
                { "href": "#model" },
                { "href": "#basePrice" },
                { "href": "#salePrice" },
                { "href": "#currency" },
                { "href": "#taxRate" },
                { "href": "#stockQuantity" },
                { "href": "#trackInventory" },
                { "href": "#lowStockThreshold" },
                { "href": "#allowBackorder" },
                { "href": "#categoryId" },
                { "href": "#categoryName" },
                { "href": "#isPrimaryCategory" },
                { "href": "#weight" },
                { "href": "#length" },
                { "href": "#width" },
                { "href": "#height" },
                { "href": "#color" },
                { "href": "#size" },
                { "href": "#material" },
                { "href": "#imageUrl" },
                { "href": "#imageAltText" },
                { "href": "#isPrimaryImage" },
                { "href": "#sortOrder" },
                { "href": "#requiresShipping" },
                { "href": "#shippingClass" },
                { "href": "#handlingTime" },
                { "href": "#freeShippingThreshold" },
                { "href": "#doCreateProduct" },
                { "href": "#doUpdateProduct" },
                { "href": "#doDeleteProduct" }
            ]
            },

            {
                "id": "ProductList", "type": "semantic", "title": "Product List", "descriptor": [
                { "href": "#Product" },
                { "href": "#goProduct" }
            ]
            },

            { "id": "goProduct", "type": "safe", "title": "View Product", "rt": "#Product" },
            { "id": "doCreateProduct", "type": "unsafe", "title": "Create Product", "rt": "#Product", "descriptor": [
                { "href": "#productName" },
                { "href": "#productSku" }
            ] },
            { "id": "doUpdateProduct", "type": "idempotent", "title": "Update Product", "rt": "#Product", "descriptor": [
                { "href": "#productName" },
                { "href": "#productDescription" }
            ] },
            { "id": "doDeleteProduct", "type": "idempotent", "title": "Delete Product", "rt": "#ProductList", "descriptor": [
                { "href": "#productSku" }
            ] }
        ]
    }
}

```

## 3. ユーザー管理システム ALPS プロファイル

```xml
<alps version="1.0"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="https://alps-io.github.io/schemas/alps.xsd">
<doc>User Management System API Profile</doc>

<!-- Ontology -->
<descriptor id="firstName" type="semantic" title="First Name" def="https://schema.org/givenName"/>
<descriptor id="lastName" type="semantic" title="Last Name" def="https://schema.org/familyName"/>
<descriptor id="email" type="semantic" title="Email" def="https://schema.org/email"/>
<descriptor id="phoneNumber" type="semantic" title="Phone Number" def="https://schema.org/telephone"/>
<descriptor id="birthDate" type="semantic" title="Birth Date" def="https://schema.org/birthDate"/>
<descriptor id="gender" type="semantic" title="Gender" def="https://schema.org/gender"/>
<descriptor id="username" type="semantic" title="Username" def="https://schema.org/identifier"/>
<descriptor id="password" type="semantic" title="Password"/>
<descriptor id="passwordConfirm" type="semantic" title="Password Confirm"/>
<descriptor id="preferredLanguage" type="semantic" title="Preferred Language" def="https://schema.org/inLanguage"/>
<descriptor id="postalCode" type="semantic" title="Postal Code" def="https://schema.org/postalCode"/>
<descriptor id="prefecture" type="semantic" title="Prefecture" def="https://schema.org/addressRegion"/>
<descriptor id="city" type="semantic" title="City" def="https://schema.org/addressLocality"/>
<descriptor id="streetAddress" type="semantic" title="Street Address" def="https://schema.org/streetAddress"/>
<descriptor id="building" type="semantic" title="Building"/>
<descriptor id="emailNewsletter" type="semantic" title="Email Newsletter"/>
<descriptor id="smsNotification" type="semantic" title="SMS Notification"/>
<descriptor id="marketingConsent" type="semantic" title="Marketing Consent"/>
<descriptor id="profileVisibility" type="semantic" title="Profile Visibility"/>
<descriptor id="dataCollectionConsent" type="semantic" title="Data Collection Consent"/>
<descriptor id="termsAgreement" type="semantic" title="Terms Agreement"/>

<!-- Taxonomy -->
<descriptor id="User" type="semantic" title="User">
<descriptor href="#firstName"/>
<descriptor href="#lastName"/>
<descriptor href="#email"/>
<descriptor href="#phoneNumber"/>
<descriptor href="#birthDate"/>
<descriptor href="#gender"/>
<descriptor href="#username"/>
<descriptor href="#password"/>
<descriptor href="#passwordConfirm"/>
<descriptor href="#preferredLanguage"/>
<descriptor href="#postalCode"/>
<descriptor href="#prefecture"/>
<descriptor href="#city"/>
<descriptor href="#streetAddress"/>
<descriptor href="#building"/>
<descriptor href="#emailNewsletter"/>
<descriptor href="#smsNotification"/>
<descriptor href="#marketingConsent"/>
<descriptor href="#profileVisibility"/>
<descriptor href="#dataCollectionConsent"/>
<descriptor href="#termsAgreement"/>
<descriptor href="#doRegisterUser"/>
<descriptor href="#doUpdateUser"/>
<descriptor href="#doDeleteUser"/>
<descriptor href="#doAuthenticateUser"/>
</descriptor>

<!-- Choreography -->
<descriptor id="doRegisterUser" type="unsafe" rt="#User" title="Register User">
<doc>新規ユーザー登録</doc>
</descriptor>
<descriptor id="doUpdateUser" type="idempotent" rt="#User" title="Update User">
<doc>ユーザー情報更新</doc>
</descriptor>
<descriptor id="doDeleteUser" type="idempotent" rt="#User" title="Delete User">
<doc>ユーザー削除</doc>
</descriptor>
<descriptor id="doAuthenticateUser" type="unsafe" rt="#User" title="Authenticate User">
<doc>ユーザー認証</doc>
</descriptor>
</alps>

```
