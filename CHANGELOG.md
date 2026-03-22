# Changelog

All notable changes to `uften/courier` will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-03-22

### Added

-   **ZimouAdapter** — full integration for Zimou Express (v3 API), a delivery router that dispatches parcels to the best available partner carrier (Yalidine, DHD, Maystro, etc.).
    -   Status normalisation by **integer `status_id`** (authoritative, 54 IDs mapped) with a string name fallback — covers all statuses from `package-statuses`, including sparse IDs 83, 112–116, 118.
    -   Public `normalizeStatusById(int $statusId): TrackingStatus` method for callers who already hold the integer from the response.
    -   Partner carrier surfacing — `delivery_company_tracking_code` (the sub-carrier's own tracking code) and `tracking_partner_company` (e.g. "Yalidine", "DHD") are injected into `OrderData::$notes` and always available in `$raw`.
    -   Zimou delivery type mapping: `DeliveryType::HOME` → `"Express"` (default); pass `"zimou_delivery_type:Flexible|note"` in `CreateOrderData::$notes` to request the cheaper `"Flexible"` tier; `DeliveryType::STOP_DESK` → `"Point relais"`.
    -   Dual-path `getOrder()`: numeric strings use `GET /v3/packages/{id}`; tracking codes use `GET /v3/packages/status?packages[]=`.
    -   `getLabel()` via `POST /v3/packages/labels`, returns `LabelType::PDF_BASE64`.
    -   `getRates()` via `GET /v3/my/prices`.
    -   Zimou-specific `error:1` response detection on HTTP 201 — throws `CourierException` with the API's own message.
    -   20 Pest tests covering identity, credentials, createOrder (payload verification via Guzzle history middleware, error handling, all delivery type variants, free shipping, volumetric dimensions), getOrder (both paths + not-found), getLabel, complete status ID coverage, getRates, and CourierManager integration.
-   `Provider::ZIMOU` enum case with `baseUrl()`, `adapterClass()`, and full `metadata()` arm.
-   `ZIMOU_API_TOKEN` env variable wired in `config/courier.php`.
-   `TestCase` updated to seed Zimou credentials for all feature tests.

---

## [Unreleased] - 2026-03-15

### Added

-   **28 providers** fully supported across 4 API engines (up from 4 in v1.0.0).
-   `Provider::YALITEC` — Yalitec uses the Yalidine API engine with a different subdomain (`api.yalitec.me`). `YalidineAdapter` now accepts a `Provider $provider` constructor param so both cases share one adapter class.
-   **Ecotrack engine sub-providers (22 total)**: Anderson Delivery, Areex, BA Consult, Conexlog (UPS), Coyote Express, DHD, Distazero, 48Hr Livraison, FRET.Direct, GOLIVRI, Mono Hub, MSM Go, Negmar Express, Packers, Prest, RB Livraison, Rex Livraison, Rocket Delivery, Salva Delivery, Speed Delivery, TSL Express, WorldExpress. All share `EcotrackAdapter`; only base URL and metadata differ.
-   `EcotrackAdapter` now accepts a `Provider $provider` constructor param, so all 23 Ecotrack-engine providers are handled by a single adapter class.
-   `ProviderMetadata` readonly DTO — `name`, `title`, `website`, `description`, `logo`, `apiDocs`, `support`, `trackingUrl`. `fromArray()` strips `"#"` placeholder logos to `null`.
-   `metadata(): ProviderMetadata` added to the `ProviderAdapter` contract and implemented in `AbstractAdapter` (delegates to `Provider::metadata()`).
-   `Provider::metadata()` match arm for every one of the 28 providers — faithfully transcribed from the original CourierDZ source files.
-   `Provider::isYalidineEngine()` and `Provider::isEcotrackEngine()` boolean helpers.
-   `CourierManager::metadataFor(Provider)` — returns `ProviderMetadata` without instantiating an adapter.
-   `CourierManager::allMetadata()` — returns `array<string, ProviderMetadata>` for all providers.
-   `Courier` facade updated with `metadataFor()` and `allMetadata()` method-doc entries.
-   **MaystroAdapter** corrections from reading the original source: `Token ` auth scheme (not `Bearer`), correct endpoint paths (`stores/orders/`, `base/wilayas/`, `delivery/starter/starter_bordureau/`), correct delivery-type mapping (`0 = home`, `1 = stop desk`).
-   `MaystroAdapter::createProduct()` — Maystro-specific method for registering products in the store catalogue.
-   `AbstractAdapter::postForm()` and `AbstractAdapter::requestRaw()` HTTP helpers.
-   `ProviderMetadataTest` — 8 unit tests.
-   `ProviderTest` and `CourierManagerTest` updated to cover all 28 providers.
-   `TestCase::defineEnvironment()` auto-seeds credentials for all providers by engine type.

---

## [Unreleased] - 2026-03-01

### Added

-   Initial release of `uften/courier`.
-   `Provider` enum (Yalidine, Maystro, Procolis, ZR Express, Ecotrack).
-   `TrackingStatus` enum with 12 canonical statuses, English + French labels, terminal/active helpers, colour hints.
-   `DeliveryType` enum (`HOME`, `STOP_DESK`).
-   `LabelType` enum (`PDF_BASE64`, `PDF_URL`, `IMAGE_URL`).
-   `ProviderAdapter` contract defining the unified surface for every adapter.
-   `StatusNormalizer` contract forcing per-adapter raw→canonical status mapping.
-   `CreateOrderData` readonly DTO with `fromArray()` and `toArray()`.
-   `OrderData` readonly DTO with `isDelivered()` and `isTerminal()`.
-   `RateData` and `LabelData` readonly DTOs.
-   Credential DTOs: `YalidineCredentials`, `TokenCredentials`, `ProcolisCredentials`.
-   Exception hierarchy: `CourierException` → `AuthenticationException`, `OrderNotFoundException`, `UnsupportedOperationException`, `InvalidCredentialsConfigException`.
-   `AbstractAdapter` base with Guzzle HTTP helpers and uniform error translation.
-   `YalidineAdapter` (17 statuses), `MaystroAdapter` (15 statuses), `ProcolisAdapter` (14 statuses, also ZR Express), `EcotrackAdapter` (18 statuses).
-   `CourierManager` — factory/registry with caching, runtime credential override, and custom driver support.
-   `CourierServiceProvider` with Laravel auto-discovery.
-   `Courier` facade.
-   Publishable `config/courier.php`.
-   Pest test suite: Unit (enums, DTOs) + Feature (manager + all four adapters via MockHandler).
-   GitHub Actions CI: PHP 8.4 × Laravel 11 + 12, Pint.
