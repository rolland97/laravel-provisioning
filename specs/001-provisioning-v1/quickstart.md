# Quickstart: validating laravel-provisioning v1

```bash
composer install
vendor/bin/pest --testsuite=core          # every core flow
composer remove filament/filament --dev && vendor/bin/pest --testsuite=core && git checkout composer.json composer.lock && composer install   # core without Filament
vendor/bin/pest --testsuite=bridge        # panel with plugin + panel without
vendor/bin/pest --testsuite=browser       # accept page + change page, light and dark
vendor/bin/phpstan analyse && vendor/bin/pint --test
```

Adoption proof (in `ew-global-sdn-bhd/procument`, unpublished `path`/`vcs` repo): the seven
provisioning tests plus `TokenIndependenceTest`, `PasswordChangeSessionTest` and the impersonation
cases pass **unchanged**; activity entries keep the five event names.
