# `andreacolzani/laravel-pgarray` — Project Brief

## 1. Overview

**Package:** `andreacolzani/laravel-pgarray`  
**Namespace:** `AndreaColzani\PgArray`  
**GitHub:** `andreacolzani/laravel-pgarray`  
**License:** MIT  
**PHP:** `^8.1`  
**Laravel:** `^11.0 || ^12.0 || ^13.0`

### Obiettivo

Creare un package Laravel che fornisca un supporto completo e idiomatico agli **array PostgreSQL**, andando oltre il supporto nativo di Laravel.

Il package nasce da esigenze reali su progetti Laravel/PostgreSQL, in particolare per:

- Eloquent casts per colonne PostgreSQL `ARRAY`
- supporto a diversi tipi di elementi
- array multidimensionali
- conversione corretta PostgreSQL ↔ PHP
- container alternativi come `Collection`
- in futuro query builder helpers per operatori PostgreSQL sugli array
- in futuro migration helpers per definire colonne array

Il package è anche un **progetto pubblico da portfolio**, quindi API, naming, test, PHPStan e qualità generale devono essere curati.

---

# 2. Stato attuale

Alla fine dell'ultima fase di sviluppo:

- parser PostgreSQL implementato
- serializzazione PostgreSQL implementata
- supporto array multidimensionali implementato
- value casters implementati (Boolean, Integer, String, Decimal, Float, Double, Real, Stringable, Date, DateTime, ImmutableDate, ImmutableDateTime, Uri, Ulid, Uuid)
- factory dei value caster implementata (match esaustivo su tutti i casi di PgArrayCast)
- `PgArray` cast implementato
- `AsPgArray` implementato
- `PgArrayCastable` implementato (con `collect()` centralizzato)
- `AsPgArray::of(PgArrayCast|class-string)` generalizzato (Milestone 2)
- `PgArrayValueCasterResolver` + `PgArrayElementDefinition` + `UnsupportedElementException` (Milestone 3)
- `Contracts\PgArrayValue` + `ObjectCaster`: value object custom con valore logico scalare (Milestone 5)
- `Contracts\PgArrayJsonValue` + `JsonObjectCaster` + trait `Concerns\InteractsWithPgArrayJson`: value object custom serializzati come elementi JSON in colonne `json[]` / `jsonb[]` (Milestone 6)
- `Contracts\PgArrayValueSerializer` + `Contracts\PgArrayJsonSerializer` + `SerializerCaster` / `JsonSerializerCaster`: serializer esterni mappati tramite config `pgarray.serializers` (`Support\PgArraySerializerRegistry`), attribute `#[PgArraySerializer]` o `Contracts\PgArraySerializable`; precedenza su `PgArrayValue` e `BackedEnum` (Milestone 7)
- `EncryptedCaster`: cifratura a livello di elemento come decorator del caster risolto per qualsiasi tipo, dichiarata con `AsPgArray::encrypted(...)` (`AsPgArray:<type>,<container>,encrypted`) o `AsEncryptedArray`; usa `Model::currentEncrypter()`, richiede colonne `text[]` (Milestone 8)
- `HashedCaster` (`PgArrayCast::Hashed`) + `AsHashedArray`: hashing a livello di elemento con `Hash::make()`, gli hash esistenti non vengono ri-hashati; verifica tramite `Support\PgArrayHash::check()` / `find()` (Milestone 9)
- tipi PostgreSQL speciali (Milestone 10): `ByteaCaster` (stringa binaria ↔ formato hex), `InetCaster` e `MacAddrCaster` (validazione + normalizzazione), `VectorCaster` + `Types\Vector` per `vector[]` pgvector (dimensioni opzionali con `AsVectorArray::withDimensions()`), `GeometryCaster` passthrough per `geometry[]` / `geography[]` + `Types\Point` (`PgArrayValue`, EWKT in scrittura, EWKB in lettura) consigliato per i punti; il resolver accetta anche un `PgArrayValueCaster` già configurato
- `Database\PgArrayTypeDefinition` (Milestone 11): definizione a livello DB di un `PgArrayType` con type modifier validati (`varchar(n)`, `char(n)`, `decimal(p,s)` / `numeric(p,s)`, precisione temporale, `vector(n)`, `geometry` / `geography(subtype, srid)`), con rendering `toSql()` / `toArraySql()`; non influenza i cast Eloquent, verrà usata dalle migration (Milestone 12)
- `EnumCaster`: supporto automatico ai `BackedEnum` (string/int) via `AsPgArray::of(Status::class)`; pure enum rifiutati con `UnsupportedElementException::pureEnum()` (Milestone 4)
- castable specifici implementati per: Boolean, Integer, String, Decimal, Float, Double, Real, Stringable, Date, DateTime, ImmutableDate, ImmutableDateTime, Uri, Ulid, Uuid
- container `array` e `Collection` supportati
- gestione `null` implementata
- precisione dei decimal preservata (no conversione a float)
- precisione dei microsecondi per date/time preservata
- test unitari implementati
- test di integrazione Eloquent implementati (incluso round-trip per temporal e numeric)
- PHPStan green
- Pest green
- Pint green
- modifiche già committate

### Prossimo lavoro immediato

**Ulid, UUID, e Uri cast completati.** I seguenti sono stati implementati e testati:

