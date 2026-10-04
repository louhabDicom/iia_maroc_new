<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Models\ContactMessage as ContactMessageRecord;
use App\Models\ContactRoute;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Organisation;
use App\Models\Room;
use App\Models\Sponsor;
use App\Models\TicketType;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'sponsors' => ['/sponsoring'],
            // The two spellings the 2024 site printed. They are registered
            // rather than redirected so an old link keeps resolving to a page
            // instead of to a redirect notice, and they are covered here for
            // exactly that reason: a spelling nobody tests is a spelling that
            // quietly stops working.
            'sponsors legacy' => ['/partenaires'],
            'sponsors alt' => ['/sponsors'],
            'contact' => ['/contact'],
            'archive' => ['/archive'],
            'login' => ['/login'],
            'register' => ['/register'],
        ];
    }

    /**
     * Arabic is the site's default language, so an unprefixed URL is Arabic.
     *
     * Asserted rather than assumed, because the failure mode is invisible: the
     * page still renders, still looks branded, and is simply in the wrong
     * language. `dir="rtl"` is asserted alongside `lang` because an Arabic page
     * that is not marked right-to-left is the exact defect the brief calls out.
     */
    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_in_arabic(string $path): void
    {
        $this->seedEdition();

        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee('lang="ar"', escape: false);
        $response->assertSee('dir="rtl"', escape: false);
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_in_french(string $path): void
    {
        $this->seedEdition();

        $response = $this->get('/fr'.$path);

        $response->assertOk();
        $response->assertSee('lang="fr"', escape: false);
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_in_english(string $path): void
    {
        $this->seedEdition();

        $response = $this->get('/en'.$path);

        $response->assertOk();
        $response->assertSee('lang="en"', escape: false);
    }

    /**
     * An unprefixed request must resolve to Arabic even when the browser asks
     * for something else.
     *
     * This is the behaviour change that makes "Arabic is the default" true
     * rather than aspirational. `Accept-Language` is not consulted: the site's
     * primary audience is Arabic-speaking, and a delegate whose operating system
     * is set to English should still reach the Arabic site first and choose to
     * switch. Pinning the header here is what makes the assertion meaningful —
     * the test client sends `en-us,en;q=0.5` by default.
     */
    public function test_the_browser_language_does_not_override_the_arabic_default(): void
    {
        $this->seedEdition();

        $response = $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')
            ->get('/programme')
            ->assertOk();

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

        foreach (['/', '/programme', '/speakers', '/tarifs', '/lieu', '/sponsoring', '/partenaires', '/contact'] as $path) {
            $response = $this->get($path);

            // The path is in the message so a failure names the page rather than
            // the loop iteration.
            $response->assertOk("GET {$path} did not render with an edition that has no child rows.");
        }
    }

    /**
     * How many reserved "logo to come" plates the page actually rendered.
     *
     * Counted on the rendered `<li>` attribute rather than on the bare class
     * name because the page inlines its own stylesheet, where `.sp-plate--pending`
     * appears as a selector too. Counting the bare class would report one hit
     * from the CSS on a page that reserved nothing at all.
     */
    private function pendingPlatesOn(TestResponse $response): int
    {
        return substr_count(
            $response->getContent(),
            'sp-plate--pending" aria-hidden="true"'
        );
    }

    /**
     * The wall reserves a fixed number of "logo to come" plates.
     *
     * `range(1, 0)` is `[1, 0]` in PHP and not an empty array, so the count is
     * asserted rather than assumed: a wall that is exactly full used to reserve
     * two stray plates for. Over-subscribing is covered too, because clamping is
     * the other direction this can fail.
     */
    #[DataProvider('sponsorCountsAndPendingPlates')]
    public function test_the_logo_wall_reserves_the_remaining_plates(int $confirmed, int $pending): void
    {
        $edition = $this->seedEdition();

        Sponsor::factory()
            ->count($confirmed)
            ->create(['edition_id' => $edition->getKey()]);

        $response = $this->get('/fr/sponsoring')->assertOk();

        $this->assertSame($pending, $this->pendingPlatesOn($response));
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function sponsorCountsAndPendingPlates(): array
    {
        return [
            'none confirmed' => [0, 4],
            'one confirmed' => [1, 3],
            // The boundary: exactly full must reserve none at all.
            'wall exactly full' => [4, 0],
            'over-subscribed' => [6, 0],
        ];
    }

    /**
     * Sponsors from earlier editions render in the courtesy rail, never on the
     * wall under sale.
     *
     * A "previous" tier sponsor is a real row in the same table, so a query that
     * forgot the tier filter would print a company that already declined this
     * edition as though it were a current sponsor.
     */
    public function test_previous_edition_sponsors_stay_off_the_current_wall(): void
    {
        $edition = $this->seedEdition();

        Sponsor::factory()
            ->previous()
            ->create([
                'edition_id' => $edition->getKey(),
                'name' => 'Ancienne Compagnie',
            ]);

        $response = $this->get('/fr/sponsoring')->assertOk();

        // The name is still on the page, in the rail.
        $response->assertSee('Ancienne Compagnie');

        // Four plates still reserved: the rail consumed none of the wall.
        $this->assertSame(4, $this->pendingPlatesOn($response));
    }

    /**
     * An unpublished sponsor is invisible, which frees its plate.
     *
     * `is_published` is the switch that takes a logo off the site without
     * deleting the row, so the wall has to count what is visible rather than
     * what exists.
     */
    public function test_an_unpublished_sponsor_does_not_occupy_a_plate(): void
    {
        $edition = $this->seedEdition();

        Sponsor::factory()->count(4)->create(['edition_id' => $edition->getKey()]);
        Sponsor::factory()->unpublished()->create(['edition_id' => $edition->getKey()]);

        $response = $this->get('/fr/sponsoring')->assertOk();

        $this->assertSame(0, $this->pendingPlatesOn($response));
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
        // the stored title. `/fr/programme` is the prefixed address, so the
        // request language comes from the URL rather than from any default.
        //
        // Escaping is left on deliberately: the title contains an apostrophe, so
        // this also asserts that Blade emitted it as an entity rather than raw.
        $this->get('/fr/programme')
            ->assertOk()
            ->assertSee('Atelier d\'audit');
    }

    public function test_an_unknown_locale_prefix_is_a_404(): void
    {
        $this->seedEdition();

        // The prefix is constrained to en|fr on purpose: a request for /ar/... or
        // /xx/... must not be treated as a localised page. Arabic is the default
        // and is served unprefixed, so /ar/... has no route at all.
        $this->get('/ar/programme')->assertNotFound();
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
            $this->get('/fr/'.$path)->assertOk("prefixed /fr/{$path} must render");
        }
    }

    public function test_generated_links_keep_the_current_locale_prefix(): void
    {
        $this->seedEdition();

        $english = $this->get('/en/pricing')->assertOk();

        // `URL::defaults()` in LocalizeUrls is what makes a `route()` call inside
        // a view emit /en/... while the Arabic page emits /....
        //
        // The full absolute href is asserted rather than a bare `href="/contact"`:
        // `route()` returns an absolute URL, so a root-relative needle would never
        // match, and the matching `assertDontSee` would pass without proving
        // anything.
        $english->assertSee('href="'.url('/en/contact').'"', escape: false);
        $english->assertDontSee('href="'.url('/contact').'"', escape: false);
    }

    public function test_arabic_is_unprefixed_and_canonical(): void
    {
        $this->seedEdition();

        $response = $this->get('/tarifs')->assertOk();

        $response->assertSee('lang="ar"', escape: false);
        $response->assertSee('href="'.url('/contact').'"', escape: false);
        $response->assertDontSee('href="'.url('/en/contact').'"', escape: false);
        $response->assertDontSee('href="'.url('/fr/contact').'"', escape: false);
    }

    /**
     * The alternates a page advertises have to be real, fetchable addresses.
     *
     * This is where the default language is easy to get wrong: building the
     * Arabic alternate as `route($name, ['locale' => 'ar'])` emits /ar/contact,
     * which does not exist on this site. The assertion is on the rendered href,
     * not on the generator, because the href is what a crawler follows.
     */
    public function test_the_hreflang_alternates_resolve_to_real_addresses(): void
    {
        $this->seedEdition();

        // Read from the French page, not the Arabic one: on a prefixed page
        // `URL::defaults()` is set, and an empty `locale` parameter loses to it,
        // which is exactly how an hreflang ends up claiming the French page is
        // the Arabic one.
        $response = $this->get('/fr/contact')->assertOk();

        $response->assertSee('hreflang="ar" href="'.url('/contact').'"', escape: false);
        $response->assertSee('hreflang="en" href="'.url('/en/contact').'"', escape: false);
        $response->assertDontSee('href="'.url('/ar/contact').'"', escape: false);
    }

    public function test_the_locale_switcher_writes_the_session(): void
    {
        $this->seedEdition();

        // English rather than Arabic: Arabic is already the default, so switching
        // to it would pass even if the endpoint did nothing at all.
        $this->post('/locale', ['switch_to' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    /**
     * Switching to the default language must drop the prefix, and switching to a
     * non-default one must add it.
     *
     * Both directions, because the bug is asymmetric: a switcher that always adds
     * the prefix produces /ar/contact, which 404s, and one that never adds it
     * leaves an English visitor on an Arabic page with no way back.
     */
    public function test_switching_language_rewrites_the_prefix(): void
    {
        $this->seedEdition();

        $this->post('/locale', ['switch_to' => 'fr'])
            ->assertRedirect('/fr/contact');

        $this->post('/locale', ['switch_to' => 'ar'])
            ->assertRedirect('/contact');
    }

    /**
     * The switcher marks the language being read, so a visitor can see which of
     * the three they are on without opening the menu.
     */
    public function test_the_switcher_marks_the_current_language(): void
    {
        $this->seedEdition();

        $this->get('/fr/contact')
            ->assertOk()
            ->assertSee('value="fr" class="ux-lang__option" lang="fr" dir="ltr" aria-current="true"', escape: false);
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
