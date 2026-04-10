<?php
// ============================================================
//  Unit Tests — CV Completeness Calculator
//  Tests: scoring logic for completeness percentage on dashboard
//  Run: ./vendor/bin/phpunit tests/unit/CompletenessTest.php --testdox
// ============================================================

use PHPUnit\Framework\TestCase;

// ── Completeness calculator (will live in includes/cv_helpers.php) ──
// Defined inline here so this test file is self-contained until
// cv_helpers.php is created in Phase 3.

function calculate_cv_completeness(array $cv): int
{
    $score  = 0;
    $total  = 100;

    $personal = $cv['personal'] ?? [];
    $fields = ['full_name', 'job_title', 'email', 'phone', 'summary', 'photo'];
    $personalScore = 0;
    foreach ($fields as $f) {
        if (!empty($personal[$f])) $personalScore += 5;
    }
    $score += min(30, $personalScore);

    $expCount = count($cv['experience'] ?? []);
    if ($expCount >= 1) $score += 15;
    if ($expCount >= 2) $score += 10;
    if ($expCount >= 3) $score += 5;

    $eduCount = count($cv['education'] ?? []);
    if ($eduCount >= 1) $score += 15;
    if ($eduCount >= 2) $score += 5;

    $skillCount = count($cv['skills'] ?? []);
    if ($skillCount >= 1) $score += 5;
    if ($skillCount >= 3) $score += 5;
    if ($skillCount >= 5) $score += 5;

    $langCount = count($cv['languages'] ?? []);
    if ($langCount >= 1) $score += 5;
    if ($langCount >= 2) $score += 5;

    $certCount = count($cv['certificates'] ?? []);
    if ($certCount >= 1) $score += 5;

    return min(100, $score);
}

class CompletenessTest extends TestCase
{
    // ── Empty CV ─────────────────────────────────────────────

    public function test_empty_cv_scores_zero(): void
    {
        $cv = ['personal' => [], 'experience' => [], 'education' => [], 'skills' => [], 'languages' => []];
        $this->assertEquals(0, calculate_cv_completeness($cv));
    }

    // ── Personal Info ─────────────────────────────────────────

    public function test_full_name_adds_to_score(): void
    {
        $cv = ['personal' => ['full_name' => 'Ahmed']];
        $this->assertGreaterThan(0, calculate_cv_completeness($cv));
    }

    public function test_all_personal_fields_filled_scores_30(): void
    {
        $cv = [
            'personal' => [
                'full_name' => 'Ahmed Mohamed Abubakr',
                'job_title' => 'Software Engineer',
                'email'     => 'ahmed@test.com',
                'phone'     => '01113284597',
                'summary'   => 'Experienced developer.',
                'photo'     => 'photo.jpg',
            ]
        ];
        $score = calculate_cv_completeness($cv);
        $this->assertEquals(30, $score);
    }

    public function test_partial_personal_fields_score_proportionally(): void
    {
        $cv = ['personal' => ['full_name' => 'Ahmed', 'email' => 'a@b.com']];
        $score = calculate_cv_completeness($cv);
        $this->assertEquals(10, $score);
    }

    // ── Experience ────────────────────────────────────────────

    public function test_one_experience_entry_adds_15(): void
    {
        $cv = ['experience' => [['company' => 'ACME', 'job_title' => 'Dev']]];
        $this->assertEquals(15, calculate_cv_completeness($cv));
    }

    public function test_two_experience_entries_adds_25(): void
    {
        $cv = ['experience' => [['company' => 'A'], ['company' => 'B']]];
        $this->assertEquals(25, calculate_cv_completeness($cv));
    }

    public function test_three_or_more_experience_entries_adds_30(): void
    {
        $cv = ['experience' => [['company' => 'A'], ['company' => 'B'], ['company' => 'C'], ['company' => 'D']]];
        $this->assertEquals(30, calculate_cv_completeness($cv));
    }

    // ── Education ────────────────────────────────────────────

    public function test_one_education_entry_adds_15(): void
    {
        $cv = ['education' => [['degree' => 'BSc']]];
        $this->assertEquals(15, calculate_cv_completeness($cv));
    }

    public function test_two_education_entries_adds_20(): void
    {
        $cv = ['education' => [['degree' => 'BSc'], ['degree' => 'MSc']]];
        $this->assertEquals(20, calculate_cv_completeness($cv));
    }

    // ── Skills ───────────────────────────────────────────────

    public function test_one_skill_adds_5(): void
    {
        $cv = ['skills' => [['name' => 'PHP']]];
        $this->assertEquals(5, calculate_cv_completeness($cv));
    }

    public function test_three_skills_adds_10(): void
    {
        $cv = ['skills' => [['name' => 'PHP'], ['name' => 'MySQL'], ['name' => 'JS']]];
        $this->assertEquals(10, calculate_cv_completeness($cv));
    }

    public function test_five_skills_adds_15(): void
    {
        $cv = ['skills' => array_fill(0, 5, ['name' => 'Skill'])];
        $this->assertEquals(15, calculate_cv_completeness($cv));
    }

    // ── Languages ────────────────────────────────────────────

    public function test_one_language_adds_5(): void
    {
        $cv = ['languages' => [['name' => 'English']]];
        $this->assertEquals(5, calculate_cv_completeness($cv));
    }

    public function test_two_languages_adds_10(): void
    {
        $cv = ['languages' => [['name' => 'English'], ['name' => 'Arabic']]];
        $this->assertEquals(10, calculate_cv_completeness($cv));
    }

    // ── Certificates ─────────────────────────────────────────

    public function test_one_certificate_adds_5(): void
    {
        $cv = ['certificates' => [['name' => 'AWS']]];
        $this->assertEquals(5, calculate_cv_completeness($cv));
    }

    // ── Score Cap ────────────────────────────────────────────

    public function test_score_never_exceeds_100(): void
    {
        $cv = [
            'personal'     => ['full_name' => 'A', 'job_title' => 'B', 'email' => 'c@d.com', 'phone' => '123', 'summary' => 'X', 'photo' => 'p.jpg'],
            'experience'   => [['company' => 'A'], ['company' => 'B'], ['company' => 'C']],
            'education'    => [['degree' => 'BSc'], ['degree' => 'MSc']],
            'skills'       => array_fill(0, 10, ['name' => 'S']),
            'languages'    => [['name' => 'EN'], ['name' => 'AR']],
            'certificates' => [['name' => 'AWS'], ['name' => 'GCP']],
        ];
        $this->assertLessThanOrEqual(100, calculate_cv_completeness($cv));
    }

    // ── Full complete CV ─────────────────────────────────────

    public function test_fully_complete_cv_scores_100(): void
    {
        $cv = [
            'personal'     => ['full_name' => 'A', 'job_title' => 'B', 'email' => 'c@d.com', 'phone' => '123', 'summary' => 'X', 'photo' => 'p.jpg'],
            'experience'   => [['company' => 'A'], ['company' => 'B'], ['company' => 'C']],
            'education'    => [['degree' => 'BSc'], ['degree' => 'MSc']],
            'skills'       => array_fill(0, 5, ['name' => 'S']),
            'languages'    => [['name' => 'EN'], ['name' => 'AR']],
            'certificates' => [['name' => 'AWS']],
        ];
        $this->assertEquals(100, calculate_cv_completeness($cv));
    }
}
