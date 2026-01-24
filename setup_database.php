<?php
require_once __DIR__ . '/src/Config/Database.php';

// Mock autoloader for single script execution
spl_autoload_register(function ($class) {
    $prefix = 'Routina\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Routina\Config\Database;

try {
    $db = Database::getConnection();

    $config = require __DIR__ . '/config/config.php';
    $driver = $config['db_connection'] ?? 'sqlite';

    if ($driver === 'pgsql') {
        echo "Using PostgreSQL (pgsql)\n";

        // Users
        echo "Creating users table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id BIGSERIAL PRIMARY KEY,
            email TEXT UNIQUE,
            password TEXT,
            display_name TEXT,
            job_title TEXT,
            phone TEXT,
            address TEXT,
            bio TEXT,
            currency TEXT DEFAULT 'USD',
            spouse_count INTEGER DEFAULT 0,
            avatar_image_url TEXT,
            avatar_preset_key TEXT,
            dob TEXT,
            gender TEXT,
            country_of_origin TEXT,
            current_location TEXT
        )");

        // Best-effort: add missing profile columns if upgrading an older DB
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS dob TEXT");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender TEXT");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS country_of_origin TEXT");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS current_location TEXT");

        // Vehicles
        echo "Creating vehicles table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            make TEXT,
            model TEXT,
            year INTEGER,
            license_plate TEXT,
            status TEXT DEFAULT 'active'
        )");

        echo "Creating vehicle vendors table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_vendors (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            name TEXT,
            phone TEXT,
            email TEXT,
            notes TEXT
        )");

        echo "Creating vehicle parts catalog table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_parts (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            name TEXT,
            part_number TEXT,
            vendor_id BIGINT,
            cost NUMERIC(10,2)
        )");

        echo "Creating vehicle maintenance jobs table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_maintenance (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title TEXT,
            status TEXT DEFAULT 'open',
            due_date TEXT,
            notes TEXT
        )");

        echo "Creating vehicle documents table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_documents (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title TEXT,
            file_url TEXT,
            uploaded_at TEXT
        )");

        echo "Creating vehicle events table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_events (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            event_type TEXT,
            event_date TEXT,
            notes TEXT
        )");

        echo "Creating vehicle plans table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vehicle_plans (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vehicle_id BIGINT,
            title TEXT,
            status TEXT DEFAULT 'planned',
            notes TEXT
        )");

        // Finance
        echo "Creating transactions table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS transactions (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            description TEXT,
            amount NUMERIC(10,2),
            type TEXT,
            date TEXT
        )");

        echo "Creating finance assets table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_assets (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            name TEXT,
            asset_type TEXT,
            value NUMERIC(12,2),
            notes TEXT
        )");

        echo "Creating finance bills table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_bills (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            name TEXT,
            amount NUMERIC(10,2),
            due_date TEXT,
            status TEXT DEFAULT 'unpaid'
        )");

        echo "Creating finance budgets table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_budgets (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            category TEXT,
            amount NUMERIC(10,2),
            month TEXT
        )");

        echo "Creating finance income table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_income (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            source TEXT,
            amount NUMERIC(10,2),
            received_date TEXT
        )");

        echo "Creating finance savings table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_savings (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            goal TEXT,
            target_amount NUMERIC(12,2),
            current_amount NUMERIC(12,2)
        )");

        echo "Creating finance reflections table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_reflections (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            month TEXT,
            summary TEXT
        )");

        echo "Creating finance diary table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS finance_diary (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            entry_date TEXT,
            notes TEXT
        )");

        // Journal / Health / Calendar / Family / Home
        echo "Creating journal table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            entry_date TEXT,
            content TEXT,
            mood TEXT
        )");

        echo "Creating health table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS health_entries (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            entry_date TEXT,
            weight NUMERIC(5,2),
            steps INTEGER,
            sleep_hours NUMERIC(4,1),
            water_glasses INTEGER
        )");

        echo "Creating calendar table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS calendar_events (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            title TEXT,
            start_datetime TEXT,
            end_datetime TEXT,
            type TEXT DEFAULT 'event'
        )");

        echo "Creating family table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS family_members (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            name TEXT,
            relation TEXT,
            birthdate TEXT
        )");

        echo "Creating home tasks table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS home_tasks (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            title TEXT,
            frequency TEXT,
            assigned_to TEXT,
            is_completed INTEGER DEFAULT 0
        )");

        // Vacation
        echo "Creating vacation table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacations (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            destination TEXT,
            start_date TEXT,
            end_date TEXT,
            status TEXT DEFAULT 'planned'
        )");

        echo "Creating vacation checklist table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacation_checklist_items (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vacation_id BIGINT,
            text TEXT,
            is_done INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            created_at TEXT,
            completed_at TEXT
        )");

        echo "Creating vacation notes table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS vacation_notes (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT,
            vacation_id BIGINT,
            title TEXT,
            body TEXT,
            created_at TEXT
        )");

        // Indexes
        echo "Creating indexes...\n";
        $tables = ['vehicles', 'vehicle_vendors', 'vehicle_parts', 'vehicle_maintenance', 'vehicle_documents', 'vehicle_events', 'vehicle_plans', 'transactions', 'finance_assets', 'finance_bills', 'finance_budgets', 'finance_income', 'finance_savings', 'finance_reflections', 'finance_diary', 'journal_entries', 'health_entries', 'calendar_events', 'family_members', 'home_tasks', 'vacations', 'vacation_checklist_items', 'vacation_notes'];
        foreach ($tables as $table) {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_user_id ON {$table}(user_id)");
        }

        // Composite indexes for common access patterns
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_maintenance_user_vehicle_due ON vehicle_maintenance(user_id, vehicle_id, due_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_documents_user_vehicle_uploaded ON vehicle_documents(user_id, vehicle_id, uploaded_at)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_events_user_vehicle_date ON vehicle_events(user_id, vehicle_id, event_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_plans_user_vehicle_id ON vehicle_plans(user_id, vehicle_id, id)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacations_user_start ON vacations(user_id, start_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacation_notes_user_vacation_created ON vacation_notes(user_id, vacation_id, created_at)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacation_checklist_user_vacation_sort ON vacation_checklist_items(user_id, vacation_id, sort_order, id)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_journal_entries_user_date ON journal_entries(user_id, entry_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_user_date ON transactions(user_id, date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_calendar_events_user_start ON calendar_events(user_id, start_datetime)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_health_entries_user_date ON health_entries(user_id, entry_date)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_bills_user_due ON finance_bills(user_id, due_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_budgets_user_month ON finance_budgets(user_id, month)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_income_user_date ON finance_income(user_id, received_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_diary_user_date ON finance_diary(user_id, entry_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_reflections_user_month ON finance_reflections(user_id, month)");

        // Seed User
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = 1");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            echo "Seeding default user...\n";
            $hash = password_hash('demo', PASSWORD_DEFAULT);
            $insert = $db->prepare("INSERT INTO users (id, email, password, display_name, currency) VALUES (1, 'demo@routina.com', :password, 'Demo User', 'USD')");
            $insert->execute(['password' => $hash]);
        } else {
            echo "User already exists.\n";
        }

        echo "Database setup complete.\n";
        return;
    }
    
    echo "Creating users table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE,
        password TEXT,
        display_name TEXT,
        job_title TEXT,
        phone TEXT,
        address TEXT,
        bio TEXT,
        currency TEXT DEFAULT 'USD',
        spouse_count INTEGER DEFAULT 0,
        avatar_image_url TEXT,
        avatar_preset_key TEXT,
        dob TEXT,
        gender TEXT,
        country_of_origin TEXT,
        current_location TEXT
    )");

    // Add missing profile columns for existing databases
    $columnsStmt = $db->query("PRAGMA table_info(users)");
    $columns = $columnsStmt ? $columnsStmt->fetchAll() : [];
    $columnNames = array_map(function ($col) { return $col['name']; }, $columns);
    $addColumn = function ($name, $type) use ($db, $columnNames) {
        if (!in_array($name, $columnNames, true)) {
            $db->exec("ALTER TABLE users ADD COLUMN {$name} {$type}");
        }
    };
    $addColumn('dob', 'TEXT');
    $addColumn('gender', 'TEXT');
    $addColumn('country_of_origin', 'TEXT');
    $addColumn('current_location', 'TEXT');

    echo "Creating vehicles table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        make TEXT,
        model TEXT,
        year INTEGER,
        license_plate TEXT,
        status TEXT DEFAULT 'active'
    )");

    echo "Creating vehicle vendors table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_vendors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        phone TEXT,
        email TEXT,
        notes TEXT
    )");

    echo "Creating vehicle parts catalog table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_parts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        part_number TEXT,
        vendor_id INTEGER,
        cost DECIMAL(10,2)
    )");

    echo "Creating vehicle maintenance jobs table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_maintenance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vehicle_id INTEGER,
        title TEXT,
        status TEXT DEFAULT 'open',
        due_date TEXT,
        notes TEXT
    )");

    echo "Creating vehicle documents table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vehicle_id INTEGER,
        title TEXT,
        file_url TEXT,
        uploaded_at TEXT
    )");

    echo "Creating vehicle events table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vehicle_id INTEGER,
        event_type TEXT,
        event_date TEXT,
        notes TEXT
    )");

    echo "Creating vehicle plans table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vehicle_id INTEGER,
        title TEXT,
        status TEXT DEFAULT 'planned',
        notes TEXT
    )");

    echo "Creating transactions table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        description TEXT,
        amount DECIMAL(10,2),
        type TEXT, -- 'income' or 'expense'
        date TEXT
    )");

    echo "Creating finance assets table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_assets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        asset_type TEXT,
        value DECIMAL(12,2),
        notes TEXT
    )");

    echo "Creating finance bills table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_bills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        amount DECIMAL(10,2),
        due_date TEXT,
        status TEXT DEFAULT 'unpaid'
    )");

    echo "Creating finance budgets table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_budgets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        category TEXT,
        amount DECIMAL(10,2),
        month TEXT
    )");

    echo "Creating finance income table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_income (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        source TEXT,
        amount DECIMAL(10,2),
        received_date TEXT
    )");

    echo "Creating finance savings table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_savings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        goal TEXT,
        target_amount DECIMAL(12,2),
        current_amount DECIMAL(12,2)
    )");

    echo "Creating finance reflections table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_reflections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        month TEXT,
        summary TEXT
    )");

    echo "Creating finance diary table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS finance_diary (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        entry_date TEXT,
        notes TEXT
    )");

    echo "Creating journal table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        entry_date TEXT,
        content TEXT,
        mood TEXT
    )");

    echo "Creating health table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS health_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        entry_date TEXT,
        weight DECIMAL(5,2),
        steps INTEGER,
        sleep_hours DECIMAL(4,1),
        water_glasses INTEGER
    )");

    echo "Creating calendar table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        title TEXT,
        start_datetime TEXT,
        end_datetime TEXT,
        type TEXT DEFAULT 'event'
    )");

    echo "Creating family table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS family_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        relation TEXT,
        birthdate TEXT
    )");

    echo "Creating home tasks table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS home_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        title TEXT,
        frequency TEXT,
        assigned_to TEXT,
        is_completed INTEGER DEFAULT 0
    )");

    echo "Creating vacation table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vacations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        destination TEXT,
        start_date TEXT,
        end_date TEXT,
        status TEXT DEFAULT 'planned'
    )");

    echo "Creating vacation checklist table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vacation_checklist_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vacation_id INTEGER,
        text TEXT,
        is_done INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        created_at TEXT,
        completed_at TEXT
    )");

    echo "Creating vacation notes table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS vacation_notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vacation_id INTEGER,
        title TEXT,
        body TEXT,
        created_at TEXT
    )");

    // Optimize: Add Indexes
    echo "Creating indexes...\n";
    $tables = ['vehicles', 'vehicle_vendors', 'vehicle_parts', 'vehicle_maintenance', 'vehicle_documents', 'vehicle_events', 'vehicle_plans', 'transactions', 'finance_assets', 'finance_bills', 'finance_budgets', 'finance_income', 'finance_savings', 'finance_reflections', 'finance_diary', 'journal_entries', 'health_entries', 'calendar_events', 'family_members', 'home_tasks', 'vacations', 'vacation_checklist_items', 'vacation_notes'];
    foreach ($tables as $table) {
         $db->exec("CREATE INDEX IF NOT EXISTS idx_{$table}_user_id ON {$table}(user_id)");
    }

        // Composite indexes for common access patterns
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_maintenance_user_vehicle_due ON vehicle_maintenance(user_id, vehicle_id, due_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_documents_user_vehicle_uploaded ON vehicle_documents(user_id, vehicle_id, uploaded_at)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_events_user_vehicle_date ON vehicle_events(user_id, vehicle_id, event_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vehicle_plans_user_vehicle_id ON vehicle_plans(user_id, vehicle_id, id)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacations_user_start ON vacations(user_id, start_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacation_notes_user_vacation_created ON vacation_notes(user_id, vacation_id, created_at)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vacation_checklist_user_vacation_sort ON vacation_checklist_items(user_id, vacation_id, sort_order, id)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_journal_entries_user_date ON journal_entries(user_id, entry_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_user_date ON transactions(user_id, date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_calendar_events_user_start ON calendar_events(user_id, start_datetime)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_health_entries_user_date ON health_entries(user_id, entry_date)");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_bills_user_due ON finance_bills(user_id, due_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_budgets_user_month ON finance_budgets(user_id, month)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_income_user_date ON finance_income(user_id, received_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_diary_user_date ON finance_diary(user_id, entry_date)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_finance_reflections_user_month ON finance_reflections(user_id, month)");

    // Seed User
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding default user...\n";
        $hash = password_hash('demo', PASSWORD_DEFAULT);
        $insert = $db->prepare("INSERT INTO users (id, email, password, display_name, currency) VALUES (1, 'demo@routina.com', :password, 'Demo User', 'USD')");
        $insert->execute(['password' => $hash]);
    } else {
        echo "User already exists.\n";
    }

    echo "Database setup complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