- `UriCaster` (maps to `Symfony\Component\Uid\Uri`) + `AsUriArray`
- `UlidCaster` (maps to `Symfony\Component\Uid\Ulid`) + `AsUlidArray`
- `UuidCaster` (maps to `Ramsey\Uuid\UuidInterface`) + `AsUuidArray`

Aggiornamenti correlati:
- `PgArrayCast` enum: added `Uri`, `Uuid`, `Ulid` cases
- `PgArrayValueCasterFactory`: exhaustive `match` over all `PgArrayCast` cases (no `default` fallback)
- `composer.json`: added `ramsey/uuid: ^4.7` and `symfony/uid: ^7.0` as runtime dependencies
- Dataset `pg array value casters` e `pg array castables` aggiornati
- `TestModel` Eloquent aggiornato con `uuid_array`, `uuid_collection`, `ulid_array`, `ulid_collection`

> **Note tecniche:** `Illuminate\Support\Ulid` e `Illuminate\Support\Uuid` non esistono in Laravel v13 — il package usa direttamente `Symfony\Component\Uid\Ulid` e `Ramsey\Uuid\UuidInterface` (le stesse classi che Laravel utilizza internamente).

---

# 3. Composer / compatibility

Il package utilizza attualmente:

```json
{
    "require": {
        "php": "^8.1",
        "illuminate/contracts": "^11.0||^12.0||^13.0",
        "illuminate/support": "^11.0||^12.0||^13.0",
        "ramsey/uuid": "^4.7",
        "spatie/laravel-package-tools": "^1.16",
        "symfony/uid": "^7.0"
    }
}
```

Dev dependencies principali:

- Laravel Pint
- Larastan
- PHPStan
- Pest
- Pest Laravel
- Pest Arch
- Orchestra Testbench
- Collision

Il package è basato sul template di:

`spatie/package-skeleton-laravel`

---

# 4. Coding conventions

## PHP

Tutti i file PHP devono utilizzare:

```php
declare(strict_types=1);
```

quando appropriato, e nella pratica questa è la convenzione adottata per tutte le classi/test del package.

## Enum

PHP 8.1+ permette l'uso degli enum.

Le enum case seguono il naming **PascalCase**, ad esempio:

```php
PgArrayCast::Integer
PgArrayCast::DateTime
PgArrayContainer::Collection
```

Non usare naming tipo:

```php
INTEGER
DATE_TIME
```

---

# 5. Architettura generale

L'architettura attuale è divisa principalmente in:

```text
Casts/
    AsPgArray
    PgArray
    PgArrayCastable
    AsIntegerArray
    AsBooleanArray
    AsStringArray
    AsDecimalArray
    AsDoubleArray
    AsFloatArray
    AsRealArray
    AsStringableArray
    AsDateArray
    AsDateTimeArray
    AsImmutableDateArray
    AsImmutableDateTimeArray
    AsUriArray
    AsUlidArray
    AsUuidArray
    ...

Casts/Values/
    PgArrayValueCaster
    PgArrayValueCasterFactory
    BooleanCaster
    IntegerCaster
    StringCaster
    DecimalCaster
    FloatCaster
    DoubleCaster
    RealCaster
    StringableCaster
    UriCaster
    UlidCaster
    UuidCaster
    DateCaster
    DateTimeCaster
    ImmutableDateCaster
    ImmutableDateTimeCaster
    AbstractCarbonCaster
    ...

Enums/
    PgArrayType
    PgArrayCast
    PgArrayContainer

Support/
    PgArrayParser

Database/
    PgArrayTypeDefinition

Types/
    Vector   (pgvector)
    Point    (PostGIS)
```

Il principio fondamentale è distinguere tre concetti:

```text
PostgreSQL type
        ↓
PgArrayType

Laravel cast semantics
        ↓
PgArrayCast

PHP output container
        ↓
PgArrayContainer
```

---

# 6. `PgArrayType`

`PgArrayType` rappresenta i **tipi PostgreSQL**, quindi non deve essere confuso con i Laravel casts.

Versione iniziale attuale:

```php
enum PgArrayType: string
{
    case Char = 'char';
    case Varchar = 'varchar';
    case Text = 'text';

    case SmallInt = 'smallint';
    case Integer = 'integer';
    case BigInt = 'bigint';

    case Real = 'real';
    case DoublePrecision = 'double precision';
    case Decimal = 'decimal';
    case Numeric = 'numeric';

    case Boolean = 'boolean';

    case Date = 'date';
    case Time = 'time';
    case TimeTz = 'timetz';
    case Timestamp = 'timestamp';
    case TimestampTz = 'timestamptz';

    case Bytea = 'bytea';
    case Uuid = 'uuid';
    case Inet = 'inet';
    case MacAddr = 'macaddr';

    case Json = 'json';
    case Jsonb = 'jsonb';

    case Geometry = 'geometry';
    case Geography = 'geography';

    case Vector = 'vector';
}
```

Questa enum rappresenta il livello database.

Non bisogna aggiungere indiscriminatamente ogni Laravel cast a questa enum.

---

# 7. Futuri PostgreSQL types

La lista di tipi PostgreSQL che si vuole eventualmente supportare comprende:

### String/text

- `char`
- `varchar`
- `text`

con eventuali parametri come lunghezza dove necessario.

### Numeric

- `smallint`
- `integer`
- `bigint`
- `real`
- `double precision`
- `decimal`
- `numeric`
- `float` con precisione quando applicabile

