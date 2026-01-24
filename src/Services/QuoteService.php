<?php

namespace Routina\Services;

final class QuoteService
{
    /**
     * Deterministic, algorithmic quote generator.
     * Same result for the same (userId, date) pair.
     */
    public static function quoteOfTheDay(?int $userId = null, ?\DateTimeInterface $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable('now');
        $userId = $userId ?? 0;

        $dayKey = $date->format('Y-m-d');
        $seed = self::seedFromString($dayKey . '|' . (string)$userId);

        $openers = [
            'Today,',
            'Right now,',
            'For this orbit,',
            'On this timeline,',
            'In this chapter,',
        ];

        $verbs = [
            'choose', 'build', 'protect', 'simplify', 'ship', 'repair', 'celebrate', 'notice', 'forgive', 'practice',
        ];

        $nouns = [
            'moment', 'habit', 'plan', 'routine', 'idea', 'boundary', 'spark', 'checkpoint', 'trail', 'engine', 'map',
        ];

        $adjectives = [
            'small', 'steady', 'honest', 'curious', 'brave', 'gentle', 'quiet', 'bold', 'patient', 'playful',
        ];

        $objects = [
            'your future self',
            'your calendar',
            'your peace',
            'your family',
            'your home',
            'your health',
            'your next trip',
            'your finances',
            'your garage',
        ];

        $actions = [
            'one step at a time.',
            'before perfection shows up.',
            'and let it compound.',
            'then rest without guilt.',
            'and keep the signal clean.',
            'even if it feels tiny.',
        ];

        $templates = [
            fn() => sprintf('%s %s the %s %s that moves %s %s',
                self::pick($openers, $seed),
                self::pick($verbs, $seed),
                self::pick($adjectives, $seed),
                self::pick($nouns, $seed),
                self::pick($objects, $seed),
                self::pick($actions, $seed)
            ),
            fn() => sprintf('%s %s is a %s %s. %s',
                self::pick($openers, $seed),
                ucfirst(self::pick($nouns, $seed)),
                self::pick($adjectives, $seed),
                self::pick($nouns, $seed),
                ucfirst(self::pick($actions, $seed))
            ),
            fn() => sprintf('%s %s %s: %s %s %s',
                self::pick($openers, $seed),
                self::pick($verbs, $seed),
                self::pick($objects, $seed),
                self::pick($adjectives, $seed),
                self::pick($nouns, $seed),
                self::pick($actions, $seed)
            ),
        ];

        $quote = $templates[self::pickIndex(count($templates), $seed)]();
        $quote = preg_replace('/\s+/', ' ', trim((string)$quote));

        return [
            'date' => $dayKey,
            'quote' => $quote,
            'signature' => '— Routina Autopilot',
        ];
    }

    private static function seedFromString(string $input): int
    {
        // crc32 returns unsigned int packed into signed on some platforms; normalize.
        $crc = crc32($input);
        if ($crc < 0) {
            $crc = $crc + 4294967296;
        }
        return (int)$crc;
    }

    private static function nextInt(int &$seed): int
    {
        // LCG constants (Numerical Recipes)
        $seed = (int)(($seed * 1664525 + 1013904223) % 4294967296);
        return $seed;
    }

    private static function pickIndex(int $count, int &$seed): int
    {
        if ($count <= 1) {
            return 0;
        }
        $n = self::nextInt($seed);
        return $n % $count;
    }

    private static function pick(array $items, int &$seed): string
    {
        if (!$items) {
            return '';
        }
        $idx = self::pickIndex(count($items), $seed);
        $val = $items[$idx] ?? '';
        return is_string($val) ? $val : (string)$val;
    }
}
