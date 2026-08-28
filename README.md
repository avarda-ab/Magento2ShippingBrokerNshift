# Avarda_ShippingBrokerNshift

nShift (formerly Unifaun) Delivery Checkout provider for `Avarda_ShippingBroker`.
Avarda's checkout renders the nShift widget, the customer picks a delivery option
and — where the carrier supports it — a pickup point, and the choice comes back
to Magento as the broker carrier's shipping rate. Registers as the `nshift`
provider (an alternative to the partner provider).

## Requirements

- `avarda/shipping-broker`
- `avarda/checkout3`

## Configuration

Under **Stores → Configuration → Sales → Payment Methods → Avarda Checkout V3 →
Avarda Shipping Broker**, set **Provider** to `nshift`. The module ships that as
the default value, so an install with no other provider needs no configuration
change.

Everything else — the nShift account, the carriers, their prices and the widget
appearance — is configured in the Avarda merchant portal, not in Magento.

## Cart attributes sent to nShift

Each rate request carries the cart's current state as nShift custom attributes,
so carrier rules in the portal can react to it:

| Attribute | Notes |
|---|---|
| `freefreight` | `true` when the cart qualifies for Magento free shipping |
| `discount` | Placeholder, always `1` — cart discounts are not passed on yet |
| `weight` | Total cart weight in grams, converted from the store's weight unit (kg or lbs) |

## Selected option

The option the customer picks in the widget becomes the broker carrier's rate,
including its price, tax rate and carrier/service identifiers. Before anything is
selected the cheapest available option is used, so the cart always has a shipping
cost to show.

## Pickup points

When the selected option is a pickup point (parcel locker, service point), the
point is copied onto the order's shipping address as `nshift_pickup_point` when
the order is placed, where label printing and order exports can read it. The
column is added to `sales_order_address` and exposed as an order address
extension attribute.

## Notes and limitations

- The nShift widget script is loaded from `api.unifaun.com` on the Avarda
  checkout pages; the hosts it needs are whitelisted for CSP. A compatibility
  shim keeps RequireJS from claiming the script as an AMD module.
- Weight uses the default store's weight unit for the whole cart; per-store
  weight units are not resolved separately.
- The pickup point is stored only on the order, and only for orders whose quote
  still exists at the time the order is saved.