### Boolean

- `boolean`

### Date/time

- `date`
- `timestamp`
- `timestamp with time zone`
- `time`
- `time with time zone`
- precisione temporale quando applicabile

### Other

- `year`
- `bytea`
- `uuid`
- `inet`
- `macaddr`
- `geometry`
- `geography`
- `vector`
- eventualmente `json`

Per alcuni di questi tipi sarà necessario un sistema di parametri, quindi non necessariamente sarà sufficiente un semplice enum string-based.

Esempio futuro:

```text
varchar(50)
decimal(10, 2)
timestamp(6)
vector(1536)
```

Questo problema va affrontato quando verranno implementati i relativi migration/database features.

---

# 8. `PgArrayCast`

`PgArrayCast` rappresenta invece il **Laravel-side cast**.

Questo è volutamente distinto da `PgArrayType`.

Esempi previsti/attuali:

```text
Boolean
Date
DateTime
ImmutableDate
ImmutableDateTime
Decimal
Double
Float
Integer
String
Stringable
Uri
Ulid
Uuid
...
```

I seguenti cast Laravel potrebbero essere supportati in futuro ma non tutti hanno necessariamente senso come PostgreSQL array cast:

- encrypted
- hashed
- eventuali enum/custom casts

### Principio importante

Non assumere che:

```text
PgArrayCast == PgArrayType
```

Esempio:

```text
Laravel Ulid
```

non è un vero PostgreSQL type dedicato.

Un ULID può essere memorizzato, ad esempio, come `varchar(26)`.

Quindi:

```text
PgArrayCast::Ulid
```

può esistere indipendentemente da `PgArrayType`.

---

# 9. `PgArrayContainer`

Il package supporta attualmente due container PHP:

```php
enum PgArrayContainer: string
{
    case Array = 'array';
    case Collection = 'collection';
}
```

Il default è:

```php
PgArrayContainer::Array
```

Quindi:

```php
AsIntegerArray::class
```

restituisce un normale array.

Per ottenere una Collection:

```php
AsIntegerArray::collect()
```

---

# 10. `Collection` semantics

La scelta attuale è:

### Input

Se viene assegnata una Collection:

```php
collect([1, 2, 3])
```

il cast la converte tramite:

```php
$collection->all()
```

prima della serializzazione PostgreSQL.

### Output

Se il container è `Collection`, il risultato top-level viene trasformato in:

```php
new Collection($values)
```

Le strutture multidimensionali annidate rimangono array.

Quindi non si vuole trasformare ricorsivamente ogni array interno in Collection.

Esempio concettuale:

```text
Collection([
    [1, 2],
    [3, 4],
])
```

e non:

```text
Collection([
    Collection([1, 2]),
    Collection([3, 4]),
])
```

---

# 11. `PgArrayParser`

`PgArrayParser` è implementato in:

```text
Support/PgArrayParser
```

Non viene utilizzata una dipendenza esterna.

Responsabilità:

```text
PostgreSQL string
        ↓
PgArrayParser::parse()
        ↓
PHP array

PHP array
        ↓
PgArrayParser::serialize()
        ↓
PostgreSQL string
```

## Deve supportare

- array semplici
- array vuoti
- stringhe quotate
- escape
- virgole all'interno di valori quotati
- `{NULL}` come null PostgreSQL
- `{"NULL"}` come stringa `"NULL"`
- empty string
- caratteri speciali
- array multidimensionali

Esempio:

```text
{foo,bar,baz}
```

→

```php
['foo', 'bar', 'baz']
```

E:

```text
{{1,2},{3,4}}
```

→

```php
[
    [1, 2],
    [3, 4],
]
```

---

# 12. PHPStan typing del parser

È stata provata una recursive type alias, ma PHPStan ha segnalato:

```text
typeAlias.circular
```

La soluzione attuale, volutamente pratica, utilizza:

```php
array<int, string|int|bool|null|array<int, mixed>>
```

per il tipo interno del parser.

Non introdurre nuovamente un recursive alias senza una necessità concreta.

---

# 13. `PgArrayValueCaster`

Il `PgArray` principale non deve conoscere direttamente i dettagli di ogni tipo.

Delegazione:

```text
PgArray
   ↓
PgArrayElementDefinition (PgArrayCast|class-string)
   ↓
PgArrayValueCasterResolver
   ├── PgArrayCast  → PgArrayValueCasterFactory → specific caster
   └── class-string → PgArrayJsonValue → JsonObjectCaster (Milestone 6)
                     → PgArrayValue     → ObjectCaster (Milestone 5, precedenza sui BackedEnum)
                      BackedEnum   → EnumCaster (Milestone 4)
                      altri        → UnsupportedElementException
```

Interfaccia concettuale:

```php
get(mixed $value): mixed
set(mixed $value): mixed
```

La responsabilità del value caster è convertire **un singolo elemento**.

Il `PgArray` si occupa invece della struttura dell'array e della ricorsione.

---

# 14. Value casters implementati

Sono attualmente presenti e testati:

```text
BooleanCaster
IntegerCaster
StringCaster
DecimalCaster
FloatCaster
DoubleCaster
RealCaster
StringableCaster

DateCaster
DateTimeCaster
ImmutableDateCaster
ImmutableDateTimeCaster

UriCaster
UlidCaster
UuidCaster
```

