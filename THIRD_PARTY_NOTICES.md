# Third-Party Notices

This file preserves attribution for open-source components used by Kipit.
**Do not remove or alter license files inside third-party / vendor packages.**

## Dual-license structure

| Layer | License | Where |
|-------|---------|--------|
| Upstream / retained open-source portions | See root `LICENSE` (GNU GPL v3) and package-level notices | Project root & historical core |
| Selected Lakasir-related packages | MIT | `vendor/lakasir/**`, `vendor/sheenazien8/**` (and their `LICENSE*` files) |
| Other Composer / npm dependencies | As stated in each package | `vendor/**`, `node_modules/**` |
| Kipit proprietary features & branding | `COMMERCIAL_LICENSE.txt` | Kipit-owned additions |

## MIT packages derived from Lakasir / related authors

The following dependency license files are left intact (examples):

- `vendor/lakasir/has-crud-action/LICENSE.md` — MIT, Copyright (c) lakasir
- `vendor/sheenazien8/konstantiq/LICENSE.md` — MIT, Copyright (c) sheenazien8

Additional MIT/GPL/Apache notices appear throughout `vendor/` and `node_modules/`. Those notices remain authoritative for those components.

## Policy

1. Never edit, delete, or overwrite `LICENSE` / `LICENSE.md` files under paths pulled from Lakasir or other upstream packages.
2. Kipit proprietary terms live only in `COMMERCIAL_LICENSE.txt`.
3. When redistributing, include this notice file together with the applicable license texts.

## Disclaimer

This notice is for attribution and engineering clarity. It is not legal advice.
Consult qualified counsel for compliance with GPL, MIT, and commercial licensing.
