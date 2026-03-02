<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    // Règles PSR-12 (standard PHP)
    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::SYMPLIFY,
        SetList::COMMON,
        SetList::CLEAN_CODE,
        SetList::ARRAY,
        SetList::COMMENTS,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::STRICT,
        SetList::CONTROL_STRUCTURES,
        SetList::SPACES,
    ]);

    // Dossiers à analyser
    $ecsConfig->paths([__DIR__ . '/src', __DIR__ . '/config', __DIR__ . '/migrations', __DIR__ . '/templates']);

    $ecsConfig->indentation('    ');
    $ecsConfig->lineEnding("\n");

    // Fichiers à ignorer
    $ecsConfig->skip([
        // Ignorer les fichiers de cache et vendor
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        __DIR__ . '/public',

        // Ignorer les fichiers de migration générés automatiquement
        __DIR__ . '/migrations/*.php',

        // Ignorer les fichiers de configuration générés
        __DIR__ . '/config/bundles.php',
        __DIR__ . '/config/preload.php',
    ]);
};