> **Design decision:** `Char`, `Varchar`, `Text`, `Time`, `TimeTz` PostgreSQL types mappano tutti a `StringCaster` (nessuna classe separata). `Json`/`Jsonb` non hanno value casters: gli elementi JSON sono gestiti tramite `PgArrayJsonValue` (Milestone 6).

`EnumCaster` (Milestone 4), `ObjectCaster` (Milestone 5) e `JsonObjectCaster` (Milestone 6) non sono mappati da `PgArrayCast`: vengono istanziati dal resolver con la classe dell'elemento come argomento.

Tutti i test attuali sono green (285 test, PHPStan pulito, Pint pulito).

---

# 15. Numeric semantics

## Integer

PostgreSQL:

```text
{1,2,3}
```

diventa:

```php
[1, 2, 3]
```

## Floating point

Sono supportati:

```text
Float
Double
Real
```

e producono PHP `float`.

## Decimal

I decimal devono preservare la precisione.

Quindi il valore deve essere trattato come stringa quando necessario:

```text
1234567890.123456789
```

non deve essere convertito inutilmente in un PHP float causando perdita di precisione.

Questo comportamento è già testato.

---

# 16. Boolean caster

Il boolean caster deve gestire correttamente i valori PostgreSQL boolean.

È stato aggiunto anche un test esplicito per un valore non castabile, quindi non assumere che qualunque valore possa essere convertito silenziosamente.

I test relativi sono già green.

---

# 17. Date/time architecture

Per evitare duplicazione è stato introdotto:

```text
AbstractCarbonCaster
        │
        ├── DateCaster
        ├── DateTimeCaster
        ├── ImmutableDateCaster
        └── ImmutableDateTimeCaster
```

La normalizzazione comune deve restare nella base class.

Non duplicare `normalize()` nei singoli caster se non necessario.

---

# 18. Date/time input

I temporal caster devono supportare:

- `Carbon`
- `CarbonImmutable`
- `DateTimeInterface`
- string
- `null`

Esempio:

```php
new DateTimeImmutable('2026-08-20 14:30:00.123456')
```

deve mantenere:

```text
2026-08-20 14:30:00.123456
```

---

# 19. Microsecond precision

Questo è stato un problema reale durante lo sviluppo.

Un test inizialmente falliva perché:

```text
expected:
2026-08-20 14:30:00.123456

actual:
2026-08-20 14:30:00.000000
```

Il problema non era il parsing Carbon, ma la serializzazione nel `set()`.

Il caster è stato corretto in modo da accettare direttamente:

```text
DateTimeInterface
```

e stringhe, oltre a Carbon.

La normalizzazione ora preserva i microsecondi.

Questo comportamento è considerato parte del contratto del package e deve rimanere coperto dai test.

---

# 20. Date caster semantics

Indicativamente:

```text
DateCaster
    DB → Carbon
    PHP → YYYY-MM-DD

DateTimeCaster
    DB → Carbon
    PHP → YYYY-MM-DD HH:MM:SS.u

ImmutableDateCaster
    DB → CarbonImmutable
    PHP → YYYY-MM-DD

ImmutableDateTimeCaster
    DB → CarbonImmutable
    PHP → YYYY-MM-DD HH:MM:SS.u
```

I dettagli esatti dei formatter devono essere determinati dalle implementazioni correnti e dai test, non reinventati.

---

# 21. `PgArrayValueCasterFactory`

La factory:

```text
PgArrayValueCasterFactory::make(PgArrayCast $type)
```

restituisce il caster appropriato.

Esempio concettuale:

```text
PgArrayCast::Integer
    → IntegerCaster

PgArrayCast::Decimal
    → DecimalCaster

PgArrayCast::DateTime
    → DateTimeCaster
```

La factory deve essere aggiornata quando viene aggiunto un nuovo `PgArrayCast`.

---

# 22. `PgArray`

`PgArray` è il vero Eloquent cast.

Concettualmente:

```text
PostgreSQL value
      ↓
PgArrayParser
      ↓
PHP structural array
      ↓
PgArrayValueCaster
      ↓
typed PHP values
      ↓
PgArrayContainer
      ↓
array / Collection
```

Nel senso inverso:

```text
PHP array / Collection
      ↓
PgArrayValueCaster
      ↓
normalized values
      ↓
PgArrayParser::serialize()
      ↓
PostgreSQL array
```

---

# 23. `PgArray::get()`

Il comportamento è:

```php
if ($value === null) {
    return null;
}
```

Altrimenti:

```text
parse
→ recursive cast
→ container conversion
```

La ricorsione permette di gestire:

```php
[
    [1, 2],
    [3, 4],
]
```

non solo array monodimensionali.

---

# 24. `PgArray::set()`

Se:

```php
$value === null
```

ritorna:

```php
null
```

Se il valore è una:

```text
Collection
```

viene convertito con:

```php
$value->all()
```

Poi viene applicato ricorsivamente il value caster e infine:

```text
PgArrayParser::serialize(...)
```

---

# 25. Null semantics

Il package deve distinguere:

```text
NULL
```

da:

```text
"NULL"
```

e da:

```text
""
```

Quindi:

```text
{NULL}
```

→

```php
[null]
```

mentre:

```text
{"NULL"}
```

→

```php
['NULL']
```

e:

```text
{""}
```

→

```php
['']
```

Questa distinzione è fondamentale per un parser PostgreSQL corretto.

