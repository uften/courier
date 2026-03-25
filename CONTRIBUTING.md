# Contributing to uften/courier

Thank you for considering contributing! This document explains the project conventions and process for submitting changes.

---

## Development Setup

```bash
git clone https://github.com/uften/courier.git
cd courier
composer install
```

Run the test suite:

```bash
composer test
```

Fix code style:

```bash
composer format
```

---

## Adding a New Provider Adapter

This is the most common contribution. Follow these steps precisely so the new adapter integrates seamlessly.

### 1. Add the provider to the `Provider` enum

Open `src/Enums/Provider.php` and add a new case under the appropriate engine group comment:

```php
case MYCARRIER = 'mycarrier';
```

Then update **all four** `match` expressions inside the enum:

- `adapterClass()` — return the FQCN of your new adapter.
- `baseUrl()` — return the provider's base API URL.
- `metadata()` — return a populated `ProviderMetadata` instance.
- `isEcotrackEngine()` — if your provider is **not** Ecotrack-based, add `&& $this !== self::MYCARRIER` to exclude it from the Ecotrack catch-all.
- `requiresApiId()` — only add a `true` branch if your provider requires a separate `id` field alongside `token` (Procolis engine pattern).

> **Engine groups:** Yalidine (2), Maystro (1), Procolis/ZR Express legacy (2), ZR Express NEW (1), Zimou (1), Ecotrack (23). Total: 30.
> If your provider shares an existing engine (e.g. a new Ecotrack sub-provider), you only need to add the enum case and metadata — no new adapter class is needed.

### 2. Add a Credentials DTO (if needed)

If the provider uses a credential shape not already covered by `TokenCredentials` or `ProcolisCredentials`, create a new DTO in `src/Data/Credentials/`:

```php
final readonly class MyCarrierCredentials
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}

    /** @param array<string, string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? throw new \InvalidArgumentException('Requires "username".'),
            password: $data['password'] ?? throw new \InvalidArgumentException('Requires "password".'),
        );
    }
}
```

### 3. Create the Adapter

Create `src/Adapters/MyCarrierAdapter.php` extending `AbstractAdapter`:

```php
final class MyCarrierAdapter extends AbstractAdapter
{
    /**
     * Map every documented provider status string/ID to a canonical TrackingStatus.
     *
     * Prefer mapping by integer ID when the provider returns one — IDs are
     * more stable than localised string names (see ZimouAdapter for an example).
     */
    private const array STATUS_MAP = [
        'raw_status_from_api' => TrackingStatus::IN_TRANSIT,
        // ... cover every known status
    ];

    public function __construct(
        private readonly TokenCredentials $credentials,
        ?Client $httpClient = null,
    ) {
        parent::__construct(
            baseUrl: Provider::MYCARRIER->baseUrl(),
            defaultHeaders: ['Authorization' => "Bearer {$this->credentials->token}"],
            httpClient: $httpClient,
        );
        $this->providerEnum = Provider::MYCARRIER;
    }

    public function normalizeStatus(string $rawStatus): TrackingStatus
    {
        return self::STATUS_MAP[mb_strtolower(trim($rawStatus))] ?? TrackingStatus::UNKNOWN;
    }

    // implement remaining ProviderAdapter methods ...
}
```

