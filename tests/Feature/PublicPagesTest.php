<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Enums\OtpPurpose;
use App\Mail\ContactMessage;
use App\Models\ContactMessage as ContactMessageRecord;
use App\Models\ContactRoute;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Membership;
use App\Models\Organisation;
use App\Models\Room;
use App\Models\TicketType;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Renders every public page in every supported language.
 *
 * This suite exists because the pages were written without being requested once.
 * Each of them touches a different slice of the schema — translated casts, JSON
 * columns, relations, `null` on a missing edition — and a template that has never
 * been executed fails at runtime, not at build time. Compiling the Blade files
 * proves nothing; only a request does.
 *
 * `preventLazyLoading` is on in the testing environment, so a missing eager load
 * is a failure here rather than a slow query in production.
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // `Edition::current()` memoises in a static property for the request. In
        // production that is one request, so the memo is correct; in a test suite
        // it would hand the first test's edition to every test that follows, and
        // the failure would look like a data problem rather than a leaked cache.
        Edition::forgetCurrent();
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    /**
     * Seed the minimum that a page reads. Deliberately sparse: the point is to
     * cover the "some data is missing" shape as well as the full one, because a
     * seeded demo would hide every null-guard in the templates.
     */
    private function seedEdition(bool $withContent = true): Edition
    {
        $country = Country::query()->create([
            'iso2' => 'MA',
            'iso3' => 'MAR',
            'phone_code' => '+212',
            'name_fr' => 'Maroc',
            'name_en' => 'Morocco',
            'name_ar' => 'المغرب',
            'is_active' => true,
        ]);

        $edition = Edition::query()->create([
            'year' => 2026,
            'code' => 'ARABCIA2026',
            'organiser' => 'ARABCIA',
            'host_institute' => 'IIA Maroc',
            'title' => ['fr' => 'Conférence ARABCIA 2026', 'en' => 'ARABCIA Conference 2026', 'ar' => 'مؤتمر ARABCIA 2026'],
            'theme' => ['fr' => 'Audit interne 2026', 'en' => 'Internal audit 2026', 'ar' => 'التدقيق الداخلي 2026'],
            'introduction' => [
                'fr' => 'La conférence ARABCIA.',
                'en' => 'The ARABCIA conference.',
                'ar' => 'مؤتمر ARABCIA.',
            ],
            'city' => 'Rabat',
            'country_iso2' => 'MA',
            'venue_name' => 'Four Seasons Hotel Rabat',
            'venue_address' => 'Av. Annakhil, Hay Riad, Rabat',
            'contact_email' => 'contact@arabcia.test',
            'contact_phone' => '+212 537 00 00 00',
            'starts_on' => '2026-12-16',
            'ends_on' => '2026-12-17',
            'languages' => ['fr', 'en', 'ar'],
            'status' => 'published',
            'is_current' => true,
            'registration_open' => true,
        ]);

        if (! $withContent) {
            return $edition;
        }

        Organisation::query()->create([
            'edition_id' => $edition->getKey(),
            'code' => 'ARABCIA',
            'name' => ['fr' => 'ARABCIA', 'en' => 'ARABCIA', 'ar' => 'ARABCIA'],
            'description' => ['fr' => 'Association des directeurs d\'audit interne.', 'en' => 'Association of internal audit directors.', 'ar' => 'جمعية مديري التدقيق الداخلي.'],
            'role' => ['fr' => 'Organisateur', 'en' => 'Organiser', 'ar' => 'المنظم'],
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $room = Room::query()->create([
            'edition_id' => $edition->getKey(),
            'code' => 'PLENARY',
            'name' => ['fr' => 'Salle plénière', 'en' => 'Plenary hall', 'ar' => 'القاعة العامة'],
            'capacity' => 300,
            'floor' => 0,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $track = Track::query()->create([
            'edition_id' => $edition->getKey(),
            'code' => 'audit-transformation',
            'name' => ['fr' => 'Transformation', 'en' => 'Transformation', 'ar' => 'التحول'],
            'colour' => 'brand',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        TicketType::query()->create([
            'edition_id' => $edition->getKey(),
            'code' => 'conference',
            'name' => ['fr' => 'Conférence', 'en' => 'Conference', 'ar' => 'المؤتمر'],
            'description' => ['fr' => 'Deux jours', 'en' => 'Two days', 'ar' => 'يومان'],
            'includes' => ['fr' => 'Repas', 'en' => 'Meals', 'ar' => 'وجبات'],
            'currency' => 'MAD',
            'currency_numeric' => '504',
            // Minor units: 7 500,00 MAD and 8 500,00 MAD per the brief.
            'price_member' => 750000,
            'price_standard' => 850000,
            'max_quantity' => 10,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Referenced so the relations in the layout resolve. The variable is
        // unused on purpose: the relations are what is under test here.
        unset($room, $track, $country);

        return $edition;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'programme' => ['/programme'],
            'speakers' => ['/speakers'],
            'pricing' => ['/tarifs'],
            'pricing alt' => ['/pricing'],
            'venue' => ['/lieu'],
            'venue alt' => ['/venue'],
            'sponsors' => ['/partenaires'],
            'sponsors alt' => ['/sponsors'],
            'contact' => ['/contact'],
            'archive' => ['/archive'],
            'login' => ['/login'],
            'register' => ['/register'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutes')]
    public function test_public_pages_render_in_french(string $path): void
    {
        $this->seedEdition();

        $response = $this->withHeader('Accept-Language', 'fr')->get($path);

        $response->assertOk();

        // `SetLocale` resolves the prefix, then `?lang=`, then the session, then
        // the user, then `Accept-Language`, and only then the configured default.
        // Symfony's `Request::create()` — which backs the test client — always
        // sends `Accept-Language: en-us,en;q=0.5`, so an unprefixed request is an
        // *English* request unless the header is pinned. Asserting `lang` here is
        // what makes this test French rather than merely non-erroring.
        $response->assertSee('lang="fr"', escape: false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutes')]
    public function test_public_pages_render_in_english(string $path): void
    {
        $this->seedEdition();

        $response = $this->get('/en'.$path);

        $response->assertOk();
        $response->assertSee('lang="en"', escape: false);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutes')]
    public function test_public_pages_render_in_arabic(string $path): void
    {
        $this->seedEdition();

        $response = $this->get('/ar'.$path);

        $response->assertOk();

        // The Arabic pages must actually be marked right-to-left, not merely
        // contain Arabic text. `dir` is what makes the layout mirror.
        $response->assertSee('dir="rtl"', escape: false);
        $response->assertSee('lang="ar"', escape: false);
    }

    /**
     * The templates guard every optional relation. An edition with no rooms, no
     * tickets, no organisations and no programme is the state the site is in
     * today, and it has to render rather than throw.
     */
    public function test_pages_render_with_an_empty_edition(): void
    {
        $this->seedEdition(withContent: false);

        foreach (['/', '/programme', '/speakers', '/tarifs', '/lieu', '/partenaires', '/contact'] as $path) {
            $response = $this->get($path);

            // The path is in the message so a failure names the page rather than
            // the loop iteration.
            $response->assertOk("GET {$path} did not render with an edition that has no child rows.");
        }
    }

    public function test_programme_renders_when_sessions_exist(): void
    {
        $edition = $this->seedEdition();

        $room = $edition->rooms()->firstOrFail();
        $track = $edition->tracks()->firstOrFail();

        $edition->sessions()->create([
            'track_id' => $track->getKey(),
            'room_id' => $room->getKey(),
            'format' => 'workshop',
            'title' => ['fr' => 'Atelier d\'audit', 'en' => 'Audit workshop', 'ar' => 'ورشة تدقيق'],
            'summary' => ['fr' => 'Résumé', 'en' => 'Abstract', 'ar' => 'ملخص'],
            // `session_date` is a separate required date column, and the start/end
            // are `time` columns, so the date is not repeated in them.
            'session_date' => '2026-12-16',
            'starts_at' => '09:00:00',
            'ends_at' => '10:30:00',
            'is_published' => true,
        ]);

        // Pinned to French so the assertion below tests the French rendering of
        // the stored title. `/programme` is unprefixed, so the request language
        // falls through to `Accept-Language`, which the test client sets to
        // English by default (Symfony's Request::create()).
        //
        // Escaping is left on deliberately: the title contains an apostrophe, so
        // this also asserts that Blade emitted it as an entity rather than raw.
        $this->withHeader('Accept-Language', 'fr')
            ->get('/programme')
            ->assertOk()
            ->assertSee('Atelier d\'audit');
    }

    public function test_an_unknown_locale_prefix_is_a_404(): void
    {
        $this->seedEdition();

        // The prefix is constrained to en|ar on purpose: a request for /fr/... or
        // /xx/... must not be treated as a localised page.
        $this->get('/fr/programme')->assertNotFound();
        $this->get('/xx/programme')->assertNotFound();
    }

    /**
     * The unprefixed French addresses have to be registered as their own routes.
     *
     * A single `/{locale?}/programme` declaration looks like it covers both cases
     * and does not: a route with an optional *leading* segment compiles to a
     * pattern in which the separator after that segment is mandatory, so it
     * matches /en/programme and 404s on /programme. Nothing reports this — the
     * route exists, `route:list` looks right, and only a request to the bare path
     * reveals it. Hence the explicit pair.
     */
    public function test_every_page_is_reachable_both_prefixed_and_unprefixed(): void
    {
        $this->seedEdition();

        $paths = [
            'programme', 'speakers', 'tarifs', 'lieu', 'partenaires', 'contact', 'archive',
        ];

        foreach ($paths as $path) {
            $this->get('/'.$path)->assertOk("unprefixed /{$path} must render");
            $this->get('/en/'.$path)->assertOk("prefixed /en/{$path} must render");
            $this->get('/ar/'.$path)->assertOk("prefixed /ar/{$path} must render");
        }
    }

    public function test_generated_links_keep_the_current_locale_prefix(): void
    {
        $this->seedEdition();

        $english = $this->get('/en/pricing')->assertOk();

        // `URL::defaults()` in LocalizeUrls is what makes a `route()` call inside
        // a view emit /en/... while the French page emits /....
        //
        // The full absolute href is asserted rather than a bare `href="/contact"`:
        // `route()` returns an absolute URL, so a root-relative needle would never
        // match, and the matching `assertDontSee` would pass without proving
        // anything.
        $english->assertSee('href="'.url('/en/contact').'"', escape: false);
        $english->assertDontSee('href="'.url('/contact').'"', escape: false);
    }

    public function test_french_is_unprefixed_and_canonical(): void
    {
        $this->seedEdition();

        // The header is pinned for the same reason as in the render test above:
        // this is about URL generation, not about header negotiation.
        $response = $this->withHeader('Accept-Language', 'fr')
            ->get('/tarifs')
            ->assertOk();

        $response->assertSee('lang="fr"', escape: false);
        $response->assertSee('href="'.url('/contact').'"', escape: false);
        $response->assertDontSee('href="'.url('/en/contact').'"', escape: false);
    }

    public function test_the_locale_switcher_writes_the_session(): void
    {
        $this->seedEdition();

        $this->post('/locale', ['switch_to' => 'ar'])
            ->assertRedirect();

        $this->assertSame('ar', session('locale'));
    }

    public function test_the_locale_switcher_does_not_accept_an_arbitrary_value(): void
    {
        $this->seedEdition();

        // Anything not in the enum must be rejected, not stored: this endpoint
        // writes to the session, and an unvalidated value would be loaded as a
        // translation file name.
        $this->post('/locale', ['switch_to' => '../../etc/passwd'])->assertSessionHasErrors();
    }

    public function test_the_contact_form_routes_by_subject_type_and_records_the_enquiry(): void
    {
        $edition = $this->seedEdition();
        Mail::fake();

        foreach (ContactMessageRecord::subjectTypes() as $index => $type) {
            ContactRoute::query()->create([
                'subject_type' => $type,
                'label_fr' => 'Sujet',
                'label_en' => 'Subject',
                'label_ar' => 'الموضوع',
                'to_email' => $type.'@arabcia.test',
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        $this->post('/contact', [
            'name' => 'Fatima Zahra',
            'email' => 'fatima@example.test',
            'subject_type' => ContactMessageRecord::SUBJECT_SPONSORING,
            'organisation' => 'Groupe BNA',
            'phone' => '+212600000000',
            'subject' => 'Sponsoring',
            'message' => 'We would like to discuss sponsoring tiers for 2026.',
        ])->assertSessionHasNoErrors();

        Mail::assertQueued(ContactMessage::class, function (ContactMessage $mail) {
            return $mail->hasTo('sponsoring@arabcia.test');
        });

        // Persisted, not just mailed: a queue outage must not lose the enquiry.
        $this->assertDatabaseHas('contact_messages', [
            'edition_id' => $edition->getKey(),
            'subject_type' => ContactMessageRecord::SUBJECT_SPONSORING,
            'email' => 'fatima@example.test',
            'organisation' => 'Groupe BNA',
        ]);
    }

    public function test_an_unknown_subject_type_is_rejected(): void
    {
        $this->seedEdition();
        Mail::fake();

        $this->post('/contact', [
            'name' => 'X',
            'email' => 'x@example.test',
            'subject_type' => 'exec',
            'message' => 'A message that is long enough to pass the length rule.',
        ])->assertSessionHasErrors('subject_type');

        Mail::assertNothingQueued();
    }

    public function test_the_contact_form_is_rate_limited(): void
    {
        $this->seedEdition();
        ContactRoute::query()->create([
            'subject_type' => ContactMessageRecord::SUBJECT_OTHER,
            'label_fr' => 'Autre',
            'label_en' => 'Other',
            'label_ar' => 'آخر',
            'to_email' => 'contact@arabcia.test',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Mail::fake();

        $payload = [
            'name' => 'Test',
            'email' => 'test@example.test',
            'subject_type' => ContactMessageRecord::SUBJECT_OTHER,
            'message' => 'A message that is long enough to pass the length rule.',
        ];

        // The limit comes from config/security.php; the fourth attempt is the one
        // that must be refused, so the loop runs one more time than the cap.
        $limit = (int) config('security.rate_limit_contact');

        for ($i = 0; $i < $limit; $i++) {
            $this->post('/contact', $payload)->assertSessionHasNoErrors();
        }

        $this->post('/contact', $payload)->assertStatus(429);
    }
}
