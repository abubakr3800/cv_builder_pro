<?php
// ============================================================
//  Test Fixtures — Seed helpers for integration tests
//  Usage: TestFixtures::seedUser(), TestFixtures::seedFullCV()
// ============================================================

class TestFixtures
{
    /** Create and return a user row */
    public static function seedUser(array $overrides = []): array
    {
        $defaults = [
            'name'      => 'Test User',
            'email'     => 'test_' . uniqid() . '@fixture.com',
            'password'  => password_hash('TestPass123', PASSWORD_BCRYPT),
            'role'      => 'user',
            'is_active' => 1,
        ];
        $data = array_merge($defaults, $overrides);

        $id = Database::insert(
            "INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)",
            [$data['name'], $data['email'], $data['password'], $data['role'], $data['is_active']]
        );

        return array_merge($data, ['id' => (int)$id]);
    }

    /** Create and return an admin user */
    public static function seedAdmin(array $overrides = []): array
    {
        return self::seedUser(array_merge(['role' => 'admin', 'email' => 'admin_' . uniqid() . '@fixture.com'], $overrides));
    }

    /** Create and return a blank CV for a user */
    public static function seedCV(int $userId, array $overrides = []): array
    {
        $defaults = [
            'user_id'      => $userId,
            'title'        => 'Test CV',
            'lang'         => 'en',
            'template'     => 'modern',
            'completeness' => 0,
        ];
        $data = array_merge($defaults, $overrides);

        $id = Database::insert(
            "INSERT INTO cvs (user_id, title, lang, template, completeness) VALUES (?, ?, ?, ?, ?)",
            [$data['user_id'], $data['title'], $data['lang'], $data['template'], $data['completeness']]
        );

        return array_merge($data, ['id' => (int)$id]);
    }

    /** Create personal info for a CV */
    public static function seedPersonalInfo(int $cvId, array $overrides = []): array
    {
        $defaults = [
            'cv_id'     => $cvId,
            'full_name' => 'Ahmed Mohamed Abubakr',
            'job_title' => 'Software Engineer',
            'email'     => 'ahmed@test.com',
            'phone'     => '01113284597',
            'address'   => 'Cairo, Egypt',
            'summary'   => 'Experienced PHP developer.',
            'photo'     => null,
        ];
        $data = array_merge($defaults, $overrides);

        $id = Database::insert(
            "INSERT INTO personal_info (cv_id, full_name, job_title, email, phone, address, summary, photo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$data['cv_id'], $data['full_name'], $data['job_title'], $data['email'],
             $data['phone'], $data['address'], $data['summary'], $data['photo']]
        );

        return array_merge($data, ['id' => (int)$id]);
    }

    /** Create an experience entry */
    public static function seedExperience(int $cvId, array $overrides = []): array
    {
        $defaults = [
            'cv_id'      => $cvId,
            'sort_order' => 0,
            'company'    => 'ACME Corp',
            'job_title'  => 'Developer',
            'start_date' => '2020-01-01',
            'end_date'   => '2023-12-31',
            'is_current' => 0,
            'description'=> 'Built web applications.',
        ];
        $data = array_merge($defaults, $overrides);

        $id = Database::insert(
            "INSERT INTO experience (cv_id, sort_order, company, job_title, start_date, end_date, is_current, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$data['cv_id'], $data['sort_order'], $data['company'], $data['job_title'],
             $data['start_date'], $data['end_date'], $data['is_current'], $data['description']]
        );

        return array_merge($data, ['id' => (int)$id]);
    }

    /** Delete all fixture data by email pattern */
    public static function cleanUsers(string $emailPattern = '%@fixture.com'): void
    {
        Database::execute("DELETE FROM users WHERE email LIKE ?", [$emailPattern]);
    }

    /** Delete all CVs for a user */
    public static function cleanCVs(int $userId): void
    {
        Database::execute("DELETE FROM cvs WHERE user_id = ?", [$userId]);
    }
}
