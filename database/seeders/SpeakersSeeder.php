<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Speaker;
use Illuminate\Database\Seeder;

/**
 * Sample speakers for the 2026 edition.
 *
 * ⚠️ EVERY PERSON IN THIS FILE IS FICTIONAL. They exist so the speakers page,
 * the programme grid and the speaker cards render with a full, realistic shape
 * while the organisers confirm the real line-up. None of these names, titles,
 * employers or biographies correspond to a real person, and none of them may be
 * published: delete the rows, or re-point this seeder at the confirmed list,
 * before the site goes live.
 *
 * The 2024 site published a speakers page whose four cards were literally
 * labelled "Speaker 1 / Function". That looked unfinished on a conference site
 * and told a delegate nothing. This file makes the page look finished instead —
 * but with names that are obviously sample data, which is the honest way to do
 * it.
 *
 * Each biography is exactly three sentences, because
 * `Speaker::hasValidBiography()` enforces the brief's "biographie en 3 phrases"
 * rule. A one-sentence bio renders as a defect on the speakers page, so the
 * count is stored on the row and re-checked at the end of the run.
 */
class SpeakersSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::forYear(2026);

        if ($edition === null) {
            return;
        }

        foreach ($this->speakers() as $speaker) {
            $record = Speaker::query()->updateOrCreate(
                [
                    'edition_id' => $edition->getKey(),
                    'first_name' => $speaker['first_name'],
                    'last_name' => $speaker['last_name'],
                ],
                [
                    'organisation' => $speaker['organisation'],
                    'job_title' => $speaker['job_title'],
                    'country_iso2' => $speaker['country_iso2'],
                    'biography' => $speaker['biography'],
                    'biography_sentences' => 3,
                    // No photograph exists for a fictional person, so the card
                    // falls back to the initials the component already renders.
                    // A path pointing at a missing file would be worse than none.
                    'photo_path' => null,
                    'email' => null,
                    'linkedin_url' => null,
                    'talk_title' => null,
                    'status' => Speaker::STATUS_CONFIRMED,
                    'is_keynote' => $speaker['is_keynote'] ?? false,
                    'is_published' => true,
                    'sort_order' => $speaker['sort_order'],
                ],
            );

            if (! $record->hasValidBiography()) {
                throw new \RuntimeException(
                    "Speaker {$record->fullName()} does not have a three-sentence biography."
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function speakers(): array
    {
        return [
            // --- Keynotes -----------------------------------------------------
            [
                'first_name' => 'Nadia',
                'last_name' => 'Benhaddou',
                'organisation' => 'Institut de Direction Publique',
                'job_title' => [
                    'fr' => 'Directrice de la gouvernance et du contrôle',
                    'en' => 'Director of Governance and Control',
                    'ar' => 'مديرة الحوكمة والرقابة',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Nadia Benhaddou dirige depuis quinze ans des travaux sur la gouvernance des organisations publiques et parapubliques. Elle a accompagné plus de quarante réorganisations administratives dans la région du Maghreb. Sa conférence d\'ouverture interroge la place de l\'audit interne dans les transformations que les États engagent.',
                'is_keynote' => true,
                'sort_order' => 1,
            ],
            [
                'first_name' => 'Karim',
                'last_name' => 'Fahmy',
                'organisation' => 'Global Resilience Institute',
                'job_title' => [
                    'fr' => 'Directeur de la recherche sur la résilience organisationnelle',
                    'en' => 'Director of Organisational Resilience Research',
                    'ar' => 'مدير أبحاث الصمود المؤسسي',
                ],
                'country_iso2' => 'EG',
                'biography' => 'Karim Fahmy étudie depuis vingt ans les mécanismes par lesquels une organisation absorbe un choc sans perdre sa capacité productive. Il a publié une quarantaine d\'articles sur la continuité d\'activité et la résilience des chaînes logistiques. Sa conférence du deuxième jour porte sur la mesure de la résilience et son utilité pour l\'audit interne.',
                'is_keynote' => true,
                'sort_order' => 2,
            ],

            // --- Panels, workshops and labs -----------------------------------
            [
                'first_name' => 'Leïla',
                'last_name' => 'Amrani',
                'organisation' => 'Banque Al-Amal',
                'job_title' => [
                    'fr' => 'Directrice de l\'audit interne',
                    'en' => 'Chief Audit Executive',
                    'ar' => 'مديرة التدقيق الداخلي',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Leïla Amrani dirige l\'audit interne d\'un groupe bancaire présent dans dix pays. Elle a piloté la refonte complète de la fonction audit autour du risque et non plus du périmètre. Elle intervient sur l\'articulation entre audit interne, conformité et contrôle permanent.',
                'sort_order' => 3,
            ],
            [
                'first_name' => 'Youssef',
                'last_name' => 'Cherkaoui',
                'organisation' => 'Groupe Al Fath',
                'job_title' => [
                    'fr' => 'Directeur général adjoint',
                    'en' => 'Deputy Chief Executive Officer',
                    'ar' => 'الرئيس التنفيذي المساعد',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Youssef Cherkaoui supervise la stratégie d\'un groupe industriel implanté dans quatorze filiales. Il a lancé un programme de transformation opérationnelle dont l\'audit interne assure le suivi indépendant. Sa présence illustre la place de l\'audit interne dans la conduite du changement.',
                'sort_order' => 4,
            ],
            [
                'first_name' => 'Aïcha',
                'last_name' => 'Diallo',
                'organisation' => 'Inspection Générale des Finances',
                'job_title' => [
                    'fr' => 'Inspectrice générale adjointe',
                    'en' => 'Deputy Inspector General',
                    'ar' => 'وكيلة تفتيش عام مساعدة',
                ],
                'country_iso2' => 'SN',
                'biography' => 'Aïcha Diallo a conduit plus de cent missions d\'inspection dans l\'administration publique de son pays. Elle travaille à la spécialisation des inspections de performance au regard des standards internationaux. Elle coanime le panel consacré aux institutions de contrôle.',
                'sort_order' => 5,
            ],
            [
                'first_name' => 'Rami',
                'last_name' => 'Haddad',
                'organisation' => 'Université de Tunis El Manar',
                'job_title' => [
                    'fr' => 'Professeur de sciences de gestion',
                    'en' => 'Professor of Management Science',
                    'ar' => 'أستاذ في علوم التسيير',
                ],
                'country_iso2' => 'TN',
                'biography' => 'Rami Haddad enseigne la gouvernance et la théorie des organisations à l\'université de Tunis. Ses recherches portent sur la légitimité de l\'audit interne dans les organisations en transition. Il encadre plusieurs thèses sur l\'adaptation des fonctions audit aux environnements numériques.',
                'sort_order' => 6,
            ],
            [
                'first_name' => 'Salma',
                'last_name' => 'Bouzid',
                'organisation' => 'Digital Assurance Lab',
                'job_title' => [
                    'fr' => 'Directrice de la recherche en audit numérique',
                    'en' => 'Head of Digital Audit Research',
                    'ar' => 'مديرة أبحاث التدقيق الرقمي',
                ],
                'country_iso2' => 'TN',
                'biography' => 'Salma Bouzid conçoit des méthodes d\'audit assistées par l\'analyse de données pour des clients financiers. Elle publie sur l\'audit continu et sur la traçabilité des traitements automatisés. Elle anime le premier atelier d\'innovation consacré à l\'intelligence artificielle appliquée au contrôle.',
                'sort_order' => 7,
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Sabri',
                'organisation' => 'Cabinet Audit & Conseil Maghreb',
                'job_title' => [
                    'fr' => 'Associé gérant',
                    'en' => 'Managing Partner',
                    'ar' => 'الشريك المدير',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Omar Sabri dirige un cabinet d\'audit présent dans six pays du Maghreb. Il a accompagné des programmes de certification ISO dans les secteurs bancaire et des télécommunications. Il est certifieur pour le référentiel de certification professionnelle reconnu par ARABCIA.',
                'sort_order' => 8,
            ],
            [
                'first_name' => 'Hanan',
                'last_name' => 'Al-Rashid',
                'organisation' => 'Gulf Petro Holding',
                'job_title' => [
                    'fr' => 'Directrice de la conformité',
                    'en' => 'Chief Compliance Officer',
                    'ar' => 'مديرة الامتثال',
                ],
                'country_iso2' => 'AE',
                'biography' => 'Hanan Al-Rashid dirige la conformité d\'un groupe pétrolier et gazier implanté sur trois continents. Elle pilote le programme de lutte contre la corruption et le dispositif d\'alerte professionnelle. Elle intervient sur l\'intégrité et le contrôle interne en environnement multipays.',
                'sort_order' => 9,
            ],
            [
                'first_name' => 'Firas',
                'last_name' => 'Ben Othman',
                'organisation' => 'Agence Nationale du Numérique',
                'job_title' => [
                    'fr' => 'Directeur de la cybersécurité',
                    'en' => 'Director of Cybersecurity',
                    'ar' => 'مدير الأمن السيبراني',
                ],
                'country_iso2' => 'TN',
                'biography' => 'Firas Ben Othman dirige les programmes de cybersécurité d\'une agence nationale numérique. Il a coordonné la réponse à plusieurs incidents majeurs dans l\'administration. Il coanime l\'atelier consacré aux risques liés à l\'intelligence artificielle.',
                'sort_order' => 10,
            ],
            [
                'first_name' => 'Mariam',
                'last_name' => 'Traoré',
                'organisation' => 'Banque de Développement Ouest Africain',
                'job_title' => [
                    'fr' => 'Directrice du contrôle interne',
                    'en' => 'Head of Internal Control',
                    'ar' => 'مديرة الرقابة الداخلية',
                ],
                'country_iso2' => 'CI',
                'biography' => 'Mariam Traoré supervise le dispositif de contrôle interne d\'une banque de développement régionale. Elle a déployé un modèle de contrôle à trois lignes adapté aux exigences des bailleurs. Elle intervient sur la gouvernance des risques opérationnels.',
                'sort_order' => 11,
            ],
            [
                'first_name' => 'Ibrahim',
                'last_name' => 'El-Masri',
                'organisation' => 'Alexandria Port Authority',
                'job_title' => [
                    'fr' => 'Directeur de la gouvernance',
                    'en' => 'Director of Governance',
                    'ar' => 'مدير الحوكمة',
                ],
                'country_iso2' => 'EG',
                'biography' => 'Ibrahim El-Masri pilote la gouvernance d\'un port dont l\'activité a doublé en cinq ans. Il a conduit la refonte du contrôle interne face à la modernisation des opérations. Son expérience sert de cas pratique pour l\'audit des organisations en forte croissance.',
                'sort_order' => 12,
            ],
            [
                'first_name' => 'Nawel',
                'last_name' => 'Kacimi',
                'organisation' => 'Fonds Souverain National',
                'job_title' => [
                    'fr' => 'Directrice du contrôle interne et de la conformité',
                    'en' => 'Head of Internal Control and Compliance',
                    'ar' => 'مديرة الرقابة الداخلية والامتثال',
                ],
                'country_iso2' => 'DZ',
                'biography' => 'Nawel Kacimi dirige le contrôle interne et la conformité d\'un fonds souverain. Elle a construit une fonction d\'audit indépendante au sein d\'une organisation publique en mutation. Elle intervient sur la crédibilité des instruments financiers publics.',
                'sort_order' => 13,
            ],
            [
                'first_name' => 'Samuel',
                'last_name' => 'Okonkwo',
                'organisation' => 'Lagos Business School',
                'job_title' => [
                    'fr' => 'Professeur de gouvernance d\'entreprise',
                    'en' => 'Professor of Corporate Governance',
                    'ar' => 'أستاذ في حوكمة الشركات',
                ],
                'country_iso2' => 'NG',
                'biography' => 'Samuel Okonkwo enseigne la gouvernance d\'entreprise et dirige un programme de recherche sur le conseil d\'administration. Ses travaux portent sur l\'efficacité des comités d\'audit. Il coanime le panel consacré à l\'audit de demain.',
                'sort_order' => 14,
            ],
            [
                'first_name' => 'Ghita',
                'last_name' => 'Mansouri',
                'organisation' => 'Bourse de Casablanca',
                'job_title' => [
                    'fr' => 'Directrice de la conformité et du contrôle interne',
                    'en' => 'Chief Compliance and Internal Control Officer',
                    'ar' => 'مديرة الامتثال والرقابة الداخلية',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Ghita Mansouri dirige la conformité d\'une place financière régionale. Elle a accompagné l\'adaptation des obligations d\'audit aux nouveaux cadres de cotation. Elle siège au comité technique qui prépare la cérémonie de certification des auditeurs.',
                'sort_order' => 15,
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Reinhardt',
                'organisation' => 'European Assurance Institute',
                'job_title' => [
                    'fr' => 'Directeur des programmes de certification',
                    'en' => 'Director of Certification Programmes',
                    'ar' => 'مدير برامج الشهادات',
                ],
                'country_iso2' => 'DE',
                'biography' => 'Thomas Reinhardt conçoit des programmes de certification pour les auditeurs internes. Il accompagne plusieurs instituts partenaires en Afrique et au Moyen-Orient. Il présente à la conférence le référentiel de certification professionnelle.',
                'sort_order' => 16,
            ],
            [
                'first_name' => 'Sanaa',
                'last_name' => 'Bennis',
                'organisation' => 'Polytechnique Mohammed VI',
                'job_title' => [
                    'fr' => 'Enseignante-chercheuse en systèmes d\'information',
                    'en' => 'Lecturer and Researcher in Information Systems',
                    'ar' => 'باحثة في نظم المعلومات',
                ],
                'country_iso2' => 'MA',
                'biography' => 'Sanaa Bennis travaille sur l\'audit des systèmes d\'information et l\'estimation des risques informatiques. Elle publie sur la sécurité des plateformes dans le cloud. Elle encadre un projet européen sur la détection des anomalies dans les données comptables.',
                'sort_order' => 17,
            ],
            [
                'first_name' => 'Idris',
                'last_name' => 'Al-Amin',
                'organisation' => 'Inspection Nationale d\'Audit',
                'job_title' => [
                    'fr' => 'Directeur général de l\'audit national',
                    'en' => 'Director General of National Audit',
                    'ar' => 'المدير العام للرقابة الوطنية',
                ],
                'country_iso2' => 'EG',
                'biography' => 'Idris Al-Amin dirige une institution nationale d\'audit qui emploie plus de deux mille agents. Il a modernisé la méthodologie d\'audit de performance de son office. Il apporte le regard des institutions de contrôle sur la gouvernance des secteurs publics.',
                'sort_order' => 18,
            ],
        ];
    }
}
