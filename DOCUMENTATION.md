# uften/courier — Technical Documentation

Full API reference and architectural guide for contributors and advanced users.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Enums](#enums)
3. [Data Transfer Objects (DTOs)](#data-transfer-objects)
4. [Credentials DTOs](#credentials-dtos)
5. [Contracts (Interfaces)](#contracts)
6. [Adapters](#adapters)
7. [CourierManager](#couriermanager)
8. [Exception Hierarchy](#exception-hierarchy)
9. [Adding a New Provider](#adding-a-new-provider)
10. [Testing Guide](#testing-guide)

---

## Architecture Overview

```
Facade (Courier)
    └── CourierManager                  ← factory + cache
            ├── YalidineAdapter         (Yalidine + Yalitec)
            ├── MaystroAdapter
            ├── ProcolisAdapter         (Procolis + ZR Express)
            ├── ZimouAdapter            (Zimou Express — delivery router)
            └── EcotrackAdapter         (Ecotrack + 22 sub-providers)
                        ↑
            AbstractAdapter             ← shared HTTP helpers
                        ↑
            ProviderAdapter             ← contract (interface)
            StatusNormalizer            ← contract (interface)
```

**Data flow for `createOrder()`:**

```
Application
  → CreateOrderData (DTO)
  → Adapter::createOrder(CreateOrderData)
      → maps DTO fields to provider-specific payload
      → HTTP POST to provider API
      → response decoded
      → normalizeStatus(rawStatus) → TrackingStatus enum
  → OrderData (DTO) returned to application
```

Every piece of data entering or leaving an adapter is a typed DTO. No raw arrays cross the public boundary.

---

## Enums

### `Provider`

29 cases across 5 engine groups. The enum is the single source of truth for base URL, adapter class, credential requirements, and display metadata.

```
Yalidine engine  : YALIDINE, YALITEC
Maystro          : MAYSTRO
Procolis engine  : PROCOLIS, ZREXPRESS
Zimou Express    : ZIMOU
Ecotrack engine  : ECOTRACK, ANDERSON, AREEX, BA_CONSULT, CONEXLOG,
                   COYOTE_EXPRESS, DHD, DISTAZERO, E48HR, FRETDIRECT,
                   GOLIVRI, MONO_HUB, MSM_GO, NEGMAR_EXPRESS, PACKERS,
                   PREST, RB_LIVRAISON, REX_LIVRAISON, ROCKET_DELIVERY,
                   SALVA_DELIVERY, SPEED_DELIVERY, TSL_EXPRESS, WORLDEXPRESS
```

| Method               | Returns            | Description                                            |
| -------------------- | ------------------ | ------------------------------------------------------ |
| `label()`            | `string`           | Human-readable name (delegates to `metadata()->title`) |
| `adapterClass()`     | `string`           | FQCN of the concrete adapter                           |
| `baseUrl()`          | `string`           | Provider's base API URL                                |
| `metadata()`         | `ProviderMetadata` | Full branding / metadata DTO                           |
| `isYalidineEngine()` | `bool`             | `true` for YALIDINE and YALITEC                        |
| `isEcotrackEngine()` | `bool`             | `true` for ECOTRACK and all 22 sub-providers           |
| `requiresApiId()`    | `bool`             | `true` for PROCOLIS and ZREXPRESS only                 |

---

### `TrackingStatus`

```php
enum TrackingStatus: string {
    case PENDING; case PICKED_UP; case IN_TRANSIT;
    case OUT_FOR_DELIVERY; case DELIVERED; case FAILED_DELIVERY;
    case RETURNING; case RETURNED; case CANCELLED;
    case READY_FOR_PICKUP; case EXCEPTION; case UNKNOWN;
}
```

| Method           | Returns  | Description                               |
| ---------------- | -------- | ----------------------------------------- |
| `label()`        | `string` | English label                             |
| `labelFr()`      | `string` | French label                              |
| `labelAr()`      | `string` | Arabic label                              |
| `isTerminal()`   | `bool`   | `true` for DELIVERED, RETURNED, CANCELLED |
| `isSuccessful()` | `bool`   | `true` only for DELIVERED                 |
| `isActive()`     | `bool`   | `true` while parcel is moving             |
| `color()`        | `string` | Colour hint for UI badges                 |

---

### `DeliveryType`

```php
enum DeliveryType: int {
    case HOME      = 1;
    case STOP_DESK = 2;
}
```

---

### `LabelType`

```php
enum LabelType: string {
    case PDF_BASE64 = 'pdf_base64';
    case PDF_URL    = 'pdf_url';
    case IMAGE_URL  = 'image_url';
}
```

---

## Data Transfer Objects

All DTOs are `final readonly class` — immutable by construction.

### `CreateOrderData`

The unified order-creation payload. All adapters receive this and map its fields to their own API format internally.

```php
new CreateOrderData(
    orderId:            string,        // required — your internal reference
    firstName:          string,        // required
    lastName:           string,        // required
    phone:              string,        // required — Algerian format 05/06/07XXXXXXXX
    address:            string,        // required
    toWilayaId:         int,           // required — 1-58
    toCommune:          string,        // required
    productDescription: string,        // required
    price:              float,         // required — COD amount in DZD
    deliveryType:       DeliveryType,  // default: HOME
    freeShipping:       bool,          // default: false
    hasExchange:        bool,          // default: false
    exchangeProduct:    ?string,
    stopDeskId:         ?int,          // required when deliveryType = STOP_DESK
    fromWilayaId:       ?int,          // required by Yalidine/Yalitec
    phoneAlt:           ?string,
    notes:              ?string,       // see Zimou notes convention below
    weight:             ?float,        // kg
    length:             ?float,        // cm
    width:              ?float,        // cm
    height:             ?float,        // cm
);
```

**Zimou Express notes convention:**

Zimou has three delivery tiers. `DeliveryType::HOME` maps to `"Express"` by default. To request `"Flexible"`:

```php
notes: 'zimou_delivery_type:Flexible|Your actual observation note'
```

The adapter strips the `zimou_delivery_type:Flexible` prefix and sends only the text after `|` as the `observation` field. This convention keeps `CreateOrderData` provider-agnostic while exposing the Zimou-specific option.

---

### `OrderData`

The unified response for `createOrder()` and `getOrder()`.

```php
readonly class OrderData {
    public string           $orderId;
    public string           $trackingNumber;
    public Provider         $provider;
    public TrackingStatus   $status;         // always canonical
    public string           $recipientName;
    public string           $phone;
    public string           $address;
    public int              $toWilayaId;
    public string           $toCommune;
    public float            $price;
    public ?float           $shippingFee;
    public ?string          $rawStatus;      // original provider value, for logging
    public ?string          $notes;          // free-text; Zimou uses this for partner info
    public ?CarbonImmutable $createdAt;
    public ?CarbonImmutable $updatedAt;
    public array            $raw;            // full unmodified API response

    // The toArray() output also includes:
    // 'status_label'    => string (English)
    // 'status_label_fr' => string (French)
    // 'status_label_ar' => string (Arabic)
}
```

Helper methods: `isDelivered(): bool`, `isTerminal(): bool`, `toArray(): array`, `label(): string`, `labelFr(): string`, `labelAr(): string`.
The `toArray()` method includes localized status labels based on the canonical `TrackingStatus`, and they are also available via direct helper methods on the DTO.

**Zimou-specific fields in `$raw`:**

| Key                              | Description                               |
| -------------------------------- | ----------------------------------------- |
| `tracking_code`                  | Zimou's own tracking code                 |
| `delivery_company_tracking_code` | The sub-carrier's own tracking code       |
| `tracking_partner_company`       | Name of the partner carrier dispatched to |

---

### `RateData`

```php
readonly class RateData {
    public Provider     $provider;
    public int          $toWilayaId;
    public string       $toWilayaName;
    public float        $homeDeliveryPrice;
    public float        $stopDeskPrice;
    public DeliveryType $deliveryType;
    public ?int         $fromWilayaId;
    public ?string      $fromWilayaName;
    public ?int         $estimatedDaysMin;
    public ?int         $estimatedDaysMax;
}
```

---

### `LabelData`

```php
readonly class LabelData {
    public Provider  $provider;
    public string    $trackingNumber;
    public LabelType $type;
    public ?string   $base64;
    public ?string   $url;
}
```

Static constructors: `fromBase64()`, `fromUrl()`.

`decodePdf(): string` — decodes the base64 blob to raw binary bytes. Throws `RuntimeException` if `$type !== PDF_BASE64`.

---

### `ProviderMetadata`

```php
readonly class ProviderMetadata {
    public string  $name;         // machine-readable, e.g. "ZimouExpress"
    public string  $title;        // display name, e.g. "Zimou Express"
    public string  $website;
    public string  $description;
    public ?string $logo;         // null when not publicly available
    public ?string $apiDocs;
    public ?string $support;
    public ?string $trackingUrl;  // null when no public tracking page
}
```

Available from the enum without instantiating an adapter:

```php
$meta = Provider::ZIMOU->metadata();
$meta = Provider::DHD->metadata();
```

---

## Credentials DTOs

| Class                 | Required Keys  | Used By                                      |
| --------------------- | -------------- | -------------------------------------------- |
| `YalidineCredentials` | `token`, `key` | Yalidine, Yalitec                            |
| `TokenCredentials`    | `token`        | Maystro, Zimou, Ecotrack + all sub-providers |
| `ProcolisCredentials` | `id`, `token`  | Procolis, ZR Express                         |

All have `fromArray(array $data): self` that throws `\InvalidArgumentException` on missing keys. `CourierManager` wraps these in `InvalidCredentialsConfigException`.

---

## Contracts

### `ProviderAdapter`

Every adapter must implement:

```php
public function provider(): Provider;
public function metadata(): ProviderMetadata;
public function testCredentials(): bool;
public function getRates(?int $fromWilayaId, ?int $toWilayaId): array;
public function getCreateOrderValidationRules(): array;
public function createOrder(CreateOrderData $data): OrderData;
public function getOrder(string $trackingNumber): OrderData;
public function cancelOrder(string $trackingNumber): bool;
public function getLabel(string $trackingNumber): LabelData;
```

`AbstractAdapter` provides default `UnsupportedOperationException`-throwing stubs for `getRates()` and `cancelOrder()`, so concrete adapters only override what their provider supports.

### `StatusNormalizer`

```php
public function normalizeStatus(string $rawStatus): TrackingStatus;
```

Must always return a `TrackingStatus` — never throw; return `TrackingStatus::UNKNOWN` for unrecognised strings.

---

## Adapters

### `AbstractAdapter`

Base class. Constructor:

```php
public function __construct(
    protected readonly string $baseUrl,
    protected readonly array  $defaultHeaders = [],
    protected readonly int    $timeoutSeconds = 30,
    ?Client $httpClient = null,  // injectable for testing
)
```

HTTP helpers: `get()`, `post()`, `postForm()`, `put()`, `delete()`, `requestRaw()`.

All JSON-returning helpers return `array<string, mixed>` and translate HTTP errors to typed exceptions. `requestRaw()` returns the raw response body string — used for PDF label endpoints.

Utility methods: `dig(array, string ...$keys): mixed` (safe nested access), `parseDate(?string): ?CarbonImmutable`.

---

### Concrete Adapters

| Adapter           | Engine         |  Status mapping  |        Label        | Notes                                                                                                      |
| ----------------- | -------------- | :--------------: | :-----------------: | ---------------------------------------------------------------------------------------------------------- |
| `YalidineAdapter` | Yalidine       |    20 strings    |   ✅ URL + Base64   | Accepts `Provider` param — covers YALIDINE and YALITEC. `getRates()` requires `$fromWilayaId`.             |
| `MaystroAdapter`  | Standalone     |    15 strings    | ✅ Base64 (raw PDF) | Auth: `Token <token>`. Delivery type mapping is inverted (0=home, 1=stop desk). Exposes `createProduct()`. |
| `ProcolisAdapter` | Procolis       |    14 strings    |      ❌ throws      | Covers PROCOLIS and ZREXPRESS via `$resolvedProvider` param. Auth params appended to each request.         |
| `ZimouAdapter`    | Zimou (router) | 54 IDs + strings | ✅ Base64 (raw PDF) | See below.                                                                                                 |
| `EcotrackAdapter` | Ecotrack       |    18 strings    |   ✅ URL + Base64   | Accepts `Provider` param — covers ECOTRACK + all 22 sub-providers by subdomain.                            |

---

### `ZimouAdapter` — Deep Dive

Zimou Express is a **delivery router**: it accepts a package, assigns it to the best available partner carrier, and returns both its own tracking code and the sub-carrier's code.

**Auth:** `Authorization: Bearer {token}`

**Status normalisation strategy:**

Zimou uses integer `status_id` values (54 defined IDs), which are more stable than names. The adapter implements two normalisation methods:

```php
// Primary — integer from API response (preferred)
public function normalizeStatusById(int $statusId): TrackingStatus

// Secondary — string (used by StatusNormalizer contract; handles both
//             numeric strings like "8" and French names like "livré")
public function normalizeStatus(string $rawStatus): TrackingStatus
```

**`getOrder()` dual-path logic:**

```
trackingNumber is numeric?
  YES → GET /v3/packages/{id}          (most efficient — direct lookup)
  NO  → GET /v3/packages/status?packages[]={trackingNumber}
```

**Partner carrier surfacing:**

After `createOrder()` or `getOrder()`, the partner carrier details are available in:

```php
$order->notes;                                     // "Via: Yalidine | Partner tracking: YALI-99999"
$order->raw['tracking_partner_company'];           // "Yalidine"
$order->raw['delivery_company_tracking_code'];     // "YALI-99999"
```

**Delivery type resolution:**

| `CreateOrderData`                                                    | Zimou API field  |
| -------------------------------------------------------------------- | ---------------- |
| `DeliveryType::HOME` (default)                                       | `"Express"`      |
| `DeliveryType::HOME` + notes prefix `"zimou_delivery_type:Flexible"` | `"Flexible"`     |
| `DeliveryType::STOP_DESK`                                            | `"Point relais"` |

**Error handling:**

Zimou returns HTTP 201 even for validation failures, with `{"error": 1, "message": "..."}`. The adapter detects this and throws `CourierException` with the exact API message.

---

## CourierManager

```php
// Resolve from config
$adapter = app(CourierManager::class)->provider(Provider::ZIMOU);

// Runtime credentials
$adapter = app(CourierManager::class)->provider(Provider::ZIMOU, [
    'token' => 'my-bearer-token',
]);

// By string value
$adapter = app(CourierManager::class)->via('zimou');

// Metadata without adapter instantiation
$meta = app(CourierManager::class)->metadataFor(Provider::ZIMOU);

// All 29 providers' metadata
$all = app(CourierManager::class)->allMetadata(); // array<string, ProviderMetadata>

// Custom driver
app(CourierManager::class)->extend(Provider::ZIMOU, fn() => new MyAdapter());

// Flush cache
app(CourierManager::class)->flushResolved();
```

Resolved adapters are cached by `provider.value + credential hash` for the lifetime of the request.

---

## Exception Hierarchy

```
\RuntimeException
  └── CourierException
        ├── AuthenticationException           (HTTP 401/403)
        ├── OrderNotFoundException            (tracking number not found)
        ├── UnsupportedOperationException     (operation not supported by provider)
        └── InvalidCredentialsConfigException (bad config structure)
```

`CourierException` is also thrown directly when:

-   Zimou returns `{"error": 1}` on HTTP 201.
-   A label endpoint returns an empty or unrecognisable response.

---

## Adding a New Provider

See [CONTRIBUTING.md](CONTRIBUTING.md#adding-a-new-provider-adapter) for the complete step-by-step guide.

**TL;DR checklist:**

1. Add enum case to `Provider` (+ `adapterClass()`, `baseUrl()`, `metadata()`, `isEcotrackEngine()` exclusion if standalone).
2. Create Credentials DTO if needed.
3. Create `MyAdapter extends AbstractAdapter`.
4. Implement `STATUS_MAP` + `normalizeStatus()`.
5. Implement all `ProviderAdapter` methods (stub unsupported ones via parent).
6. Wire in `CourierManager::make()`.
7. Add to `config/courier.php`.
8. Update `tests/TestCase.php` to seed credentials.
9. Write `tests/Feature/Adapters/MyAdapterTest.php`.
10. Update README capability table, DOCUMENTATION.md adapter table, and CHANGELOG.md.

---

## Testing Guide

Tests use [Pest](https://pestphp.com) and [Orchestra Testbench](https://github.com/orchestral/testbench).

**Never hit real provider APIs in tests.** Use Guzzle's `MockHandler`:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock    = new MockHandler([
    new Response(200, [], json_encode(['data' => [...]])),
]);
$client  = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
$adapter = new ZimouAdapter(new TokenCredentials('test-token'), httpClient: $client);
```

**Verifying request payloads with Guzzle history middleware:**

```php
$container = [];
$history   = \GuzzleHttp\Middleware::history($container);
$stack     = HandlerStack::create(new MockHandler([new Response(201, [], '{}')]));
$stack->push($history);
$client    = new Client(['handler' => $stack, 'http_errors' => false]);
$adapter   = new ZimouAdapter(new TokenCredentials('t'), $client);

$adapter->createOrder($data);

$sentBody = json_decode((string) $container[0]['request']->getBody(), true);
expect($sentBody['delivery_type'])->toBe('Flexible');
```

Run tests:

```bash
composer test                       # all tests
composer test -- --filter Zimou     # single adapter
composer test -- --filter status    # specific group
composer test:coverage              # with Xdebug coverage
```
