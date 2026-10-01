<?php

namespace Tests\Feature;

use App\Enums\SessionFormat;
use App\Models\ConferenceSession;
use App\Models\ContentBlock;
use App\Models\Edition;
use App\Models\Order;
use App\Models\PresentationFile;
use App\Models\Speaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards on the model layer.
 *
 * Each test here corresponds to a defect that was actually present in the
 * first pass of this code, so they are regression tests rather than
 * aspirational coverage.
 */
class ModelIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('fr');
    }

    public function test_a_translated_string_resolves_for_the_active_locale(): void
    {
        $edition = Edition::factory()->create([
            'title' => json_encode([
                'fr' => 'Conférence ARABCIA 2026',
                'en' => 'ARABCIA 2026 Conference',
                'ar' => 'مؤتمر ARABCIA 2026',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        // A custom cast receives the raw JSON string, so a cast that only
        // checks is_array() returns null for every row.
        $this->assertSame('Conférence ARABCIA 2026', $edition->fresh()->title);

        app()->setLocale('ar');
        $this->assertSame('مؤتمر ARABCIA 2026', $edition->fresh()->title);

        app()->setLocale('en');
        $this->assertSame('ARABCIA 2026 Conference', $edition->fresh()->title);
    }

    public function test_a_translated_string_falls_back_instead_of_returning_null(): void
    {
        $edition = Edition::factory()->create([
            'title' => json_encode(['en' => 'Only English'], JSON_UNESCAPED_UNICODE),
        ]);

        // French is the app fallback: a page must never render a blank title.
        $this->assertSame('Only English', $edition->fresh()->title);
    }

    public function test_malformed_translation_json_degrades_to_null_instead_of_throwing(): void
    {
        $edition = Edition::factory()->create();

        // Written through the query builder to simulate pre-existing corrupt
        // data: going through the model would have normalised it away.
        \DB::table('editions')->where('id', $edition->id)->update(['title' => '{not json']);

        $this->assertNull($edition->fresh()->title);
    }

    public function test_title_in_can_ask_for_a_locale_other_than_the_active_one(): void
    {
        $edition = Edition::factory()->create([
            'title' => json_encode([
                'fr' => 'Titre FR',
                'en' => 'English title',
                'ar' => 'العنوان',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        app()->setLocale('fr');

        $this->assertSame('English title', $edition->titleIn('en'));
        $this->assertSame('العنوان', $edition->titleIn('ar'));
    }

    public function test_a_translated_list_returns_only_the_active_locale(): void
    {
        $edition = Edition::factory()->create([
            'target_audience' => json_encode([
                'fr' => ['Chercheurs', 'Doctorants'],
                'en' => ['Researchers'],
                'ar' => ['باحثون'],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame(['Chercheurs', 'Doctorants'], $edition->fresh()->target_audience);

        app()->setLocale('ar');
        $this->assertSame(['باحثون'], $edition->fresh()->target_audience);

        // No cross-locale fallback for lists: a list of wrong bullets reads as
        // authoritative, which is worse than an empty list.
        app()->setLocale('de');
        $this->assertSame([], $edition->fresh()->target_audience);
    }

    public function test_sessions_expose_presentation_files_as_a_plain_relation(): void
    {
        $session = ConferenceSession::factory()->create();

        $file = PresentationFile::factory()->create([
            'conference_session_id' => $session->id,
            'sort_order' => 2,
        ]);

        $files = $session->presentationFiles()->get();

        $this->assertCount(1, $files);
        $this->assertSame($file->id, $files->first()->id);
    }

    public function test_speakers_are_ordered_by_pivot_sort_order(): void
    {
        $session = ConferenceSession::factory()->create();

        $chair = Speaker::factory()->create(['first_name' => 'Zahra']);
        $speaker = Speaker::factory()->create(['first_name' => 'Amine']);

        // Inserted alphabetically second, but the chair must be listed first.
        $speaker->sessions()->attach($session, ['role' => 'speaker', 'sort_order' => 1]);
        $chair->sessions()->attach($session, ['role' => 'chair', 'sort_order' => 0]);

        $names = $session->speakers()->pluck('first_name')->all();

        $this->assertSame(['Zahra', 'Amine'], $names);
    }

    public function test_a_session_knows_its_format_and_duration(): void
    {
        $session = ConferenceSession::factory()->create([
            'format' => SessionFormat::Workshop,
            'starts_at' => '09:00:00',
            'ends_at' => '10:30:00',
        ]);

        $this->assertSame(SessionFormat::Workshop, $session->format);
        $this->assertSame(90, $session->durationMinutes());
    }

    public function test_overlapping_sessions_in_the_same_room_are_detected(): void
    {
        $room = \App\Models\Room::factory()->create();

        $a = ConferenceSession::factory()->create([
            'session_date' => '2026-12-16',
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
            'room_id' => $room->id,
        ]);

        $b = ConferenceSession::factory()->create([
            'session_date' => '2026-12-16',
            'starts_at' => '09:30:00',
            'ends_at' => '10:30:00',
            'room_id' => $room->id,
        ]);

        $c = ConferenceSession::factory()->create([
            'session_date' => '2026-12-16',
            'starts_at' => '11:00:00',
            'ends_at' => '12:00:00',
            'room_id' => $room->id,
        ]);

        $this->assertTrue($a->conflictsWith($b));
        $this->assertFalse($a->conflictsWith($c));
    }

    public function test_an_order_keeps_an_integer_primary_key_and_gets_a_uuid(): void
    {
        $order = $this->makeOrder();

        $this->assertIsInt($order->id);
        $this->assertNotEmpty($order->uuid);
        $this->assertNotEmpty($order->reference);
    }

    public function test_order_invoice_numbers_do_not_collide(): void
    {
        $first = $this->makeOrder();
        $second = $this->makeOrder();

        $this->assertNotSame(
            $first->assignInvoiceNumber(),
            $second->assignInvoiceNumber(),
        );
    }

    public function test_a_content_block_exposes_text_and_items_separately(): void
    {
        $block = ContentBlock::create([
            'key' => 'target_audience',
            'group' => 'home',
            'value' => ['fr' => ['Chercheurs', 'Doctorants'], 'en' => ['Researchers']],
            'value_type' => ContentBlock::TYPE_LIST,
            'is_published' => true,
        ]);

        $this->assertTrue($block->isList());
        $this->assertNull($block->text());
        $this->assertSame(['Chercheurs', 'Doctorants'], $block->items());

        $text = ContentBlock::create([
            'key' => 'intro',
            'group' => 'home',
            'value' => ['fr' => 'Bonjour', 'en' => 'Hello'],
            'value_type' => ContentBlock::TYPE_TEXT,
        ]);

        $this->assertSame('Bonjour', $text->text());
        $this->assertSame(['Bonjour'], $text->items());
    }

    public function test_the_current_edition_is_resolved_and_can_be_archived(): void
    {
        $current = Edition::factory()->current()->create(['year' => 2026]);
        $past = Edition::factory()->archived()->create(['year' => 2024]);

        Edition::forgetCurrent();

        $this->assertSame($current->id, Edition::current()?->id);

        $archive = Edition::archive();

        $this->assertCount(1, $archive);
        $this->assertSame($past->id, $archive->first()->id);
    }

    private ?Edition $edition = null;

    /**
     * One edition per test: the factory's unique() helper resets per instance,
     * so creating an edition per order collides on the unique year column.
     */
    private function makeOrder(): Order
    {
        $this->edition ??= Edition::factory()->current()->create(['year' => 2026]);

        $ticket = \App\Models\TicketType::factory()->create(['edition_id' => $this->edition->id]);

        return Order::create([
            'edition_id' => $this->edition->id,
            'user_id' => \App\Models\User::factory()->create()->id,
            'ticket_type_id' => $ticket->id,
            'billing' => ['name' => 'Test Buyer', 'email' => 'buyer@example.test'],
            'subtotal' => 750000,
            'total' => 750000,
            'currency' => 'MAD',
            'currency_numeric' => '504',
        ]);
    }
}
