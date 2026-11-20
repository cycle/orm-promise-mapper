<div align="center">
    <a href="https://cycle-orm.dev" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="https://github.com/cycle/.github/blob/main/logo/words-vector-dark.svg?raw=true">
            <img width="50%" align="center" src="https://github.com/cycle/.github/blob/main/logo/words-vector-light.svg?raw=true" alt="CycleORM Logo">
        </picture>
    </a>
</div>

<p align="center">Promise mapper for Cycle ORM</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://cycle-orm.dev/docs/mapper-promise-mapper/current/en)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/cycle)
[![Discord](https://img.shields.io/discord/538114875570913290?style=for-the-badge&logo=discord&labelColor=7289d9&logoColor=white&color=39456d)](https://discord.gg/spiralphp)

[![Psalm Level](https://shepherd.dev/github/cycle/orm-promise-mapper/level.svg)](https://shepherd.dev/github/cycle/orm-promise-mapper)
[![Type Coverage](https://shepherd.dev/github/cycle/orm-promise-mapper/coverage.svg)](https://shepherd.dev/github/cycle/orm-promise-mapper)
[![Codecov](https://codecov.io/gh/cycle/orm-promise-mapper/branch/1.x/graph/badge.svg)](https://codecov.io/gh/cycle/orm-promise-mapper)

</div>

<br />

With `cycle/orm-promise-mapper`, Cycle ORM hydrates entities as instances of your own classes and puts `\Cycle\ORM\Reference\Promise` objects into lazy-loaded relations.

## Get Started

### Requirements

- PHP 8.2+

### Installation

```bash
composer require cycle/orm-promise-mapper
```

[![PHP](https://img.shields.io/packagist/php-v/cycle/orm-promise-mapper.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/orm-promise-mapper)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cycle/orm-promise-mapper.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/cycle/orm-promise-mapper)
[![License](https://img.shields.io/packagist/l/cycle/orm-promise-mapper.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/cycle/orm-promise-mapper.svg?style=flat-square)](https://packagist.org/packages/cycle/orm-promise-mapper/stats)

### Define the Entity

```php
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\PromiseMapper\PromiseMapper;
use Cycle\ORM\Reference\ReferenceInterface;

#[Entity(mapper: PromiseMapper::class)]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[HasMany(target: Post::class, load: 'eager')]
    public array $posts;
    
    #[HasMany(target: Tag::class, load: 'lazy')]
    public ReferenceInterface|array $tags;
}

#[Entity(mapper: PromiseMapper::class)]
class Post
{
    // ...

    #[BelongsTo(target: User::class, load: 'lazy')]
    public ReferenceInterface|User $user;

    #[BelongsTo(target: Tag::class, load: 'eager')]
    public Tag $tag;
}
```

### Fetching entity data

```php
$user = $orm->getRepository('user')->findByPK(1);

// $user->posts contains an array because of eager loading
foreach ($user->posts as $post) {
    // ...
}

// $user->tags contains Cycle\ORM\Reference\Promise object because of lazy loading
$tags = $user->tags->fetch();
foreach ($tags as $tag) {
    // ...
}

$post = $orm->getRepository('post')->findByPK(1);

// $post->user contains Cycle\ORM\Reference\Promise object because of lazy loading
$userId = $post->user->fetch()->id;

// $post->tag contains Tag object because of eager loading
$tagName = $post->tag->name;
```

## Testing

Tests run on [Testo](https://github.com/php-testo/testo) through the `cycle/testing` harness: `tests/Unit` needs no
database, `tests/Feature` runs once per database driver. Unit and SQLite tests need no containers:

```bash
composer test:unit
composer test:sqlite
```

The other drivers run against the shared test databases:

```bash
composer db:up              # or: composer db:up -- mysql
composer test               # every suite; drivers whose server is down are skipped
composer test:mysql         # also test:postgres, test:sqlserver
composer db:down
```