> **Two-step `createOrder()` pattern:** Some APIs (e.g. ZR Express NEW) return only an ID on creation and require a second GET call to hydrate the full response. Call `getOrder($id)` internally from `createOrder()` so the caller always receives a complete `OrderData`.
}
```

**Rules for `STATUS_MAP`:**

- Cover every status documented by the provider's API — including sparse/high IDs.
- Unknown or future statuses must return `TrackingStatus::UNKNOWN` — never throw.
- Use `mb_strtolower()` for case-insensitive string matching.
- If the provider returns integer status IDs, map by ID (like `ZimouAdapter`) for stability.

**Provider-specific operations:**

If the provider exposes useful methods that are not part of the `ProviderAdapter` contract (e.g. `MaystroAdapter::createProduct()`, `ZimouAdapter::normalizeStatusById()`), add them as public methods on the concrete adapter class with full PHPDoc. Callers who need them can type-hint the concrete class directly.

### 4. Wire it in `CourierManager`

Add a new `match` arm in `CourierManager::make()`:

```php
$provider === Provider::MYCARRIER => new MyCarrierAdapter(
    credentials: $this->buildTokenCredentials($provider, $creds),
),
```

If your provider uses a custom credential shape, add a corresponding `build*Credentials()` private method.

### 5. Update the config

Add the new provider's credential keys to `config/courier.php` under the correct engine group comment:

```php
'mycarrier' => [
    'token' => env('MYCARRIER_API_TOKEN'),
],
```

### 6. Seed test credentials

Add your provider to `tests/TestCase.php::defineEnvironment()` so all feature tests have credentials available.

### 7. Write tests

Create `tests/Feature/Adapters/MyCarrierAdapterTest.php` using Guzzle's `MockHandler` — do **not** hit the real API in tests. Cover at minimum:

- `provider()` returns the correct enum value.
- `metadata()` returns a `ProviderMetadata` with non-empty `title` and `website`.
- `testCredentials()` returns `true` on 200 and `false` on 401/403.
- `createOrder()` maps payload correctly (use Guzzle history middleware to inspect the sent request body).
- `createOrder()` returns correctly shaped `OrderData` (provider, status, recipientName, etc.).
- `getOrder()` throws `OrderNotFoundException` on empty/404 response.
- `normalizeStatus()` for **every entry** in `STATUS_MAP` plus one unknown value.
- Any unsupported operations throw `UnsupportedOperationException`.
- If the provider exposes adapter-specific public methods, test those too.

### 8. Update documentation

All four documents must be updated:

- **`README.md`** — Add a row to the provider table and the **Supported Methods** matrix. Add a `.env` snippet for the new credentials.
- **`DOCUMENTATION.md`** — Add the adapter to the Concrete Adapters table. If the adapter has notable behaviour (dual-path `getOrder()`, custom delivery type mapping, etc.), add a dedicated sub-section.
- **`CHANGELOG.md`** — Add a bullet under `[Unreleased]` describing the new adapter, its status map size, any unique behaviour, and test count.
- **`CONTRIBUTING.md`** — Update the engine groups list in Step 1 if the provider count changes.

---

## Commit Style

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(adapter): add MyCarrier adapter
fix(maystro): correct Token auth scheme (was incorrectly using Bearer)
fix(zimou): map status_id 83 (Bloqué) to TrackingStatus::EXCEPTION
docs: add ZimouAdapter deep-dive section to DOCUMENTATION.md
test(zimou): verify all 54 status IDs are mapped and non-UNKNOWN
```

---

## Pull Request Checklist

- [ ] `composer test` passes with no failures.
- [ ] `composer format` produces no diff.
- [ ] New public methods have PHPDoc blocks including `@param`, `@return`, and `@throws`.
- [ ] `CHANGELOG.md` has a bullet under `[Unreleased]`.
- [ ] `README.md` provider table and capability matrix are updated.
- [ ] `DOCUMENTATION.md` adapter table is updated.
- [ ] `CONTRIBUTING.md` engine groups count is updated (if applicable).
- [ ] `tests/TestCase.php` seeds credentials for the new provider.

---

## Code Conventions

- All files: `declare(strict_types=1)` at the top.
- DTOs: `final readonly class`.
- Adapters: `final class` extending `AbstractAdapter`.
- Enums: backed enums (`string` or `int`) only.
- All `array` return types must have a `@return list<Foo>` or `@return array<string, mixed>` PHPDoc.
- Prefer named arguments for constructors with more than 3 parameters.
- Never swallow exceptions silently in adapters — let them propagate or wrap in `CourierException`.
- Prefer normalising by integer status IDs when available — they are more stable than localised name strings.

---

## Reporting Security Issues

Please do **not** open a public GitHub issue for security vulnerabilities. Email `security@uften.dev` instead. We will respond within 72 hours.
