<?php

namespace Tests\Unit;

use App\Support\PermissionCatalog;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase {

    /**
     * Garante que o catálogo tenha nomes únicos e metadados completos.
     *
     * @return void
     */
    public function test_catalog_has_unique_names_and_complete_metadata(): void {
        $catalog = PermissionCatalog::all();

        $this->assertNotEmpty($catalog);
        $this->assertSame($catalog->count(), $catalog->pluck('name')->unique()->count());

        foreach ($catalog as $permission) {
            $this->assertNotEmpty($permission['name'] ?? null);
            $this->assertNotEmpty($permission['label'] ?? null);
            $this->assertNotEmpty($permission['module'] ?? null);
            $this->assertNotEmpty($permission['type'] ?? null);
        }
    }

    /**
     * Garante que todo alias aponte somente para permissões canônicas existentes.
     *
     * @return void
     */
    public function test_every_legacy_alias_targets_catalog_permissions(): void {
        foreach (PermissionCatalog::aliases() as $legacy => $canonical) {
            $this->assertFalse(PermissionCatalog::contains($legacy));

            foreach ((array) $canonical as $permission) {
                $this->assertTrue(
                    PermissionCatalog::contains($permission),
                    "Alias {$legacy} aponta para a permissão inexistente {$permission}.",
                );
            }
        }
    }

    /**
     * Garante que os contextos pessoais de aluno e professor continuem separados.
     *
     * @return void
     */
    public function test_student_and_teacher_portal_namespaces_are_preserved(): void {
        $studentPermissions = PermissionCatalog::all()->where('module', 'Portal do Aluno');
        $teacherPermissions = PermissionCatalog::all()->where('module', 'Portal do Professor');

        $this->assertNotEmpty($studentPermissions);
        $this->assertNotEmpty($teacherPermissions);
        $this->assertTrue($studentPermissions->every(
            fn (array $permission): bool => str_starts_with($permission['name'], 'student.'),
        ));
        $this->assertTrue($teacherPermissions->every(
            fn (array $permission): bool => str_starts_with($permission['name'], 'teacher.'),
        ));
    }

    /**
     * Garante que os nomes canônicos usados em runtime pertençam ao catálogo.
     *
     * @return void
     */
    public function test_runtime_permission_literals_belong_to_catalog(): void {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            preg_match_all(
                "/['\"]((?:student|teacher|academic|admin|financial|reports|system|guardian)\.[a-z0-9_-]+\.[a-z0-9_.-]+)['\"]/",
                $contents ?: '',
                $matches,
            );

            foreach ($matches[1] as $permission) {
                $cataloged = PermissionCatalog::contains($permission)
                    || PermissionCatalog::names()->contains(
                        fn (string $name): bool => str_starts_with($name, "{$permission}."),
                    );

                $this->assertTrue(
                    $cataloged,
                    "Permissão {$permission} não catalogada em {$file->getPathname()}.",
                );
            }
        }
    }
}
