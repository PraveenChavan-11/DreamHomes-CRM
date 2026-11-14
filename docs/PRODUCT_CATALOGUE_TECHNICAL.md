# Product Catalogue: Technical Notes

## Schema (migration `20261108000001_create_product_catalogue.php`)

Idempotent static `apply($db)`; also run lazily by `ProductService::__construct()` (Hostinger has no shell). Safe to run repeatedly.

- **`products`**: a legacy `products` table already exists (retired SocietyHub/PeopleHub rows from `20261105000001`). It is **extended in place** (`ALTER TABLE ADD COLUMN` for each missing column; nothing dropped, legacy rows untouched, legacy columns `summary`, `features`, `product_url`, `meta_*`, `sort_order` kept and unused). On a fresh database the table is created with legacy and new columns so the older migration's `INSERT IGNORE` keeps working. Default `status` is now `draft`.
  New columns: `short_description, long_description, category, is_featured, is_flagship, display_order, logo_path, hero_image_path, hero_video_url, demo_url, signup_url, docs_url, pricing_summary, cta_label, cta_url, primary_cta_type(enquiry|demo|external), seo_title, seo_description, og_image_path, canonical_url, no_index, created_by, updated_by, published_at`. Unique `slug`; listing index `(status, deleted_at, is_flagship, is_featured, display_order)`.
- Child tables (FK `product_id` -> `products.id` ON DELETE CASCADE, `sort`): `product_features(title, description, icon)`, `product_modules`, `product_benefits(+audience)`, `product_audiences`, `product_faqs(question, answer)`, `product_media(kind screenshot|video|gallery, path_or_url, caption, alt_text)`, `product_sections(section_key, title, body)`.
- Permissions `products.view`, `products.manage` (`INSERT IGNORE`, granted to `super_admin`). Setting `worksphere_signup_url` (`INSERT IGNORE`).
- **Seed**: WorkSphere (flagship, published) is inserted only if no row with slug `worksphere` exists (including soft-deleted), so admin edits/deletes are never overwritten.
- `down()` drops only the child tables.
- The older `20261106000001_two_service_structure` migration used to archive *every* non-archived product; it now only touches slugs `societyhub` and `peoplehub` (otherwise it would re-archive new products on the next lazy check).

## Routes

Public (`Web\ProductsController`): `GET /products`, `GET /products/(:segment)`, `POST /products/(:segment)/enquiry` (rate limit 10/60s, CSRF).
Admin (`Admin\ProductsController`, inside the `admin` `auth` group, block marked "Product catalogue (admin)"): `GET products`, `products/new`, `products/(:num)/edit`, `products/(:num)/preview`; `POST products/create`, `.../update`, `.../status`, `.../feature`, `.../move`, `.../delete`.

## Code map

- `Services\ProductService`: queries, CRUD (`save()` is transactional and replaces child rows), slug rules, `validateUrl()`, `videoEmbedUrl()`, `validateImageFile()`, `storeUpload()`, `deleteStoredFile()`, ordering, status.
- `Controllers\Traits\RendersProductPage`: builds meta/JSON-LD/media for the shared template (public page and admin preview).
- Views: `public/products/index.php`, `public/products/show.php` (standard + flagship branches), `admin/products/index.php`, `admin/products/form.php`. CSS: `public/assets/css/products.css`.
- `public/layouts/main.php` gained optional `$noindex`, `$canonical_url`, `$og_image`, `$json_ld` hooks and a Products nav/footer link. `SitemapService::generatePagesXml()` lists `/products` and published, indexable products.

## Security controls

- RBAC server-side on every action; all admin POSTs CSRF-protected by the global filter; audit log per mutation.
- All text stored raw, escaped on output (`esc()`); no HTML accepted. JSON-LD uses `JSON_HEX_TAG|JSON_HEX_AMP`.
- URLs: syntax-only validation, `http/https` only, no credentials, no control or quote characters. Nothing is fetched server-side (no SSRF). Video: only `youtube.com`, `youtu.be`, `vimeo.com` with strict id patterns; the embed URL is constructed by us (`youtube-nocookie.com`, `player.vimeo.com`) and rendered in a sandboxed iframe.
- Uploads: extension allow-list, double-extension refusal, MIME sniffed from content, `getimagesize()` type check (JPEG/PNG/WebP), dimension caps, size caps (3 MB logo, 5 MB images), random 32-hex names with extension from the real type, directory `.htaccess` denying script execution, only app-generated paths (`uploads/products/{id}/{hex}.ext`) are ever deleted, and media rows may only reference files of the same product.
- Public visibility: only `status='published' AND deleted_at IS NULL`; draft/archived/deleted return 404; drafts are previewable only by admins with `products.view` (noindex).
- Enquiry: CSRF, `FormSecurityService` honeypot/captcha/timestamp, rate limit, consent required; success only after `ItLeadService::create()` returns an id; notification via `CrmNotificationService` is best-effort.

## Extension points

- New section types: add a collection to `ProductService::CHILDREN`, a table in the migration (use the `$child()` helper), a repeater in `admin/products/form.php`, and a block in `public/products/show.php`. For one-off content, use the generic `product_sections` table ("Extra sections" in the form).
- New icons: add an SVG path to `$icons` in `show.php`.
- New layout variants: branch on a column (today `is_flagship`) in `show.php`.

## Operations

- No deployment step: first request that touches `ProductService` creates/extends the tables. Upload folder `public/uploads/products/` must be writable.
- Rollback: unpublish/archive products in admin; `down()` drops child tables only. New columns on `products` are additive and harmless.
- Tests: `tests/unit/ProductCatalogueTest.php` (run against a local test database, e.g. `database.tests.database=talenthub_test_products`).
