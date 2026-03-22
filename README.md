# uften/courier

> A modern, strictly-typed Laravel package for integrating **most** Algerian shipping providers — built on PHP 8.4, backed by enums, readonly DTOs, and a clean adapter pattern.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue?logo=php)](https://php.net) [![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-red?logo=laravel)](https://laravel.com) [![Tests](https://img.shields.io/github/actions/workflow/status/uften/courier/tests.yml?branch=main&label=tests)](https://github.com/uften/courier/actions) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow)](LICENSE.md)

---

## Why uften/courier?

Every Algerian courier has a different API shape, different field names, and different tracking status strings (`"Livré"`, `"delivered"`, `"SORTIE EN LIVRAISON"` …). Integrating more than one means writing glue code over and over.

**uften/courier** solves this with three principles:

1. **Unified DTOs** — `CreateOrderData`, `OrderData`, `RateData`, `LabelData`. One shape, every provider.
2. **Canonical status enum** — `TrackingStatus` with 12 values. Each adapter normalises its raw strings or IDs into this dictionary. Your code only ever sees `TrackingStatus::DELIVERED`.
3. **Swappable adapters** — swap providers with a single enum change. Zero application-code changes.

---

## Supported Providers — 29 total

### Yalidine engine

| Provider | Enum                 | Base URL           |
| -------- | -------------------- | ------------------ |
| Yalidine | `Provider::YALIDINE` | `api.yalidine.app` |
| Yalitec  | `Provider::YALITEC`  | `api.yalitec.me`   |

### Maystro (standalone)

| Provider         | Enum                | Base URL                           |
| ---------------- | ------------------- | ---------------------------------- |
| Maystro Delivery | `Provider::MAYSTRO` | `backend.maystro-delivery.com/api` |

### Procolis engine

| Provider   | Enum                  | Base URL              |
| ---------- | --------------------- | --------------------- |
| Procolis   | `Provider::PROCOLIS`  | `procolis.com/api_v1` |
| ZR Express | `Provider::ZREXPRESS` | `procolis.com/api_v1` |

### Zimou Express (delivery router)

| Provider      | Enum              | Base URL            |
| ------------- | ----------------- | ------------------- |
| Zimou Express | `Provider::ZIMOU` | `zimou.express/api` |

### Ecotrack engine — 23 providers sharing one API surface

| Provider           | Enum                        | Subdomain                     |
| ------------------ | --------------------------- | ----------------------------- |
| Ecotrack (generic) | `Provider::ECOTRACK`        | `ecotrack.dz`                 |
| Anderson Delivery  | `Provider::ANDERSON`        | `anderson.ecotrack.dz`        |
| Areex              | `Provider::AREEX`           | `areex.ecotrack.dz`           |
| BA Consult         | `Provider::BA_CONSULT`      | `bacexpress.ecotrack.dz`      |
| Conexlog (UPS)     | `Provider::CONEXLOG`        | `app.conexlog-dz.com`         |
| Coyote Express     | `Provider::COYOTE_EXPRESS`  | `coyoteexpressdz.ecotrack.dz` |
| DHD                | `Provider::DHD`             | `dhd.ecotrack.dz`             |
| Distazero          | `Provider::DISTAZERO`       | `distazero.ecotrack.dz`       |
| 48Hr Livraison     | `Provider::E48HR`           | `48hr.ecotrack.dz`            |
| FRET.Direct        | `Provider::FRETDIRECT`      | `fret.ecotrack.dz`            |
| GOLIVRI            | `Provider::GOLIVRI`         | `golivri.ecotrack.dz`         |
| Mono Hub           | `Provider::MONO_HUB`        | `mono.ecotrack.dz`            |
| MSM Go             | `Provider::MSM_GO`          | `msmgo.ecotrack.dz`           |
| Negmar Express     | `Provider::NEGMAR_EXPRESS`  | `negmar.ecotrack.dz`          |
| Packers            | `Provider::PACKERS`         | `packers.ecotrack.dz`         |
| Prest              | `Provider::PREST`           | `prest.ecotrack.dz`           |
| RB Livraison       | `Provider::RB_LIVRAISON`    | `rblivraison.ecotrack.dz`     |
| Rex Livraison      | `Provider::REX_LIVRAISON`   | `rex.ecotrack.dz`             |
| Rocket Delivery    | `Provider::ROCKET_DELIVERY` | `rocket.ecotrack.dz`          |
| Salva Delivery     | `Provider::SALVA_DELIVERY`  | `salvadelivery.ecotrack.dz`   |
| Speed Delivery     | `Provider::SPEED_DELIVERY`  | `speeddelivery.ecotrack.dz`   |
| TSL Express        | `Provider::TSL_EXPRESS`     | `tsl.ecotrack.dz`             |
| WorldExpress       | `Provider::WORLDEXPRESS`    | `worldexpress.ecotrack.dz`    |

---

### Supported Methods

| Method                            | Yalidine / Yalitec | Maystro | Procolis / ZR Express | Zimou Express | Ecotrack engine (all 23) |
| --------------------------------- | :----------------: | :-----: | :-------------------: | :-----------: | :----------------------: |
| `testCredentials()`               |         ✅         |   ✅    |          ✅           |      ✅       |            ✅            |
| `metadata()`                      |         ✅         |   ✅    |          ✅           |      ✅       |            ✅            |
| `getRates()`                      |       ✅ \*        |   ❌    |          ✅           |      ✅       |            ✅            |
| `getCreateOrderValidationRules()` |         ✅         |   ✅    |          ✅           |      ✅       |            ✅            |
| `createOrder()`                   |         ✅         |   ✅    |          ✅           |      ✅       |            ✅            |
| `getOrder()`                      |         ✅         |   ✅    |          ✅           |    ✅ \*\*    |            ✅            |
| `cancelOrder()`                   |         ➖         |   ➖    |          ➖           |      ➖       |            ➖            |
| `getLabel()`                      |         ✅         |   ✅    |          ❌           |      ✅       |            ✅            |
| `createProduct()` \*\*\*          |         ❌         |   ✅    |          ❌           |      ❌       |            ❌            |

> \* Yalidine `getRates()` requires `$fromWilayaId`.  
> \*\* `getOrder()` accepts either the **Zimou integer package ID** or the **`tracking_code`** string.  
> \*\*\* Maystro-only method, not part of the `ProviderAdapter` contract — type-hint `MaystroAdapter` directly.  
> ➖ = planned / unknown support.

---

## Requirements

-   PHP **8.4+**
-   Laravel **11** or **12**

## Installation

```bash
composer require uften/courier
```

Publish the config:

```bash
php artisan vendor:publish --tag=courier-config
```

---

## Configuration

Add your credentials to `.env`:

```dotenv
# Yalidine engine
YALIDINE_API_TOKEN=your-api-id
YALIDINE_API_KEY=your-api-key
YALITEC_API_TOKEN=your-api-id
YALITEC_API_KEY=your-api-key

# Maystro
MAYSTRO_API_TOKEN=your-token

# Procolis / ZR Express
PROCOLIS_ID=your-id
PROCOLIS_TOKEN=your-token
ZREXPRESS_ID=your-id
ZREXPRESS_TOKEN=your-token

# Zimou Express
ZIMOU_API_TOKEN=your-bearer-token

# Ecotrack-engine providers (one token per carrier account)
ECOTRACK_API_TOKEN=your-token
DHD_API_TOKEN=your-token
CONEXLOG_API_TOKEN=your-token
# ... see config/courier.php for the full list
```

---

## Usage

### Resolve a provider

```php
use Uften\Courier\Facades\Courier;
use Uften\Courier\Enums\Provider;

$zimou    = Courier::provider(Provider::ZIMOU);
$yalidine = Courier::provider(Provider::YALIDINE);
$dhd      = Courier::via('dhd');

// Runtime credentials override
$zimou = Courier::provider(Provider::ZIMOU, ['token' => 'my-token']);
```

### Create an order

```php
use Uften\Courier\Data\CreateOrderData;
use Uften\Courier\Enums\DeliveryType;

$order = Courier::provider(Provider::ZIMOU)->createOrder(
    new CreateOrderData(
        orderId:            'MY-ORD-069',
        firstName:          'Mohammed',
        lastName:           'A. ALLAL',
        phone:              '0669096909',
        address:            '69 Rue Hattab Amar',
        toWilayaId:         '09',
        toCommune:          'Blida',
        productDescription: 'Smartphone Samsung Galaxy S25',
        price:              120000.0,
        deliveryType:       DeliveryType::HOME,
        weight: 1, // required for zimou
    )
);

echo $order->trackingNumber; // Zimou's own tracking code, e.g. "ZM-ABC123"
echo $order->raw['id']; // Zimou's own package ID
echo $order->status->value;  // "pending"
echo $order->notes;          // "Via: Yalidine | Partner tracking: YALI-99999"
```

#### Zimou: requesting "Flexible" delivery

Zimou supports three delivery tiers: **Express** (default for `HOME`), **Flexible** (cheaper, slower), and **Point relais** (`STOP_DESK`). To request Flexible, embed the hint in the `notes` field:

```php
new CreateOrderData(
    // ...
    deliveryType: DeliveryType::HOME,
    notes: 'zimou_delivery_type:Flexible|Leave at door if absent',
)
```

The adapter strips the prefix and uses `"Flexible"` for the API call. The remaining text after `|` is sent as the observation note.

### Track a shipment

```php
use Uften\Courier\Enums\TrackingStatus;

// By Zimou integer package ID
$order = Courier::provider(Provider::ZIMOU)->getOrder('2632165');

// Status is always canonical
if ($order->status->value === TrackingStatus::DELIVERED) { /* ... */ }
if ($order->status->isTerminal()) { /* stop polling */ }

// Access the partner carrier details (not always available)
echo $order->raw['tracking_partner_company'];        // "Yalidine"
echo $order->raw['delivery_company_tracking_code'];  // "YALI-99999"
```

### Access provider metadata (no API call needed)

```php
$meta = Provider::ZIMOU->metadata();
echo $meta->title;       // "Zimou Express"
echo $meta->website;     // "https://zimou.express"
echo $meta->apiDocs;     // "https://zimou.express/api/docs"

// All 29 providers at once — perfect for building provider-selection UIs
$all = Courier::allMetadata(); // array<string, ProviderMetadata>
```

### Get shipping rates

```php
// Zimou returns your account's configured prices
$rates = Courier::provider(Provider::ZIMOU)->getRates();

foreach ($rates as $rate) {
    echo "{$rate->toWilayaName}: {$rate->homeDeliveryPrice} DZD (home) / {$rate->stopDeskPrice} DZD (stop desk)";
}
```

### Fetch a label

```php
use Uften\Courier\Enums\LabelType;

$label = Courier::provider(Provider::ZIMOU)->getLabel('2632165');

// Zimou always returns PDF_BASE64
return response($label->decodePdf(), 200, [
    'Content-Type'        => 'application/pdf',
    'Content-Disposition' => 'inline; filename="label.pdf"',
]);
```

---

## TrackingStatus Dictionary

| Case               | Value              | Meaning                    |
| ------------------ | ------------------ | -------------------------- |
| `PENDING`          | `pending`          | Created, not yet collected |
| `PICKED_UP`        | `picked_up`        | Collected from sender      |
| `IN_TRANSIT`       | `in_transit`       | Moving between hubs        |
| `OUT_FOR_DELIVERY` | `out_for_delivery` | With delivery agent        |
| `DELIVERED`        | `delivered`        | Successfully delivered ✓   |
| `FAILED_DELIVERY`  | `failed_delivery`  | Attempt failed             |
| `RETURNING`        | `returning`        | Heading back to sender     |
| `RETURNED`         | `returned`         | Back at sender             |
| `CANCELLED`        | `cancelled`        | Cancelled before shipment  |
| `READY_FOR_PICKUP` | `ready_for_pickup` | At stop desk / relay       |
| `EXCEPTION`        | `exception`        | Lost, damaged, blocked     |
| `UNKNOWN`          | `unknown`          | Unmapped raw status        |

---

## Error Handling

```php
use Uften\Courier\Exceptions\{
    AuthenticationException,
    CourierException,
    OrderNotFoundException,
    UnsupportedOperationException
};

try {
    $order = Courier::provider(Provider::ZIMOU)->getOrder($orderId);
} catch (OrderNotFoundException $e) {       // order number not found
} catch (AuthenticationException $e) {      // bad credentials
} catch (UnsupportedOperationException $e) { // feature not supported
} catch (CourierException $e) {             // any other provider error
    // For Zimou: also thrown when the API returns {"error": 1, "message": "..."}
}
```

---

## Extending: Custom Adapter at Runtime

```php
Courier::extend(Provider::ZIMOU, function (?array $credentials) {
    return new MyCustomZimouAdapter($credentials);
});
```

---

## Testing

```bash
composer test           # Pest suite
composer test:coverage  # with Xdebug
composer format         # Laravel Pint
```

---

## Disclaimer

-   Not officially affiliated with or endorsed by any shipping provider.
-   Verify all providers are authorised by [ARPCE](https://www.arpce.dz/ar/service/post-sd#operators) before use.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Credits

Thanks to [Piteur Studio](https://github.com/PiteurStudio/CourierDZ) for providing the primary endpoints for this package.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE.md](LICENSE.md).