Sono presenti test per:

- null array
- null assigned value
- null array elements
- serialization of null elements

---

# 26. Multidimensional arrays

Il supporto multidimensionale è intenzionale e non deve essere trattato come feature futura.

Esempio:

```text
{{1,2},{3,4}}
```

deve produrre:

```php
[
    [1, 2],
    [3, 4],
]
```

e viceversa:

```php
[
    [1, 2],
    [3, 4],
]
```

deve essere serializzato correttamente.

La conversione dei valori deve essere ricorsiva.

---

# 27. `AsPgArray`

`AsPgArray` è il castable generico.

Serve come **escape hatch** per gli utenti che vogliono specificare direttamente il tipo.

API:

```php
AsPgArray::of(
    PgArrayCast $type,
    ?PgArrayContainer $container = null,
): string
```

Il default del container è:

```php
PgArrayContainer::Array
```

Esempio:

```php
AsPgArray::of(PgArrayCast::Integer)
```

produce:

```text
AsPgArray:integer,array
```

Mentre:

```php
AsPgArray::of(
    PgArrayCast::Integer,
    PgArrayContainer::Collection,
)
```

produce:

```text
AsPgArray:integer,collection
```

---

# 28. `AsPgArray` default

Quando viene utilizzato:

```php
AsPgArray::class
```

senza argomenti, il tipo di default è:

```text
PgArrayType::String
```

Questo è stato deciso intenzionalmente.

---

# 29. Validation of `AsPgArray`

Gli argomenti vengono convertiti tramite enum:

```php
PgArrayCast::from(...)
PgArrayContainer::from(...)
```

Quindi valori non supportati devono produrre:

```text
ValueError
```

Sono già presenti test per:

- unsupported type
- unsupported container

---

# 30. `PgArrayCastable`

Per evitare duplicazione nei castable specifici è stata introdotta una base:

```text
PgArrayCastable
```

Questa classe implementa il comportamento comune `collect()`.

Questo è importante:

> I castable concreti **non devono implementare `collect()` individualmente**.

La responsabilità è della base.

---

# 31. Specific castables

L'obiettivo API è permettere:

```php
AsIntegerArray::class
```

anziché obbligare l'utente a scrivere:

```php
AsPgArray::of(PgArrayCast::Integer)
```

L'API esplicita rimane comunque disponibile.

Castable già previsti/implementati:

```text
AsBooleanArray
AsIntegerArray
AsStringArray
```

e temporal:

```text
AsDateArray
AsDateTimeArray
AsImmutableDateArray
AsImmutableDateTimeArray
```

e numerici/Stringable:

```text
AsDecimalArray
AsDoubleArray
AsFloatArray
AsRealArray
AsStringableArray
```

e Laravel-specific:

```text
AsUriArray
AsUlidArray
AsUuidArray
```

> **Ulid/UUID design decision:** I cast `Ulid` e `Uuid` restituiscono oggetti tipizzati (`Symfony\Component\Uid\Ulid` e `Ramsey\Uuid\UuidInterface`) invece di stringhe, seguendo lo stesso pattern degli altri cast tipizzati (UriCaster → Uri, StringableCaster → Stringable, Carbon casters → Carbon). Questo è coerente con l'architettura esistente e permette operazioni type-safe sui valori.

---

# 32. Struttura dei castable specifici

Un castable specifico segue questa struttura:

```php
final class AsIntegerArray extends PgArrayCastable
{
    /**
     * @param array{0?: value-of<PgArrayContainer>} $arguments
     */
    public static function castUsing(array $arguments): PgArray
    {
        return new PgArray(
            type: PgArrayCast::Integer,
            container: PgArrayContainer::from(
                $arguments[0] ?? PgArrayContainer::Array->value,
            ),
        );
    }
}
```

Non introdurre un metodo astratto tipo:

```text
type()
```

nel base class.

Questo approccio è stato discusso e **scartato**.

Ogni concrete castable conosce direttamente il proprio `PgArrayCast`.

---

# 33. `collect()`

La base `PgArrayCastable` permette:

```php
AsIntegerArray::collect()
```

che produce:

```text
AsIntegerArray:collection
```

Analogamente:

```text
AsBooleanArray::collect()
AsStringArray::collect()
AsDateArray::collect()
```

ecc.

---

# 34. Castable contract tests

Esiste un dataset centralizzato:

```php
dataset('pg array castables', [
    AsBooleanArray::class,
    AsIntegerArray::class,
    AsStringArray::class,
    AsDecimalArray::class,
    AsDoubleArray::class,
    AsFloatArray::class,
    AsRealArray::class,
    AsStringableArray::class,
    AsDateArray::class,
    AsDateTimeArray::class,
    AsImmutableDateArray::class,
    AsImmutableDateTimeArray::class,
    AsUriArray::class,
    AsUlidArray::class,
    AsUuidArray::class,
]);
```

I test verificano centralmente che tutti:

1. implementino `Castable`
2. producano correttamente il cast `collection`

Esempio:

```php
it('implements Castable', function (string $castable): void {
    expect($castable)
        ->toImplement(Castable::class);
})->with('pg array castables');
```

e:

```php
it('creates a collection cast definition', function (string $castable): void {
    expect($castable::collect())
        ->toBe($castable.':collection');
})->with('pg array castables');
```

Non duplicare questi test in ogni classe concreta.

---

