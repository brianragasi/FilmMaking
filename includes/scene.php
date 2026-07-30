<?php
declare(strict_types=1);

function scene_cues(): array
{
    return [
        'restored' => [
            'label' => 'Website open',
            'short' => 'EcoCart is available to customer screens.',
        ],
        'sale_live' => [
            'label' => 'Sale is live',
            'short' => 'Customer screens announce that the Big Blowout Sale is live.',
        ],
        'outage' => [
            'label' => 'Website down',
            'short' => 'Customer screens display the EcoCart server-error scene.',
        ],
    ];
}

function scene_default_state(int $revision = 0): array
{
    return [
        'cue' => 'restored',
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
    $cue = (string) ($state['cue'] ?? 'restored');
    if (!isset($cues[$cue])) {
        $cue = 'restored';
    }

    $normalized = [
        'cue' => $cue,
        'revision' => max(0, (int) ($state['revision'] ?? 0)),
        'updated_at' => (string) ($state['updated_at'] ?? gmdate(DATE_ATOM)),
        'expires_at' => isset($state['expires_at']) ? (string) $state['expires_at'] : null,
        'updated_by' => (string) ($state['updated_by'] ?? 'system'),
    ];

    if (
        $normalized['expires_at']
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
            'expires_at' => null,
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
    $definition = scene_cues()[$cue] ?? scene_cues()['restored'];

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
    return ($state ?? read_scene_state())['cue'] === 'outage';
}
