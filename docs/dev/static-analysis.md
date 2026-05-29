# Statická analýza (PHPStan)

Tento dokument popisuje, jak v projektu funguje PHPStan, jaká pravidla kolem něj
platí a jak postupovat, když ti na něm spadne PR.

## Jak to funguje

- Konfigurace: [`phpstan/phpstan.neon`](../../phpstan/phpstan.neon)
  - `level: 2`
  - `strictRules.allRules: true` — zapnuté přísné pravidla
    (`phpstan/phpstan-strict-rules`): zákaz `empty()`, „only booleans in if",
    žádné loose comparisons apod. **Většina hodnoty pochází ze strict rules, ne
    z levelu.**
  - PrestaShop API je PHPStanu známé přes stubs
    (`stancer/php-stubs-prestashop`) — `Db`, `Configuration`, `Tools`, …
- Baseline: [`phpstan/phpstan-baseline.neon`](../../phpstan/phpstan-baseline.neon)
  — snapshot **existujícího** dluhu, který PHPStan ignoruje. Nový kód do baseline
  nepatří.
- CI: workflow **PHPStan Analysis**
  ([`.github/workflows/run-phpstan.yml`](../../.github/workflows/run-phpstan.yml))
  běží na každý PR i push do `main`. Spouští `composer run phpstan`.

Výsledný model: **nový kód, který poruší level 2 / strict rules, padá v CI.**
Baseline drží jen starý dluh, který se postupně umazává.

## Pravidlo: baseline jen smršťovat

Baseline se smí **jen zmenšovat**, nikdy růst. Když opravíš starý kód, odeber
příslušné položky z baseline (PHPStan tě k tomu sám donutí — hlásí nenamatchované
ignorované chyby).

Jedinou legitimní výjimkou je **vědomé zvednutí levelu** (viz níže), kdy nové
kontroly jednorázově odhalí další chyby ve starém kódu. To se commitne **zvlášť**
a popíše v PR.

Tohle pravidlo hlídá CI krok „Baseline nesmí růst" v `run-phpstan.yml`: porovná
počet baselinovaných chyb na PR proti base větvi a spadne, když jich přibylo.

## Spadl mi PR na PHPStanu — co s tím?

1. Spusť analýzu lokálně (viz níže) a přečti si chyby.
2. **Oprav kód.** To je správné řešení v drtivé většině případů.
3. Regenerace baseline (`composer run phpstan-generate-baseline`) **není** způsob,
   jak PR protlačit — propašoval bys novou chybu mezi ignorované a CI krok
   „Baseline nesmí růst" tě stejně zastaví.

## Zvedání levelu

Cílový strop je **level 5**. Výš nejdeme:

| Level | Chyby nad současnou baseline |
|-------|------------------------------|
| 3 | ~10 |
| 4 | ~21 |
| 5 | ~43 |
| 6 | ~342 ❌ |

Level 6 zapíná kontrolu chybějících typehintů po celém legacy kódu (+~300 chyb) —
to je velký refactor mimo rozsah průběžné údržby, nedělá se.

Postup při bumpu (jeden level = jeden PR):

1. Zvedni `level:` v `phpstan/phpstan.neon` o jedna.
2. Spusť analýzu, drobné chyby oprav přímo v kódu.
3. Co je opravdu drahé opravit, snapshotni do baseline
   (`composer run phpstan-generate-baseline`) — to je ten povolený jednorázový
   růst, commitni ho zvlášť a popiš v PR.
4. Ověř zelené CI.

## Lokální spuštění

```bash
# celé QA modulu (EC + PHPCS + CS-Fixer + PHPStan), z dev-env rootu:
make check

# jen PHPStan, přímo v kontejneru:
docker exec ps82 bash -c "cd /var/www/packetery-dev/ && composer run phpstan"

# změřit dopad konkrétního levelu (chyby nad rámec baseline):
docker exec ps82 bash -c "cd /var/www/packetery-dev/ && \
  php -d memory_limit=2G vendor/bin/phpstan analyse -c phpstan/phpstan.neon -l 5 --no-progress"
```
