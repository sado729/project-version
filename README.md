## Project Version

Laravel paketi layihənizin versiyasını **avtomatik** olaraq git aktivliyinə əsasən artırır. Versiya `"major.minor.patch"` formatındadır:

- **patch** — hər dəyişdirilən fayl üçün +1 (ignor edilməyənlər arasından)
- **minor** — `module_paths` konfiqurasiyasındakı qovluqlara əlavə olunan hər **yeni modul** üçün +1, patch sıfırlanır. Modul yalnız əvvəllər mövcud olmayan qovluq yaradılanda "yeni" sayılır — mövcud modula fayl əlavə etmək adi patch artımıdır.
- **major** — `version:release major` komandası ilə artırılır

Versiya `informations` cədvəlində saxlanılır, tarixçə isə `version_histories` cədvəlində.

**Tələblər:** PHP 8.1+, Laravel 10 / 11 / 12.

---
## Quraşdırma
```sh
composer require sado729/project-version
php artisan vendor:publish --provider="Sado729\ProjectVersion\ProjectVersionServiceProvider"
php artisan migrate
```

`config/project-version.php`-ni öz layihənizə uyğunlaşdırın (ignor paternləri, modul yolları, patch overflow, və s.).

---
## Avtomatik versiyalama (tövsiyə olunan)

```sh
php artisan version:install-hooks
```

Bu komanda `.git/hooks/post-commit` və `post-merge` skriptlərini yazır. Mövcud hook-larla **toqquşmur** — özünə aid bloku marker arasında əlavə edir, başqa məzmunu pozmur. Yenidən çağırılsa, mövcud bloku yeniləyir (idempotent).

Bundan sonra:
- `git commit` → versiya son commit-in diff-inə görə yenilənir
- `git pull` / `git merge` → versiya `ORIG_HEAD..HEAD` diapazonuna görə yenilənir

Hook-u silmək üçün:
```sh
php artisan version:install-hooks --uninstall
```

> `.git/hooks/` versiya nəzarətində saxlanılmadığından hər klondan sonra bir dəfə quraşdırılmalıdır.

---
## Əl ilə komandalar

```sh
# İki ref arası diff (custom range)
php artisan version:bump --from=HEAD~1 --to=HEAD

# Working tree (uncommitted + untracked)
php artisan version:bump --working-tree

# Manual release
php artisan version:release patch
php artisan version:release minor
php artisan version:release major

# Köhnə davranış: git pull + bump
php artisan git:pull
```

---
## View-da istifadə

```blade
<footer>v@projectVersion</footer>
```

Yaxud helper:
```php
echo project_version(); // "1.2.7"
```

---
## Konfiqurasiya

`config/project-version.php`:

| Açar | Təyinat |
|---|---|
| `ignore_patterns` | Versiyaya təsir etməyən fayllar (glob, `fnmatch` ilə müqayisə; `*` qovluq ayırıcılarını da əhatə edir — `vendor/*` iç-içə faylları da tutur). Default: `*.md`, lock fayllar, `vendor/*`, `node_modules/*`, `storage/*`, `tests/*`, və s. |
| `module_paths` | "Yeni modul" sayılan yollar. Default: `app/*`. Qovluq yalnız əvvəlki vəziyyətdə (git-də) mövcud deyildisə yeni modul sayılır. |
| `patch_rollover_threshold` | Patch bu rəqəmə çatanda minor-a roll over edir. Default: `null` (söndürülmüş). Məs. `100` → `1.0.847` əvəzinə `1.8.47`. |
| `history_enabled` | `version_histories` cədvəlinə hər bump-da yazsın? Default: `true`. |
| `version_header` | `project-version` middleware-in yazdığı response header-in adı. Default: `X-App-Version`. `null` → söndürülür. |
| `git_repository_name`, `git_branch_name` | `git:pull` komandası üçün. |

---
## Event

```php
use Sado729\ProjectVersion\Events\GitPullEvent;

Event::listen(GitPullEvent::class, function (GitPullEvent $event) {
    logger()->info("Yeni versiya: {$event->version}");
});
```

---
## Middleware

`project-version` middleware-i cavaba cari versiyanı header kimi əlavə edir (default: `X-App-Version`):

```php
Route::middleware('project-version')->group(fn () => /* ... */);
```

---
## CI/CD

Local hook əvəzinə CI-də versiyalama üçün hazır nümunə: [`examples/github-actions-version-bump.yml`](examples/github-actions-version-bump.yml).

---
## Testlər

```sh
composer install
vendor/bin/phpunit
```

---
## License
MIT — [LICENSE](LICENSE)

---
## More from me
- [MrSadiq.info](https://mrsadiq.info)
