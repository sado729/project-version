<?php

return [
    /*
     * Response header written by the 'project-version' middleware.
     * Set to null to disable the header.
     */
    'version_header' => 'X-App-Version',

    'git_repository_name' => 'origin',
    'git_branch_name' => 'main',

    /*
     * Files whose changes should NOT bump the version.
     * Each entry is a glob pattern matched against the file path
     * relative to the project root. '*' also crosses directory
     * separators, so 'vendor/*' covers 'vendor/a/b.php' and '*.md'
     * covers 'docs/notes.md'.
     */
    'ignore_patterns' => [
        '*.md',
        'composer.lock',
        'package-lock.json',
        'yarn.lock',
        'pnpm-lock.yaml',
        '.gitignore',
        '.gitattributes',
        '.env.example',
        'vendor/*',
        'node_modules/*',
        'storage/*',
        'bootstrap/cache/*',
        'public/build/*',
        'public/hot',
        'tests/*',
    ],

    /*
     * Paths that, when a NEW direct child is added, should bump the
     * minor version (and reset patch). Each entry is a glob: the
     * package counts each new path matching the pattern as a module.
     */
    'module_paths' => [
        'app/*',
    ],

    /*
     * When patch reaches this number the package will roll it over
     * into a minor bump (minor += 1, patch = 0). Set to null to
     * disable. Useful to avoid "1.0.847"-style versions.
     */
    'patch_rollover_threshold' => null,

    /*
     * Persist every bump (version + commit hash + timestamp) into
     * the version_histories table. Requires the published migration.
     */
    'history_enabled' => true,
];
