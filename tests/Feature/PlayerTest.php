<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    public function test_player_lists_every_episode_by_its_number(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Splendid Sam')
            ->assertSee('Radio drama')
            ->assertSeeInOrder(['For a Dozen Quid of Platinum', 'With Friends Like These'])
            ->assertDontSee('Episode 387: For a Dozen Quid of Platinum')
            ->assertSee('Episode 387')
            ->assertDontSee('Episode 387 of')
            ->assertSee('Son of Sonnet')
            ->assertViewHas('books', function ($books) {
                $book = $books->sole();

                return $book['slug'] === 'splendid-sam'
                    && $book['chapterName'] === 'Episode'
                    && $book['chapters']->pluck('number')->all() === [387, 388];
            });
    }

    public function test_credits_list_the_people_behind_the_production(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-action="open-credits"', false)
            ->assertSeeInOrder(['Written by', 'Michael F. Kane', 'Narration', 'Son of Sonnet', 'Commercials', 'Randy McCarten', 'Music', 'M. Anthony Harris', 'Art', 'Venom Walter'])
            ->assertSee('href="https://randymccarten.com/"', false);
    }

    public function test_credits_are_left_out_when_there_are_none(): void
    {
        config(['audiobook.credits' => []]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-action="open-credits"', false)
            ->assertDontSee('id="credits"', false);
    }

    public function test_a_single_book_has_no_book_tabs(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-show-book=', false)
            ->assertDontSee('Coming soon');
    }

    public function test_a_book_without_chapters_is_listed_as_coming_soon(): void
    {
        config(['audiobook.books.book-three' => [
            'title' => 'Book Three',
            'label' => 'Book 3',
            'narrator' => 'Randy McCarten',
            'cover' => 'images/book-three.jpg',
            'ebook' => 'Book Three.epub',
            'chapters' => [],
            'upcoming_chapters' => ['First Light', 'Second Wind'],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertSeeInOrder(['First Light', 'Second Wind'])
            ->assertDontSee('data-book="book-three"', false);
    }

    public function test_each_chapter_has_an_options_menu_for_its_progress(): void
    {
        config(['audiobook.books.book-three' => [
            'title' => 'Book Three',
            'label' => 'Book 3',
            'narrator' => 'Randy McCarten',
            'cover' => 'images/book-three.jpg',
            'chapters' => [],
            'upcoming_chapters' => ['First Light'],
        ]]);

        $response = $this->get('/')->assertOk();
        $chapterCount = collect(config('audiobook.books'))->sum(fn (array $book): int => count($book['chapters']));

        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-chapter-options='));
        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-action="reset-progress"'));
        $response->assertSeeInOrder(['aria-label="Options for For a Dozen Quid of Platinum"', 'id="actions-splendid-sam-0"', 'Reset progress'], false)
            ->assertSee('aria-controls="actions-splendid-sam-1"', false)
            ->assertDontSee('data-options-book="book-three"', false);
    }

    public function test_chapter_lengths_are_shown_in_minutes_and_seconds(): void
    {
        config(['audiobook.books' => [
            'first' => ['title' => 'First', 'label' => 'Book 1', 'narrator' => 'Reader', 'cover' => 'images/first.jpg', 'chapters' => [
                ['title' => 'Short', 'file' => 'short.mp3', 'duration' => 8.9],
                ['title' => 'Medium', 'file' => 'medium.mp3', 'duration' => 2250.5],
                ['title' => 'Long', 'file' => 'long.mp3', 'duration' => 4718.58],
            ]],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Short', '00:08', 'Medium', '37:30', 'Long', '78:38']);
    }

    public function test_cover_urls_change_when_the_cover_does(): void
    {
        $hash = substr(hash_file('xxh3', public_path('images/splendid-sam.jpg')), 0, 8);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->firstWhere('slug', 'splendid-sam')['cover'] === asset('images/splendid-sam.jpg').'?v='.$hash);
    }

    public function test_player_links_to_signed_audio_urls_instead_of_files(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('.mp3')
            ->assertViewHas('books', function ($books) {
                $url = $books->first()['chapters']->first()['url'];

                return str_starts_with($url, url('/audio/splendid-sam/1?expires='))
                    && str_contains($url, 'signature=');
            });
    }

    public function test_player_knows_each_chapters_download_size(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('splendid-sam/audio/'.config('audiobook.books.splendid-sam.chapters.0.file'), str_repeat('a', 1234));

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                $chapters = $books->firstWhere('slug', 'splendid-sam')['chapters'];

                return $chapters[0]['size'] === 1234 && $chapters[1]['size'] === null;
            });
    }

    public function test_tracks_that_are_not_chapters_are_left_unnumbered(): void
    {
        config(['audiobook.books' => [
            'first' => ['title' => 'First', 'label' => 'Book 1', 'narrator' => 'Reader', 'cover' => 'images/first.jpg', 'ebook' => 'first.epub', 'chapters' => [
                ['title' => 'Opening Credits', 'file' => 'opening-credits.mp3', 'duration' => 10.0, 'numbered' => false],
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
                ['title' => 'Two', 'file' => 'chapter-02-two.mp3', 'duration' => 10.0],
                ['title' => 'Credits', 'file' => 'credits.mp3', 'duration' => 10.0, 'numbered' => false],
            ]],
            'second' => ['title' => 'Second', 'label' => 'Book 2', 'narrator' => 'Reader', 'cover' => 'images/second.jpg', 'ebook' => 'second.epub', 'chapters' => [
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
            ]],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books[0]['chapters']->pluck('number')->all() === [null, 1, 2, null]
                && $books[1]['chapters']->pluck('number')->all() === [1]);
    }

    public function test_every_configured_chapter_file_exists(): void
    {
        $disk = Storage::disk(config('audiobook.disk'));

        foreach (config('audiobook.books') as $book => $details) {
            if ($details['chapters'] === []) {
                continue;
            }

            if ($disk->files("{$book}/audio") === []) {
                $this->markTestSkipped("The audio is not in the repo; add it to storage/app/private/{$book}/audio to check it.");
            }

            foreach ($details['chapters'] as $chapter) {
                $this->assertTrue($disk->exists("{$book}/audio/{$chapter['file']}"), "Missing {$book}/audio/{$chapter['file']}");
            }
        }
    }

    public function test_audio_files_are_not_publicly_accessible(): void
    {
        foreach (config('audiobook.books') as $book => $details) {
            foreach ($details['chapters'] as $chapter) {
                $this->assertFileDoesNotExist(public_path("audio/{$chapter['file']}"));
                $this->assertFileDoesNotExist(public_path("{$book}/audio/{$chapter['file']}"));
            }
        }
    }
}
