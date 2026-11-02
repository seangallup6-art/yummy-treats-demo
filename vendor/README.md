# vendor/

Drop plugin zips here that cannot be fetched by script from this machine (wordpress.org is blocked
from the dev container; the hosted Playground link installs them itself from wordpress.org).

Needed for local dev:

- `translatepress-multilingual.zip` — TranslatePress (free), download from
  https://wordpress.org/plugins/translatepress-multilingual/ and save here.

`tools/setup-site.sh` unzips anything in this folder into `site/wp-content/plugins/`.
Zips are git-ignored.
