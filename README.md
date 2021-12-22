# Cycle ORM PromiseMapper
[![Latest Stable Version](https://poser.pugx.org/cycle/orm-promise-mapper/version)](https://packagist.org/packages/cycle/orm-promise-mapper)
[![Build Status](https://github.com/cycle/orm-promise-mapper/workflows/build/badge.svg)](https://github.com/cycle/orm-promise-mapper/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/cycle/orm-promise-mapper/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/cycle/orm-promise-mapper/?branch=1.x)
[![Codecov](https://codecov.io/gh/cycle/orm-promise-mapper/graph/badge.svg)](https://codecov.io/gh/cycle/orm-promise-mapper)
<a href="https://discord.gg/TFeEmCs"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

Cycle ORM provides the ability to carry data over the specific class instances by using `cycle/orm-promise-mapper`
package with `\Cycle\ORM\Reference\Promise` objects for relations with lazy loading.

## Installation

The preferred way to install this package is through [Composer](https://getcomposer.org/download/):

```bash
composer require cycle/orm-promise-mapper
```

## Define the Entity

```php
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Reference\ReferenceInterface;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[HasMany(target: Post::class, load: 'eager')]
    public array $posts;
    
    #[HasMany(target: Tag::class, load: 'lazy')]
    public ReferenceInterface|array $tags;
}

#[Entity]
class Post
{
    // ...

    #[BelongsTo(target: User::class, load: 'lazy')]
    public ReferenceInterface|User $user;

    #[BelongsTo(target: Tag::class, load: 'eager')]
    public Tag $tag;
}
```

## Fetching entity data

```php
$user = $orm->getRepository('user')->findByPK(1);

// $user->posts contains an array because of eager loading
foreach ($user->posts as $post) {
    // ...
}

// $user->tags contains Cycle\ORM\Reference\Promise object because of lazy loading
$tags = $user->tags->fetch();
foreach ($tags as $post) {
    // ...
}

$post = $orm->getRepository('post')->findByPK(1);

// $post->user contains Cycle\ORM\Reference\Promise object because of lazy loading
$userId = $post->user->fetch()->id;

// $post->tag contains Tag object because of eager loading
$tagName = $post->tag->name;
```

## License:

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
Maintained by [Spiral Scout](https://spiralscout.com).
