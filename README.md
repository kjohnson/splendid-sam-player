# Splendid Sam — Radio Drama Player

A mobile-first Laravel site for listening to *Splendid Sam*, the radio drama from inside Michael F. Kane's
*After Moses* books: episodes 387, "For a Dozen Quid of Platinum", and 388, "With Friends Like These", read by
Son of Sonnet, with commercials read by Randy McCarten.

- Episode list with per-episode progress, and a credits sheet for the people behind the production (`credits` in `config/audiobook.php`)
- Resume where you left off (saved in the browser's localStorage)
- Back 15s / forward 30s
- Works offline: the episode that's playing and the next one are saved on the device automatically, and
  "Save all" in the chapters menu saves every episode. A service worker (`public/sw.js`) serves saved
  chapters, including seeking, and keeps the player itself available without a connection.
- Lock-screen and headphone controls via the Media Session API
- Installable to the home screen (web app manifest)

No database is required.

## Local development

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
composer run dev
```

> `php artisan serve` doesn't support HTTP range requests, so seeking in Safari/iOS only works against a real web server (Laravel Cloud, Herd, Valet, nginx).

## Adding episodes

The episodes live in `storage/app/private/splendid-sam/audio/` and are listed under `books.splendid-sam` in
`config/audiobook.php`. The audio isn't committed to git. The cover is `public/images/splendid-sam.jpg`
(1024×1024 JPEG); the app icons in `public/images/` and `public/apple-touch-icon.png` are crops of it.

1. Drop the MP3 in the `audio/` folder (lowercase, no spaces, e.g. `episode-389-some-title.mp3`).
2. Add an entry to `chapters` with its title, file, duration in seconds, and episode `number`:

   ```sh
   ffprobe -v error -show_entries format=duration -of csv=p=0 storage/app/private/splendid-sam/audio/episode-389-some-title.mp3
   ```

3. Upload it to the bucket with `php artisan audiobook:upload s3`.

The player still supports more than one book, follow-along text (`php artisan audiobook:sync`) and "coming
soon" books, as in the After Moses player it was copied from; add another entry to `books` to use them.

## Deploying to Laravel Cloud

1. Push this repo to GitHub/GitLab/Bitbucket and create an application in Laravel Cloud.
2. Build command: `composer install --no-dev && npm ci && npm run build && php artisan optimize`
3. Deploy command: leave empty (there are no migrations to run).
4. Environment variables: `SESSION_DRIVER=cookie`, `CACHE_STORE=file` (no database needed).

### Hosting the audio in Object Storage

The MP3s are too large for the repo, so they live in a private bucket. Locally they sit in
`storage/app/private/splendid-sam/audio/` (git-ignored), outside the web root. The player only
receives signed links (`/audio/{book}/{chapter}?expires=…&signature=…`) that expire after `AUDIOBOOK_URL_LIFETIME` minutes (default 360),
are rate limited, and are refused when opened directly in a browser tab or embedded on another site. To set
up the bucket:

1. Add a **private** Laravel Object Storage bucket to the environment and mark it as the **default disk**
   (any disk name). Redeploy so the bucket's connection is injected. Listeners are then redirected to
   short-lived pre-signed URLs.
2. Copy one of the bucket's access keys (Organization > Resources > Object storage > your bucket >
   Access keys > View credentials) into your local `.env`:

   ```dotenv
   AWS_ACCESS_KEY_ID=…
   AWS_SECRET_ACCESS_KEY=…
   AWS_BUCKET=…
   AWS_ENDPOINT=…
   AWS_DEFAULT_REGION=auto
   ```

3. Upload the files:

   ```sh
   php artisan audiobook:upload s3
   ```

   This copies `audio/*.mp3` from local storage into the same folders in the bucket, skipping files that are
   already uploaded unchanged. Run it again after adding episodes.

`AUDIOBOOK_DISK` can name a disk other than the default, but isn't needed on Laravel Cloud.
