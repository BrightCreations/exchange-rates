<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php update_version.php <new-version>\n");
    exit(1);
}

$newVersion = $argv[1];
$composerFile = 'composer.json';

// Read composer.json
$composerJson = file_get_contents($composerFile);
if ($composerJson === false) {
    fwrite(STDERR, "Failed to read composer.json\n");
    exit(1);
}

// Decode JSON
$data = json_decode($composerJson, true);
if ($data === null) {
    fwrite(STDERR, "Failed to decode composer.json\n");
    exit(1);
}

// Update version
$data['version'] = $newVersion;

// Encode JSON (pretty print, unescaped slashes)
$newComposerJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
if ($newComposerJson === false) {
    fwrite(STDERR, "Failed to encode composer.json\n");
    exit(1);
}

// Write back to composer.json
if (file_put_contents($composerFile, $newComposerJson) === false) {
    fwrite(STDERR, "Failed to write composer.json\n");
    exit(1);
}

echo "composer.json version updated to $newVersion\n";

// Git add, commit, push
exec('git add composer.json', $output, $ret1);
if ($ret1 !== 0) {
    fwrite(STDERR, "git add failed\n");
    exit(1);
}

$commitMsg = "Bump version to $newVersion";
exec('git commit -m "'.addslashes($commitMsg).'"', $output, $ret2);
if ($ret2 !== 0) {
    fwrite(STDERR, "git commit failed\n");
    exit(1);
}

exec('git push', $output, $ret3);
if ($ret3 !== 0) {
    fwrite(STDERR, "git push failed\n");
    exit(1);
}

// Git tag and push tag
exec('git tag '.escapeshellarg($newVersion), $output, $ret4);
if ($ret4 !== 0) {
    fwrite(STDERR, "git tag failed\n");
    exit(1);
}

exec('git push origin '.escapeshellarg($newVersion), $output, $ret5);
if ($ret5 !== 0) {
    fwrite(STDERR, "git push origin $newVersion failed\n");
    exit(1);
}

echo "Tag $newVersion created and pushed.\n";