# 35. `AsPgArray` tests

I test attuali coprono:

- default string type
- explicit type
- explicit container
- `of()`
- `of()` con Collection
- unsupported type
- unsupported container
- uso come Eloquent cast
- risoluzione dei castable classes

In particolare:

```php
AsPgArray::of(PgArrayCast::Integer)
```

deve risultare:

```text
AsPgArray:integer,array
```

e:

```php
AsPgArray::of(
    PgArrayCast::Integer,
    PgArrayContainer::Collection,
)
```

deve risultare:

```text
AsPgArray:integer,collection
```

---

# 36. Eloquent integration

Il test model usa il metodo:

```php
protected function casts(): array
```

anziché un semplice `$casts`.

Questo è importante perché vengono utilizzate definizioni Castable come:

```php
AsPgArray::of(...)
```

e:

```php
AsIntegerArray::collect()
```

Esempio concettuale:

```php
protected function casts(): array
{
    return [
        'tags' => AsPgArray::class,

        'numbers' => AsPgArray::of(
            PgArrayCast::Integer,
        ),

        'number_collection' => AsPgArray::of(
            PgArrayCast::Integer,
            PgArrayContainer::Collection,
        ),

        'integer_array' => AsIntegerArray::class,

        'integer_collection' => AsIntegerArray::collect(),
    ];
}
```

Il test model ha anche:

```php
protected $fillable = [
    'numbers',
];
```

necessario per alcuni integration test Eloquent.

---

# 37. Eloquent tests

Gli integration test devono verificare il comportamento reale del cast, non solamente che una classe implementi una determinata interfaccia.

Devono coprire progressivamente:

```text
DB value
    ↓
Eloquent hydration
    ↓
PHP typed array / Collection
```

e:

```text
PHP typed array / Collection
    ↓
Eloquent assignment
    ↓
DB serialized value
```

Questo è particolarmente importante per i temporal caster.

---

# 38. Test layering

La strategia dei test è intenzionalmente divisa in livelli.

## Level 1 — Value caster

Testano:

```text
singolo valore
```

Esempio:

```text
"123" → 123
```

oppure:

```text
"2026-08-20" → Carbon
```

## Level 2 — Castable contract

Testano che tutti i:

```text
As*Array
```

rispettino il contratto comune.

## Level 3 — `AsPgArray`

Testano:

```text
API generica
```

## Level 4 — `PgArray`

Testano:

```text
parser + recursive casting + container
```

## Level 5 — Eloquent integration

Testano:

```text
Laravel/Eloquent integration
```

Questa separazione va mantenuta.

---

# 39. Immediate next step

**Completamento di Ulid, UUID, e Uri cast.** Tutti i seguenti sono ora implementati e testati:

### A. Castabili temporali ✅

```text
AsDateArray
AsDateTimeArray
AsImmutableDateArray
AsImmutableDateTimeArray
```

### B. Castabili numerici/Stringable ✅

```text
AsDecimalArray
AsDoubleArray
AsFloatArray
AsRealArray
AsStringableArray
```

### C. Castabili Laravel-specific ✅

```text
AsUriArray
AsUlidArray
AsUuidArray
```

### D. Prossimo lavoro

Dopo il completamento di tutti i castabili sopra (207 test, PHPStan ✅, Pint ✅), i possibili prossimi value caster/castable sono:

```text
Encrypted
Hashed
```

e successivamente:

```text
BackedEnum support
PgArrayValue contract
JSON / JSONB object serialization
```

vedi Milestone 4, 5, e 6 in ROADMAP.md.

---

# 40. Future Laravel casts

I seguenti Laravel casts sono già implementati:

```text
Stringable ✅
Ulid ✅
Uuid ✅ (come UlidCaster, restituisce Ramsey\Uuid\UuidInterface)
Uri ✅
Decimal ✅
Double ✅
Float ✅
Real ✅
```

Possibili ulteriori:

```text
Encrypted
Hashed
```

ma devono essere valutati con attenzione: non è necessario supportare automaticamente ogni Laravel cast esistente.

---

# 41. Custom casts

In futuro potrebbe essere utile consentire all'utente di fornire un caster custom per l'elemento dell'array.

L'idea generale è:

```text
PgArray
    +
custom PgArrayValueCaster
```

ma l'API non è ancora stata definita.

Non implementare questa parte prematuramente.

---

# 42. Migration helper

Il migration helper non è ancora implementato.

La decisione architetturale attuale è:

> preferire un singolo helper `pgArray()` piuttosto che tanti metodi Laravel-style.

Non:

```php
$table->stringArray(...)
$table->integerArray(...)
$table->uuidArray(...)
```

ma qualcosa concettualmente simile a:

```php
$table->pgArray(...)
```

con un enum per il tipo.

La firma esatta non è ancora definitiva.

---

# 43. Migration helper — design constraints

Il helper dovrà probabilmente gestire anche tipi PostgreSQL parametrizzati.

Esempi:

```text
varchar(50)[]
decimal(10,2)[]
timestamp(6)[]
vector(1536)[]
```

Quindi prima di fissare definitivamente l'API bisogna decidere se:

1. l'enum rappresenta solo tipi semplici;
2. esistono value objects per i tipi parametrizzati;
3. il helper accetta un tipo + opzioni;
4. oppure esiste un abstraction layer più generale.

Non forzare la decisione adesso.

---

# 44. Query builder

