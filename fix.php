<?php

$directories = [
    'app/Filament/Resources/*/*.php',
    'app/Filament/Pages/*.php'
];

foreach ($directories as $pattern) {
    foreach (glob($pattern) as $file) {
        $content = file_get_contents($file);
        
        // Fix navigationGroup
        $content = preg_replace(
            '/protected static (?:string\s*\|\s*\\\\?UnitEnum\s*\|\s*null|string\|\\\\?UnitEnum\|null|string\s*\|\s*UnitEnum\s*\|\s*null)\s+\$navigationGroup\s*=\s*([^;]+);/m',
            "public static function getNavigationGroup(): ?string\n    {\n        return $1;\n    }",
            $content
        );

        // Fix navigationIcon
        $content = preg_replace(
            '/protected static (?:string\s*\|\s*\\\\?BackedEnum\s*\|\s*null|string\|\\\\?BackedEnum\|null|string\s*\|\s*BackedEnum\s*\|\s*null)\s+\$navigationIcon\s*=\s*([^;]+);/m',
            "public static function getNavigationIcon(): ?string\n    {\n        if ($1 instanceof \BackedEnum) { return $1->value; }\n        return (string) $1;\n    }",
            $content
        );

        file_put_contents($file, $content);
    }
}
echo "Done!\n";
