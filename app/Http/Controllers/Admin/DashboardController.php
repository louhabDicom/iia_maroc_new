<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Edition;
use App\Models\Order;
use App\Models\Participant;
use App\Models\SpeakerSubmission;
use App\Models\SponsorshipEnquiry;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * The staff overview.
 *
 * Everything here is a count or a sum over the *current* edition, computed in
 * the database rather than by loading rows and reducing them in PHP. That is not
 * premature optimisation: the orders table grows by one row per transaction for
 * the whole run-up to the conference, and the dashboard is the page an organiser
 * refreshes constantly to watch the number move.
 *
 * The three "needs a decision" counts — unhandled speaker submissions, open
 * sponsorship enquiries and unanswered contact messages — are the reason the
 * page exists rather than a revenue chart. Each of those three models already
 * carries `handled_by`/`handled_at` columns that no public code path writes,
 * which is what made the queue shape obvious; the enums on them make the
 * "open" test a scope instead of a guess about which status strings mean
 * "unhandled".
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        // An admin with no edition is a legitimate state during setup, so the
        // dashboard renders empty rather than throwing on a null relation.
        if ($edition === null) {
            return view('admin.dashboard', [
                'edition' => null,
                'locale' => Locale::parse(app()->getLocale()),
                'currentRoute' => 'admin.dashboard',
                'stats' => [],
                'revenue' => null,
                'currency' => config('conference.default_currency', 'MAD'),
                'salesByType' => collect(),
                'ordersByStatus' => collect(),
                'recentOrders' => collect(),
                'queues' => [],
            ]);
        }

        $paid = OrderStatus::Paid;

        return view('admin.dashboard', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),

            // `$currentRoute` is what the admin layout uses to highlight the
            // active nav item. Every admin page sets it.
            'currentRoute' => 'admin.dashboard',

            'stats' => [
                'orders' => $edition->orders()->count(),
                'paidOrders' => $edition->orders()->where('status', $paid)->count(),
                'participants' => Participant::query()
                    ->whereIn('order_id', $edition->orders()->select('id'))
                    ->count(),
                'checkedIn' => Participant::query()
                    ->whereIn('order_id', $edition->orders()->select('id'))
                    ->whereNotNull('checked_in_at')
                    ->count(),
            ],

            // Summed in SQL for the reason given in the class docblock. Only
            // `Paid` counts as revenue: summing `total` across every status
            // would report cancelled and abandoned orders as income, which is
            // the mistake that makes a reconciliation impossible to trust.
            'revenue' => (int) $edition->orders()
                ->where('status', $paid)
                ->sum('total'),

            'currency' => (string) ($edition->orders()
                ->where('status', $paid)
                ->value('currency') ?? config('conference.default_currency', 'MAD')),

            // Places sold per tariff, counting member places at the member
            // price and the remainder at the standard one. Taken from the order
            // aggregate columns rather than from the participant rows, because
            // an order can be paid before its participants are filled in.
            'salesByType' => $this->salesByType($edition),

            // Every status in the enum, including the ones with a count of
            // zero, so the breakdown answers "is anything stuck?" rather than
            // only "what happened?".
            'ordersByStatus' => $this->ordersByStatus($edition),

            'recentOrders' => $edition->orders()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get(),

            'queues' => $this->queues($edition),
        ]);
    }

    /** @return Collection<int, object> */
    private function salesByType(Edition $edition): Collection
    {
        return $edition->ticketTypes()
            ->withSum('orders', 'member_count')
            ->withSum('orders', 'standard_count')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($type) {
                $member = (int) ($type->orders_sum_member_count ?? 0);
                $standard = (int) ($type->orders_sum_standard_count ?? 0);

                return (object) [
                    'type' => $type,
                    'member' => $member,
                    'standard' => $standard,
                    'total' => $member + $standard,
                    // Revenue is derived from the tariff's own prices rather
                    // than from the order totals, so the figure on the pricing
                    // page and the figure here can be compared directly. A
                    // tariff that changed price mid-run would break that
                    // equivalence, which is a second reason not to treat this as
                    // an accounting total — it is a planning aid.
                    'revenue' => $member * $type->price_member
                        + $standard * $type->price_standard,
                ];
            });
    }

    /** @return Collection<string, int> */
    private function ordersByStatus(Edition $edition): Collection
    {
        // A single grouped query, then keyed by the enum's backing value, so
        // absent statuses are zero rather than missing from the loop.
        $counts = $edition->orders()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s): array => [$s->value => (int) ($counts[$s->value] ?? 0)]);
    }

    /**
     * The three queues that need a human decision.
     *
     * @return array<string, array{key: string, count: int, route: string}>
     */
    private function queues(Edition $edition): array
    {
        return [
            'submissions' => [
                'key' => 'admin.queue.submissions',
                'count' => SpeakerSubmission::query()
                    ->where('edition_id', $edition->getKey())
                    ->pending()
                    ->count(),
                'route' => 'admin.submissions.index',
            ],
            'enquiries' => [
                'key' => 'admin.queue.enquiries',
                'count' => SponsorshipEnquiry::query()
                    ->where('edition_id', $edition->getKey())
                    ->open()
                    ->count(),
                'route' => 'admin.enquiries.index',
            ],
            'messages' => [
                'key' => 'admin.queue.messages',
                'count' => ContactMessage::query()
                    ->where('edition_id', $edition->getKey())
                    ->open()
                    ->count(),
                'route' => 'admin.messages.index',
            ],
        ];
    }

    /** Format a minor-unit figure for the dashboard headline. */
    public static function format(int $minorUnits, string $currency): string
    {
        return Money::format($minorUnits, $currency);
    }
}