Anche il query builder non è ancora implementato.

L'obiettivo è fornire API Laravel-friendly per gli operatori PostgreSQL degli array.

Operazioni già identificate:

```text
contains
overlap
append
```

e potenzialmente altri operatori/funzioni PostgreSQL.

La progettazione dell'API deve ancora essere fatta.

---

# 45. PostgreSQL array operators

Tra i concetti che il package dovrà eventualmente coprire:

```text
@>   contains
<@   contained by
&&   overlap
||   concatenation
```

oltre a eventuali funzioni PostgreSQL legate agli array.

Non assumere ancora una API definitiva tipo:

```php
whereArrayContains(...)
```

finché non viene progettata esplicitamente.

---

# 46. API design philosophy

Il package dovrebbe essere:

### Semplice per il caso comune

Preferire:

```php
AsIntegerArray::class
```

a:

```php
AsPgArray::of(PgArrayCast::Integer)
```

### Espandibile per casi avanzati

Mantenere:

```php
AsPgArray::of(...)
```

come escape hatch.

### Laravel-ish

Le API dovrebbero sentirsi naturali in un progetto Laravel.

### Type-safe

Dove PHP 8.1 lo permette, usare:

- enum
- union types
- readonly properties
- return types
- PHPDoc utile a PHPStan

### Nessuna astrazione prematura

Non creare:

- factory inutili
- base classes solo per ridurre poche righe
- reflection per testare implementation details
- API generiche prima di avere casi d'uso reali

---

# 47. Testing philosophy

I test devono verificare il comportamento pubblico.

Evitare test che dipendono inutilmente da:

```text
private properties
private methods
implementation details
```

Preferire:

```text
public API
observable behavior
Eloquent integration
```

Reflection va evitata salvo reale necessità.

---

# 48. Quality gates

Prima di ogni commit significativo:

```bash
composer test
composer analyse
composer format
```

Tutti devono essere green.

Idealmente:

```text
Pest      ✅
PHPStan   ✅
Pint      ✅
```

---

# 49. Current mental model

Il modello architetturale da mantenere è:

```text
                         ┌─────────────────────┐
                         │    Eloquent Model   │
                         └──────────┬──────────┘
                                    │
                                    ▼
                         ┌─────────────────────┐
                         │      As*Array       │
                         │      AsPgArray       │
                         └──────────┬──────────┘
                                    │
                                    ▼
                         ┌─────────────────────┐
                         │       PgArray       │
                         └──────┬───────┬──────┘
                                │       │
                    ┌───────────┘       └───────────┐
                    ▼                               ▼
          ┌──────────────────┐             ┌──────────────────┐
          │  PgArrayParser   │             │ ValueCaster      │
          │ parse/serialize  │             │ Factory          │
          └──────────────────┘             └────────┬─────────┘
                                                     │
                         ┌───────────────────────────┼──────────────┐
                         ▼                           ▼              ▼
                  IntegerCaster              StringCaster   DateTimeCaster
                  UuidCaster                 UriCaster      UlidCaster
                         │                           │              │
                         └───────────────────────────┼──────────────┘
                                                     ▼
                                            typed PHP values
                                                     │
                                                     ▼
                                          PgArrayContainer
                                           /             \
                                       array          Collection
```

---

# 50. Public API target

L'utente finale dovrebbe poter scrivere qualcosa di molto semplice:

```php
protected function casts(): array
{
    return [
        'tags' => AsStringArray::class,
        'numbers' => AsIntegerArray::class,
        'dates' => AsDateArray::class,
        'timestamps' => AsDateTimeArray::class,
    ];
}
```

oppure:

```php
protected function casts(): array
{
    return [
        'numbers' => AsIntegerArray::collect(),
    ];
}
```

Per esigenze più avanzate:

```php
protected function casts(): array
{
    return [
        'numbers' => AsPgArray::of(
            PgArrayCast::Integer,
            PgArrayContainer::Collection,
        ),
    ];
}
```

Questa è la direzione API principale.

---

# 51. README target

Il README dovrebbe spiegare rapidamente:

1. cosa risolve il package
2. installazione
3. cast base
4. cast specifici
5. Collection
6. tipi supportati
7. multidimensional arrays
8. esempi Eloquent
9. eventuali query builder helpers
10. migration helpers
11. testing/contributing
12. compatibility

La documentazione deve riflettere **solo feature realmente implementate**, non quelle pianificate.

---

# 52. Release strategy

Non è ancora necessario fissare una versione definitiva.

Il package può evolvere per incrementi:

```text
Core parser
    ↓
Value casters
    ↓
Eloquent casts
    ↓
Additional types
    ↓
Migration helpers
    ↓
Query builder
```

Ogni fase dovrebbe mantenere:

```text
tests green
PHPStan green
Pint green
```

---

# 53. Roadmap

## Phase 1 — Core ✅

- [x] Package scaffolding
- [x] namespace
- [x] composer setup
- [x] enums base
- [x] parser
- [x] serializer
- [x] multidimensional arrays

## Phase 2 — Value casting ✅ / quasi completa

- [x] Boolean
- [x] Integer
- [x] String
- [x] Decimal
- [x] Float
- [x] Double
- [x] Real
- [x] Date
- [x] DateTime
- [x] ImmutableDate
- [x] ImmutableDateTime

## Phase 3 — Eloquent cast layer ✅

