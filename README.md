# WooCommerce Max Membership Rules

A WooCommerce Memberships & Min/Max Quantity extension to allow a max amount of items allowed per order, per membership level, which are set in an individual product's settings.

## Requirements
 - WooCommerce, [WooCommerce Min/Max Quantities Plugin](https://woocommerce.com/products/minmax-quantities/), and [WooCommerce Memberships Plugin](https://woocommerce.com/products/woocommerce-memberships/)
 - PHP 7+ (not actually, but don't be _that_ person)
 
## Installation
`composer require craftpeak/woocommerce-max-membership-rules`

(or do it the old fashioned way)

## Setup
If you want to limit a product's max purchase amount based on the purchaser's membership level, just edit that level's max quantity in the product's "general" settings. These settings will override any existing max quantity setting.
