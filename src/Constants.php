<?php

namespace Routina;

/**
 * Application-wide constants.
 * 
 * Centralizes magic strings and configuration values used throughout the application.
 */
final class Constants
{
    // ─── Transaction Types ───────────────────────────────────────────────────
    public const TRANSACTION_TYPE_INCOME = 'income';
    public const TRANSACTION_TYPE_EXPENSE = 'expense';

    public const TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_INCOME,
        self::TRANSACTION_TYPE_EXPENSE,
    ];

    // ─── Vehicle Status ──────────────────────────────────────────────────────
    public const VEHICLE_STATUS_ACTIVE = 'active';
    public const VEHICLE_STATUS_SOLD = 'sold';
    public const VEHICLE_STATUS_SCRAPPED = 'scrapped';

    public const VEHICLE_STATUSES = [
        self::VEHICLE_STATUS_ACTIVE => 'Active',
        self::VEHICLE_STATUS_SOLD => 'Sold',
        self::VEHICLE_STATUS_SCRAPPED => 'Scrapped',
    ];

    // ─── Vacation Status ─────────────────────────────────────────────────────
    public const VACATION_STATUS_IDEA = 'idea';
    public const VACATION_STATUS_PLANNED = 'planned';
    public const VACATION_STATUS_BOOKED = 'booked';
    public const VACATION_STATUS_COMPLETED = 'completed';

    public const VACATION_STATUSES = [
        self::VACATION_STATUS_IDEA => 'Idea',
        self::VACATION_STATUS_PLANNED => 'Planned',
        self::VACATION_STATUS_BOOKED => 'Booked',
        self::VACATION_STATUS_COMPLETED => 'Completed',
    ];

    // ─── Maintenance Status ──────────────────────────────────────────────────
    public const MAINTENANCE_STATUS_OPEN = 'open';
    public const MAINTENANCE_STATUS_IN_PROGRESS = 'in_progress';
    public const MAINTENANCE_STATUS_COMPLETED = 'completed';

    public const MAINTENANCE_STATUSES = [
        self::MAINTENANCE_STATUS_OPEN => 'Open',
        self::MAINTENANCE_STATUS_IN_PROGRESS => 'In Progress',
        self::MAINTENANCE_STATUS_COMPLETED => 'Completed',
    ];

    // ─── Bill Status ─────────────────────────────────────────────────────────
    public const BILL_STATUS_UNPAID = 'unpaid';
    public const BILL_STATUS_PAID = 'paid';
    public const BILL_STATUS_OVERDUE = 'overdue';

    public const BILL_STATUSES = [
        self::BILL_STATUS_UNPAID => 'Unpaid',
        self::BILL_STATUS_PAID => 'Paid',
        self::BILL_STATUS_OVERDUE => 'Overdue',
    ];

    // ─── Buzz Request Status ─────────────────────────────────────────────────
    public const BUZZ_STATUS_PENDING = 'pending';
    public const BUZZ_STATUS_ACCEPTED = 'accepted';
    public const BUZZ_STATUS_DECLINED = 'declined';

    public const BUZZ_STATUSES = [
        self::BUZZ_STATUS_PENDING => 'Pending',
        self::BUZZ_STATUS_ACCEPTED => 'Accepted',
        self::BUZZ_STATUS_DECLINED => 'Declined',
    ];

    // ─── Gender Options ──────────────────────────────────────────────────────
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_OTHER = 'other';
    public const GENDER_PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public const GENDERS = [
        self::GENDER_MALE => 'Male',
        self::GENDER_FEMALE => 'Female',
        self::GENDER_OTHER => 'Other',
        self::GENDER_PREFER_NOT_TO_SAY => 'Prefer not to say',
    ];

    // ─── Relationship Status ─────────────────────────────────────────────────
    public const RELATIONSHIP_SINGLE = 'single';
    public const RELATIONSHIP_MARRIED = 'married';
    public const RELATIONSHIP_DIVORCED = 'divorced';
    public const RELATIONSHIP_WIDOWED = 'widowed';
    public const RELATIONSHIP_PARTNERED = 'partnered';

    public const RELATIONSHIP_STATUSES = [
        self::RELATIONSHIP_SINGLE => 'Single',
        self::RELATIONSHIP_MARRIED => 'Married',
        self::RELATIONSHIP_DIVORCED => 'Divorced',
        self::RELATIONSHIP_WIDOWED => 'Widowed',
        self::RELATIONSHIP_PARTNERED => 'Partnered',
    ];

    // ─── Calendar Event Types ────────────────────────────────────────────────
    public const CALENDAR_TYPE_EVENT = 'event';
    public const CALENDAR_TYPE_REMINDER = 'reminder';
    public const CALENDAR_TYPE_BIRTHDAY = 'birthday';
    public const CALENDAR_TYPE_ANNIVERSARY = 'anniversary';

    public const CALENDAR_TYPES = [
        self::CALENDAR_TYPE_EVENT => 'Event',
        self::CALENDAR_TYPE_REMINDER => 'Reminder',
        self::CALENDAR_TYPE_BIRTHDAY => 'Birthday',
        self::CALENDAR_TYPE_ANNIVERSARY => 'Anniversary',
    ];

    // ─── Family Relations ────────────────────────────────────────────────────
    public const FAMILY_SIDE_PATERNAL = 'paternal';
    public const FAMILY_SIDE_MATERNAL = 'maternal';
    public const FAMILY_SIDE_BOTH = 'both';

    public const FAMILY_SIDES = [
        self::FAMILY_SIDE_PATERNAL => 'Paternal',
        self::FAMILY_SIDE_MATERNAL => 'Maternal',
        self::FAMILY_SIDE_BOTH => 'Both Sides',
    ];

    // ─── Mood Options ────────────────────────────────────────────────────────
    public const MOOD_HAPPY = 'happy';
    public const MOOD_SAD = 'sad';
    public const MOOD_ANXIOUS = 'anxious';
    public const MOOD_CALM = 'calm';
    public const MOOD_EXCITED = 'excited';
    public const MOOD_TIRED = 'tired';
    public const MOOD_NEUTRAL = 'neutral';

    public const MOODS = [
        self::MOOD_HAPPY => '😊 Happy',
        self::MOOD_SAD => '😢 Sad',
        self::MOOD_ANXIOUS => '😰 Anxious',
        self::MOOD_CALM => '😌 Calm',
        self::MOOD_EXCITED => '🎉 Excited',
        self::MOOD_TIRED => '😴 Tired',
        self::MOOD_NEUTRAL => '😐 Neutral',
    ];

    // ─── Asset Types ─────────────────────────────────────────────────────────
    public const ASSET_TYPE_CASH = 'cash';
    public const ASSET_TYPE_BANK = 'bank';
    public const ASSET_TYPE_INVESTMENT = 'investment';
    public const ASSET_TYPE_PROPERTY = 'property';
    public const ASSET_TYPE_VEHICLE = 'vehicle';
    public const ASSET_TYPE_OTHER = 'other';

    public const ASSET_TYPES = [
        self::ASSET_TYPE_CASH => 'Cash',
        self::ASSET_TYPE_BANK => 'Bank Account',
        self::ASSET_TYPE_INVESTMENT => 'Investment',
        self::ASSET_TYPE_PROPERTY => 'Property',
        self::ASSET_TYPE_VEHICLE => 'Vehicle',
        self::ASSET_TYPE_OTHER => 'Other',
    ];

    // ─── Currency Codes (Common) ─────────────────────────────────────────────
    public const CURRENCY_USD = 'USD';
    public const CURRENCY_EUR = 'EUR';
    public const CURRENCY_GBP = 'GBP';
    public const CURRENCY_AED = 'AED';
    public const CURRENCY_INR = 'INR';
    public const CURRENCY_CAD = 'CAD';
    public const CURRENCY_AUD = 'AUD';

    public const DEFAULT_CURRENCY = self::CURRENCY_USD;

    // ─── Task Frequencies ────────────────────────────────────────────────────
    public const TASK_FREQ_DAILY = 'daily';
    public const TASK_FREQ_WEEKLY = 'weekly';
    public const TASK_FREQ_MONTHLY = 'monthly';
    public const TASK_FREQ_ONCE = 'once';

    public const TASK_FREQUENCIES = [
        self::TASK_FREQ_DAILY => 'Daily',
        self::TASK_FREQ_WEEKLY => 'Weekly',
        self::TASK_FREQ_MONTHLY => 'Monthly',
        self::TASK_FREQ_ONCE => 'One-time',
    ];

    // ─── Transmission Types ──────────────────────────────────────────────────
    public const TRANSMISSION_AUTOMATIC = 'automatic';
    public const TRANSMISSION_MANUAL = 'manual';
    public const TRANSMISSION_CVT = 'cvt';
    public const TRANSMISSION_DCT = 'dct';

    public const TRANSMISSION_TYPES = [
        self::TRANSMISSION_AUTOMATIC => 'Automatic',
        self::TRANSMISSION_MANUAL => 'Manual',
        self::TRANSMISSION_CVT => 'CVT',
        self::TRANSMISSION_DCT => 'Dual-Clutch',
    ];

    // ─── Fuel Types ──────────────────────────────────────────────────────────
    public const FUEL_PETROL = 'petrol';
    public const FUEL_DIESEL = 'diesel';
    public const FUEL_ELECTRIC = 'electric';
    public const FUEL_HYBRID = 'hybrid';
    public const FUEL_PLUGIN_HYBRID = 'plugin_hybrid';
    public const FUEL_LPG = 'lpg';

    public const FUEL_TYPES = [
        self::FUEL_PETROL => 'Petrol',
        self::FUEL_DIESEL => 'Diesel',
        self::FUEL_ELECTRIC => 'Electric',
        self::FUEL_HYBRID => 'Hybrid',
        self::FUEL_PLUGIN_HYBRID => 'Plug-in Hybrid',
        self::FUEL_LPG => 'LPG',
    ];

    // ─── Drivetrain Types ────────────────────────────────────────────────────
    public const DRIVETRAIN_FWD = 'fwd';
    public const DRIVETRAIN_RWD = 'rwd';
    public const DRIVETRAIN_AWD = 'awd';
    public const DRIVETRAIN_4WD = '4wd';

    public const DRIVETRAIN_TYPES = [
        self::DRIVETRAIN_FWD => 'Front-Wheel Drive',
        self::DRIVETRAIN_RWD => 'Rear-Wheel Drive',
        self::DRIVETRAIN_AWD => 'All-Wheel Drive',
        self::DRIVETRAIN_4WD => '4-Wheel Drive',
    ];

    // ─── API Configuration ───────────────────────────────────────────────────
    public const VPIC_API_BASE = 'https://vpic.nhtsa.dot.gov/api';
    public const PHOTON_API_BASE = 'https://photon.komoot.io/api';
    public const FRANKFURTER_API_BASE = 'https://api.frankfurter.app';
    public const EXCHANGERATE_API_BASE = 'https://open.er-api.com/v6';

    public const MIN_VEHICLE_YEAR = 1960;
    public const MAX_VEHICLE_YEAR_OFFSET = 2; // Current year + offset

    // ─── Cache TTL (seconds) ─────────────────────────────────────────────────
    public const CACHE_TTL_VEHICLE_MAKES = 86400;     // 24 hours
    public const CACHE_TTL_VEHICLE_MODELS = 86400;    // 24 hours
    public const CACHE_TTL_EXCHANGE_RATES = 3600;     // 1 hour
    public const CACHE_TTL_QUOTES = 86400;            // 24 hours

    // ─── Pagination ──────────────────────────────────────────────────────────
    public const DEFAULT_PAGE_SIZE = 25;
    public const MAX_PAGE_SIZE = 100;

    // Prevent instantiation
    private function __construct() {}
}
