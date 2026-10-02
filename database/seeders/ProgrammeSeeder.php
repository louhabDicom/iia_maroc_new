<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SessionFormat;
use App\Models\ConferenceSession;
use App\Models\Edition;
use App\Models\Room;
use App\Models\Speaker;
use App\Models\Track;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 2026 scientific programme.
 *
 * Transcribed from the client's programme table in the 2026 dossier (pages 20
 * and 21). The French titles and summaries are that document's "Séquence /
 * thème" and "Finalité" columns verbatim; English was written against the
 * wording used in the English sponsorship deck. Nothing here is invented.
 *
 * The grid matches the shape the dossier prints:
 *
 *   . 7 plenary-room sessions, of which 3 are panels
 *   . 18 workshops: 3 parallel blocks of 3 parcours, on each of 2 days
 *   . 6 innovation labs: 3 per day
 *   . a registration block, an opening, a trophy ceremony, the breaks and the
 *     two lunches
 *
 * The home page counts its workshops, labs and days from this table (see
 * HomeController), so the shape of the grid is what the landing page claims.
 *
 * The dossier's brief line reads "2 keynotes, 7 plenary sessions including 3
 * panels", but the programme table itself labels all seven plenary-room slots
 * "Plénière 1..7" and never marks one as a keynote. The seven are seeded as
 * printed; if the client confirms which two are keynotes, only their `format`
 * needs changing here — nothing downstream counts them.
 *
 * Workshops run as three parallel parcours in three rooms; the labs run three
 * rooms at the end of each day. Concurrency therefore peaks at three
 * simultaneous sessions, under the brief's cap of four. No room is ever
 * double-booked, and that is checked after saving rather than assumed.
 *
 * SPEAKERS ARE DELIBERATELY ABSENT. The dossier names no speaker for any
 * session, so no session carries a `speakers` key and none is attached. The
 * resolution by name is kept so the confirmed list can be added to the data
 * blocks above when it arrives, without touching the seeding mechanics.
 *
 * ARABIC IS DELIBERATELY ABSENT TOO. No Arabic was written for the new
 * content: the client's Arabic wording is theirs to supply. TranslatedString's
 * documented fallback chain renders French for an Arabic reader rather than an
 * empty string, and the missing `ar` key is visible in the admin, so the gap is
 * trackable rather than silent.
 *
 * Sessions are deleted and rebuilt rather than updated. A programme is a grid,
 * so a re-run that left stale rows behind would put two sessions in one room at
 * one time.
 */
class ProgrammeSeeder extends Seeder
{
    /** The edition being programmed. */
    private Edition $edition;

    /** @var array<string, int> Track code => id, for this edition. */
    private array $tracks = [];

    public function run(): void
    {
        $this->assertProgrammeTextIsClean(__FILE__);

        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        // The rebuild deletes every session before inserting the new grid, so a
        // failure halfway through would otherwise leave the programme page empty
        // rather than untouched. Rolled back, a failed re-run changes nothing.
        DB::transaction(function () use ($edition): void {
            $this->seedGrid($edition);
        });
    }

    private function seedGrid(Edition $edition): void
    {
        $this->edition = $edition;
        $this->tracks = Track::query()
            ->where('edition_id', $edition->getKey())
            ->pluck('id', 'code')
            ->all();

        $speakers = Speaker::query()
            ->where('edition_id', $edition->getKey())
            ->get()
            ->keyBy(fn (Speaker $s): string => $s->last_name.' '.$s->first_name);

        ConferenceSession::query()
            ->where('edition_id', $edition->getKey())
            ->delete();

        $saved = [];

        foreach ($this->grid(
            $edition->starts_on->toDateString(),
            $edition->ends_on->toDateString(),
        ) as $row) {
            $this->assertChairIsPresent($row);

            $session = ConferenceSession::query()->create([
                'edition_id' => $edition->getKey(),
                'track_id' => $this->trackId($row['track'] ?? null),
                'room_id' => $this->roomId($row['room']),
                'format' => $row['format'],
                'title' => $row['title'],
                'summary' => $row['summary'] ?? null,
                'objectives' => $row['objectives'] ?? null,
                'session_date' => $row['date'],
                'starts_at' => $row['from'],
                'ends_at' => $row['to'],
                'language' => $row['format'] === SessionFormat::InnovationLab
                    ? 'fr'
                    : 'fr, en',
                // The brief requires simultaneous FR/EN/AR interpretation in the
                // plenary rooms; a 30-seat lab cannot seat three channels.
                'interpretation_available' => $row['room'] === 'PLENARY',
                'is_published' => true,
                'sort_order' => $row['sort'],
            ]);

            foreach ($row['speakers'] ?? [] as $index => $name) {
                $speaker = $speakers->get($name);

                if ($speaker === null) {
                    throw new \RuntimeException(
                        "Programme references unknown speaker [{$name}]. Seed SpeakersSeeder first."
                    );
                }

                $session->speakers()->attach($speaker->getKey(), [
                    'role' => $this->roleFor($name, $row),
                    // Presentation order, so a chair appears first instead of
                    // being sorted alphabetically.
                    'sort_order' => $index,
                ]);
            }

            $saved[] = $session;
        }

        $this->assertNoRoomClash($saved);
    }

