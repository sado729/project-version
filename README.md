## Project Version

Laravel paketi layihənizin versiyasını **avtomatik** olaraq git aktivliyinə əsasən artırır. Versiya `"major.minor.patch"` formatındadır:

- **patch** — hər dəyişdirilən fayl üçün +1
- **minor** — `app/` qovluğuna əlavə olunan hər yeni modul (birbaşa alt-qovluq) üçün +1, patch sıfırlanır
- **major** — əl ilə idarə olunur

Versiya `informations` cədvəlindəki `version` sütununda saxlanılır.

---
## Quraşdırma
```sh
composer require sado729/project-version
```

Konfiq və migration-ları çap edin:
```sh
php artisan vendor:publish --provider="Sado729\ProjectVersion\ProjectVersionServiceProvider"
php artisan migrate
```

`config/project-version.php` faylındakı `git_repository_name` və `git_branch_name` dəyərlərini öz layihənizə uyğun redaktə edin (bu dəyərlər yalnız `git:pull` komandası üçündür).

---
## Avtomatik versiyalama (tövsiyə olunan üsul)

Git hook-larını bir dəfə quraşdırın:
```sh
php artisan version:install-hooks
```

Bu komanda `.git/hooks/post-commit` və `.git/hooks/post-merge` skriptlərini yaradır. Bundan sonra:

- Hər `git commit`-dən sonra → versiya son commit-in diff-inə görə yenilənir
- Hər `git pull` / `git merge`-dən sonra → versiya `ORIG_HEAD..HEAD` diapazonuna görə yenilənir

Heç bir əl ilə komanda yazmağa ehtiyac yoxdur. Mövcud hook-ları üzərinə yazmaq üçün:
```sh
php artisan version:install-hooks --force
```

> Qeyd: `.git/hooks/` qovluğu versiya nəzarətində saxlanılmır, ona görə hər klon-dan sonra hooks bir dəfə quraşdırılmalıdır.

---
## Əl ilə versiyalama komandaları

İstənilən vaxt versiyanı manual hesablamaq üçün:

```sh
# Working tree (commit olunmamış + untracked fayllar)
php artisan version:bump --working-tree

# İki ref arası diff
php artisan version:bump --from=HEAD~1 --to=HEAD
php artisan version:bump --from=ORIG_HEAD --to=HEAD
```

`git pull` ilə birgə işləmə (köhnə davranış):
```sh
php artisan git:pull
```
Bu komanda `git pull` icra edir, sonra pull-dan əvvəlki/sonrakı HEAD-ləri müqayisə edərək versiyanı yeniləyir və `GitPullEvent` yayır.

---
## Event-ə qulaq asma

`Sado729\ProjectVersion\Events\GitPullEvent` event-i yayılır və yeni versiyanı payload kimi daşıyır:

```php
use Sado729\ProjectVersion\Events\GitPullEvent;

Event::listen(GitPullEvent::class, function (GitPullEvent $event) {
    logger()->info("Yeni versiya: {$event->version}");
});
```

---
## Middleware

Cədvəlin mövcudluğunu yoxlayan middleware:
```php
Route::middleware('project-version')->group(function () {
    // ...
});
```

---
## License
The MIT License (MIT). Please see [License File](license.md) for more information.

---
## More from me
- [MrSadiq.info](https://mrsadiq.info)
