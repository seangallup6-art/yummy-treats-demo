# Bank Slip Upload for WooCommerce

Customers who choose **Direct bank transfer** upload a photo or screenshot of their payment slip on the
order-received page (or later from *My Account → Orders*). Shop staff see the slip on the order screen and mark the
order paid in one click. Built for Thai shops, where "transfer, then send the slip" is the normal way to pay.

## What it adds

| Where | What |
|---|---|
| Order received page, My Account → View order | "Send your payment slip" box (JPG, PNG, WebP, PDF, up to 5 MB) with preview; after upload, a "received, we're checking" state and a link to the file |
| My Account → Orders | "Send slip" button on orders still waiting |
| Customer "on hold" email | A link straight to the upload box |
| Admin → Orders | A **Slip** column: Waiting / To check / Checked |
| Admin → Edit order | A **Payment slip** box with the image and a *Slip checked: mark as paid* button (runs `payment_complete()`) |

## How it stores files

`wp-content/uploads/yt-slips/` with random file names. The folder gets an `.htaccess` that denies web access on
Apache; on nginx add `location ~ /uploads/yt-slips/ { deny all; }`. Files are streamed through `admin-post.php` to
staff (`edit_shop_orders`) or to the customer holding the order key. Nothing goes into the Media Library, because
slips carry names and account numbers (PDPA). The file is replaced if the customer sends another.

## Filters and actions

| Hook | Default | Use |
|---|---|---|
| `yt_slip_upload_methods` | `['bacs']` | Payment methods that use slips |
| `yt_slip_upload_statuses` | `['pending', 'on-hold']` | When a slip can still be sent |
| `yt_slip_upload_max_bytes` | 5 MB | Size limit |
| `yt_slip_upload_mimes` | jpg, png, webp, pdf | Accepted types |
| `yt_slip_upload_alt_text` | "Or send it to us with your order number" | e.g. point to the shop's LINE |
| `yt_slip_upload_check_text` | "We check slips within the hour…" | The shop's checking hours |
| `yt_slip_uploaded` (action) | — | Notify staff (LINE, email, Slack) |

PHP API: `yt_slip_upload_get( $order )`, `yt_slip_upload_attach( $order, $path )`.

## Requirements

WordPress 6.6+, WooCommerce 9.0+, PHP 8.1+. Compatible with HPOS order tables and the block checkout.
Translations: `languages/yt-slip-upload-th.mo` (Thai).