    /**
     * The pivot role for one participant in a session.
     *
     * A panel has a named chair and the rest are speakers; a lab is moderated;
     * a workshop is co-animated by everyone on it. The chair is therefore
     * resolved *per person* from the row's `chair` key rather than applied to
     * the whole row.
     *
     * @param  array<string, mixed>  $row
     */
    private function roleFor(string $name, array $row): string
    {
        if (($row['chair'] ?? null) === $name) {
            return 'chair';
        }

        return match ($row['format']) {
            SessionFormat::InnovationLab => 'moderator',
            SessionFormat::Workshop => 'animator',
            default => 'speaker',
        };
    }

    /**
     * A named chair has to be one of the session's speakers.
     *
     * Without this the chair is simply never attached: roleFor() compares
     * against the speakers list, finds no match, and every participant is stored
     * as a plain speaker. The panel then renders with no moderator at all and
     * nothing in the output says why.
     *
     * @param  array<string, mixed>  $row
     */
    private function assertChairIsPresent(array $row): void
    {
        $chair = $row['chair'] ?? null;

        if ($chair === null) {
            return;
        }

        if (! in_array($chair, $row['speakers'] ?? [], true)) {
            throw new \RuntimeException(sprintf(
                'Session [%s] names a chair [%s] who is not in its speaker list. '
                .'Add them to "speakers" or drop the "chair" key.',
                $row['title']['fr'] ?? $row['sort'],
                $chair,
            ));
        }
    }

    /**
     * Resolve a room code to its id, scoped to this edition.
     *
     * `rooms` is unique on (edition_id, code), so an unscoped lookup would
     * quietly return the 2024 room as soon as a second edition exists.
     */
    private function roomId(string $code): int
    {
        $id = Room::query()
            ->where('edition_id', $this->edition->getKey())
            ->where('code', $code)
            ->value('id');

        if ($id === null) {
            throw new \RuntimeException("Unknown room [{$code}] for this edition.");
        }

        return (int) $id;
    }

    private function trackId(?string $code): ?int
    {
        return $code === null ? null : ($this->tracks[$code] ?? null);
    }

    /**
     * Refuse a grid that double-books a room.
     *
     * Run after the save so the comparison uses the stored times, which is what
     * the programme page reads, rather than the strings written here.
     *
     * @param  array<int, ConferenceSession>  $sessions
     */
    private function assertNoRoomClash(array $sessions): void
    {
        foreach ($sessions as $i => $a) {
            foreach (array_slice($sessions, $i + 1) as $b) {
                if ($a->conflictsWith($b)) {
                    throw new \RuntimeException(sprintf(
                        'Room clash: "%s" and "%s" overlap in %s on %s.',
                        $a->title,
                        $b->title,
                        $a->room?->code,
                        $a->session_date->toDateString(),
                    ));
                }
            }
        }
    }

