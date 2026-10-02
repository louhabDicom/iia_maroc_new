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
 * The home page counts its figures from this table (see HomeController), so the
 * shape of the grid is what the landing page will claim. It is built to the
 * brief's numbers:
 *
 *   . 18 workshops        the figure the brief names for 2026
 *   . 6 innovation labs   one per LAB room, which is all six that exist
 *   . 2 keynotes, one opening each day
 *   . 6 panels, an opening and a certification ceremony, a wrap-up and closing
 *     remarks, plus the breaks and the two lunches
 *   over 2 days
 *
 * Workshops run as three parallel blocks of three rooms per day (3x3x2 = 18);
 * the labs run one block per day. Concurrency peaks at four simultaneous
 * sessions, which is the cap the brief sets. No room is ever double-booked,
 * and that is checked after saving rather than assumed.
 *
 * Speakers are referenced by "Lastname Firstname" against the fictional sample
 * set from SpeakersSeeder. Replacing that list with the confirmed speakers is
 * the only change needed: the reference resolves by name.
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
     * The whole two-day grid.
     *
     * @return array<int, array<string, mixed>>
     */
    private function grid(string $day1, string $day2): array
    {
        return array_merge($this->dayOne($day1), $this->dayTwo($day2));
    }

    /**
     * Day one — 16 December 2026.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayOne(string $date): array
    {
        return [
            [
                'sort' => 1, 'date' => $date, 'room' => 'PLENARY',
                'from' => '08:30', 'to' => '09:15',
                'format' => SessionFormat::Opening,
                'title' => [
                    'fr' => 'Cérémonie d\'ouverture officielle',
                    'en' => 'Official opening ceremony',
                    'ar' => 'مراسم الافتتاح الرسمية',
                ],
                'summary' => [
                    'fr' => 'Accueil des participants, mot d\'ouverture de la présidente d\'ARABCIA et de l\'IIA Maroc, puis présentation du thème de l\'édition.',
                    'en' => 'Welcome to participants, opening remarks from the Chair of ARABCIA and IIA Morocco, and presentation of the theme for this edition.',
                    'ar' => 'ترحيب بالمشاركين، وكلمة افتتاحية من رئيسة جمعية ARABCIA والمعهد الدولي للتدقيق الداخلي IIA المغرب، ثم تقديم موضوع هذه الدورة.',
                ],
                'speakers' => ['Benhaddou Nadia'],
            ],
            [
                'sort' => 2, 'date' => $date, 'room' => 'PLENARY', 'track' => 'GOUVERNANCE',
                'from' => '09:15', 'to' => '10:15',
                'format' => SessionFormat::Keynote,
                'title' => [
                    'fr' => 'Keynote — L\'audit interne, partenaire de confiance de la transformation publique',
                    'en' => 'Keynote — Internal audit, a trusted partner in public transformation',
                    'ar' => 'الكلمة الرئيسية — التدقيق الداخلي شريك موثوق في التحوّل القطاعي',
                ],
                'summary' => [
                    'fr' => 'Comment l\'audit interne garantit qu\'une transformation annoncée devient une transformation contrôlée, et ce qu\'il doit inventer pour rester crédible quand le périmètre public change tous les cinq ans.',
                    'en' => 'How internal audit ensures an announced transformation becomes a controlled one, and what it must reinvent to stay credible when the public scope changes every five years.',
                    'ar' => 'كيف يضمن التدقيق الداخلي أن يصبح التحول المعلن تحولًا محكومًا، وما عليه ابتكاره ليبقى موثوقًا عندما يتغير النطاق العمومي كل خمس سنوات.',
                ],
                'objectives' => [
                    'fr' => 'Poser le cadre de l\'édition et le lien entre transformation, résilience et valeur publique.',
                    'en' => 'Set the frame for the edition and the link between transformation, resilience and public value.',
                    'ar' => 'تحديد إطار الدورة والعلاقة بين التحول والصمود والقيمة العمومية.',
                ],
                'speakers' => ['Benhaddou Nadia'],
            ],
            [
                'sort' => 3, 'date' => $date, 'room' => 'PLENARY',
                'from' => '10:15', 'to' => '10:35',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause-café',
                    'en' => 'Coffee break',
                    'ar' => 'استراحة القهوة',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 4, 'date' => $date, 'room' => 'PLENARY', 'track' => 'GOUVERNANCE',
                'from' => '10:35', 'to' => '11:35',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 1 — Audit interne et agilité de l\'organisation',
                    'en' => 'Panel 1 — Internal audit and organisational agility',
                    'ar' => 'الجلسة 1 — التدقيق الداخلي والمرونة المؤسسية',
                ],
                'summary' => [
                    'fr' => 'Une fonction audit qui reste stable quand l\'organisation se réorganise : périmètre, ligne de rattachement, ressources et méthodes d\'accompagnement. Avec des praticiens de la banque, de l\'industrie et du secteur public.',
                    'en' => 'An audit function that stays stable while the organisation reorganises: scope, reporting line, resources and methods of support. With practitioners from banking, industry and the public sector.',
                    'ar' => 'وظيفة تدقيق تبقى مستقرة بينما تعيد المؤسسة تنظيم نفسها: النطاق، وخط التقارير، والموارد، وأساليب الدعم. مع ممارسين من القطاعين المصرفي والصناعي والقطاع العمومي.',
                ],
                'speakers' => ['Okonkwo Samuel', 'Amrani Leïla', 'Cherkaoui Youssef', 'Traoré Mariam'],
                'chair' => 'Okonkwo Samuel',
            ],
            [
                'sort' => 5, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'IA_TRANSFO',
                'from' => '11:50', 'to' => '13:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 1 — Cartographier les données à risque',
                    'en' => 'Workshop 1 — Mapping at-risk data',
                    'ar' => 'الورشة 1 — رسم خريطة البيانات المعرّضة للخطر',
                ],
                'summary' => [
                    'fr' => 'Construire une cartographie des données sensibles d\'une direction, croiser obligations réglementaires et droits d\'accès, puis hiérarchiser les zones à auditer. Les participants repartent avec un modèle de registre à compléter.',
                    'en' => 'Build a map of a department\'s sensitive data, cross regulatory obligations with access rights, then prioritise the areas to audit first. Participants leave with a register template to complete.',
                    'ar' => 'إنشاء خريطة للبيانات الحساسة في الإدارة، ومقارنة الالتزامات التنظيمية بصلاحيات الوصول، ثم تحديد الأولويات التي تخضع للتدقيق. يغادر المشاركون بقالب سجل جاهز للاستكمال.',
                ],
                'objectives' => [
                    'fr' => 'Savoir produire un registre des données à risque utilisable par l\'audit.',
                    'en' => 'Produce a register of at-risk data that audit can actually use.',
                    'ar' => 'إنتاج سجل للبيانات المعرّضة للخطر يمكن للتدقيق الداخلي الاستفادة منه.',
                ],
                'speakers' => ['Bennis Sanaa', 'Al-Rashid Hanan'],
            ],
            [
                'sort' => 6, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'RISQUE',
                'from' => '11:50', 'to' => '13:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 2 — Cartographie des risques et échelles d\'évaluation',
                    'en' => 'Workshop 2 — Risk mapping and assessment scales',
                    'ar' => 'الورشة 2 — رسم خريطة المخاطر ومقاييس التقييم',
                ],
                'summary' => [
                    'fr' => 'Passer d\'une liste de risques générique à une cartographie cotée, priorisée et reliée aux processus. Exercices sur les échelles de probabilité et d\'impact, et sur la manière de documenter les niveaux de risque résiduel.',
                    'en' => 'Move from a generic risk list to a scored, prioritised map tied to processes. Exercises on probability and impact scales, and on documenting residual risk levels.',
                    'ar' => 'الانتقال من قائمة مخاطر عامة إلى خريطة مرقّمة ومرتّبة ومربوطة بالعمليات. تمارين حول مقاييس الاحتمال والأثر، وتوثيق مستويات المخاطر المتبقية.',
                ],
                'objectives' => [
                    'fr' => 'Construire une échelle de cotation acceptée par la direction générale.',
                    'en' => 'Build an assessment scale that senior management will accept.',
                    'ar' => 'بناء مقياس تقييم تقبله الإدارة العامة.',
                ],
                'speakers' => ['Mansouri Ghita', 'El-Masri Ibrahim'],
            ],
            [
                'sort' => 7, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'DURABILITE',
                'from' => '11:50', 'to' => '13:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 3 — Audit des informations extra-financières',
                    'en' => 'Workshop 3 — Auditing non-financial disclosures',
                    'ar' => 'الورشة 3 — تدقيق الإفصاحات غير المالية',
                ],
                'summary' => [
                    'fr' => 'Travailler sur le passage des déclarations extra-financières de la preuve, en particulier les indicateurs carbone et sociaux : méthode d\'échantillonnage, niveau d\'assurance et limites d\'un audit de données non financières.',
                    'en' => 'Work on moving non-financial disclosures from claim to evidence, especially carbon and social indicators: sampling method, level of assurance, and the limits of auditing non-financial data.',
                    'ar' => 'العمل على نقل الإفصاحات غير المالية من الادعاء إلى الإثبات، خاصة مؤشرات الكربون والاجتماعية: منهج العينة، ومستوى الضمان، وحدود تدقيق البيانات غير المالية.',
                ],
                'objectives' => [
                    'fr' => 'Évaluer le degré de confiance accordable à un indicateur publié.',
                    'en' => 'Assess how much assurance a published indicator can carry.',
                    'ar' => 'تقييم مستوى الثقة الذي يمكن منحه لمؤشر منشور.',
                ],
                'speakers' => ['Traoré Mariam', 'Ben Othman Firas'],
            ],
            [
                'sort' => 8, 'date' => $date, 'room' => 'PLENARY', 'track' => 'NUMERIQUE',
                'from' => '11:50', 'to' => '12:50',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 2 — Audit interne et transformation digitale',
                    'en' => 'Panel 2 — Internal audit and digital transformation',
                    'ar' => 'الجلسة 2 — التدقيق الداخلي والتحول الرقمي',
                ],
                'summary' => [
                    'fr' => 'Quand les processus deviennent numériques, les points de contrôle changent de nature. Un panel sur l\'audit des flux automatisés, la vérification des algorithmes et la responsabilité du contrôle automatisé.',
                    'en' => 'When processes go digital the nature of control points changes. A panel on auditing automated workflows, verifying algorithms, and accountability for automated control.',
                    'ar' => 'حين تصبح العمليات رقمية تتغير طبيعة نقاط الرقابة. جلسة حول تدقيق سير العمل الآلي، والتحقق من الخوارزميات، والمساءلة عن الرقابة الآلية.',
                ],
                'speakers' => ['Bennis Sanaa', 'Haddad Rami', 'Sabri Omar'],
                'chair' => 'Bennis Sanaa',
            ],
            [
                'sort' => 9, 'date' => $date, 'room' => 'PLENARY',
                'from' => '13:20', 'to' => '14:20',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause déjeuner',
                    'en' => 'Lunch break',
                    'ar' => 'استراحة الغداء',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 10, 'date' => $date, 'room' => 'LAB_1', 'track' => 'IA_TRANSFO',
                'from' => '14:20', 'to' => '15:50',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 1 — IA générative et revue d\'audit',
                    'en' => 'Lab 1 — Generative AI and the audit review',
                    'ar' => 'المختبر 1 — الذكاء الاصطناعي التوليدي ومراجعة التدقيق',
                ],
                'summary' => [
                    'fr' => 'Atelier pratique sur l\'usage de l\'IA générative dans la revue analytique : formulation des requêtes, vérification des sources et détection des réponses inventées. Les participants travaillent sur leurs propres données.',
                    'en' => 'A hands-on lab on using generative AI in analytical review: writing queries, checking sources, and spotting invented answers. Participants work on their own data.',
                    'ar' => 'مختبر تطبيقي حول استخدام الذكاء الاصطناعي التوليدي في المراجعة التحليلية: صياغة الاستعلامات، والتحقق من المصادر، وكشف الإجابات المختلقة. يعمل المشاركون على بياناتهم الخاصة.',
                ],
                'objectives' => [
                    'fr' => 'Évaluer une réponse d\'IA générative avant de l\'intégrer à un dossier d\'audit.',
                    'en' => 'Assess a generative AI answer before letting it into an audit file.',
                    'ar' => 'تقييم إجابة الذكاء الاصطناعي التوليدي قبل إدراجها في ملف التدقيق.',
                ],
                'speakers' => ['Bennis Sanaa'],
            ],
            [
                'sort' => 11, 'date' => $date, 'room' => 'LAB_2', 'track' => 'RESILIENCE',
                'from' => '14:20', 'to' => '15:50',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 2 — Tester un plan de continuité d\'activité',
                    'en' => 'Lab 2 — Testing a business continuity plan',
                    'ar' => 'المختبر 2 — اختبار خطة استمرارية النشاط',
                ],
                'summary' => [
                    'fr' => 'Simulation en conditions réelles : un incident numérique est joué devant les participants, qui doivent activer leur plan de continuité et rendre compte des décisions prises. Retour collectif sur les écarts constatés.',
                    'en' => 'A realistic simulation: a digital incident is staged and participants must activate their continuity plan and account for the decisions taken. Collective debrief on the gaps found.',
                    'ar' => 'محاكاة واقعية: يتم تمثيل حادث رقمي أمام المشاركين، ويجب عليهم تفعيل خطة استمرارية النشاط وتوضيح القرارات المتخذة. ثم مناقشة جماعية للفجوات المرصودة.',
                ],
                'speakers' => ['El-Masri Ibrahim', 'Al-Amin Idris'],
            ],
            [
                'sort' => 12, 'date' => $date, 'room' => 'LAB_3', 'track' => 'AUDITOR_DAME',
                'from' => '14:20', 'to' => '15:50',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 3 — Préparer sa certification d\'auditeur',
                    'en' => 'Lab 3 — Preparing for auditor certification',
                    'ar' => 'المختبر 3 — التحضير لشهادة المدقق',
                ],
                'summary' => [
                    'fr' => 'Atelier d\'entraînement à l\'examen de certification : rédaction d\'un programme de travail, gestion du temps et rédaction d\'un rapport. Les candidats travaillent sur des cas issus de sessions précédentes.',
                    'en' => 'Exam-preparation drill: writing a work programme, managing time, and writing a report. Candidates work on cases drawn from previous editions.',
                    'ar' => 'تدريب على امتحان الشهادة: إعداد برنامج عمل، وإدارة الوقت، وصياغة تقرير. يعمل المرشحون على حالات مستمدة من الدورات السابقة.',
                ],
                'speakers' => ['Reinhardt Thomas'],
            ],
            [
                'sort' => 13, 'date' => $date, 'room' => 'PLENARY', 'track' => 'COMPETENCES',
                'from' => '14:20', 'to' => '15:20',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 3 — Former et certifier les auditeurs de demain',
                    'en' => 'Panel 3 — Training and certifying tomorrow\'s auditors',
                    'ar' => 'الجلسة 3 — تكوين شهادات مدققي الغد',
                ],
                'summary' => [
                    'fr' => 'Le renouvellement du vivier d\'auditeurs est le premier risque de la profession. Un panel sur les parcours de formation, la place du mentorat et l\'évolution du référentiel de certification.',
                    'en' => 'Refreshing the pool of auditors is the profession\'s first risk. A panel on training pathways, the place of mentoring, and how the certification framework is changing.',
                    'ar' => 'تجديد مخزون المدققين هو الخطر الأول في المهنة. جلسة حول مسارات التكوين، ومكان التوجيه، وتطور إطار الشهادات.',
                ],
                'speakers' => ['Amrani Leïla', 'Reinhardt Thomas', 'Cherkaoui Youssef', 'Diallo Aïcha'],
                'chair' => 'Amrani Leïla',
            ],
            [
                'sort' => 14, 'date' => $date, 'room' => 'PLENARY',
                'from' => '15:50', 'to' => '16:10',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause-café',
                    'en' => 'Coffee break',
                    'ar' => 'استراحة القهوة',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 15, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'GOUVERNANCE',
                'from' => '16:10', 'to' => '17:40',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 4 — Rédiger des recommandations qui sont suivies',
                    'en' => 'Workshop 4 — Writing recommendations that get actioned',
                    'ar' => 'الورشة 4 — صياغة توصيات قابلة للتنفيذ',
                ],
                'summary' => [
                    'fr' => 'Une recommandation sans propriétaire identifié ni échéance reste une lettre. Exercice de réécriture de constats réels d\'audit en recommandations datées, avec un plan de suivi accepté par la direction.',
                    'en' => 'A recommendation with no named owner and no date stays a letter. A rewriting exercise turning real audit findings into dated recommendations with a follow-up plan management will accept.',
                    'ar' => 'التوصية التي لا تحمل مسؤولا ولا موعدا تبقى رسالة فقط. تمرين على إعادة صياغة نتائج تدقيق حقيقية إلى توصيات مؤرخة مع خطة متابعة مقبولة من الإدارة.',
                ],
                'objectives' => [
                    'fr' => 'Passer d\'un constat à une recommandation suivie d\'un plan d\'action.',
                    'en' => 'Turn a finding into a recommendation with an action plan behind it.',
                    'ar' => 'تحويل نتيجة تدقيق إلى توصية مدعومة بخطة عمل.',
                ],
                'speakers' => ['Amrani Leïla', 'Sabri Omar'],
            ],
            [
                'sort' => 16, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'DURABILITE',
                'from' => '16:10', 'to' => '17:40',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 5 — Auditer la chaîne d\'approvisionnement responsable',
                    'en' => 'Workshop 5 — Auditing a responsible supply chain',
                    'ar' => 'الورشة 5 — تدقيق سلسلة توريد مسؤولة',
                ],
                'summary' => [
                    'fr' => 'Comment vérifier les engagements RSE d\'un fournisseur sans devenir expert de son secteur ? Méthode d\'échantillonnage, revue documentaire, entretien fournisseur et traitement des divergences entre engagements et pratique.',
                    'en' => 'How to verify a supplier\'s CSR commitments without becoming an expert in their sector? Sampling method, document review, supplier interview, and handling gaps between commitment and practice.',
                    'ar' => 'كيف يمكن التحقق من التزامات المسؤولية الاجتماعية لدى مورد دون أن تصبح خبيرا في قطاعه؟ منهج العينة، ومراجعة الوثائق، ومقابلة المورد، ومعالجة الفجوات بين الالتزام والممارسة.',
                ],
                'speakers' => ['Diallo Aïcha', 'Bouzid Salma'],
            ],
            [
                'sort' => 17, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'COMPETENCES',
                'from' => '16:10', 'to' => '17:40',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 6 — Rédiger le rapport d\'audit pour qu\'il soit lu',
                    'en' => 'Workshop 6 — Writing the audit report so it gets read',
                    'ar' => 'الورشة 6 — كتابة تقرير التدقيق ليقرأ فعلا',
                ],
                'summary' => [
                    'fr' => 'Un rapport technique ne sert à rien s\'il n\'est pas lu. Structures adaptées à des comités d\'audit pressés, choix du niveau de détail, et présentation des constats pour obtenir une décision plutôt qu\'un accord de principe.',
                    'en' => 'A technical report achieves nothing if nobody reads it. Structures for time-pressured audit committees, choosing the right level of detail, and framing findings to get a decision rather than agreement in principle.',
                    'ar' => 'التقرير التقني لا يتحقق منه شيء إن لم يقرأ. هياكل تناسب لجان التدقيق المضغوطة، واختيار مستوى التفاصيل، وصياغة النتائج للحصول على قرار لا مجرد موافقة مبدئية.',
                ],
                'speakers' => ['Cherkaoui Youssef', 'Kacimi Nawel'],
            ],
            [
                'sort' => 18, 'date' => $date, 'room' => 'PLENARY', 'track' => 'GOUVERNANCE',
                'from' => '16:10', 'to' => '17:10',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 4 — Gouvernance, éthique et indépendance de la fonction d\'audit',
                    'en' => 'Panel 4 — Governance, ethics and independence in the audit function',
                    'ar' => 'الجلسة 4 — الحوكمة وأخلاقيات واستقلالية وظيفة التدقيق',
                ],
                'summary' => [
                    'fr' => 'Les situations qui mettent en cause l\'indépendance de l\'audit : rattachement à une direction opérationnelle, pression sur le calendrier, dépendance à une seule ressource. Discussion sur les garde-fous qui protègent réellement la fonction.',
                    'en' => 'The situations that put audit independence at risk: reporting to an operational line, timetable pressure, dependence on a single resource. A discussion of the safeguards that genuinely protect the function.',
                    'ar' => 'المواقف التي تهدد استقلالية التدقيق: الارتباط بخط تشغيلي، والضغط على الجدول الزمني، والاعتماد على مورد وحيد. نقاش حول الضمانات التي تحمي الوظيفة فعليا.',
                ],
                'speakers' => ['Fahmy Karim', 'Mansouri Ghita', 'Okonkwo Samuel', 'Al-Amin Idris'],
                'chair' => 'Fahmy Karim',
            ],
            [
                'sort' => 19, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'NUMERIQUE',
                'from' => '17:40', 'to' => '18:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 7 — Contrôle interne et fraude',
                    'en' => 'Workshop 7 — Internal control and fraud',
                    'ar' => 'الورشة 7 — الرقابة الداخلية والاحتيال',
                ],
                'summary' => [
                    'fr' => 'Les signaux faibles d\'une fraude interne, et les contrôles qui les font apparaître avant que la fraude ne se matérialise. Cartographie des dispositifs anti-fraude et lien avec les cellules d\'audit interne.',
                    'en' => 'The early signals of internal fraud, and the controls that surface them before the fraud becomes real. Mapping anti-fraud devices and their link with internal audit units.',
                    'ar' => 'العلامات المبكرة على الاحتيال الداخلي، والضوابط التي تكشفها قبل أن يتحول الاحتيال إلى واقعة. رسم خريطة أجهزة مكافحة الاحتيال وصلتها بوحدات التدقيق الداخلي.',
                ],
                'speakers' => ['Okonkwo Samuel', 'Diallo Aïcha'],
            ],
            [
                'sort' => 20, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'RESILIENCE',
                'from' => '17:40', 'to' => '18:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 8 — Gouvernance des groupes et périmètre d\'audit',
                    'en' => 'Workshop 8 — Group governance and audit scope',
                    'ar' => 'الورشة 8 — حوكمة المجموعات ونطاق التدقيق',
                ],
                'summary' => [
                    'fr' => 'Auditer une filiale, une filiale de filiale, ou une participation minoritaire : où s\'arrête le périmètre, et comment obtenir des informations que l\'entité auditée ne contrôle pas.',
                    'en' => 'Auditing a subsidiary, a sub-subsidiary, or a minority stake: where does the scope end, and how do you obtain information the audited entity does not control?',
                    'ar' => 'تدقيق شركة تابعة أو شركة تابعة لها، أو مساهمة أقلية: أين ينتهي النطاق، وكيف تحصل على معلومات لا تتحكم فيها الجهة الخاضعة للتدقيق؟',
                ],
                'speakers' => ['Mansouri Ghita', 'Cherkaoui Youssef'],
            ],
            [
                'sort' => 21, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'GOUVERNANCE',
                'from' => '17:40', 'to' => '18:20',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 9 — Mesurer l\'efficacité des comités d\'audit',
                    'en' => 'Workshop 9 — Measuring audit committee effectiveness',
                    'ar' => 'الورشة 9 — قياس فعالية لجان التدقيق',
                ],
                'summary' => [
                    'fr' => 'Évaluer la contribution réelle d\'un comité d\'audit : questionnaires, entretiens avec les membres, et analyse du temps réellement consacré aux sujets qui comptent.',
                    'en' => 'Assessing the real contribution of an audit committee: questionnaires, interviews with members, and analysis of the time actually spent on the issues that matter.',
                    'ar' => 'تقييم المساهمة الفعلية للجنة التدقيق: الاستبيانات، والمقابلات مع الأعضاء، وتحليل الوقت الذي يقضونه فعلا في الموضوعات المهمة.',
                ],
                'speakers' => ['Amrani Leïla', 'El-Masri Ibrahim'],
            ],
            [
                'sort' => 22, 'date' => $date, 'room' => 'PLENARY',
                'from' => '18:20', 'to' => '18:50',
                'format' => SessionFormat::Plenary,
                'title' => [
                    'fr' => 'Synthèse de la première journée',
                    'en' => 'Day one wrap-up',
                    'ar' => 'خلاصة اليوم الأول',
                ],
                'summary' => [
                    'fr' => 'Retour de l\'équipe scientifique sur les constats issus des ateliers et des laboratoires, et annonce des temps forts du deuxième jour.',
                    'en' => 'The scientific team debriefs on the findings that came out of the workshops and labs, and previews the highlights of day two.',
                    'ar' => 'يستعرض الفريق العلمي أبرز ما توصل إليه المشاركون في الورشات والمختبرات، ويعلن عن محاور اليوم الثاني.',
                ],
                'speakers' => ['Benhaddou Nadia'],
            ],
        ];
    }

    /**
     * Day two — 17 December 2026.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dayTwo(string $date): array
    {
        return [
            [
                'sort' => 20, 'date' => $date, 'room' => 'PLENARY',
                'from' => '08:30', 'to' => '09:00',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Accueil café',
                    'en' => 'Welcome coffee',
                    'ar' => 'قهوة الترحيب',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 21, 'date' => $date, 'room' => 'PLENARY', 'track' => 'AUDITOR_DAME',
                'from' => '09:00', 'to' => '10:00',
                'format' => SessionFormat::Keynote,
                'title' => [
                    'fr' => 'Keynote — Rendre la valeur de l\'audit interne mesurable',
                    'en' => 'Keynote — Making the value of internal audit measurable',
                    'ar' => 'الكلمة الرئيسية — جعل قيمة التدقيق الداخلي قابلة للقياس',
                ],
                'summary' => [
                    'fr' => 'Comment démontrer la valeur d\'une fonction d\'audit qui produit des constats sans produire de résultats ? Indicateurs, coût du risque évité, et lien entre la qualité du plan d\'audit et la performance de l\'organisation.',
                    'en' => 'How do you demonstrate the value of an audit function that produces findings without producing outcomes? Indicators, the cost of risk avoided, and the link between audit plan quality and organisational performance.',
                    'ar' => 'كيف نثبت قيمة وظيفة تدقيق تنتج ملاحظات دون أن تنتج نتائج؟ المؤشرات، وكلفة المخاطر المتجنبة، والعلاقة بين جودة برنامج التدقيق وأداء المؤسسة.',
                ],
                'objectives' => [
                    'fr' => 'Construire un tableau de bord qui plaise autant à la direction qu\'au comité d\'audit.',
                    'en' => 'Build a dashboard that satisfies both management and the audit committee.',
                    'ar' => 'بناء لوحة قيادة ترضي الإدارة ولجنة التدقيق معا.',
                ],
                'speakers' => ['Fahmy Karim'],
            ],
            [
                'sort' => 22, 'date' => $date, 'room' => 'PLENARY',
                'from' => '10:00', 'to' => '10:20',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause-café',
                    'en' => 'Coffee break',
                    'ar' => 'استراحة القهوة',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 23, 'date' => $date, 'room' => 'PLENARY', 'track' => 'AUDITOR_DAME',
                'from' => '10:20', 'to' => '11:20',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 5 — Le profil de l\'auditeur de demain',
                    'en' => 'Panel 5 — The profile of tomorrow\'s auditor',
                    'ar' => 'الجلسة 5 — ملف مدقق الغد',
                ],
                'summary' => [
                    'fr' => 'Compétences techniques, culture générale, langues et capacité à challenger une direction : ce que les responsables d\'audit attendent réellement d\'un auditeur senior, et ce que les écoles de la profession proposent aujourd\'hui.',
                    'en' => 'Technical skills, general culture, languages and the ability to challenge management: what audit leaders actually expect of a senior auditor, and what the profession\'s schools offer today.',
                    'ar' => 'المهارات التقنية والثقافة العامة واللغات والقدرة على مساءلة الإدارة: ما الذي يتوقعه مسؤولو التدقيق فعليا من مدقق بخبرة، وما تقدمه مدارس المهنة اليوم.',
                ],
                'speakers' => ['Benhaddou Nadia', 'Okonkwo Samuel', 'Reinhardt Thomas', 'Bouzid Salma'],
                'chair' => 'Benhaddou Nadia',
            ],
            [
                'sort' => 24, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'RISQUE',
                'from' => '11:40', 'to' => '13:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 10 — Audit de la sécurité des systèmes d\'information',
                    'en' => 'Workshop 10 — Auditing information system security',
                    'ar' => 'الورشة 10 — تدقيق أمن نظم المعلومات',
                ],
                'summary' => [
                    'fr' => 'Un atelier technique sur les contrôles de sécurité vérifiables : gestion des habilitations, traçabilité des accès, sauvegarde et restauration. Les participants travaillent sur les éléments de preuve qu\'un auditeur peut réellement exiger.',
                    'en' => 'A technical workshop on verifiable security controls: access rights management, access logging, backup and restore. Participants work on the evidence an auditor can realistically demand.',
                    'ar' => 'ورشة تقنية حول ضوابط أمنية قابلة للتحقق: إدارة صلاحيات الوصول، وتسجيل عمليات الدخول، والنسخ الاحتياطي والاستعادة. يعمل المشاركون على الأدلة التي يمكن للمدقق طلبها فعليا.',
                ],
                'objectives' => [
                    'fr' => 'Distinguer un contrôle de sécurité auditable d\'une pratique seulement déclarée.',
                    'en' => 'Tell an auditable security control apart from a merely claimed practice.',
                    'ar' => 'التمييز بين ضابط أمني قابل للتدقيق وممارسة معلنة فقط.',
                ],
                'speakers' => ['Al-Rashid Hanan', 'El-Masri Ibrahim'],
            ],
            [
                'sort' => 25, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'DURABILITE',
                'from' => '11:40', 'to' => '13:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 11 — Intégrer le climat au plan d\'audit',
                    'en' => 'Workshop 11 — Putting climate into the audit plan',
                    'ar' => 'الورشة 11 — إدراج المناخ في برنامج التدقيق',
                ],
                'summary' => [
                    'fr' => 'Comment faire passer le risque climatique d\'une mention en annexe à une ligne de programme de travail ? Construction d\'un plan d\'audit climat fondé sur un modèle de risque physique et de risque de transition.',
                    'en' => 'How to move climate risk from a footnote to a line in the work programme? Building a climate audit plan on a physical-risk and transition-risk model.',
                    'ar' => 'كيف تنقل مخاطر المناخ من هامش التقرير إلى سطر في برنامج العمل؟ بناء برنامج تدقيق للمناخ على نموذج المخاطر الطبيعية ومخاطر الانتقال.',
                ],
                'speakers' => ['Ben Othman Firas', 'Mansouri Ghita'],
            ],
            [
                'sort' => 26, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'RESILIENCE',
                'from' => '11:40', 'to' => '13:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 12 — Continuité d\'activité dans le secteur public',
                    'en' => 'Workshop 12 — Business continuity in the public sector',
                    'ar' => 'الورشة 12 — استمرارية النشاط في القطاع العمومي',
                ],
                'summary' => [
                    'fr' => 'Un service public qui n\'assure pas la continuité de ses missions critiques n\'est pas résilient. Construction d\'un plan de continuité pour un service administratif : processus prioritaires, ressources de secours et exercice de crise.',
                    'en' => 'A public service that cannot sustain its critical missions is not resilient. Building a continuity plan for an administrative department: priority processes, backup resources, and a crisis exercise.',
                    'ar' => 'الخدمة العمومية التي تضمن استمرار مهامها الحرجة ليست متانة. بناء خطة استمرارية لإدارة إدارية: العمليات ذات الأولوية، والموارد الاحتياطية، وتمرين أزمة.',
                ],
                'speakers' => ['Al-Amin Idris', 'Traoré Mariam'],
            ],
            [
                'sort' => 27, 'date' => $date, 'room' => 'PLENARY',
                'from' => '13:10', 'to' => '14:10',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause déjeuner',
                    'en' => 'Lunch break',
                    'ar' => 'استراحة الغداء',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 28, 'date' => $date, 'room' => 'LAB_4', 'track' => 'GOUVERNANCE',
                'from' => '14:10', 'to' => '15:40',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 4 — Cartographier les parties prenantes',
                    'en' => 'Lab 4 — Mapping the stakeholder landscape',
                    'ar' => 'المختبر 4 — رسم خريطة الأطراف ذات المصلحة',
                ],
                'summary' => [
                    'fr' => 'Méthode de cartographie des acteurs autour d\'un comité d\'audit : qui décide, qui influence, qui subit. Application directe à un dossier réel fourni par les participants.',
                    'en' => 'A method for mapping actors around an audit committee: who decides, who influences, who bears the impact. Applied directly to a real case supplied by participants.',
                    'ar' => 'طريقة لرسم خريطة الأطراف حول لجنة التدقيق: من يقرر، ومن يؤثر، ومن يتحمل الأثر. تطبق مباشرة على حالة حقيقية يقدمها المشاركون.',
                ],
                'speakers' => ['Traoré Mariam'],
            ],
            [
                'sort' => 29, 'date' => $date, 'room' => 'LAB_5', 'track' => 'NUMERIQUE',
                'from' => '14:10', 'to' => '15:40',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 5 — Automatiser l\'analyse de données d\'audit',
                    'en' => 'Lab 5 — Automating audit data analysis',
                    'ar' => 'المختبر 5 — أتمتة تحليل بيانات التدقيق',
                ],
                'summary' => [
                    'fr' => 'Construction pas à pas d\'un contrôle automatisé sur un jeu de données d\'audit : extraction, règles de détection d\'anomalies et vérification manuelle des cas signalés. Aucun prérequis en programmation.',
                    'en' => 'Building an automated control over an audit dataset step by step: extraction, anomaly detection rules, and manual verification of flagged cases. No programming prerequisite.',
                    'ar' => 'بناء ضابط آلي على مجموعة بيانات تدقيق خطوة بخطوة: الاستخراج، وقواعد كشف الشذوذ، والتحقق اليدوي للحالات المرصودة. دون أي متطلب في البرمجة.',
                ],
                'speakers' => ['Bennis Sanaa', 'Haddad Rami'],
            ],
            [
                'sort' => 30, 'date' => $date, 'room' => 'LAB_6', 'track' => 'DURABILITE',
                'from' => '14:10', 'to' => '15:40',
                'format' => SessionFormat::InnovationLab,
                'title' => [
                    'fr' => 'Laboratoire 6 — Notations extra-financières comparées',
                    'en' => 'Lab 6 — Comparing sustainability ratings',
                    'ar' => 'المختبر 6 — مقارنة تقييمات الاستدامة',
                ],
                'summary' => [
                    'fr' => 'Les notations extra-financières divergent, et personne dans une direction d\'audit ne sait l\'expliquer au comité. Atelier de lecture critique des méthodologies derrière les principales notations.',
                    'en' => 'Sustainability ratings disagree with each other, and nobody in an audit department can explain why to the committee. A critical-reading workshop on the methodologies behind the main ratings.',
                    'ar' => 'تقييمات الاستدامة تختلف فيما بينها، ولا يستطيع أحد في إدارة التدقيق شرح ذلك للجنة. ورشة في القراءة النقدية للمنهجيات وراء التقييمات الرئيسية.',
                ],
                'speakers' => ['Ben Othman Firas'],
            ],
            [
                'sort' => 31, 'date' => $date, 'room' => 'PLENARY', 'track' => 'NUMERIQUE',
                'from' => '14:10', 'to' => '15:10',
                'format' => SessionFormat::Panel,
                'title' => [
                    'fr' => 'Panel 6 — Données, algorithmes et preuve d\'audit',
                    'en' => 'Panel 6 — Data, algorithms and audit evidence',
                    'ar' => 'الجلسة 6 — البيانات والخوارزميات وأدلة التدقيق',
                ],
                'summary' => [
                    'fr' => 'Quand la donnée devient la preuve, la question n\'est plus seulement la qualité de l\'information mais la traçabilité de sa production. Un panel sur la preuve algorithmique et sa valeur probante.',
                    'en' => 'When data becomes evidence, the question is no longer only information quality but the traceability of how it was produced. A panel on algorithmic evidence and its evidential value.',
                    'ar' => 'حين تصبح البيانات دليلا، لم يعد السؤال سوى جودة المعلومات بل قابلية تتبع إنتاجها. جلسة حول الدليل الخوارزمي وقيمته الإثباتية.',
                ],
                'speakers' => ['Fahmy Karim', 'Haddad Rami', 'Sabri Omar'],
                'chair' => 'Fahmy Karim',
            ],
            [
                'sort' => 32, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'AUDITOR_DAME',
                'from' => '15:40', 'to' => '17:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 13 — Recruter et intégrer un auditeur',
                    'en' => 'Workshop 13 — Recruiting and onboarding an auditor',
                    'ar' => 'الورشة 13 — توظيف المدقق وإدماجه',
                ],
                'summary' => [
                    'fr' => 'Trouver un auditeur qui ne vient pas de la même formation que vous, et lui donner accès aux systèmes sans lui donner accès à tout. Processus de recrutement, plan d\'intégration et règles de confidentialité pour les nouveaux arrivants.',
                    'en' => 'Finding an auditor who did not train the same way you did, and giving them system access without giving access to everything. Recruitment process, onboarding plan, and confidentiality rules for new joiners.',
                    'ar' => 'بناء فريق تدقيق متنوع الشرح عملية مستمرة وليست حدثا عابرا. تصميم عملية التوظيف، وخطة الإدماج، وقواعد السرية للوافدين الجدد.',
                ],
                'objectives' => [
                    'fr' => 'Réduire le délai entre l\'embauche et la première mission d\'audit menée en autonomie.',
                    'en' => 'Shorten the gap between hiring and a first audit run independently.',
                    'ar' => 'تقليص الفجوة بين التوظيف وأول مهمة تدقيق مستقلة.',
                ],
                'speakers' => ['Kacimi Nawel', 'Bouzid Salma'],
            ],
            [
                'sort' => 33, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'NUMERIQUE',
                'from' => '15:40', 'to' => '17:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 14 — Auditer un système d\'information en contrôle continu',
                    'en' => 'Workshop 14 — Continuous-control auditing of an information system',
                    'ar' => 'الورشة 14 — تدقيق نظم المعلومات بالرقابة المستمرة',
                ],
                'summary' => [
                    'fr' => 'Passer d\'un audit ponctuel à un contrôle permanent : indicateurs de couverture, gestion des anomalies détectées, et articulation entre le contrôle automatisé et le jugement professionnel de l\'auditeur.',
                    'en' => 'Moving from a point-in-time audit to permanent control: coverage indicators, handling of detected anomalies, and how automated control and the auditor\'s professional judgement fit together.',
                    'ar' => 'الانتقال من تدقيق لحظي إلى رقابة دائمة: مؤشرات التغطية، وإدارة الشذوذ المكتشف، والصلة بين الرقابة الآلية والحكم المهني للمدقق.',
                ],
                'speakers' => ['Sabri Omar', 'Al-Rashid Hanan'],
            ],
            [
                'sort' => 34, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'GOUVERNANCE',
                'from' => '15:40', 'to' => '17:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 15 — Préparer le programme annuel d\'audit interne',
                    'en' => 'Workshop 15 — Preparing the annual internal audit plan',
                    'ar' => 'الورشة 15 — إعداد البرنامج السنوي للتدقيق الداخلي',
                ],
                'summary' => [
                    'fr' => 'Construire le programme d\'audit annuel à partir de l\'évaluation des risques, et non à partir des demandes reçues. Hiérarchisation, couverture des processus critiques et argumentaire devant le comité d\'audit.',
                    'en' => 'Build the annual audit plan from the risk assessment rather than from requests received. Prioritisation, coverage of critical processes, and the argument to put to the audit committee.',
                    'ar' => 'إعداد برنامج التدقيق السنوي انطلاقا من تقييم المخاطر لا من الطلبات الواردة. الترتيب بالأولوية، وتغطية العمليات الحرجة، والحجة التي تعرض على لجنة التدقيق.',
                ],
                'speakers' => ['Mansouri Ghita', 'Okonkwo Samuel'],
            ],
            [
                'sort' => 35, 'date' => $date, 'room' => 'PLENARY',
                'from' => '15:40', 'to' => '16:40',
                'format' => SessionFormat::Award,
                'title' => [
                    'fr' => 'Cérémonie de certification des auditeurs',
                    'en' => 'Auditor certification ceremony',
                    'ar' => 'مراسم اعتماد المدققين',
                ],
                'summary' => [
                    'fr' => 'Remise officielle des attestations de certification aux nouveaux diplômés, prononcée conjointement par ARABCIA et l\'IIA Maroc, suivie de la présentation des nouveaux membres du comité scientifique.',
                    'en' => 'Official presentation of certification attestations to new graduates, jointly conferred by ARABCIA and IIA Morocco, followed by the introduction of the new scientific committee members.',
                    'ar' => 'تسليم رسمي لشهادات الاعتماد للخريجين الجدد، يليه تقديم أعضاء اللجنة العلمية الجديدين.',
                ],
                'speakers' => ['Benhaddou Nadia', 'Reinhardt Thomas'],
                'chair' => 'Reinhardt Thomas',
            ],
            [
                'sort' => 36, 'date' => $date, 'room' => 'ATELIER_1', 'track' => 'COMPETENCES',
                'from' => '17:30', 'to' => '18:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 16 — Partager ses retours d\'expérience entre pairs',
                    'en' => 'Workshop 16 — Sharing experience between peers',
                    'ar' => 'الورشة 16 — تبادل الخبرات بين الأقران',
                ],
                'summary' => [
                    'fr' => 'Format d\'échange entre pairs, sans intervenant imposé : chaque participant présente une mission d\'audit récente, la décision qu\'il a dû défendre, et ce qu\'il en a tiré.',
                    'en' => 'A peer-to-peer exchange with no imposed speaker: each participant presents a recent audit, the call they had to defend, and what they took from it.',
                    'ar' => 'صيغة تبادل بين الأقران دون متحدث مفروض: يعرض كل مشارك مهمة تدقيق حديثة، والقرار الذي اضطر للدفاع عنه، وما استفاد منه.',
                ],
                'speakers' => ['Kacimi Nawel', 'Sabri Omar'],
            ],
            [
                'sort' => 37, 'date' => $date, 'room' => 'ATELIER_2', 'track' => 'GOUVERNANCE',
                'from' => '17:30', 'to' => '18:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 17 — Répondre aux constats d\'inspection publique',
                    'en' => 'Workshop 17 — Responding to public inspection findings',
                    'ar' => 'الورشة 17 — معالجة نتائج الرقابة العمومية',
                ],
                'summary' => [
                    'fr' => 'Recevoir un rapport d\'inspection avec des constats importants, y répondre dans les délais, et éviter la récidive. Rédaction de la réponse, plan d\'action et articulation avec le contrôle interne.',
                    'en' => 'Receiving an inspection report with significant findings, answering within the deadline, and avoiding recurrence. Drafting the response, action plan, and links with internal control.',
                    'ar' => 'استلام تقرير رقابة يتضمن نتائج جوهرية، والرد عليها في الأجل المحدد، وتفادي التكرار. تحرير الرد، وخطة العمل، والصلة بالرقابة الداخلية.',
                ],
                'speakers' => ['Al-Amin Idris', 'Bouzid Salma'],
            ],
            [
                'sort' => 38, 'date' => $date, 'room' => 'ATELIER_3', 'track' => 'AUDITOR_DAME',
                'from' => '17:30', 'to' => '18:10',
                'format' => SessionFormat::Workshop,
                'title' => [
                    'fr' => 'Atelier 18 — Construire son plan de développement professionnel',
                    'en' => 'Workshop 18 — Building your professional development plan',
                    'ar' => 'الورشة 18 — بناء خطة التطوير المهني',
                ],
                'summary' => [
                    'fr' => 'Chaque participant construit son plan de développement sur douze mois : compétences à acquérir, certifications visées, et occasions de mettre en pratique dans son organisation d\'accueil.',
                    'en' => 'Each participant builds a twelve-month development plan: skills to acquire, certifications targeted, and opportunities to practise them in their host organisation.',
                    'ar' => 'يبني كل مشارك خطة تطوير على اثني عشر شهرا: المهارات التي يتقناها، والشهادات المستهدفة، وفرص الممارسة في جهة عمله.',
                ],
                'speakers' => ['Reinhardt Thomas', 'Haddad Rami'],
            ],
            [
                'sort' => 39, 'date' => $date, 'room' => 'PLENARY',
                'from' => '18:10', 'to' => '18:25',
                'format' => SessionFormat::Break,
                'title' => [
                    'fr' => 'Pause-café',
                    'en' => 'Coffee break',
                    'ar' => 'استراحة القهوة',
                ],
                'summary' => null, 'objectives' => null,
            ],
            [
                'sort' => 40, 'date' => $date, 'room' => 'PLENARY',
                'from' => '18:25', 'to' => '19:00',
                'format' => SessionFormat::Plenary,
                'title' => [
                    'fr' => 'Mot de clôture',
                    'en' => 'Closing remarks',
                    'ar' => 'كلمة ختامية',
                ],
                'summary' => [
                    'fr' => 'Synthèse de la conférence et remise des attestations de participation, puis annonce du lieu et de la date de l\'édition 2027.',
                    'en' => 'Conference wrap-up and presentation of certificates of attendance, followed by the announcement of the venue and date for the 2027 edition.',
                    'ar' => 'خلاصة المؤتمر وتسليم شهادات المشاركة، ثم إعلان مكان وتاريخ دورة 2027.',
                ],
                'speakers' => ['Benhaddou Nadia', 'Fahmy Karim'],
            ],
        ];
    }

    // END_OF_GRID
}
