<?php
// Setup script adjusted for public folder location
require_once __DIR__ . '/../src/Config/Database.php';

// Mock autoloader for single script execution
spl_autoload_register(function ($class) {
    $prefix = 'Routina\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Routina\Config\Database;

echo "<pre>";
try {
    $db = Database::getConnection();

    $config = require __DIR__ . '/../config/config.php';
    $driver = $config['db_connection'] ?? 'sqlite';

    if ($driver === 'mysql') {
        echo "Using MySQL\n";

        // Users
        echo "Creating users table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE,
            password VARCHAR(255),
            display_name VARCHAR(255),
            job_title VARCHAR(255),
            headline TEXT,
            phone VARCHAR(50),
            address TEXT,
            bio TEXT,
            linkedin VARCHAR(255),
            instagram VARCHAR(255),
            twitter VARCHAR(255),
            website VARCHAR(255),
            currency VARCHAR(10) DEFAULT 'USD',
            spouse_count INT DEFAULT 0,
            avatar_image_url TEXT,
            avatar_preset_key VARCHAR(50),
            dob VARCHAR(20),
            gender VARCHAR(20),
            country_of_origin VARCHAR(100),
            current_location VARCHAR(255),
            relationship_status VARCHAR(50) DEFAULT 'single',
            partner_member_id BIGINT,
            routina_id VARCHAR(50) UNIQUE,
            google_id VARCHAR(255),
            email_verified_at DATETIME,
            mfa_enabled TINYINT(1) DEFAULT 0,
            mfa_secret VARCHAR(255),
            facebook_url VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Password resets table
        echo "Creating password_resets table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            used_at DATETIME,
            INDEX idx_password_resets_email (email),
            INDEX idx_password_resets_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Alternative emails table
        echo "Creating user_alternative_emails table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS user_alternative_emails (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            email VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            verified_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_email (email),
            INDEX idx_alt_emails_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Social accounts table
        echo "Creating user_social_accounts table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS user_social_accounts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            provider VARCHAR(50) NOT NULL,
            provider_id VARCHAR(255) NOT NULL,
            provider_email VARCHAR(255),
            profile_url VARCHAR(255),
            access_token TEXT,
            refresh_token TEXT,
            linked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_provider (provider, provider_id),
            INDEX idx_social_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Vehicles
        echo "Creating vehicles table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            make VARCHAR(100),
            model VARCHAR(100),
            year INT,
            license_plate VARCHAR(50),
            status VARCHAR(50) DEFAULT 'active',
            trim VARCHAR(100),
            engine VARCHAR(100),
            transmission VARCHAR(50),
            fuel_type VARCHAR(50),
            drivetrain VARCHAR(50),
            color VARCHAR(50),
            owned_date VARCHAR(20),
            registration_date VARCHAR(20),
            registration_expiry VARCHAR(20),
            insurance_provider VARCHAR(255),
            insurance_policy_number VARCHAR(100),
            insurance_start_date VARCHAR(20),
            insurance_end_date VARCHAR(20),
            insurance_notes TEXT,            disposal_remarks TEXT,            INDEX idx_vehicles_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle vendors table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_vendors (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            name VARCHAR(255),
            phone VARCHAR(50),
            email VARCHAR(255),
            notes TEXT,
            INDEX idx_vehicle_vendors_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle parts catalog table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_parts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            name VARCHAR(255),
            part_number VARCHAR(100),
            vendor_id BIGINT,
            cost DECIMAL(10,2),
            INDEX idx_vehicle_parts_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle maintenance jobs table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_maintenance (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title VARCHAR(255),
            status VARCHAR(50) DEFAULT 'open',
            due_date VARCHAR(20),
            notes TEXT,
            INDEX idx_vehicle_maintenance_user (user_id),
            INDEX idx_vehicle_maintenance_vehicle (vehicle_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle documents table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_documents (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title VARCHAR(255),
            file_url TEXT,
            uploaded_at VARCHAR(30),
            INDEX idx_vehicle_documents_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle events table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            event_type VARCHAR(100),
            event_date VARCHAR(20),
            notes TEXT,
            INDEX idx_vehicle_events_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vehicle plans table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_plans (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title VARCHAR(255),
            status VARCHAR(50) DEFAULT 'planned',
            notes TEXT,
            INDEX idx_vehicle_plans_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Finance
        echo "Creating transactions table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS transactions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            description TEXT,
            amount DECIMAL(10,2),
            original_amount DECIMAL(10,2),
            original_currency VARCHAR(10),
            base_currency VARCHAR(10),
            exchange_rate DECIMAL(18,8),
            vacation_id BIGINT,
            type VARCHAR(20),
            date VARCHAR(20),
            INDEX idx_transactions_user (user_id),
            INDEX idx_transactions_vacation (vacation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance assets table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_assets (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            name VARCHAR(255),
            asset_type VARCHAR(100),
            value DECIMAL(12,2),
            notes TEXT,
            INDEX idx_finance_assets_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance bills table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_bills (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            name VARCHAR(255),
            amount DECIMAL(10,2),
            due_date VARCHAR(20),
            status VARCHAR(50) DEFAULT 'unpaid',
            INDEX idx_finance_bills_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance budgets table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_budgets (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            category VARCHAR(100),
            amount DECIMAL(10,2),
            month VARCHAR(10),
            INDEX idx_finance_budgets_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance income table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_income (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            source VARCHAR(255),
            amount DECIMAL(10,2),
            received_date VARCHAR(20),
            INDEX idx_finance_income_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance savings table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_savings (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            goal VARCHAR(255),
            target_amount DECIMAL(12,2),
            current_amount DECIMAL(12,2),
            INDEX idx_finance_savings_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance reflections table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_reflections (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            month VARCHAR(10),
            summary TEXT,
            INDEX idx_finance_reflections_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating finance diary table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_diary (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            entry_date VARCHAR(20),
            notes TEXT,
            INDEX idx_finance_diary_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating journal table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            entry_date VARCHAR(20),
            content TEXT,
            mood VARCHAR(50),
            INDEX idx_journal_entries_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating health table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS health_entries (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            entry_date VARCHAR(20),
            weight DECIMAL(5,2),
            steps INT,
            sleep_hours DECIMAL(4,1),
            water_glasses INT,
            INDEX idx_health_entries_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating calendar table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS calendar_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            title VARCHAR(255),
            start_datetime VARCHAR(30),
            end_datetime VARCHAR(30),
            type VARCHAR(50) DEFAULT 'event',
            INDEX idx_calendar_events_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating family table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS family_members (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            name VARCHAR(255),
            relation VARCHAR(100),
            birthdate VARCHAR(20),
            deathdate VARCHAR(20),
            gender VARCHAR(20),
            side_of_family VARCHAR(50),
            email VARCHAR(255),
            phone VARCHAR(50),
            no_email TINYINT(1) DEFAULT 0,
            mother_id BIGINT,
            father_id BIGINT,
            created_at VARCHAR(30),
            INDEX idx_family_members_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating buzz requests table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS buzz_requests (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            from_user_id BIGINT,
            to_user_id BIGINT,
            family_member_id BIGINT,
            channel VARCHAR(50),
            message TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at VARCHAR(30),
            responded_at VARCHAR(30),
            INDEX idx_buzz_to_status (to_user_id, status),
            INDEX idx_buzz_from (from_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating home tasks table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS home_tasks (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            title VARCHAR(255),
            frequency VARCHAR(50),
            assigned_to VARCHAR(100),
            is_completed TINYINT(1) DEFAULT 0,
            INDEX idx_home_tasks_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vacation table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacations (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            destination VARCHAR(255),
            start_date VARCHAR(20),
            end_date VARCHAR(20),
            status VARCHAR(50) DEFAULT 'planned',
            budget DECIMAL(12,2),
            notes TEXT,
            INDEX idx_vacations_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vacation checklist table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacation_checklist_items (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vacation_id BIGINT,
            text TEXT,
            is_done TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at VARCHAR(30),
            completed_at VARCHAR(30),
            INDEX idx_vacation_checklist_user (user_id),
            INDEX idx_vacation_checklist_vacation (vacation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Creating vacation notes table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacation_notes (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            vacation_id BIGINT,
            title VARCHAR(255),
            body TEXT,
            created_at VARCHAR(30),
            INDEX idx_vacation_notes_user (user_id),
            INDEX idx_vacation_notes_vacation (vacation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "\n=== MySQL Database setup complete! ===\n";
        echo "\nYou can now delete this file and use the app.\n";

    } else {
        echo "Database driver '$driver' - please run the full setup_database.php from command line.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
echo "</pre>";
