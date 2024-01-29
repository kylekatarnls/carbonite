<?php

$replacementsByFile = [
    'vendor/nesbot/carbon/src/Carbon/Traits/Creator.php' => [
        'setLastErrors(array $lastErrors)' => 'setLastErrors($lastErrors)',
    ],
];

foreach ($replacementsByFile as $file => $replacements) {
    $contents = (string) @file_get_contents($file);

    if ($contents !== '') {
        $newContents = strtr($contents, $replacements);

        if ($newContents !== $contents) {
            file_put_contents($file, $newContents);
        }
    }
}
