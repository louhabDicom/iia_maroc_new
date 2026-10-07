<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who may open an order, and who may not.
 *
 * Two defects, both of which had to be fixed together for the page to work at
 * all — a delegate could not open a single one of their own invoices.
 *
 *  1. The route read `$page('orders.show', 'commande', ...)`. There was no
 *     `{order}` segment to put the id in, so `route('orders.show', ['order' =>
 *     $id])` produced `/commande?order=21` — the id as a query string. The URL
 *     then matched `/commande`, where route-model binding had no `order` to
 *     resolve, and the ownership check compared a null order against the
 *     signed-in user. Every delegate was refused their own invoice with a 403.
 *
 *  2. The view read `$order->latestPayment` with property syntax. Eloquent
 *     treats a same-named method as a relationship, demanded a Relation back,
 *     and threw — a 500 on the invoice once the 403 was out of the way.
 *
 * The ownership rule itself is unchanged and is asserted here in both
 * directions: an owner gets their order, and nobody else does.
 */
class OrderInvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function conference(): TicketType
    {
        Country::query()->create([
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
            'title' => ['fr' => 'ARABCIA 2026', 'en' => 'ARABCIA 2026', 'ar' => 'ARABCIA 2026'],
            'theme' => ['fr' => 'Thème', 'en' => 'Theme', 'ar' => 'الموضوع'],
            'introduction' => ['fr' => 'La conférence.', 'en' => 'The conference.', 'ar' => 'المؤتمر.'],
            'city' => 'Rabat',
            'country_iso2' => 'MA',
            'venue_name' => 'Four Seasons Hotel Rabat',
            'contact_email' => 'contact@arabcia.test',
            'starts_on' => '2026-12-16',
            'ends_on' => '2026-12-17',
            'languages' => ['fr', 'en', 'ar'],
            'status' => EditionStatus::Published,
            'is_current' => true,
            'registration_open' => true,
        ]);

        return TicketType::factory()->create(['edition_id' => $edition->getKey()]);
    }

    private function delegate(): User
    {
        return User::factory()->create([
            'totp_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            'totp_confirmed_at' => now(),
            'terms_accepted_at' => now(),
            'locale' => 'fr',
        ]);
    }

    private function orderFor(User $owner, TicketType $ticket): Order
    {
        return Order::factory()->create([
            'edition_id' => $ticket->edition_id,
            'user_id' => $owner->getKey(),
            'ticket_type_id' => $ticket->getKey(),
            'status' => OrderStatus::Paid,
        ]);
    }

    public function test_a_delegate_can_open_their_own_order(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $this->actingAs($owner)
            ->get(route('orders.show', ['order' => $order->getKey()]))
            ->assertOk()
            ->assertSee($order->reference);
    }

    /**
     * The page is the invoice sheet, not a table that merely resembles one.
     *
     * The sheet markup and styles live in resources/views/invoices/partials/
     * and are shared with the standalone print pages, so this asserts the
     * order page still includes them rather than drifting into a second,
     * divergent design.
     */
    public function test_the_order_page_renders_the_shared_invoice_sheet(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $this->actingAs($owner)
            ->get(route('orders.show', ['order' => $order->getKey()]))
            ->assertOk()
            ->assertSee('invoice-doc--embedded', false)
            ->assertSee('class="sheet"', false)
            ->assertSee(__('order.invoice.proforma_title', [], 'fr'));
    }

    /**
     * The regression itself: the id has to travel in the path. When it was
     * generated as `?order=21` the route resolved to `/commande` with no order
     * bound, and the owner was refused their own invoice.
     */
    public function test_the_order_id_is_part_of_the_path_not_a_query_string(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $url = route('orders.show', ['order' => $order->getKey()]);

        $this->assertStringEndsWith('/commande/'.$order->getKey(), $url);
        $this->assertStringNotContainsString('order=', $url);
    }

    /**
     * 404 rather than 403, on purpose.
     *
     * The order is resolved through the buyer's own orders relation, so the
     * miss is indistinguishable from an id that was never issued. A 403 would
     * confirm the order exists and turn the endpoint into an oracle for
     * guessing live reference numbers.
     */
    public function test_a_delegate_cannot_open_somebody_elses_order(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $stranger = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $response = $this->actingAs($stranger)
            ->get(route('orders.show', ['order' => $order->getKey()]));

        $response->assertNotFound();

        // And the reference must not leak into the error page either.
        $this->assertStringNotContainsString($order->reference, $response->getContent() ?: '');
    }

    public function test_a_signed_out_visitor_is_sent_to_the_login_page(): void
    {
        $ticket = $this->conference();
        $order = $this->orderFor($this->delegate(), $ticket);

        $this->get(route('orders.show', ['order' => $order->getKey()]))
            ->assertRedirect(route('login'));
    }

    public function test_a_delegate_cannot_download_somebody_elses_invoice(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $stranger = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $this->actingAs($stranger)
            ->post(route('orders.invoice', ['order' => $order->getKey()]))
            ->assertNotFound();
    }

    /**
     * `latestPaymentRecord()` is a plain method, not a relation, and has to stay
     * one. Eloquent resolves property access to a same-named method as a
     * relationship and throws when the return type is not a Relation — which is
     * exactly the 500 this page used to serve.
     */
    public function test_the_latest_payment_is_reachable_without_a_relationship(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        $this->assertNull($order->latestPaymentRecord());

        $this->actingAs($owner)
            ->get(route('orders.show', ['order' => $order->getKey()]))
            ->assertOk();
    }

    /**
     * The invoice in all three languages.
     *
     * Asserted on an order with no attendees, because that is the branch that
     * reads the two strings added with the redesign. A missing key does not
     * throw in Laravel — it renders as the dotted key — so an untranslated
     * string would otherwise reach a delegate looking like
     * `order.participants_pending`.
     */
    public function test_the_invoice_renders_in_every_language(): void
    {
        $ticket = $this->conference();
        $owner = $this->delegate();
        $order = $this->orderFor($owner, $ticket);

        foreach (['fr', 'en', 'ar'] as $locale) {
            // One request: the response is both the assertion that the page
            // renders and the document the keys are searched in. The locale
            // matters — implicit route-model binding resolved the order on the
            // unprefixed URL but not on the prefixed one, which is why the
            // controller no longer depends on it.
            // Arabic is the default language, so it is served without a prefix and
            // `/ar/commande/1` canonicalises to `/commande/1` with a 301. Following
            // the redirect is what a browser does, and it lets one assertion
            // cover every language.
            $response = $this->followingRedirects()
                ->actingAs($owner)
                ->get(route('orders.show', ['order' => $order->getKey(), 'locale' => $locale]));

            $response->assertOk();
            $html = $response->getContent() ?: '';

            foreach (['order.participants_pending', 'order.cta_lede'] as $key) {
                $this->assertStringNotContainsString($key, $html, "[$locale] left {$key} untranslated");
            }
        }
    }
}
