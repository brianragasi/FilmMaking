<?php
declare(strict_types=1);

function scene_cues(): array
{
    return [
        'standby' => [
            'label' => 'Standby',
            'short' => 'Reset every screen before the take.',
        ],
        'sale_live' => [
            'label' => 'Sale live',
            'short' => 'The countdown ends and customers begin shopping.',
        ],
        'traffic_rising' => [
            'label' => 'Traffic rising',
            'short' => 'The attacker begins and the operations graph climbs.',
        ],
        'checkout_loading' => [
            'label' => 'Checkout loading',
            'short' => 'Sarah clicks checkout and the loading screen holds.',
        ],
        'outage' => [
            'label' => 'Website down',
            'short' => 'The next customer refresh returns the server error.',
        ],
        'recovery' => [
            'label' => 'Recovering',
            'short' => 'Filtering is active while customers continue waiting.',
        ],
        'restored' => [
            'label' => 'Services restored',
            'short' => 'The next refresh returns the working store and saved cart.',
        ],
    ];
}

function scene_default_state(int $revision = 0): array
{
    return [
        'cue' => 'standby',
        'revision' => $revision,
        'updated_at' => gmdate(DATE_ATOM),
        'expires_at' => null,
        'updated_by' => 'system',
    ];
}

function scene_state_path(): string
{
    return __DIR__ . '/scene-state.json';
}

function normalize_scene_state(array $state): array
{
    $cues = scene_cues();
    $cue = (string) ($state['cue'] ?? 'standby');
    if (!isset($cues[$cue])) {
        $cue = 'standby';
    }

    $normalized = [
        'cue' => $cue,
        'revision' => max(0, (int) ($state['revision'] ?? 0)),
        'updated_at' => (string) ($state['updated_at'] ?? gmdate(DATE_ATOM)),
        'expires_at' => isset($state['expires_at']) ? (string) $state['expires_at'] : null,
        'updated_by' => (string) ($state['updated_by'] ?? 'system'),
    ];

    if (
        $normalized['cue'] !== 'standby'
        && $normalized['expires_at']
        && strtotime($normalized['expires_at']) !== false
        && strtotime($normalized['expires_at']) <= time()
    ) {
        return scene_default_state($normalized['revision'] + 1);
    }

    return $normalized;
}

function read_scene_state(): array
{
    $path = scene_state_path();
    if (!is_file($path)) {
        return scene_default_state();
    }

    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return scene_default_state();
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            return scene_default_state();
        }
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    $decoded = json_decode((string) $contents, true);
    return normalize_scene_state(is_array($decoded) ? $decoded : []);
}

function update_scene_state(string $cue, array $operator): array
{
    if (!isset(scene_cues()[$cue])) {
        throw new InvalidArgumentException('Unknown scene cue.');
    }

    $path = scene_state_path();
    $handle = @fopen($path, 'c+');
    if (!$handle) {
        throw new RuntimeException('Scene control storage is unavailable.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Scene control is currently busy.');
        }

        rewind($handle);
        $decoded = json_decode((string) stream_get_contents($handle), true);
        $current = normalize_scene_state(is_array($decoded) ? $decoded : []);
        $next = [
            'cue' => $cue,
            'revision' => (int) $current['revision'] + 1,
            'updated_at' => gmdate(DATE_ATOM),
            'expires_at' => $cue === 'standby' ? null : gmdate(DATE_ATOM, time() + 900),
            'updated_by' => (string) ($operator['email'] ?? 'director'),
        ];

        $encoded = json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Scene control could not encode the new cue.');
        }

        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $encoded . PHP_EOL) === false) {
            throw new RuntimeException('Scene control could not save the new cue.');
        }
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    return $next;
}

function scene_public_payload(?array $state = null): array
{
    $state = $state ?? read_scene_state();
    $cue = (string) $state['cue'];
    $definition = scene_cues()[$cue] ?? scene_cues()['standby'];

    return [
        'cue' => $cue,
        'label' => $definition['label'],
        'revision' => (int) $state['revision'],
        'updated_at' => (string) $state['updated_at'],
        'expires_at' => $state['expires_at'],
    ];
}

function scene_is_outage(?array $state = null): bool
{
    return in_array(($state ?? read_scene_state())['cue'], ['outage', 'recovery'], true);
}