    /**
     * Stop the seed if the three-language text has picked up a foreign script.
     *
     * Written by hand in three scripts, a stray Han or Cyrillic character in a
     * French sentence is invisible in a diff and embarrassing on a printed
     * programme. Whole Unicode blocks are whitelisted, so accented French and
     * Arabic punctuation pass by construction and a foreign script stands out.
     * Cheap, and it runs before anything is written.
     */
    private function assertProgrammeTextIsClean(string $file): void
    {
        $source = (string) file_get_contents($file);

        $allowed = '/[\x{00A0}-\x{00FF}\x{0100}-\x{024F}\x{2000}-\x{206F}'
            .'\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{200B}-\x{200F}'
            .'\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';

        $suspect = (string) preg_replace($allowed, '', $source);

        if (preg_match_all('/[^\x00-\x7F]/u', $suspect, $found) === 0) {
            return;
        }

        // Reported as codepoints and line numbers: the offending glyph renders
        // as mojibake in a terminal, so printing it would mislead the reader.
        $points = array_map(
            static fn (string $c): string => 'U+'.strtoupper(
                bin2hex(mb_convert_encoding($c, 'UTF-16BE', 'UTF-8'))
            ),
            array_values(array_unique($found[0])),
        );

        $lines = [];
        foreach (explode("\n", $suspect) as $index => $line) {
            if (preg_match('/[^\x00-\x7F]/u', $line)) {
                $lines[] = $index + 1;
            }
        }

        throw new \RuntimeException(sprintf(
            'Programme text contains characters outside Latin and Arabic: %s (lines %s).',
            implode(', ', $points),
            implode(', ', $lines),
        ));
    }

    // --- Grid ------------------------------------------------------------

    /**
     * The three parallel workshop tracks, in room order.
     *
     * The dossier organises the parallel workshops into three named "parcours",
     * and each one runs in its own room at the same time. The order here is the
     * order the client printed them in, so ATELIER_1 always carries the first
     * parcours.
     */
    private const PARCOURS = [
        'AUDIT_TRANSFO' => 'ATELIER_1',
        'RESILIENCE' => 'ATELIER_2',
        'AUDITOR_DAME' => 'ATELIER_3',
    ];

    /** Innovation Labs, in room order. The dossier lists three per day. */
    private const LAB_ROOMS = ['LAB_1', 'LAB_2', 'LAB_3'];

    /**
     * The whole two-day grid.
     *
     * @return array<int, array<string, mixed>>
     */
    private function grid(string $day1, string $day2): array
    {
        return array_merge($this->dayOne($day1), $this->dayTwo($day2));
    }

    /**
     * Day one — Wednesday 16 December 2026.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayOne(string $date): array
    {
        return $this->buildDay($date, $this->dayOneSessions(), $this->dayOneWorkshops(), $this->dayOneLabs());
    }

    /**
     * Day two — Thursday 17 December 2026.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayTwo(string $date): array
    {
        return $this->buildDay($date, $this->dayTwoSessions(), $this->dayTwoWorkshops(), $this->dayTwoLabs());
    }

    /**
     * Assemble one day in running order: plenary-room programme first, then the
     * parallel workshop blocks, then the labs.
     *
     * `sort` is assigned here rather than in the data blocks so that inserting a
     * session cannot silently give two of them the same sort order.
     *
     * @param  array<int, array<string, mixed>>  $sessions
     * @param  array<int, array{0: string, 1: string, 2: array<string, array<string, string>>}>  $workshops
     * @param  array<int, array{0: string, 1: string, 2: list<string>}>  $labs
     * @return array<int, array<string, mixed>>
     */
    private function buildDay(string $date, array $sessions, array $workshops, array $labs): array
    {
        $rows = [];
        $sort = 1;

        foreach ($sessions as $session) {
            $rows[] = $session + ['sort' => $sort++, 'date' => $date];
        }

        foreach ($workshops as [$from, $to, $topics]) {
            foreach (self::PARCOURS as $track => $room) {
                $title = $topics[$track] ?? null;

                // A parcours slot with no topic is a data error, not an empty
                // session: the dossier prints one topic per parcours per block,
                // so a missing key means a transcription slip and must not seed
                // as a blank workshop that attendees would see.
                if ($title === null) {
                    throw new \RuntimeException(sprintf(
                        'Workshop block %s-%s on %s has no topic for track [%s].',
                        $from,
                        $to,
                        $date,
                        $track,
                    ));
                }

                $rows[] = [
                    'sort' => $sort++,
                    'date' => $date,
                    'room' => $room,
                    'track' => $track,
                    'from' => $from,
                    'to' => $to,
                    'format' => SessionFormat::Workshop,
                    'title' => $title,
                    // The dossier prints no purpose line for a workshop, and
                    // inventing one would put text in the client's mouth.
                    'summary' => null,
                    'objectives' => null,
                ];
            }
        }

        foreach ($labs as [$from, $to, $topics]) {
            foreach (self::LAB_ROOMS as $index => $room) {
                $title = $topics[$index] ?? null;

                if ($title === null) {
                    throw new \RuntimeException(sprintf(
                        'Innovation Lab block %s-%s on %s has fewer than %d topics.',
                        $from,
                        $to,
                        $date,
                        count(self::LAB_ROOMS),
                    ));
                }

                $rows[] = [
                    'sort' => $sort++,
                    'date' => $date,
                    'room' => $room,
                    'from' => $from,
                    'to' => $to,
                    'format' => SessionFormat::InnovationLab,
                    'title' => $title,
                    // A lab belongs to no parcours: it is a single topic in its
                    // own room, and assigning one would mislabel it on the grid.
                    'track' => null,
                    'summary' => null,
                    'objectives' => null,
                ];
            }
        }

        return $rows;
    }