- [x] `PgArray`
- [x] `AsPgArray`
- [x] `PgArrayCastable`
- [x] `AsBooleanArray`
- [x] `AsIntegerArray`
- [x] `AsStringArray`
- [x] `AsDateArray`
- [x] `AsDateTimeArray`
- [x] `AsImmutableDateArray`
- [x] `AsImmutableDateTimeArray`
- [x] `AsDecimalArray`
- [x] `AsDoubleArray`
- [x] `AsFloatArray`
- [x] `AsRealArray`
- [x] `AsStringableArray`
- [x] `AsUriArray`
- [x] `AsUlidArray`
- [x] `AsUuidArray`
- [x] Array container
- [x] Collection container
- [x] null handling
- [x] Eloquent integration

## Phase 4 — More Laravel casts ✅

- [x] Stringable
- [x] ULID
- [x] Decimal
- [x] Double
- [x] Float
- [x] Real
- [x] Uri
- [ ] additional numeric casts
- [ ] other useful Laravel casts

## Phase 5 — PostgreSQL-specific types

- [x] UUID
- [ ] varchar/char parameters
- [ ] timestamp/time precision
- [ ] inet
- [ ] macaddr
- [ ] bytea
- [ ] vector
- [ ] geometry/geography
- [ ] other useful PostgreSQL types

## Phase 6 — Migration

- [ ] `pgArray()`
- [ ] enum/type abstraction
- [ ] parametrized PostgreSQL types

## Phase 7 — Query builder

- [ ] contains
- [ ] contained by
- [ ] overlap
- [ ] concatenation/append
- [ ] additional array operators/functions

## Phase 8 — Documentation / release

- [ ] polished README
- [ ] API documentation
- [ ] examples
- [ ] changelog
- [ ] GitHub release
- [ ] Packagist verification

---

# 54. Most important continuity rules

Quando riprenderemo il progetto in una nuova conversazione, mantenere queste regole come **source of truth**:

1. **`PgArrayType` e `PgArrayCast` sono concetti distinti.**
2. `PgArray` delega la conversione dei singoli valori a `PgArrayValueCasterFactory`.
3. `PgArrayParser` gestisce struttura, parsing e serializzazione PostgreSQL.
4. Gli array multidimensionali sono supportati.
5. `null`, `"NULL"` e stringa vuota devono rimanere distinti.
6. `PgArrayContainer` supporta solo `Array` e `Collection`.
7. `PgArrayCastable` implementa `collect()`.
8. I concrete `As*Array` implementano direttamente `castUsing()`.
9. **Non introdurre un metodo astratto `type()` nel base class.**
10. `AsPgArray::of()` rimane l'API generica/escape hatch.
11. I temporal caster condividono `AbstractCarbonCaster`.
12. La precisione dei microsecondi è importante e deve essere preservata.
13. I decimal devono preservare la precisione evitando conversioni a float.
14. I test devono privilegiare API pubbliche e comportamento reale.
15. Prima di considerare completato uno step: Pest + PHPStan + Pint devono essere green.
16. Non introdurre API future (`migration`, `query builder`, custom casts) prima di averle progettate.
17. Non reinventare classi o architetture già concordate: prima verificare la struttura corrente del progetto.
18. Quando viene aggiunto un nuovo `PgArrayCast`, aggiornare coerentemente:
    - enum
    - value caster
    - factory
    - castable specifico, se applicabile
    - dataset
    - unit tests
    - integration tests
19. **Ulid/UUID architectural decision:** Dedicated typed-object casters (`UlidCaster` → `Symfony\Component\Uid\Ulid`, `UuidCaster` → `Ramsey\Uuid\UuidInterface`) are preferred over `AsStringArray` for consistency with the existing typed-caster pattern (UriCaster → Uri, StringableCaster → Stringable, Carbon casters → Carbon). `Illuminate\Support\Ulid`/`Uuid` don't exist in Laravel v13; use `Symfony\Uid\Ulid` and `Ramsey\Uuid` directly.
20. `PgArrayCast` values for `Char`, `Varchar`, `Text`, `Time`, `TimeTz` all map to `StringCaster` — no separate caster classes. `Json`/`Jsonb` have no built-in casters: JSON elements are handled through `PgArrayJsonValue` (Milestone 6).

---

# 55. Stato di riferimento per la nuova conversazione

Il prossimo punto naturale da cui ripartire è:

> **Tutti i Laravel value casts e castability sono completati** (Boolean, Integer, String, Decimal, Double, Float, Real, Date, DateTime, ImmutableDate, ImmutableDateTime, Stringable, Uri, Ulid, Uuid). I prossimi step architetturali sono quelli descritti in ROADMAP.md Milestones 3-6: element caster resolver (Milestone 3, done), automatic `BackedEnum` support (Milestone 4, done), `PgArrayValue` contract (Milestone 5, done), and JSON/JSONB object serialization (Milestone 6, done), external serializers (Milestone 7, done), element-level encryption (Milestone 8, done), element-level hashing (Milestone 9, done), special PostgreSQL types (Milestone 10, done) and parameterized PostgreSQL types (Milestone 11, done). Next: migration helper (Milestone 12). ROADMAP.md remains the source of truth for expected behavior and next milestones.

Questo documento descrive l'architettura e le decisioni prese finora; **le classi presenti nel repository/worktree dell'utente restano la source of truth per l'implementazione effettiva**.
