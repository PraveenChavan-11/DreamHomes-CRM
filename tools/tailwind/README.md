# Admin CSS build (Tailwind 3.4.17)

The admin portal used to load `https://cdn.tailwindcss.com`, which compiles CSS **in every visitor's browser** (a ~400 KB script, runtime
`eval`, a third-party dependency on every page). It now serves a pre-built, minified file: `public/assets/css/admin-tailwind.css`.

Hosting needs no Node: the built file is committed. Rebuild only after adding/changing Tailwind classes in admin views, controllers or services:

```bash
cd tools/tailwind
npm ci            # once; installs the pinned tailwindcss (node_modules is git-ignored)
npm run build     # writes ../../public/assets/css/admin-tailwind.css
```

- `tailwind.config.js` mirrors the colour/font config the layout used with the CDN; `content` lists every place class names can live.
- Class names must appear as **complete literal strings** in those files (`'bg-red-500'`, not `'bg-' + colour`), otherwise they are not generated.
- The layout cache-busts the file with its modification time.