    /**
     * A plenary-room row.
     *
     * Factored so the eight plenary-room sessions across the two days read as
     * data rather than as twelve lines of array plumbing each.
     *
     * @param  array<string, string>  $title
     * @param  array<string, string>|null  $summary
     * @return array<string, mixed>
     */
    private function plenaryRow(
        string $from,
        string $to,
        SessionFormat $format,
        array $title,
        ?array $summary = null,
    ): array {
        return [
            'room' => 'PLENARY',
            'from' => $from,
            'to' => $to,
            'format' => $format,
            'title' => $title,
            'summary' => $summary,
            'objectives' => null,
        ];
    }

    /**
     * Day one's plenary-room programme.
     *
     * Titles are the dossier's "Séquence / thème" column and summaries its
     * "Finalité" column, verbatim in French.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayOneSessions(): array
    {
        return [
            $this->plenaryRow('08:00', '09:00', SessionFormat::Registration, [
                'fr' => 'Accueil, inscription et networking',
                'en' => 'Registration and networking',
            ], [
                'fr' => 'Enregistrement des participants et premiers échanges.',
                'en' => 'Participants register and have first exchanges.',
            ]),
            $this->plenaryRow('09:30', '10:00', SessionFormat::Opening, [
                'fr' => 'Ouverture officielle par l\'IIA Maroc, l\'ARABCIA et The IIA',
                'en' => 'Official opening by IIA Morocco, ARABCIA and The IIA',
            ], [
                'fr' => 'Mots de bienvenue, reconnaissances et présentation des ambitions de la conférence.',
                'en' => 'Welcome remarks, acknowledgements and a presentation of the conference\'s ambitions.',
            ]),
            $this->plenaryRow('10:00', '10:30', SessionFormat::Award, [
                'fr' => 'Remise des trophées de certification',
                'en' => 'Presentation of the certification trophies',
            ], [
                // "Reconnaitre" is transcribed as printed in the dossier. It
                // looks truncated there — the sentence has no object — so this
                // needs confirming with the client before print.
                'fr' => 'Reconnaitre',
                'en' => 'To recognise',
            ]),
            $this->plenaryRow('10:30', '11:15', SessionFormat::Plenary, [
                'fr' => 'Empreinte : bâtir la confiance à l\'ère de l\'accélération de l\'IA',
                'en' => 'The footprint of trust: building trust in the age of accelerating AI',
            ], [
                'fr' => 'Montrer comment l\'Audit Interne anticipe les risques, soutient la gouvernance et renforce la résilience.',
                'en' => 'Show how internal audit anticipates risks, supports governance and strengthens resilience.',
            ]),
            $this->plenaryRow('11:15', '11:45', SessionFormat::Break, [
                'fr' => 'Pause-café',
                'en' => 'Coffee break',
            ]),
            $this->plenaryRow('11:45', '12:30', SessionFormat::Plenary, [
                'fr' => 'Diriger dans l\'incertitude d\'un monde fragmenté : nouveau paysage mondial et régional des risques',
                'en' => 'Leading through uncertainty in a fragmented world: the new global and regional risk landscape',
            ], [
                'fr' => 'Relier IA, cybermenaces, instabilité géopolitique, criminalité financière, transformation digitale et chaînes d\'approvisionnement.',
                'en' => 'Connect AI, cyber threats, geopolitical instability, financial crime, digital transformation and supply chains.',
            ]),
            $this->plenaryRow('12:30', '13:30', SessionFormat::Panel, [
                'fr' => 'Ce que les conseils attendent de l\'Audit Interne en période de disruption continue',
                'en' => 'What boards expect from internal audit in a period of continuous disruption',
            ], [
                'fr' => 'Examiner les attentes, les moyens disponibles et les pratiques qui permettent de construire la confiance.',
                'en' => 'Examine the expectations, the means available and the practices that build trust.',
            ]),
            $this->plenaryRow('13:30', '14:30', SessionFormat::Break, [
                'fr' => 'Déjeuner',
                'en' => 'Lunch',
            ]),
            $this->plenaryRow('16:20', '16:50', SessionFormat::Break, [
                'fr' => 'Pause-café',
                'en' => 'Coffee break',
            ]),
        ];
    }

    /**
     * Day two's plenary-room programme.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayTwoSessions(): array
    {
        return [
            $this->plenaryRow('08:00', '09:00', SessionFormat::Registration, [
                'fr' => 'Accueil des participants',
                'en' => 'Participants welcome',
            ], [
                'fr' => 'Accueil et networking.',
                'en' => 'Welcome and networking.',
            ]),
            $this->plenaryRow('09:00', '10:00', SessionFormat::Plenary, [
                'fr' => 'L\'avantage humain à l\'ère de l\'IA : l\'auditeur résilient et ce que la technologie ne remplacera jamais',
                'en' => 'The human advantage in the age of AI: the resilient auditor and what technology will never replace',
            ], [
                'fr' => 'Valoriser jugement professionnel, esprit critique, éthique, discernement et capacité à créer la confiance.',
                'en' => 'Value professional judgement, critical thinking, ethics, discernment and the capacity to create trust.',
            ]),
            $this->plenaryRow('10:00', '11:00', SessionFormat::Panel, [
                'fr' => 'Les femmes à la tête de la transformation de l\'Audit Interne',
                'en' => 'Women leading the transformation of internal audit',
            ], [
                'fr' => 'Mettre en lumière le leadership féminin et les expériences de transformation de la profession.',
                'en' => 'Highlight women\'s leadership and the profession\'s experiences of transformation.',
            ]),
            $this->plenaryRow('11:00', '11:30', SessionFormat::Break, [
                'fr' => 'Pause-café',
                'en' => 'Coffee break',
            ]),
            $this->plenaryRow('11:30', '12:30', SessionFormat::Plenary, [
                'fr' => 'De la coordination des Trois Lignes à l\'assurance intégrée : construire une cartographie d\'assurance au service de la résilience',
                'en' => 'From Three Lines coordination to integrated assurance: building an assurance map in service of resilience',
            ], [
                'fr' => 'Présenter les principes d\'une vision cohérente, complète et dynamique des risques et des contrôles.',
                'en' => 'Present the principles of a coherent, complete and dynamic view of risks and controls.',
            ]),
            $this->plenaryRow('12:30', '13:30', SessionFormat::Panel, [
                'fr' => 'Au-delà de l\'assurance : comment l\'Audit Interne crée de la valeur stratégique dans un monde en transformation',
                'en' => 'Beyond assurance: how internal audit creates strategic value in a transforming world',
            ], [
                'fr' => 'Relier performance, transformation, intérêt général et création de valeur durable.',
                'en' => 'Connect performance, transformation, the public interest and the creation of sustainable value.',
            ]),
            $this->plenaryRow('13:30', '14:30', SessionFormat::Break, [
                'fr' => 'Déjeuner',
                'en' => 'Lunch',
            ]),
            $this->plenaryRow('16:20', '16:50', SessionFormat::Break, [
                'fr' => 'Pause-café',
                'en' => 'Coffee break',
            ]),
        ];
    }

    /**
     * Day one's three parallel workshop blocks.
     *
     * The dossier prints the three parcours as three parallel columns under one
     * start time, so a block is one time slot across all three tracks.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, array<string, string>>}>
     */
    private function dayOneWorkshops(): array
    {
        return [
            ['14:30', '15:00', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'IA générative appliquée à l\'Audit Interne : opportunités et limites',
                    'en' => 'Generative AI applied to internal audit: opportunities and limits',
                ],
                'RESILIENCE' => [
                    'fr' => 'Cyber-résilience : au-delà de la cybersécurité',
                    'en' => 'Cyber-resilience: beyond cybersecurity',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Les compétences essentielles de l\'auditeur interne de demain',
                    'en' => 'The essential skills of tomorrow\'s internal auditor',
                ],
            ]],
            ['15:10', '15:40', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'Gouvernance de l\'IA et audit des cadres d\'IA responsable',
                    'en' => 'AI governance and auditing responsible AI frameworks',
                ],
                'RESILIENCE' => [
                    'fr' => 'Résilience opérationnelle et processus critiques',
                    'en' => 'Operational resilience and critical processes',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Leadership et influence dans un environnement de disruption continue',
                    'en' => 'Leadership and influence in a continuously disrupted environment',
                ],
            ]],
            ['15:50', '16:20', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'Audit des algorithmes et modèles d\'intelligence artificielle',
                    'en' => 'Auditing AI algorithms and models',
                ],
                'RESILIENCE' => [
                    'fr' => 'Culture organisationnelle et résilience : que doit évaluer l\'Audit Interne ?',
                    'en' => 'Organisational culture and resilience: what should internal audit assess?',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Pensée stratégique et compréhension des enjeux métiers',
                    'en' => 'Strategic thinking and understanding business stakes',
                ],
            ]],
        ];
    }

    /**
     * Day two's three parallel workshop blocks.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, array<string, string>>}>
     */
    private function dayTwoWorkshops(): array
    {
        return [
            ['14:30', '15:00', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'Audit des programmes de transformation digitale',
                    'en' => 'Auditing digital transformation programmes',
                ],
                'RESILIENCE' => [
                    'fr' => 'Risques géopolitiques : quel rôle pour l\'Audit Interne ?',
                    'en' => 'Geopolitical risks: what role for internal audit?',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Attirer, fidéliser et développer les talents de l\'Audit Interne',
                    'en' => 'Attracting, retaining and developing internal audit talent',
                ],
            ]],
            ['15:10', '15:40', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'Gouvernance des données et qualité des données',
                    'en' => 'Data governance and data quality',
                ],
                'RESILIENCE' => [
                    'fr' => 'Résilience du secteur public',
                    'en' => 'Public sector resilience',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Storytelling et visualisation des données au service de l\'impact de l\'Audit Interne',
                    'en' => 'Storytelling and data visualisation in service of the impact of internal audit',
                ],
            ]],
            ['15:50', '16:20', [
                'AUDIT_TRANSFO' => [
                    'fr' => 'Audit des technologies émergentes : cloud, blockchain, automatisation et IA agentique',
                    'en' => 'Auditing emerging technologies: cloud, blockchain, automation and agentic AI',
                ],
                'RESILIENCE' => [
                    'fr' => 'Confiance numérique et résilience des écosystèmes digitaux',
                    'en' => 'Digital trust and the resilience of digital ecosystems',
                ],
                'AUDITOR_DAME' => [
                    'fr' => 'Agilité et adaptabilité dans un environnement en évolution continue',
                    'en' => 'Agility and adaptability in a continuously evolving environment',
                ],
            ]],
        ];
    }

    /**
     * Day one's Innovation Labs.
     *
     * @return array<int, array{0: string, 1: string, 2: list<string>}>
     */
    private function dayOneLabs(): array
    {
        return [
            ['16:50', '17:35', [
                ['fr' => 'Continuous Monitoring', 'en' => 'Continuous Monitoring'],
                ['fr' => 'AI Copilots pour Audit Interne', 'en' => 'AI Copilots for Internal Audit'],
                ['fr' => 'GRC platforms', 'en' => 'GRC platforms'],
            ]],
        ];
    }

    /**
     * Day two's Innovation Labs.
     *
     * @return array<int, array{0: string, 1: string, 2: list<string>}>
     */
    private function dayTwoLabs(): array
    {
        return [
            ['16:50', '17:35', [
                ['fr' => 'Monitoring SAP', 'en' => 'SAP Monitoring'],
                ['fr' => 'Process Mining', 'en' => 'Process Mining'],
                ['fr' => 'Détection de la fraude et analyse prédictive', 'en' => 'Fraud detection and predictive analysis'],
            ]],
        ];
    }
}
