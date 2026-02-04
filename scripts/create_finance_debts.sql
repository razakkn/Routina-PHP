-- Finance debts/credits table
CREATE TABLE IF NOT EXISTS finance_debts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    debt_type TEXT NOT NULL,
    amount REAL NOT NULL,
    entry_date TEXT NOT NULL,
    person_email TEXT NOT NULL,
    description TEXT,
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_finance_debts_user ON finance_debts(user_id);
CREATE INDEX IF NOT EXISTS idx_finance_debts_person ON finance_debts(user_id, person_email);
