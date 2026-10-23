<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Series Details
    |--------------------------------------------------------------------------
    */

    'series' => 'Splendid Sam',

    'author' => 'Michael F. Kane',

    /*
    |--------------------------------------------------------------------------
    | Credits
    |--------------------------------------------------------------------------
    |
    | Shown in the credits sheet, in order. From the episode 388 release
    | announcement: https://x.com/MichaelFKane/status/2113373211865809179
    |
    */

    'about' => 'A radio serial from the universe of After Moses, in the spirit of the 1930s and ’40s: a pulp hero, a sidekick, and in-universe commercials.',

    'credits' => [
        [
            'role' => 'Written by',
            'name' => 'Michael F. Kane',
            'url' => 'https://michaelfkane.com/',
        ],
        [
            'role' => 'Narration',
            'name' => 'Son of Sonnet',
            'url' => 'https://www.sonofsonnet.com/',
        ],
        [
            'role' => 'Commercials',
            'name' => 'Randy McCarten',
            'url' => 'https://randymccarten.com/',
        ],
        [
            'role' => 'Music',
            'name' => 'M. Anthony Harris',
            'url' => 'https://michaelharriscreations.com/',
        ],
        [
            'role' => 'Art',
            'name' => 'Venom Walter',
            'url' => 'https://x.com/VenomWalter_art',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Location
    |--------------------------------------------------------------------------
    |
    | Each book has its own folder (named after its key below) on a private
    | disk, holding its ebook and its "audio", "text" and "transcripts"
    | folders. Nothing is served from a public URL. On the local disk audio
    | is streamed through a signed route; on S3-compatible object storage
    | (e.g. a private Laravel Cloud bucket) listeners are redirected to a
    | short-lived pre-signed URL.
    |
    | Leave AUDIOBOOK_DISK unset to use the default disk: "local" here, and
    | the bucket attached as the default disk on Laravel Cloud.
    |
    | Signed player URLs expire after "url_lifetime" minutes. The player
    | fetches a fresh one automatically if a listener comes back later.
    |
    */

    'disk' => env('AUDIOBOOK_DISK'),

    'url_lifetime' => (int) env('AUDIOBOOK_URL_LIFETIME', 360),

    /*
    |--------------------------------------------------------------------------
    | Books
    |--------------------------------------------------------------------------
    |
    | In reading order. The key names the book's folder and appears in its
    | URLs. Finishing a book carries on into the next. The "label" shows
    | above the book's title in the player. A book with no chapters yet is
    | shown as coming soon, listing its "upcoming_chapters" titles.
    |
    | Splendid Sam is the radio drama from inside Michael F. Kane's After
    | Moses books. Its episodes
    | come from https://www.youtube.com/watch?v=rvICdYvcKwQ (387) and
    | https://www.youtube.com/watch?v=uuDWff2OjKo (388, the classic audio
    | version); Randy McCarten reads the commercials. It has no ebook, so it
    | has no follow-along text.
    |
    | Running "php artisan audiobook:sync" matches each chapter's text in the
    | ebook against a WhisperX transcript of its audio (in "transcripts") and
    | writes word timings to the "text" folder, which the player reads.
    | Chapters are found in the ebook by a "Chapter N" title matching their
    | place in the list. Add an "epub" key to a chapter to point at a
    | specific file instead.
    |
    | Durations are in seconds, so the chapter list can show lengths before
    | any audio has been loaded. Get them with:
    | ffprobe -v error -show_entries format=duration -of csv=p=0 file.mp3
    |
    | Set "numbered" to false for tracks that aren't chapters, like the
    | credits, so they're listed without a chapter number. A chapter's
    | "number" can be set instead, and a book's "chapter_name" names its
    | chapters (Splendid Sam's are "Episode 387", its number in the story).
    |
    */

    'books' => [
        'splendid-sam' => [
            'title' => 'Splendid Sam',
            'label' => 'Radio drama',
            'narrator' => 'Son of Sonnet',
            'cover' => 'images/splendid-sam.jpg',
            'chapter_name' => 'Episode',
            'chapters' => [
                [
                    'title' => 'For a Dozen Quid of Platinum',
                    'file' => 'episode-387-for-a-dozen-quid-of-platinum.mp3',
                    'duration' => 1413.14,
                    'number' => 387,
                ],
                [
                    'title' => 'With Friends Like These',
                    'file' => 'episode-388-with-friends-like-these.mp3',
                    'duration' => 1666.97,
                    'number' => 388,
                ],
            ],
        ],
    ],

];
