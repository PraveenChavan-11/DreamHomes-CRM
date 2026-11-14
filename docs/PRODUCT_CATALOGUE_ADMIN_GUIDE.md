# Product Catalogue: Admin Guide

The catalogue lives at **Admin > Products** (`/admin/products`) and publishes to `/products` and `/products/{slug}`.

## Who can do what

| Permission | Allows |
|---|---|
| `products.view` | Open the list, open a product (read-only form), preview drafts |
| `products.manage` | Create, edit, publish/unpublish/archive, feature, reorder, upload images, delete |

Super administrators have both. Grant them to other roles under **Roles**. Every change is written to the audit log (`PRODUCT_CREATE`, `PRODUCT_UPDATE`, `PRODUCT_STATUS`, `PRODUCT_FEATURE`, `PRODUCT_REORDER`, `PRODUCT_DELETE`).

## Adding a product

1. **Products > New product.**
2. Fill **Basics** (name, tagline, category, short description, overview). The slug is generated from the name; edit it if you want a different address.
3. Add **Key features**, **Modules**, **Benefits**, **Who it is for**, **FAQs** with *Add item*. Blank items are dropped on save.
4. Choose the **Primary button**:
   - *Open enquiry form*: scrolls to the enquiry form on the page.
   - *Start demo / sign-up link*: needs a Sign-up URL (or Demo URL).
   - *Custom link*: needs a Custom button link (`https://...` or a site path such as `/contact`).
5. Save as **Draft**, use **Preview** (admin only, not indexed, enquiry form disabled), then set Status to **Published** (or use *Publish* in the list). A short description is required to publish.

## Statuses

- **Draft**: visible only in admin preview. Public URL returns 404.
- **Published**: listed on `/products`, in the sitemap (unless *no-index*), enquiry form active.
- **Archived**: hidden (404). Use to retire a product while keeping it.
- **Delete**: soft delete. Hidden everywhere; the row stays in the database. A deleted product still reserves its slug.

Retired placeholder products (`societyhub`, `peoplehub`) redirect to `/services/it-services`.

## Display order, featured, flagship

- Public order is: flagship, then featured, then *Display order* (lowest first), then name. Use the up/down buttons in the list to reorder.
- **Flagship** switches the detail page to the richer layout (hero capability chips, numbered modules grid, benefits grouped by role, "what is included in the demo"). Set *Audience* on each Benefit (for example "Employees") to group them by role.

## Images and video

- JPG, PNG or WebP only. Logo up to 3 MB; hero, social share and screenshot images up to 5 MB; no side longer than 6000 px (minimum 16 px). SVG and any other type are rejected.
- Files are checked by content, not just by name, and stored under random names in `public/uploads/products/{id}/`. Replacing or removing an image deletes the old file.
- **Use real images of the product only.** No stock mock-ups, invented dashboards, fake customer logos, testimonials, ratings, certifications or metrics. Where there is no approved screenshot, leave the gallery empty: the page then shows feature cards instead.
- Add alt text to every screenshot (what the image shows, for screen-reader users).
- Video is by link only and only from **YouTube or Vimeo**. The page embeds a privacy-enhanced player. Other hosts are rejected.
- Links (sign-up, demo, docs, canonical) must be full `http://` or `https://` addresses.

## SEO fields

| Field | Used for | Fallback |
|---|---|---|
| SEO title | `<title>`, Open Graph/Twitter title | Product name and tagline |
| Meta description | description, Open Graph/Twitter description | Short description |
| Canonical URL | canonical link | The product page address |
| Social share image | Open Graph / Twitter image | Hero image, then logo |
| No-index | `robots: noindex` and removal from the sitemap | Off |

Each product page also emits `SoftwareApplication` structured data (no ratings, reviews or prices) and `FAQPage` data when FAQs exist.

## Enquiries

The form on each product page creates a lead in **IT Project Leads** (service "Other", product slug recorded, source *product_enquiry*). Visitors see a success message only after the lead was saved. The form uses CSRF, honeypot/captcha and a rate limit.

## WorkSphere sign-up address

The seeded WorkSphere product uses `https://worksphere.talenthubconsultant.com/register` (the public registration page of the WorkSphere app). Change it in the product's *Sign-up URL* field. The default is also stored in the setting `worksphere_signup_url`, used only if the product's own field is empty.
